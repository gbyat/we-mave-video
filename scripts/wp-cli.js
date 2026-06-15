/**
 * Shared WP-CLI resolver and runner for local i18n scripts.
 */

const fs = require('fs');
const path = require('path');
const { spawnSync } = require('child_process');

const rootDir = path.join(__dirname, '..');
const programFiles = process.env.ProgramFiles || 'C:\\Program Files';

/**
 * Preferred PHP binaries (Windows-first, newest local install first).
 *
 * @return {string[]}
 */
function getPhpCandidates() {
	return [
		path.join(programFiles, 'PHP', 'php-8-5', 'php.exe'),
		path.join(programFiles, 'PHP', 'php-8-3', 'php.exe'),
		path.join(programFiles, 'PHP', 'php.exe'),
		path.join(process.env.USERPROFILE || '', 'scoop', 'apps', 'php', 'current', 'php.exe'),
		'php',
	].filter(Boolean);
}

/**
 * Known WP-CLI phar locations.
 *
 * @return {string[]}
 */
function getPharCandidates() {
	return [
		path.join(rootDir, 'tools', 'wp-cli.phar'),
		path.join(rootDir, 'wp-cli.phar'),
		path.join(process.env.USERPROFILE || '', 'bin', 'wp-cli.phar'),
	];
}

/**
 * Default local WP-CLI phar path.
 *
 * @return {string}
 */
function getLocalPharPath() {
	return path.join(rootDir, 'tools', 'wp-cli.phar');
}

/**
 * Resolve extension_dir for a PHP binary install.
 *
 * @param {string} phpBinary PHP executable path.
 * @return {string}
 */
function getPhpExtensionDir(phpBinary) {
	if ('php' === phpBinary) {
		return '';
	}

	const extDir = path.join(path.dirname(phpBinary), 'ext');
	return fs.existsSync(extDir) ? extDir : '';
}

/**
 * Runtime PHP flags for local CLI usage.
 *
 * @param {string} phpBinary PHP executable path.
 * @return {string[]}
 */
function getPhpRuntimeArgs(phpBinary) {
	const args = ['-d', 'error_reporting=E_ALL&~E_DEPRECATED&~E_USER_DEPRECATED'];
	const extensionDir = getPhpExtensionDir(phpBinary);

	if ('' !== extensionDir) {
		args.push('-d', `extension_dir=${extensionDir}`);
	}

	return args;
}

/**
 * Run a command and return its status.
 *
 * @param {string} command Command to run.
 * @param {string[]} args Command arguments.
 * @return {{ status: number|null, error: Error|undefined }}
 */
function run(command, args) {
	const result = spawnSync(command, args, {
		cwd: rootDir,
		stdio: 'inherit',
		shell: false,
	});

	return { status: result.status, error: result.error };
}

/**
 * Run a command quietly and return stdout.
 *
 * @param {string} command Command to run.
 * @param {string[]} args Command arguments.
 * @return {string}
 */
function runQuiet(command, args) {
	const result = spawnSync(command, args, {
		cwd: rootDir,
		stdio: ['ignore', 'pipe', 'ignore'],
		encoding: 'utf8',
		shell: false,
	});

	if (result.error || result.status !== 0) {
		return '';
	}

	return (result.stdout || '').trim();
}

/**
 * Resolve the first working PHP binary with mbstring available.
 *
 * @return {string}
 */
function resolvePhpBinary() {
	for (const phpBinary of getPhpCandidates()) {
		if ('php' !== phpBinary && !fs.existsSync(phpBinary)) {
			continue;
		}

		if (phpHasMbstring(phpBinary)) {
			return phpBinary;
		}
	}

	return '';
}

/**
 * Check whether mbstring is loadable for a PHP binary.
 *
 * @param {string} phpBinary PHP executable path.
 * @return {boolean}
 */
function phpHasMbstring(phpBinary) {
	const loaded = runQuiet(phpBinary, [
		...getPhpRuntimeArgs(phpBinary),
		'-r',
		"echo extension_loaded('mbstring') ? '1' : '0';",
	]);

	return '1' === loaded;
}

/**
 * Resolve an available WP-CLI invocation.
 *
 * @return {{ command: string, argsPrefix: string[] }|null}
 */
function resolveWpCli() {
	for (const pharPath of getPharCandidates()) {
		if (!fs.existsSync(pharPath)) {
			continue;
		}

		for (const phpBinary of getPhpCandidates()) {
			if ('php' !== phpBinary && !fs.existsSync(phpBinary)) {
				continue;
			}

			if (!phpHasMbstring(phpBinary)) {
				continue;
			}

			const version = runQuiet(phpBinary, [
				...getPhpRuntimeArgs(phpBinary),
				pharPath,
				'--version',
			]);

			if ('' !== version) {
				return {
					command: phpBinary,
					argsPrefix: [...getPhpRuntimeArgs(phpBinary), pharPath],
				};
			}
		}
	}

	const wpCommands = process.platform === 'win32' ? ['wp.cmd', 'wp'] : ['wp'];
	for (const wpCommand of wpCommands) {
		const direct = run(wpCommand, ['--version']);
		if (!direct.error && direct.status === 0) {
			return { command: wpCommand, argsPrefix: [] };
		}
	}

	return null;
}

/**
 * Ensure WP-CLI is available, installing the local phar if needed.
 *
 * @return {{ command: string, argsPrefix: string[] }}
 */
function ensureWpCli() {
	let cli = resolveWpCli();
	if (cli) {
		return cli;
	}

	const installScript = path.join(__dirname, 'install-wp-cli.js');
	const installed = spawnSync(process.execPath, [installScript], {
		cwd: rootDir,
		stdio: 'inherit',
		shell: false,
	});

	if (installed.error || installed.status !== 0) {
		printWpCliHelp();
		process.exit(1);
	}

	cli = resolveWpCli();
	if (!cli) {
		printWpCliHelp();
		process.exit(1);
	}

	return cli;
}

/**
 * Run a WP-CLI command.
 *
 * @param {string[]} args WP-CLI arguments.
 * @return {void}
 */
function runWp(args) {
	const cli = ensureWpCli();
	const result = run(cli.command, [...cli.argsPrefix, ...args]);

	if (result.error) {
		throw result.error;
	}
	if (result.status !== 0) {
		process.exit(result.status || 1);
	}
}

/**
 * Print setup hints when WP-CLI cannot be resolved.
 *
 * @return {void}
 */
function printWpCliHelp() {
	console.error('WP-CLI not found or mbstring is unavailable.');
	console.error('Run: npm run wp-cli:install');
	console.error('Also verify PHP mbstring in php.ini, for example:');
	console.error(`extension_dir = "${path.join(programFiles, 'PHP', 'php-8-5', 'ext')}"`);
	console.error('extension=mbstring');
}

module.exports = {
	rootDir,
	getPhpCandidates,
	getPharCandidates,
	getLocalPharPath,
	getPhpExtensionDir,
	getPhpRuntimeArgs,
	resolvePhpBinary,
	phpHasMbstring,
	resolveWpCli,
	ensureWpCli,
	runWp,
	printWpCliHelp,
};

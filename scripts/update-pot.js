/**
 * Build POT file from PHP + Gutenberg JS sources.
 */

const fs = require('fs');
const path = require('path');
const { rootDir, runWp } = require('./wp-cli');

const languagesDir = path.join(rootDir, 'languages');
const textDomain = 'we-mave-video';
const potFile = path.join(languagesDir, `${textDomain}.pot`);

if (!fs.existsSync(languagesDir)) {
	fs.mkdirSync(languagesDir, { recursive: true });
}

try {
	runWp([
		'i18n',
		'make-pot',
		'.',
		potFile,
		`--domain=${textDomain}`,
		'--exclude=node_modules,vendor,scripts,assets/vendor',
		'--skip-block-json',
	]);

	console.log(`POT file updated: ${potFile}`);
} catch (error) {
	console.error('WP-CLI POT build failed.');
	console.error(error instanceof Error ? error.message : String(error));
	process.exit(1);
}

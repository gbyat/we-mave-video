/**
 * Create or update the German PO catalog from the POT template.
 */

const fs = require('fs');
const path = require('path');
const { rootDir, runWp } = require('./wp-cli');

const languagesDir = path.join(rootDir, 'languages');
const potFile = path.join(languagesDir, 'we-mave-video.pot');
const poFile = path.join(languagesDir, 'we-mave-video-de_DE.po');

const translations = {
	'we Mave Video': 'we Mave Video',
	'Self-hosted mave.io video player with shortcode, snippet generator, and optional block.':
		'Selbst gehosteter mave.io-Videoplayer mit Shortcode, Snippet-Generator und optionalem Block.',
	'npm registry returned HTTP %d.': 'Die npm-Registry antwortete mit HTTP %d.',
	'npm registry response was invalid.': 'Die Antwort der npm-Registry war ungültig.',
	'Could not create the local vendor directory.': 'Das lokale Vendor-Verzeichnis konnte nicht erstellt werden.',
	'Could not write the player bundle file.': 'Die Player-Bundle-Datei konnte nicht geschrieben werden.',
	'Downloaded player bundle appears incomplete or invalid.':
		'Das heruntergeladene Player-Bundle scheint unvollständig oder ungültig zu sein.',
	'Could not download %1$s (HTTP %2$d).': '%1$s konnte nicht heruntergeladen werden (HTTP %2$d).',
	'Downloaded file %s appears empty.': 'Die heruntergeladene Datei %s scheint leer zu sein.',
	'Could not create directory %s.': 'Verzeichnis %s konnte nicht erstellt werden.',
	'Could not write file %s.': 'Datei %s konnte nicht geschrieben werden.',
	'Could not download the player bundle (HTTP %d).': 'Das Player-Bundle konnte nicht heruntergeladen werden (HTTP %d).',
	'Once weekly (we Mave Video)': 'Einmal wöchentlich (we Mave Video)',
	'Once monthly (we Mave Video)': 'Einmal monatlich (we Mave Video)',
	'Update available: %1$s (installed: %2$s).': 'Update verfügbar: %1$s (installiert: %2$s).',
	none: 'keine',
	'Components are up to date (%s).': 'Die Komponenten sind auf dem neuesten Stand (%s).',
	'Components updated to version %s.': 'Komponenten auf Version %s aktualisiert.',
	'This plugin self-hosts <a href="%s" target="_blank" rel="noopener noreferrer">@maveio/components</a>, which is licensed under AGPL-3.0-or-later.':
		'Dieses Plugin hostet <a href="%s" target="_blank" rel="noopener noreferrer">@maveio/components</a> selbst, lizenziert unter AGPL-3.0-or-later.',
	'Hosted components': 'Gehostete Komponenten',
	'Installed version': 'Installierte Version',
	'Not installed': 'Nicht installiert',
	Status: 'Status',
	'Last check': 'Letzte Prüfung',
	'Last update': 'Letztes Update',
	'Local path': 'Lokaler Pfad',
	'Front-end script source': 'Frontend-Skriptquelle',
	'Official CDN (debug mode)': 'Offizielles CDN (Debug-Modus)',
	'Self-hosted': 'Selbst gehostet',
	'Last error': 'Letzter Fehler',
	'Check for updates': 'Auf Updates prüfen',
	'Update now': 'Jetzt aktualisieren',
	'Plugin updates': 'Plugin-Updates',
	'GitHub auto updates': 'Automatische GitHub-Updates',
	Disabled: 'Deaktiviert',
	Enabled: 'Aktiviert',
	'Check for plugin updates from <a href="https://github.com/%s/releases" target="_blank" rel="noopener noreferrer">GitHub releases</a>. This updates the WordPress plugin only, not the self-hosted mave components.':
		'Prüft Plugin-Updates über <a href="https://github.com/%s/releases" target="_blank" rel="noopener noreferrer">GitHub Releases</a>. Aktualisiert nur das WordPress-Plugin, nicht die selbst gehosteten mave-Komponenten.',
	'Update schedule': 'Update-Zeitplan',
	Schedule: 'Zeitplan',
	Daily: 'Täglich',
	Weekly: 'Wöchentlich',
	Monthly: 'Monatlich',
	'Manual only': 'Nur manuell',
	'Automatic updates': 'Automatische Updates',
	'Download and replace components automatically when a newer version is found.':
		'Komponenten automatisch herunterladen und ersetzen, wenn eine neuere Version gefunden wird.',
	'Load script globally': 'Skript global laden',
	'Enqueue the player script on every front-end page (not recommended).':
		'Das Player-Skript auf jeder Frontend-Seite laden (nicht empfohlen).',
	'Load from official CDN': 'Vom offiziellen CDN laden',
	'Load the player script from the official mave CDN instead of the self-hosted copy.':
		'Das Player-Skript vom offiziellen mave-CDN statt aus der selbst gehosteten Kopie laden.',
	'Use this only for debugging: compare player behavior against the reference implementation documented at <a href="%1$s" target="_blank" rel="noopener noreferrer">mave.io</a>. The script is loaded from <code>%2$s</code>. Disable again for production self-hosting.':
		'Nur zum Debuggen verwenden: Player-Verhalten mit der Referenzimplementierung auf <a href="%1$s" target="_blank" rel="noopener noreferrer">mave.io</a> vergleichen. Das Skript wird von <code>%2$s</code> geladen. Für produktives Self-Hosting wieder deaktivieren.',
	'Player defaults': 'Player-Standardwerte',
	'These defaults apply to every embed unless overridden by shortcode or block attributes.':
		'Diese Standardwerte gelten für jedes Embed, sofern sie nicht per Shortcode oder Block überschrieben werden.',
	'Aspect ratio': 'Seitenverhältnis',
	Autoplay: 'Autoplay',
	Off: 'Aus',
	Always: 'Immer',
	'When in view': 'Wenn sichtbar',
	Controls: 'Steuerung',
	Full: 'Vollständig',
	Big: 'Groß',
	None: 'Keine',
	'Controls color': 'Steuerungsfarbe',
	'Color opacity': 'Farbdeckkraft',
	Loop: 'Schleife',
	'Loop playback': 'Wiedergabe wiederholen',
	'Poster URL': 'Poster-URL',
	Subtitles: 'Untertitel',
	Theme: 'Theme',
	Quality: 'Qualität',
	'Highest available': 'Höchste verfügbare',
	'Audio tracks': 'Audiospuren',
	Auto: 'Automatisch',
	'Width (px)': 'Breite (px)',
	'Height (px)': 'Höhe (px)',
	'Save settings': 'Einstellungen speichern',
	'Embed snippets': 'Embed-Snippets',
	'For Enfold, use the Shortcode element and paste the shortcode below. This is the most reliable integration path.':
		'Für Enfold das Shortcode-Element verwenden und den Shortcode unten einfügen. Das ist der zuverlässigste Integrationsweg.',
	'Embed ID': 'Embed-ID',
	Shortcode: 'Shortcode',
	'Recommended for Enfold and most page builders.': 'Empfohlen für Enfold und die meisten Page Builder.',
	'HTML markup': 'HTML-Markup',
	'Use only in HTML/code modules. The plugin loads the script automatically when this markup is detected in page content.':
		'Nur in HTML-/Code-Modulen verwenden. Das Plugin lädt das Skript automatisch, wenn dieses Markup im Seiteninhalt erkannt wird.',
	Never: 'Nie',
	'No changelog available.': 'Kein Changelog verfügbar.',
	'Player settings': 'Player-Einstellungen',
	'Example: 16/9': 'Beispiel: 16/9',
	'Use global default': 'Globalen Standard verwenden',
	'Example: en, de or none': 'Beispiel: en, de oder none',
	'Mave Player': 'Mave Player',
	'Preview: mave-player embed': 'Vorschau: mave-player Embed',
	'Add an embed ID in the block settings.': 'Embed-ID in den Block-Einstellungen hinzufügen.',
};

if (!fs.existsSync(potFile)) {
	console.error(`Missing POT file: ${potFile}`);
	console.error('Run: npm run pot');
	process.exit(1);
}

if (!fs.existsSync(languagesDir)) {
	fs.mkdirSync(languagesDir, { recursive: true });
}

let content = fs.readFileSync(potFile, 'utf8');

content = content.replace(
	/^"POT-Creation-Date:.*$/m,
	'"PO-Revision-Date: ' + new Date().toISOString().replace(/\.\d{3}Z$/, '+00:00') + '\\n"'
);

if (!content.includes('"Language: de_DE\\n"')) {
	content = content.replace(
		'"Content-Transfer-Encoding: 8bit\\n"',
		'"Content-Transfer-Encoding: 8bit\\n"\n"Language: de_DE\\n"\n"Plural-Forms: nplurals=2; plural=(n != 1);\\n"\n"Language-Team: webentwicklerin, Gabriele Laesser\\n"'
	);
}

content = content.replace(/^msgid "((?:\\.|[^"\\])*)"\nmsgstr ""$/gm, (match, msgid) => {
	const decoded = msgid.replace(/\\n/g, '\n').replace(/\\"/g, '"');
	if (Object.prototype.hasOwnProperty.call(translations, decoded)) {
		const translated = translations[decoded]
			.replace(/\\/g, '\\\\')
			.replace(/"/g, '\\"')
			.replace(/\n/g, '\\n');
		return `msgid "${msgid}"\nmsgstr "${translated}"`;
	}
	return match;
});

fs.writeFileSync(poFile, content, 'utf8');

try {
	runWp(['i18n', 'update-po', potFile, poFile]);
	console.log(`PO file created/updated: ${poFile}`);
} catch (error) {
	console.error(error instanceof Error ? error.message : String(error));
	process.exit(1);
}

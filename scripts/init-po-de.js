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
	'WE Mave Video': 'WE Mave Video',
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
	'Once weekly (WE Mave Video)': 'Einmal wöchentlich (WE Mave Video)',
	'Once monthly (WE Mave Video)': 'Einmal monatlich (WE Mave Video)',
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
	'Borlabs Cookie': 'Borlabs Cookie',
	'Block mave player embeds as external media until consent is given. mave.io does not use tracking cookies; this is a content blocker only.':
		'mave-Player-Embeds bis zur Einwilligung als externes Medium blockieren. mave.io verwendet keine Tracking-Cookies; es handelt sich nur um einen Content Blocker.',
	'Content blocker': 'Content Blocker',
	'Wrap player embeds with the Borlabs content blocker.':
		'Player-Embeds mit dem Borlabs Content Blocker umschließen.',
	'The Borlabs Cookie API is not available on this request. Save settings after Borlabs Cookie has finished loading.':
		'Die Borlabs-Cookie-API ist in dieser Anfrage nicht verfügbar. Einstellungen speichern, nachdem Borlabs Cookie vollständig geladen ist.',
	'Content blocker ID': 'Content-Blocker-ID',
	'Create a content blocker in Borlabs Cookie with this ID (default: %1$s). Assign it to the <strong>External Media</strong> service group. Privacy policy: <a href="%2$s" target="_blank" rel="noopener noreferrer">mave.io privacy</a>. Suggested hostnames: %3$s.':
		'In Borlabs Cookie einen Content Blocker mit dieser ID anlegen (Standard: %1$s). Der Service-Gruppe <strong>Externe Medien</strong> zuordnen. Datenschutzerklärung: <a href="%2$s" target="_blank" rel="noopener noreferrer">mave.io Datenschutz</a>. Vorgeschlagene Hostnames: %3$s.',
	'Real Cookie Banner': 'Real Cookie Banner',
	'Defer mave player embeds until external media is allowed. mave.io does not use tracking cookies; assign the service to your external media group.':
		'mave-Player-Embeds verzögern, bis externe Medien erlaubt sind. mave.io verwendet keine Tracking-Cookies; den Service der Gruppe für externe Medien zuordnen.',
	'Borlabs Cookie is active and takes precedence when its content blocker integration is enabled.':
		'Borlabs Cookie ist aktiv und hat Vorrang, wenn die Content-Blocker-Integration aktiviert ist.',
	'Consent integration': 'Einwilligungs-Integration',
	'Wait for Real Cookie Banner consent before loading the player.':
		'Vor dem Laden des Players auf die Einwilligung in Real Cookie Banner warten.',
	'The Real Cookie Banner API is not available on this request. Save settings after Real Cookie Banner has finished loading.':
		'Die Real-Cookie-Banner-API ist in dieser Anfrage nicht verfügbar. Einstellungen speichern, nachdem Real Cookie Banner vollständig geladen ist.',
	'Service unique identifier': 'Eindeutige Service-Kennung',
	'Create a service in Real Cookie Banner with this unique identifier (default: %1$s). Use the <strong>External media</strong> group. Privacy policy: <a href="%2$s" target="_blank" rel="noopener noreferrer">mave.io privacy</a>. Optional content blocker hostnames: %3$s.':
		'In Real Cookie Banner einen Service mit dieser eindeutigen Kennung anlegen (Standard: %1$s). Der Gruppe <strong>Externe Medien</strong> zuordnen. Datenschutzerklärung: <a href="%2$s" target="_blank" rel="noopener noreferrer">mave.io Datenschutz</a>. Optionale Content-Blocker-Hostnames: %3$s.',
	'External video content from mave.io is blocked until you allow external media. mave.io does not use tracking cookies.':
		'Externer Videoinhalt von mave.io ist blockiert, bis externe Medien erlaubt sind. mave.io verwendet keine Tracking-Cookies.',
	'mave.io privacy policy': 'mave.io Datenschutzerklärung',
	'Load video': 'Video laden',
	'Setup guides for embedding and consent tools are available in the Help tab (top right).':
		'Einrichtungsanleitungen für Embeds und Consent-Tools findest du im Hilfe-Tab (oben rechts).',
	'Embedding videos': 'Videos einbinden',
	'This plugin self-hosts the mave.io player script on your server by default. Videos are embedded with a shortcode, the block editor block, or raw HTML markup.':
		'Dieses Plugin hostet das mave.io-Player-Skript standardmäßig auf deinem Server. Videos werden per Shortcode, Block oder HTML-Markup eingebunden.',
	'Shortcode (recommended for Enfold)': 'Shortcode (empfohlen für Enfold)',
	'Use the snippet generator at the bottom of this settings page to copy ready-made examples.':
		'Nutze den Snippet-Generator unten auf dieser Seite, um fertige Beispiele zu kopieren.',
	'mave.io does not use tracking cookies. Use a content blocker only and assign it to the external media service group.':
		'mave.io verwendet keine Tracking-Cookies. Nur einen Content Blocker verwenden und der Service-Gruppe für externe Medien zuordnen.',
	'In Borlabs Cookie, open Content Blocker and click Add New.':
		'In Borlabs Cookie Content Blocker öffnen und Neu hinzufügen.',
	'Set the ID to %s (or match the ID configured on this settings page).':
		'ID auf %s setzen (oder an die ID auf dieser Einstellungsseite anpassen).',
	'Assign the blocker to the External Media service group.':
		'Den Blocker der Service-Gruppe Externe Medien zuordnen.',
	'Set the privacy policy URL to %s.': 'Datenschutz-URL auf %s setzen.',
	'Add these hostnames if prompted: %s.': 'Diese Hostnames bei Bedarf ergänzen: %s.',
	'Activate the content blocker in Borlabs Cookie.': 'Den Content Blocker in Borlabs Cookie aktivieren.',
	'On this settings page, enable “Wrap player embeds with the Borlabs content blocker”.':
		'Auf dieser Seite „Player-Embeds mit dem Borlabs Content Blocker umschließen“ aktivieren.',
	'No script blocker is required for the self-hosted player file. If you enable “Load from official CDN” for debugging, keep the hostnames above so Borlabs can block the external script until consent.':
		'Für die selbst gehostete Player-Datei ist kein Script Blocker nötig. Bei „Vom offiziellen CDN laden“ (Debug) die Hostnames oben beibehalten, damit Borlabs das externe Skript bis zur Einwilligung blockiert.',
	'Borlabs Cookie is not active on this site. Install and activate it before using this integration.':
		'Borlabs Cookie ist auf dieser Website nicht aktiv. Vor der Integration installieren und aktivieren.',
	'mave.io does not use tracking cookies. Create a service for external media and let this plugin wait for consent before loading the player.':
		'mave.io verwendet keine Tracking-Cookies. Einen Service für externe Medien anlegen; dieses Plugin wartet mit dem Player-Laden auf die Einwilligung.',
	'In Real Cookie Banner, open Cookies and add a new service (or create from scratch).':
		'In Real Cookie Banner Cookies öffnen und einen neuen Service anlegen (oder von Grund auf erstellen).',
	'Set the unique identifier to %s (or match the identifier on this settings page).':
		'Eindeutige Kennung auf %s setzen (oder an die Kennung auf dieser Einstellungsseite anpassen).',
	'Assign the service to the External media group.':
		'Den Service der Gruppe Externe Medien zuordnen.',
	'Optional: add a content blocker for the player markup or hostnames if you want Real Cookie Banner to manage the placeholder UI as well.':
		'Optional: Content Blocker für Player-Markup oder Hostnames, wenn Real Cookie Banner auch die Platzhalter-UI steuern soll.',
	'Suggested hostnames for a content blocker: %s.':
		'Vorgeschlagene Hostnames für einen Content Blocker: %s.',
	'On this settings page, enable “Wait for Real Cookie Banner consent before loading the player”.':
		'Auf dieser Seite „Vor dem Laden des Players auf die Einwilligung in Real Cookie Banner warten“ aktivieren.',
	'This integration also works when you load the player script from the official mave CDN (debug setting). The script is deferred until consent in both cases.':
		'Die Integration funktioniert auch mit dem offiziellen mave-CDN (Debug-Einstellung). Das Skript wird in beiden Fällen bis zur Einwilligung verzögert.',
	'If Borlabs Cookie is active and its content blocker integration is enabled, Borlabs takes precedence and Real Cookie Banner integration is skipped.':
		'Ist Borlabs Cookie aktiv und die Content-Blocker-Integration eingeschaltet, hat Borlabs Vorrang; die Real-Cookie-Banner-Integration entfällt.',
	'Real Cookie Banner is not active on this site. Install and activate it before using this integration.':
		'Real Cookie Banner ist auf dieser Website nicht aktiv. Vor der Integration installieren und aktivieren.',
	'More information': 'Weitere Informationen',
	'mave.io player documentation': 'mave.io Player-Dokumentation',
	'mave.io privacy policy': 'mave.io Datenschutzerklärung',
	'Plugin on GitHub': 'Plugin auf GitHub',
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

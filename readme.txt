=== WE Mave Video ===
Contributors: webentwicklerin
Tags: video, mave, embed, shortcode, player
Requires at least: 6.5
Tested up to: 6.8
Requires PHP: 8.0
Stable tag: 1.0.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Self-host the mave.io player components and embed videos via shortcode, HTML snippet, or block.

== Description ==

WE Mave Video lets you embed mave.io hosted videos without loading the player script from an external CDN.

Features:

* Self-hosted `@maveio/components` package
* Scheduled or manual component updates from the npm registry
* Shortcode: `[we_mave_player embed="YOUR_EMBED_ID"]`
* Copyable snippets for Enfold and other page builders
* Optional Gutenberg block
* Global player defaults with per-embed overrides

The bundled mave components are licensed under AGPL-3.0-or-later.

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/we-mave-video/`.
2. Activate the plugin through the Plugins screen.
3. Go to Settings → WE Mave Video to verify the components were downloaded.
4. Add `[we_mave_player embed="YOUR_EMBED_ID"]` to a page or use the block.

== Frequently Asked Questions ==

= How do I embed a video in Enfold? =

Use Enfold's Shortcode element and paste `[we_mave_player embed="YOUR_EMBED_ID"]`.

= Where does the plugin download components from? =

From the official npm registry package `@maveio/components`.

== Changelog ==

= 1.0.0 =

See `CHANGELOG.md` for detailed release notes.

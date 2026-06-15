<?php
/**
 * Contextual help tabs for the settings screen.
 *
 * @package Webentwicklerin\WeMaveVideo
 */

declare(strict_types=1);

namespace Webentwicklerin\WeMaveVideo\Admin;

use Webentwicklerin\WeMaveVideo\Integrations\Borlabs_Cookie;
use Webentwicklerin\WeMaveVideo\Integrations\Real_Cookie_Banner;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers WordPress admin help tabs (screen top-right "Help").
 */
final class Settings_Help {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'load-settings_page_' . Settings_Page::PAGE_SLUG_PUBLIC, array( $this, 'add_help_tabs' ) );
	}

	/**
	 * Add help tabs to the settings screen.
	 *
	 * @return void
	 */
	public function add_help_tabs(): void {
		$screen = get_current_screen();

		if ( ! $screen ) {
			return;
		}

		$screen->add_help_tab(
			array(
				'id'      => 'we-mave-video-help-embed',
				'title'   => __( 'Embedding videos', 'we-mave-video' ),
				'content' => $this->get_embed_help_content(),
			)
		);

		$screen->add_help_tab(
			array(
				'id'      => 'we-mave-video-help-borlabs',
				'title'   => __( 'Borlabs Cookie', 'we-mave-video' ),
				'content' => $this->get_borlabs_help_content(),
			)
		);

		$screen->add_help_tab(
			array(
				'id'      => 'we-mave-video-help-rcb',
				'title'   => __( 'Real Cookie Banner', 'we-mave-video' ),
				'content' => $this->get_rcb_help_content(),
			)
		);

		$screen->set_help_sidebar( $this->get_help_sidebar_content() );
	}

	/**
	 * Help content: embedding videos.
	 *
	 * @return string
	 */
	private function get_embed_help_content(): string {
		$content  = '<p>' . esc_html__( 'This plugin self-hosts the mave.io player script on your server by default. Videos are embedded with a shortcode, the block editor block, or raw HTML markup.', 'we-mave-video' ) . '</p>';
		$content .= '<p><strong>' . esc_html__( 'Shortcode (recommended for Enfold)', 'we-mave-video' ) . '</strong></p>';
		$content .= '<p><code>[we_mave_player embed="YOUR_EMBED_ID"]</code></p>';
		$content .= '<p><strong>' . esc_html__( 'HTML markup', 'we-mave-video' ) . '</strong></p>';
		$content .= '<p><code>&lt;mave-player embed="YOUR_EMBED_ID"&gt;&lt;/mave-player&gt;</code></p>';
		$content .= '<p>' . esc_html__( 'Use the snippet generator at the bottom of this settings page to copy ready-made examples.', 'we-mave-video' ) . '</p>';
		$content .= '<p>' . esc_html__( 'Player defaults on this page apply to every embed unless overridden by shortcode or block attributes.', 'we-mave-video' ) . '</p>';

		return $content;
	}

	/**
	 * Help content: Borlabs Cookie setup.
	 *
	 * @return string
	 */
	private function get_borlabs_help_content(): string {
		$blocker_id = Borlabs_Cookie::CONTENT_BLOCKER_ID;
		$hosts      = implode( ', ', Borlabs_Cookie::suggested_hostnames() );
		$privacy    = '<a href="' . esc_url( Borlabs_Cookie::PRIVACY_URL ) . '" target="_blank" rel="noopener noreferrer">mave.io/privacy</a>';

		$content  = '<p>' . esc_html__( 'mave.io does not use tracking cookies. You still need a content blocker for external media because video streams are loaded from mave infrastructure after consent. In Borlabs Cookie 3, set up a provider, a service, and a content blocker, then enable the integration in this plugin.', 'we-mave-video' ) . '</p>';

		$content .= '<p><strong>' . esc_html__( '1. Create a provider (required)', 'we-mave-video' ) . '</strong></p>';
		$content .= '<p>' . esc_html__( 'Every content blocker must be linked to a provider.', 'we-mave-video' ) . '</p>';
		$content .= '<ol>';
		$content .= '<li>' . esc_html__( 'Open Borlabs Cookie → Consent Management → Providers.', 'we-mave-video' ) . '</li>';
		$content .= '<li>' . esc_html__( 'Click Add New and enter a name, for example “mave.io”.', 'we-mave-video' ) . '</li>';
		$content .= '<li>' . sprintf(
			/* translators: %s: privacy policy URL link */
			esc_html__( 'Set the privacy policy URL to %s.', 'we-mave-video' ),
			$privacy
		) . '</li>';
		$content .= '<li>' . esc_html__( 'Add the provider address if you want it shown in the consent dialog.', 'we-mave-video' ) . '</li>';
		$content .= '<li>' . esc_html__( 'Save and activate the provider.', 'we-mave-video' ) . '</li>';
		$content .= '</ol>';

		$content .= '<p><strong>' . esc_html__( '2. Create a service (recommended)', 'we-mave-video' ) . '</strong></p>';
		$content .= '<p>' . esc_html__( 'Linking a service to the content blocker allows automatic unblocking for visitors who already accepted that service, and extends the information shown on the blocker preview.', 'we-mave-video' ) . '</p>';
		$content .= '<ol>';
		$content .= '<li>' . esc_html__( 'Open Borlabs Cookie → Consent Management → Services.', 'we-mave-video' ) . '</li>';
		$content .= '<li>' . esc_html__( 'Click Add New (or use the Library if a mave template becomes available later).', 'we-mave-video' ) . '</li>';
		$content .= '<li>' . esc_html__( 'Name the service, for example “mave.io video player”.', 'we-mave-video' ) . '</li>';
		$content .= '<li>' . esc_html__( 'Assign the service to the External Media group.', 'we-mave-video' ) . '</li>';
		$content .= '<li>' . esc_html__( 'Select the mave.io provider you created in step 1.', 'we-mave-video' ) . '</li>';
		$content .= '<li>' . esc_html__( 'Describe the purpose: embedding self-hosted mave.io videos. No tracking cookies are set by mave.io.', 'we-mave-video' ) . '</li>';
		$content .= '<li>' . sprintf(
			/* translators: %s: privacy policy URL link */
			esc_html__( 'Set the privacy policy URL to %s.', 'we-mave-video' ),
			$privacy
		) . '</li>';
		$content .= '<li>' . esc_html__( 'Leave cookie definitions empty unless your legal review requires documenting technical session data.', 'we-mave-video' ) . '</li>';
		$content .= '<li>' . esc_html__( 'Save and activate the service.', 'we-mave-video' ) . '</li>';
		$content .= '</ol>';

		$content .= '<p><strong>' . esc_html__( '3. Create the content blocker', 'we-mave-video' ) . '</strong></p>';
		$content .= '<ol>';
		$content .= '<li>' . esc_html__( 'Open Borlabs Cookie → Content Blocker → Add New.', 'we-mave-video' ) . '</li>';
		$content .= '<li>' . sprintf(
			/* translators: %s: content blocker ID */
			esc_html__( 'Set the ID to %s (must match the ID on this settings page).', 'we-mave-video' ),
			'<code>' . esc_html( $blocker_id ) . '</code>'
		) . '</li>';
		$content .= '<li>' . esc_html__( 'Choose a name, for example “WE Mave Video”.', 'we-mave-video' ) . '</li>';
		$content .= '<li>' . esc_html__( 'In Service information, select the mave.io service from step 2.', 'we-mave-video' ) . '</li>';
		$content .= '<li>' . esc_html__( 'In Provider information, select the mave.io provider from step 1 (required).', 'we-mave-video' ) . '</li>';
		$content .= '<li>' . sprintf(
			/* translators: %s: comma-separated hostnames */
			esc_html__( 'Under hosts / URLs, add: %s. This blocks external requests until consent.', 'we-mave-video' ),
			'<code>' . esc_html( $hosts ) . '</code>'
		) . '</li>';
		$content .= '<li>' . esc_html__( 'Customize the preview text if needed, for example “Load video” and a short note that mave.io does not use tracking cookies.', 'we-mave-video' ) . '</li>';
		$content .= '<li>' . esc_html__( 'Activate the content blocker.', 'we-mave-video' ) . '</li>';
		$content .= '</ol>';

		$content .= '<p><strong>' . esc_html__( '4. Enable this plugin integration', 'we-mave-video' ) . '</strong></p>';
		$content .= '<ol>';
		$content .= '<li>' . esc_html__( 'On this settings page, enable “Wrap player embeds with the Borlabs content blocker”.', 'we-mave-video' ) . '</li>';
		$content .= '<li>' . esc_html__( 'Save settings and test a page with a mave embed in a private browser window.', 'we-mave-video' ) . '</li>';
		$content .= '</ol>';

		$content .= '<p><strong>' . esc_html__( 'CDN debug mode', 'we-mave-video' ) . '</strong></p>';
		$content .= '<p>' . esc_html__( 'No script blocker is required when the player file is self-hosted on your domain. If you enable “Load from official CDN” for debugging, keep the hostnames above so Borlabs can block the external module script until consent.', 'we-mave-video' ) . '</p>';

		if ( ! Borlabs_Cookie::is_plugin_active() ) {
			$content .= '<p><em>' . esc_html__( 'Borlabs Cookie is not active on this site. Install and activate it before using this integration.', 'we-mave-video' ) . '</em></p>';
		}

		return $content;
	}

	/**
	 * Help content: Real Cookie Banner setup.
	 *
	 * @return string
	 */
	private function get_rcb_help_content(): string {
		$service_id = Real_Cookie_Banner::SERVICE_ID;
		$hosts      = implode( ', ', Real_Cookie_Banner::suggested_hostnames() );
		$privacy    = '<a href="' . esc_url( Real_Cookie_Banner::PRIVACY_URL ) . '" target="_blank" rel="noopener noreferrer">mave.io/privacy</a>';

		$content  = '<p>' . esc_html__( 'mave.io does not use tracking cookies. Create a service for external media and let this plugin wait for consent before loading the player.', 'we-mave-video' ) . '</p>';

		$content .= '<p><strong>' . esc_html__( '1. Create the service', 'we-mave-video' ) . '</strong></p>';
		$content .= '<ol>';
		$content .= '<li>' . esc_html__( 'Open Real Cookie Banner → Cookies → Add service (or create from scratch).', 'we-mave-video' ) . '</li>';
		$content .= '<li>' . sprintf(
			/* translators: %s: service unique identifier */
			esc_html__( 'Set the unique identifier to %s (must match this settings page).', 'we-mave-video' ),
			'<code>' . esc_html( $service_id ) . '</code>'
		) . '</li>';
		$content .= '<li>' . esc_html__( 'Name the service, for example “mave.io video player”.', 'we-mave-video' ) . '</li>';
		$content .= '<li>' . esc_html__( 'Assign the service to the External media group.', 'we-mave-video' ) . '</li>';
		$content .= '<li>' . esc_html__( 'Describe the purpose: external video hosting via mave.io. No tracking cookies.', 'we-mave-video' ) . '</li>';
		$content .= '<li>' . sprintf(
			/* translators: %s: privacy policy URL link */
			esc_html__( 'Set the privacy policy URL to %s.', 'we-mave-video' ),
			$privacy
		) . '</li>';
		$content .= '<li>' . esc_html__( 'Do not add marketing or statistics cookies for mave.io unless your legal review says otherwise.', 'we-mave-video' ) . '</li>';
		$content .= '<li>' . esc_html__( 'Save and activate the service.', 'we-mave-video' ) . '</li>';
		$content .= '</ol>';

		$content .= '<p><strong>' . esc_html__( '2. Optional content blocker in Real Cookie Banner', 'we-mave-video' ) . '</strong></p>';
		$content .= '<p>' . esc_html__( 'This plugin already shows a placeholder and defers the player script until consent. You can additionally create a content blocker in Real Cookie Banner if you want RCB to manage blocking by hostname or custom selectors.', 'we-mave-video' ) . '</p>';
		$content .= '<ol>';
		$content .= '<li>' . esc_html__( 'Open Real Cookie Banner → Cookies → Content blocker → Add content blocker.', 'we-mave-video' ) . '</li>';
		$content .= '<li>' . esc_html__( 'Link the blocker to the mave.io service from step 1.', 'we-mave-video' ) . '</li>';
		$content .= '<li>' . sprintf(
			/* translators: %s: comma-separated hostnames */
			esc_html__( 'Suggested hostnames: %s.', 'we-mave-video' ),
			'<code>' . esc_html( $hosts ) . '</code>'
		) . '</li>';
		$content .= '<li>' . esc_html__( 'Optional: block custom elements such as mave-player or .we-mave-video-player.', 'we-mave-video' ) . '</li>';
		$content .= '</ol>';

		$content .= '<p><strong>' . esc_html__( '3. Enable this plugin integration', 'we-mave-video' ) . '</strong></p>';
		$content .= '<ol>';
		$content .= '<li>' . esc_html__( 'On this settings page, enable “Wait for Real Cookie Banner consent before loading the player”.', 'we-mave-video' ) . '</li>';
		$content .= '<li>' . esc_html__( 'Save settings and test in a private browser window.', 'we-mave-video' ) . '</li>';
		$content .= '</ol>';

		$content .= '<p><strong>' . esc_html__( 'CDN debug mode', 'we-mave-video' ) . '</strong></p>';
		$content .= '<p>' . esc_html__( 'The integration also works when you load the player script from the official mave CDN. The script is deferred until consent in both cases.', 'we-mave-video' ) . '</p>';
		$content .= '<p>' . esc_html__( 'If Borlabs Cookie is active and its content blocker integration is enabled, Borlabs takes precedence and Real Cookie Banner integration is skipped.', 'we-mave-video' ) . '</p>';

		if ( ! Real_Cookie_Banner::is_plugin_active() ) {
			$content .= '<p><em>' . esc_html__( 'Real Cookie Banner is not active on this site. Install and activate it before using this integration.', 'we-mave-video' ) . '</em></p>';
		}

		return $content;
	}

	/**
	 * Help sidebar links.
	 *
	 * @return string
	 */
	private function get_help_sidebar_content(): string {
		$links  = '<p><strong>' . esc_html__( 'More information', 'we-mave-video' ) . '</strong></p><ul>';
		$links .= '<li><a href="https://www.mave.io/docs/player/" target="_blank" rel="noopener noreferrer">' . esc_html__( 'mave.io player documentation', 'we-mave-video' ) . '</a></li>';
		$links .= '<li><a href="' . esc_url( Borlabs_Cookie::PRIVACY_URL ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'mave.io privacy policy', 'we-mave-video' ) . '</a></li>';
		$links .= '<li><a href="https://github.com/gbyat/we-mave-video" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Plugin on GitHub', 'we-mave-video' ) . '</a></li>';
		$links .= '</ul>';

		return $links;
	}
}

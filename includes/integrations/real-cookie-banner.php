<?php
/**
 * Real Cookie Banner consent integration.
 *
 * @package Webentwicklerin\WeMaveVideo
 */

declare(strict_types=1);

namespace Webentwicklerin\WeMaveVideo\Integrations;

use Webentwicklerin\WeMaveVideo\Frontend\Script_Loader;
use Webentwicklerin\WeMaveVideo\Options;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Defers mave player embeds until Real Cookie Banner consent (external media, no cookies).
 */
final class Real_Cookie_Banner {

	public const SERVICE_ID = 'we-mave-video';

	public const PRIVACY_URL = 'https://www.mave.io/privacy/';

	/**
	 * Hostnames to block in a Real Cookie Banner content blocker (documentation default).
	 *
	 * @return string[]
	 */
	public static function suggested_hostnames(): array {
		$hosts = array(
			'cdn.video-dns.com',
			'*.video-dns.com',
			'*.mave.io',
		);

		/**
		 * Filter suggested hostnames for Real Cookie Banner setup.
		 *
		 * @param string[] $hosts Suggested hostnames.
		 */
		return apply_filters( 'we_mave_video_rcb_suggested_hostnames', $hosts );
	}

	/**
	 * Whether the Real Cookie Banner PHP API is available.
	 *
	 * @return bool
	 */
	public static function is_api_available(): bool {
		return function_exists( 'wp_rcb_consent_given' );
	}

	/**
	 * Whether this integration should run (and Borlabs is not handling consent).
	 *
	 * @return bool
	 */
	public static function is_enabled(): bool {
		if ( Borlabs_Cookie::is_enabled() ) {
			return false;
		}

		if ( ! self::is_api_available() ) {
			return false;
		}

		$settings = Options::get_settings();

		return ! empty( $settings['rcb_consent_enabled'] );
	}

	/**
	 * Service unique identifier configured in Real Cookie Banner.
	 *
	 * @return string
	 */
	public static function get_service_id(): string {
		$settings = Options::get_settings();
		$id       = sanitize_key( (string) ( $settings['rcb_service_id'] ?? self::SERVICE_ID ) );

		if ( '' === $id ) {
			$id = self::SERVICE_ID;
		}

		/**
		 * Filter the Real Cookie Banner service ID used for mave player embeds.
		 *
		 * @param string $id Service unique identifier.
		 */
		return (string) apply_filters( 'we_mave_video_rcb_service_id', $id );
	}

	/**
	 * Whether consent for the mave service has already been given.
	 *
	 * @return bool
	 */
	public static function has_consent(): bool {
		if ( ! self::is_api_available() ) {
			return false;
		}

		$result = wp_rcb_consent_given( self::get_service_id() );

		if ( ! is_array( $result ) ) {
			return false;
		}

		return ! empty( $result['consentGiven'] ) && ! empty( $result['cookieOptIn'] );
	}

	/**
	 * Render a placeholder until the visitor allows external media.
	 *
	 * @param string $html Original player markup.
	 * @return string
	 */
	public static function render_placeholder( string $html ): string {
		if ( '' === $html ) {
			return '';
		}

		/**
		 * Filter player HTML before the Real Cookie Banner placeholder is rendered.
		 *
		 * @param string $html       Player markup.
		 * @param string $service_id Service unique identifier.
		 */
		$html = (string) apply_filters( 'we_mave_video_rcb_before_placeholder', $html, self::get_service_id() );

		$privacy_link = sprintf(
			'<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>',
			esc_url( self::PRIVACY_URL ),
			esc_html__( 'mave.io privacy policy', 'we-mave-video' )
		);

		$placeholder = sprintf(
			'<div class="we-mave-video-rcb-blocked"><p class="we-mave-video-rcb-notice">%1$s</p><p class="we-mave-video-rcb-privacy">%2$s</p><p><button type="button" class="button" data-we-mave-rcb-load>%3$s</button></p><template class="we-mave-video-rcb-template">%4$s</template></div>',
			esc_html__( 'External video content from mave.io is blocked until you allow external media. mave.io does not use tracking cookies.', 'we-mave-video' ),
			wp_kses(
				$privacy_link,
				array(
					'a' => array(
						'href'   => true,
						'target' => true,
						'rel'    => true,
					),
				)
			),
			esc_html__( 'Load video', 'we-mave-video' ),
			$html
		);

		/**
		 * Filter the Real Cookie Banner placeholder markup.
		 *
		 * @param string $placeholder Placeholder markup.
		 * @param string $html        Original player markup.
		 * @param string $service_id  Service unique identifier.
		 */
		return (string) apply_filters( 'we_mave_video_rcb_placeholder', $placeholder, $html, self::get_service_id() );
	}

	/**
	 * Whether the Real Cookie Banner plugin is active.
	 *
	 * @return bool
	 */
	public static function is_plugin_active(): bool {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return is_plugin_active( 'real-cookie-banner/real-cookie-banner.php' )
			|| is_plugin_active( 'real-cookie-banner-pro/real-cookie-banner-pro.php' );
	}

	/**
	 * Register hooks for Real Cookie Banner integration.
	 *
	 * @return void
	 */
	public static function register_hooks(): void {
		add_filter( 'the_content', array( self::class, 'filter_the_content' ), 999 );
		add_action( 'RCB/Templates/TechnicalHandlingIntegration', array( self::class, 'register_technical_handling' ) );
		add_action( 'update_option_' . Options::OPTION_SETTINGS, array( self::class, 'invalidate_template_cache' ) );
		add_action( 'add_option_' . Options::OPTION_SETTINGS, array( self::class, 'invalidate_template_cache' ) );
	}

	/**
	 * Tell Real Cookie Banner that this plugin handles the mave embed technically.
	 *
	 * @param object $integration Technical handling integration instance.
	 * @return void
	 */
	public static function register_technical_handling( $integration ): void {
		if ( ! self::is_plugin_active() || ! is_object( $integration ) || ! method_exists( $integration, 'integrate' ) ) {
			return;
		}

		if ( ! $integration->integrate( WE_MAVE_VIDEO_FILE, self::get_service_id() ) ) {
			return;
		}

		if ( method_exists( $integration, 'setCodeOptIn' ) ) {
			$integration->setCodeOptIn( '' );
		}

		if ( method_exists( $integration, 'setCodeOptOut' ) ) {
			$integration->setCodeOptOut( '' );
		}
	}

	/**
	 * Invalidate Real Cookie Banner template cache after plugin settings change.
	 *
	 * @return void
	 */
	public static function invalidate_template_cache(): void {
		if ( function_exists( 'wp_rcb_invalidate_templates_cache' ) ) {
			wp_rcb_invalidate_templates_cache( true );
		}
	}

	/**
	 * Block raw mave-player markup in post content (HTML modules).
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	public static function filter_the_content( string $content ): string {
		if ( ! self::is_enabled() || ! str_contains( $content, 'mave-player' ) ) {
			return $content;
		}

		if ( self::has_consent() ) {
			Script_Loader::mark_required();
			return $content;
		}

		$result = preg_replace_callback(
			'/<mave-player\b[^>]*>\s*<\/mave-player>/i',
			static function ( array $matches ): string {
				$wrapped = sprintf(
					'<div class="we-mave-video-player">%s</div>',
					$matches[0]
				);

				Script_Loader::mark_rcb_deferred();

				return self::render_placeholder( $wrapped );
			},
			$content
		);

		return is_string( $result ) ? $result : $content;
	}
}

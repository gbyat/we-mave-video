<?php
/**
 * Borlabs Cookie content blocker integration.
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
 * Wraps player markup for Borlabs Cookie (external media, no cookies).
 */
final class Borlabs_Cookie {

	public const CONTENT_BLOCKER_ID = 'we-mave-video';

	public const PRIVACY_URL = 'https://www.mave.io/privacy/';

	/**
	 * Hostnames to add in the Borlabs content blocker (documentation default).
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
		 * Filter suggested hostnames for the Borlabs content blocker setup.
		 *
		 * @param string[] $hosts Suggested hostnames.
		 */
		return apply_filters( 'we_mave_video_borlabs_suggested_hostnames', $hosts );
	}

	/**
	 * Whether the Borlabs Cookie PHP API is available.
	 *
	 * @return bool
	 */
	public static function is_api_available(): bool {
		return function_exists( 'borlabsCookieApi' ) && null !== borlabsCookieApi();
	}

	/**
	 * Whether player output should be wrapped by Borlabs.
	 *
	 * @return bool
	 */
	public static function is_enabled(): bool {
		if ( ! self::is_api_available() ) {
			return false;
		}

		$settings = Options::get_settings();

		return ! empty( $settings['borlabs_content_blocker_enabled'] );
	}

	/**
	 * Content blocker ID configured in Borlabs Cookie.
	 *
	 * @return string
	 */
	public static function get_content_blocker_id(): string {
		$settings = Options::get_settings();
		$id       = sanitize_key( (string) ( $settings['borlabs_content_blocker_id'] ?? self::CONTENT_BLOCKER_ID ) );

		if ( '' === $id ) {
			$id = self::CONTENT_BLOCKER_ID;
		}

		/**
		 * Filter the Borlabs content blocker ID used for mave player embeds.
		 *
		 * @param string $id Content blocker ID.
		 */
		return (string) apply_filters( 'we_mave_video_borlabs_content_blocker_id', $id );
	}

	/**
	 * Borlabs service ID linked to the content blocker (for consent checks).
	 *
	 * @return string
	 */
	public static function get_service_id(): string {
		$settings   = Options::get_settings();
		$service_id = sanitize_key( (string) ( $settings['borlabs_service_id'] ?? '' ) );

		if ( '' === $service_id ) {
			$service_id = self::get_content_blocker_id();
		}

		/**
		 * Filter the Borlabs service ID used for mave player consent checks.
		 *
		 * @param string $service_id Service unique identifier.
		 */
		return (string) apply_filters( 'we_mave_video_borlabs_service_id', $service_id );
	}

	/**
	 * Whether consent for the mave service or content blocker has already been given.
	 *
	 * @return bool
	 */
	public static function has_consent(): bool {
		if ( ! self::is_api_available() ) {
			return false;
		}

		$api = borlabsCookieApi();

		if ( null === $api || ! method_exists( $api, 'consentsApi' ) ) {
			return false;
		}

		$consents = $api->consentsApi();

		if ( ! is_object( $consents ) ) {
			return false;
		}

		$blocker_id = self::get_content_blocker_id();
		$service_id = self::get_service_id();

		if ( method_exists( $consents, 'hasConsentForContentBlockerId' ) && $consents->hasConsentForContentBlockerId( $blocker_id ) ) {
			return true;
		}

		if ( method_exists( $consents, 'hasConsent' ) && $consents->hasConsent( $service_id ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Wrap player HTML with the Borlabs content blocker.
	 *
	 * @param string $html Player markup.
	 * @return string
	 */
	public static function block_player_html( string $html ): string {
		if ( ! self::is_enabled() || '' === $html || self::has_consent() ) {
			return $html;
		}

		$api = borlabsCookieApi();

		if ( null === $api ) {
			return $html;
		}

		/**
		 * Filter player HTML before Borlabs content blocking.
		 *
		 * @param string               $html       Player markup.
		 * @param string               $blocker_id Content blocker ID.
		 */
		$html = (string) apply_filters( 'we_mave_video_borlabs_before_block', $html, self::get_content_blocker_id() );

		$blocked = $api->contentBlockerApi()->blockContent(
			$html,
			null,
			self::get_content_blocker_id()
		);

		/**
		 * Filter player HTML after Borlabs content blocking.
		 *
		 * @param string $blocked    Blocked markup.
		 * @param string $html       Original player markup.
		 * @param string $blocker_id Content blocker ID.
		 */
		return (string) apply_filters( 'we_mave_video_borlabs_after_block', $blocked, $html, self::get_content_blocker_id() );
	}

	/**
	 * Whether the Borlabs Cookie plugin is active.
	 *
	 * @return bool
	 */
	public static function is_plugin_active(): bool {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return is_plugin_active( 'borlabs-cookie/borlabs-cookie.php' );
	}

	/**
	 * Register front-end content filters.
	 *
	 * @return void
	 */
	public static function register_hooks(): void {
		add_filter( 'the_content', array( self::class, 'filter_the_content' ), 999 );
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

		$result = preg_replace_callback(
			'/<mave-player\b[^>]*>\s*<\/mave-player>/i',
			static function ( array $matches ): string {
				$wrapped = sprintf(
					'<div class="we-mave-video-player">%s</div>',
					$matches[0]
				);

				if ( self::has_consent() ) {
					Script_Loader::mark_required();

					return $wrapped;
				}

				Script_Loader::mark_borlabs_deferred();

				return self::block_player_html( $wrapped );
			},
			$content
		);

		return is_string( $result ) ? $result : $content;
	}
}

<?php
/**
 * Enqueues the self-hosted mave components module.
 *
 * @package Webentwicklerin\WeMaveVideo
 */

declare(strict_types=1);

namespace Webentwicklerin\WeMaveVideo\Frontend;

use Webentwicklerin\WeMaveVideo\Admin\Asset_Updater;
use Webentwicklerin\WeMaveVideo\Integrations\Borlabs_Cookie;
use Webentwicklerin\WeMaveVideo\Integrations\Real_Cookie_Banner;
use Webentwicklerin\WeMaveVideo\Options;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Loads the local ESM entry when a player is present.
 */
final class Script_Loader {

	public const SCRIPT_HANDLE = 'we-mave-video-mave-components';

	public const BORLABS_LOADER_HANDLE = 'we-mave-video-borlabs';

	public const RCB_LOADER_HANDLE = 'we-mave-video-rcb';

	private static bool $required = false;

	private static bool $borlabs_deferred = false;

	private static bool $rcb_deferred = false;

	private static bool $enqueued = false;

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		// Footer hook: shortcodes and blocks run before footer, so requirements are known here.
		add_action( 'wp_footer', array( $this, 'enqueue_scripts' ), 1 );
	}

	/**
	 * Mark the script as required on the current request.
	 *
	 * @return void
	 */
	public static function mark_required(): void {
		self::$required = true;
	}

	/**
	 * Defer module loading until Borlabs unblocks the player.
	 *
	 * @return void
	 */
	public static function mark_borlabs_deferred(): void {
		self::$borlabs_deferred = true;
		self::$required         = true;
	}

	/**
	 * Defer module loading until Real Cookie Banner consent is given.
	 *
	 * @return void
	 */
	public static function mark_rcb_deferred(): void {
		self::$rcb_deferred = true;
		self::$required     = true;
	}

	/**
	 * Mark script loading based on active consent integrations.
	 *
	 * @return void
	 */
	public static function mark_for_consent(): void {
		self::$required = true;

		if ( Borlabs_Cookie::is_enabled() ) {
			self::$borlabs_deferred = true;
			return;
		}

		if ( Real_Cookie_Banner::is_enabled() && ! Real_Cookie_Banner::has_consent() ) {
			self::$rcb_deferred = true;
		}
	}

	/**
	 * Register and enqueue the player module in the footer.
	 *
	 * @return void
	 */
	public function enqueue_scripts(): void {
		if ( self::$enqueued ) {
			return;
		}

		if ( ! self::$required && ! $this->should_load_globally() ) {
			return;
		}

		$updater = new Asset_Updater();
		$src     = $updater->get_script_url();

		if ( '' === $src ) {
			return;
		}

		self::$enqueued = true;

		if ( Borlabs_Cookie::is_enabled() && self::$borlabs_deferred ) {
			$this->enqueue_borlabs_deferred_loader( $src );
			return;
		}

		if ( Real_Cookie_Banner::is_enabled() && self::$rcb_deferred ) {
			$this->enqueue_rcb_deferred_loader( $src );
			return;
		}

		wp_register_script(
			self::SCRIPT_HANDLE,
			$src,
			array(),
			WE_MAVE_VIDEO_VERSION,
			true
		);
		wp_script_add_data( self::SCRIPT_HANDLE, 'type', 'module' );
		wp_enqueue_script( self::SCRIPT_HANDLE );
	}

	/**
	 * Load the module only after Borlabs unblocks the embed.
	 *
	 * @param string $src Module URL.
	 * @return void
	 */
	private function enqueue_borlabs_deferred_loader( string $src ): void {
		wp_register_script(
			self::BORLABS_LOADER_HANDLE,
			WE_MAVE_VIDEO_URL . 'assets/frontend/borlabs-unblock.js',
			array(),
			WE_MAVE_VIDEO_VERSION,
			true
		);

		wp_localize_script(
			self::BORLABS_LOADER_HANDLE,
			'weMaveVideoBorlabs',
			array(
				'src'       => $src,
				'blockerId' => Borlabs_Cookie::get_content_blocker_id(),
			)
		);

		wp_enqueue_script( self::BORLABS_LOADER_HANDLE );
	}

	/**
	 * Load the module only after Real Cookie Banner consent is given.
	 *
	 * @param string $src Module URL.
	 * @return void
	 */
	private function enqueue_rcb_deferred_loader( string $src ): void {
		wp_register_script(
			self::RCB_LOADER_HANDLE,
			WE_MAVE_VIDEO_URL . 'assets/frontend/rcb-consent.js',
			array(),
			WE_MAVE_VIDEO_VERSION,
			true
		);

		wp_localize_script(
			self::RCB_LOADER_HANDLE,
			'weMaveVideoRcb',
			array(
				'src'       => $src,
				'serviceId' => Real_Cookie_Banner::get_service_id(),
			)
		);

		wp_enqueue_script( self::RCB_LOADER_HANDLE );
	}

	/**
	 * Whether global loading is enabled.
	 *
	 * @return bool
	 */
	private function should_load_globally(): bool {
		$settings = Options::get_settings();
		return ! empty( $settings['load_globally'] );
	}
}

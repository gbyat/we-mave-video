<?php
/**
 * Prints the self-hosted mave components module in the footer.
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
 * Loads the mave ESM entry when a player is present.
 *
 * Uses a direct script tag in wp_footer (as in 1.0.4). wp_enqueue_script with
 * type="module" is unreliable when registered late in the footer.
 */
final class Script_Loader {

	private static bool $required = false;

	private static bool $borlabs_deferred = false;

	private static bool $rcb_deferred = false;

	private static bool $printed = false;

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		// Priority 20: shortcodes/blocks run before footer; print after core footer scripts.
		add_action( 'wp_footer', array( $this, 'print_scripts' ), 20 );
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
	 * Print player scripts in the footer.
	 *
	 * @return void
	 */
	public function print_scripts(): void {
		if ( self::$printed ) {
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

		self::$printed = true;

		if ( Borlabs_Cookie::is_enabled() && self::$borlabs_deferred ) {
			$this->print_borlabs_deferred_loader( $src );
			return;
		}

		if ( Real_Cookie_Banner::is_enabled() && self::$rcb_deferred ) {
			$this->print_rcb_deferred_loader( $src );
			return;
		}

		$this->print_module_script( $src );
	}

	/**
	 * Output a type="module" script tag.
	 *
	 * @param string $src Module URL.
	 * @return void
	 */
	private function print_module_script( string $src ): void {
		printf(
			'<script type="module" src="%s"></script>' . "\n",
			esc_url( $src )
		);
	}

	/**
	 * Load the module only after Borlabs unblocks the embed.
	 *
	 * @param string $src Module URL.
	 * @return void
	 */
	private function print_borlabs_deferred_loader( string $src ): void {
		$config = array(
			'src'       => $src,
			'blockerId' => Borlabs_Cookie::get_content_blocker_id(),
		);

		printf(
			'<script>window.weMaveVideoBorlabs = %s;</script>' . "\n",
			wp_json_encode( $config )
		);

		printf(
			'<script src="%s"></script>' . "\n",
			esc_url( $this->plugin_asset_url( 'assets/frontend/borlabs-unblock.js' ) )
		);
	}

	/**
	 * Load the module only after Real Cookie Banner consent is given.
	 *
	 * @param string $src Module URL.
	 * @return void
	 */
	private function print_rcb_deferred_loader( string $src ): void {
		$config = array(
			'src'       => $src,
			'serviceId' => Real_Cookie_Banner::get_service_id(),
		);

		printf(
			'<script>window.weMaveVideoRcb = %s;</script>' . "\n",
			wp_json_encode( $config )
		);

		printf(
			'<script src="%s"></script>' . "\n",
			esc_url( $this->plugin_asset_url( 'assets/frontend/rcb-consent.js' ) )
		);
	}

	/**
	 * Build a versioned URL to a plugin asset.
	 *
	 * @param string $relative_path Path relative to the plugin root.
	 * @return string
	 */
	private function plugin_asset_url( string $relative_path ): string {
		return add_query_arg(
			'ver',
			WE_MAVE_VIDEO_VERSION,
			WE_MAVE_VIDEO_URL . ltrim( $relative_path, '/' )
		);
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

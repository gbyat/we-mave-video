<?php
/**
 * Enqueues the self-hosted mave components module.
 *
 * @package Webentwicklerin\WeMaveVideo
 */

declare(strict_types=1);

namespace Webentwicklerin\WeMaveVideo\Frontend;

use Webentwicklerin\WeMaveVideo\Admin\Asset_Updater;
use Webentwicklerin\WeMaveVideo\Options;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Loads the local ESM entry when a player is present.
 */
final class Script_Loader {

	private static bool $required = false;

	private static bool $printed = false;

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'wp_footer', array( $this, 'print_script' ), 20 );
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
	 * Print the module script in the footer.
	 *
	 * @return void
	 */
	public function print_script(): void {
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

		printf(
			'<script type="module" src="%s"></script>',
			esc_url( $src )
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

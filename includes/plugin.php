<?php
/**
 * Main plugin bootstrap.
 *
 * @package Webentwicklerin\WeMaveVideo
 */

declare(strict_types=1);

namespace Webentwicklerin\WeMaveVideo;

use Webentwicklerin\WeMaveVideo\Admin\Asset_Updater;
use Webentwicklerin\WeMaveVideo\Admin\Cron_Scheduler;
use Webentwicklerin\WeMaveVideo\Admin\Settings_Page;
use Webentwicklerin\WeMaveVideo\Blocks\Player_Block;
use Webentwicklerin\WeMaveVideo\Core\Plugin_Updater;
use Webentwicklerin\WeMaveVideo\Frontend\Content_Detector;
use Webentwicklerin\WeMaveVideo\Frontend\Script_Loader;
use Webentwicklerin\WeMaveVideo\Frontend\Shortcode;
use Webentwicklerin\WeMaveVideo\Integrations\Borlabs_Cookie;
use Webentwicklerin\WeMaveVideo\Integrations\Real_Cookie_Banner;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Coordinates plugin services.
 */
final class Plugin {

	private static ?Plugin $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return self
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Initialize plugin hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		load_plugin_textdomain( 'we-mave-video', false, dirname( plugin_basename( WE_MAVE_VIDEO_FILE ) ) . '/languages' );

		if ( is_admin() || wp_doing_cron() ) {
			new Plugin_Updater( WE_MAVE_VIDEO_FILE );
		}

		if ( is_admin() ) {
			( new Settings_Page() )->register();

			$cron = new Cron_Scheduler();
			$cron->register();
			$this->ensure_cron_scheduled( $cron );
		}

		( new Shortcode() )->register();
		( new Script_Loader() )->register();
		( new Content_Detector() )->register();
		( new Player_Block() )->register();

		Borlabs_Cookie::register_hooks();
		Real_Cookie_Banner::register_hooks();
	}

	/**
	 * Run activation tasks.
	 *
	 * @return void
	 */
	public static function activate(): void {
		if ( false === get_option( Options::OPTION_SETTINGS ) ) {
			add_option( Options::OPTION_SETTINGS, Options::default_settings() );
		}

		if ( false === get_option( Options::OPTION_ASSET ) ) {
			add_option( Options::OPTION_ASSET, Options::default_asset() );
		}

		$updater = new Asset_Updater();
		if ( ! $updater->is_ready() ) {
			$updater->maybe_update( true );
		}

		( new Cron_Scheduler() )->schedule();
	}

	/**
	 * Run deactivation tasks.
	 *
	 * @return void
	 */
	public static function deactivate(): void {
		( new Cron_Scheduler() )->clear_schedule();
	}

	/**
	 * Ensure cron is scheduled when automatic checks are enabled.
	 *
	 * @param Cron_Scheduler $cron Cron scheduler instance.
	 * @return void
	 */
	private function ensure_cron_scheduled( Cron_Scheduler $cron ): void {
		$settings = Options::get_settings();

		if ( 'manual' === (string) ( $settings['update_schedule'] ?? 'weekly' ) ) {
			return;
		}

		if ( ! wp_next_scheduled( Cron_Scheduler::HOOK ) ) {
			$cron->schedule();
		}
	}
}

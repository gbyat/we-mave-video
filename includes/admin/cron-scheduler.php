<?php
/**
 * Schedules automatic asset update checks.
 *
 * @package Webentwicklerin\WeMaveVideo
 */

declare(strict_types=1);

namespace Webentwicklerin\WeMaveVideo\Admin;

use Webentwicklerin\WeMaveVideo\Options;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers cron schedules and runs update checks.
 */
final class Cron_Scheduler {

	public const HOOK = 'we_mave_video_check_asset_update';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_filter( 'cron_schedules', array( $this, 'add_schedules' ) );
		add_action( self::HOOK, array( $this, 'run_scheduled_check' ) );
		add_action( 'update_option_' . Options::OPTION_SETTINGS, array( $this, 'reschedule' ), 10, 0 );
	}

	/**
	 * Add custom cron intervals.
	 *
	 * @param array<string, array<string, int|string>> $schedules Existing schedules.
	 * @return array<string, array<string, int|string>>
	 */
	public function add_schedules( array $schedules ): array {
		$schedules['we_mave_video_weekly'] = array(
			'interval' => WEEK_IN_SECONDS,
			'display'  => __( 'Once weekly (WE Mave Video)', 'we-mave-video' ),
		);

		$schedules['we_mave_video_monthly'] = array(
			'interval' => 30 * DAY_IN_SECONDS,
			'display'  => __( 'Once monthly (WE Mave Video)', 'we-mave-video' ),
		);

		return $schedules;
	}

	/**
	 * Schedule the cron event based on settings.
	 *
	 * @return void
	 */
	public function schedule(): void {
		$this->clear_schedule();

		$settings = Options::get_settings();
		$schedule = (string) ( $settings['update_schedule'] ?? 'weekly' );

		if ( 'manual' === $schedule ) {
			return;
		}

		$recurrence = $this->map_schedule( $schedule );
		if ( null === $recurrence ) {
			return;
		}

		if ( ! wp_next_scheduled( self::HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, $recurrence, self::HOOK );
		}
	}

	/**
	 * Clear scheduled events.
	 *
	 * @return void
	 */
	public function clear_schedule(): void {
		$timestamp = wp_next_scheduled( self::HOOK );
		while ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::HOOK );
			$timestamp = wp_next_scheduled( self::HOOK );
		}
	}

	/**
	 * Reschedule after settings change.
	 *
	 * @return void
	 */
	public function reschedule(): void {
		$this->schedule();
	}

	/**
	 * Run the scheduled asset check.
	 *
	 * @return void
	 */
	public function run_scheduled_check(): void {
		$settings = Options::get_settings();
		$updater  = new Asset_Updater();

		if ( ! empty( $settings['auto_update'] ) ) {
			$updater->maybe_update( false );
			return;
		}

		$updater->check_for_update();
	}

	/**
	 * Map settings value to WP cron recurrence.
	 *
	 * @param string $schedule Settings schedule key.
	 * @return string|null
	 */
	private function map_schedule( string $schedule ): ?string {
		switch ( $schedule ) {
			case 'daily':
				return 'daily';
			case 'weekly':
				return 'we_mave_video_weekly';
			case 'monthly':
				return 'we_mave_video_monthly';
			default:
				return null;
		}
	}
}

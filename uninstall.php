<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package Webentwicklerin\WeMaveVideo
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'we_mave_video_settings' );
delete_option( 'we_mave_video_asset' );

$timestamp = wp_next_scheduled( 'we_mave_video_check_asset_update' );
while ( $timestamp ) {
	wp_unschedule_event( $timestamp, 'we_mave_video_check_asset_update' );
	$timestamp = wp_next_scheduled( 'we_mave_video_check_asset_update' );
}

$vendor_root = plugin_dir_path( __FILE__ ) . 'assets/vendor/mave-components/';
if ( is_dir( $vendor_root ) ) {
	we_mave_video_delete_directory( $vendor_root );
}

/**
 * Recursively delete a directory.
 *
 * @param string $directory Directory path.
 * @return void
 */
function we_mave_video_delete_directory( string $directory ): void {
	if ( ! file_exists( $directory ) ) {
		return;
	}

	if ( is_file( $directory ) || is_link( $directory ) ) {
		wp_delete_file( $directory );
		return;
	}

	$entries = scandir( $directory );
	if ( false === $entries ) {
		return;
	}

	foreach ( $entries as $entry ) {
		if ( '.' === $entry || '..' === $entry ) {
			continue;
		}

		we_mave_video_delete_directory( trailingslashit( $directory ) . $entry );
	}

	rmdir( $directory );
}

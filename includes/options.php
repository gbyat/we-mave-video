<?php
/**
 * Plugin options helper.
 *
 * @package Webentwicklerin\WeMaveVideo
 */

declare(strict_types=1);

namespace Webentwicklerin\WeMaveVideo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reads and writes plugin settings.
 */
final class Options {

	public const OPTION_SETTINGS = 'we_mave_video_settings';
	public const OPTION_ASSET    = 'we_mave_video_asset';

	/**
	 * Default plugin settings.
	 *
	 * @return array<string, mixed>
	 */
	public static function default_settings(): array {
		return array(
			'update_schedule' => 'weekly',
			'auto_update'     => true,
			'load_globally'          => false,
			'load_from_cdn'          => false,
			'github_updates_enabled' => 'yes',
			'borlabs_content_blocker_enabled' => true,
			'borlabs_content_blocker_id'      => 'we-mave-video',
			'rcb_consent_enabled'             => true,
			'rcb_service_id'                  => 'we-mave-video',
			'player_defaults' => array(
				'aspect_ratio' => '16/9',
				'autoplay'     => 'false',
				'controls'     => 'full',
				'color'        => '',
				'opacity'      => '',
				'loop'         => false,
				'poster'       => '',
				'subtitles'    => '',
				'theme'        => '',
				'quality'      => '',
				'audiotracks'  => 'auto',
				'width'        => '',
				'height'       => '',
			),
		);
	}

	/**
	 * Default asset metadata.
	 *
	 * @return array<string, mixed>
	 */
	public static function default_asset(): array {
		return array(
			'version'       => '',
			'path'          => '',
			'entry'         => 'mave-components.esm.js',
			'updated_at'    => 0,
			'last_check_at' => 0,
			'status'        => 'missing',
			'error_message' => '',
		);
	}

	/**
	 * Get merged settings.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_settings(): array {
		$stored = get_option( self::OPTION_SETTINGS, array() );

		if ( ! is_array( $stored ) ) {
			$stored = array();
		}

		return array_replace_recursive( self::default_settings(), $stored );
	}

	/**
	 * Update settings.
	 *
	 * @param array<string, mixed> $settings Settings payload.
	 * @return bool
	 */
	public static function update_settings( array $settings ): bool {
		return update_option( self::OPTION_SETTINGS, $settings );
	}

	/**
	 * Get asset metadata.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_asset(): array {
		$stored = get_option( self::OPTION_ASSET, array() );

		if ( ! is_array( $stored ) ) {
			$stored = array();
		}

		return array_merge( self::default_asset(), $stored );
	}

	/**
	 * Update asset metadata.
	 *
	 * @param array<string, mixed> $asset Asset payload.
	 * @return bool
	 */
	public static function update_asset( array $asset ): bool {
		return update_option( self::OPTION_ASSET, $asset );
	}
}

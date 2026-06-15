<?php
/**
 * Persistent storage for downloaded mave player bundles (outside the plugin directory).
 *
 * @package Webentwicklerin\WeMaveVideo
 */

declare(strict_types=1);

namespace Webentwicklerin\WeMaveVideo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stores self-hosted mave components under wp-content/uploads so plugin updates do not wipe them.
 */
final class Vendor_Storage {

	private const UPLOADS_SUBDIR = 'we-mave-video/mave-components';

	private const LEGACY_PLUGIN_SUBDIR = 'assets/vendor/mave-components';

	/**
	 * Absolute path to the vendor root in uploads.
	 *
	 * @return string
	 */
	public static function get_root(): string {
		$uploads = wp_upload_dir();

		return trailingslashit( $uploads['basedir'] ) . self::UPLOADS_SUBDIR . '/';
	}

	/**
	 * Public URL to the vendor root in uploads.
	 *
	 * @return string
	 */
	public static function get_root_url(): string {
		$uploads = wp_upload_dir();

		return trailingslashit( $uploads['baseurl'] ) . self::UPLOADS_SUBDIR . '/';
	}

	/**
	 * Absolute path to a version directory.
	 *
	 * @param string $version Package version.
	 * @return string
	 */
	public static function get_version_path( string $version ): string {
		return self::get_root() . sanitize_file_name( $version ) . '/';
	}

	/**
	 * Legacy vendor root inside the plugin directory.
	 *
	 * @return string
	 */
	public static function get_legacy_plugin_root(): string {
		return trailingslashit( WE_MAVE_VIDEO_PATH . self::LEGACY_PLUGIN_SUBDIR );
	}

	/**
	 * Whether a path points to the old in-plugin vendor location.
	 *
	 * @param string $path Absolute install path.
	 * @return bool
	 */
	public static function is_legacy_plugin_path( string $path ): bool {
		$legacy_root = wp_normalize_path( self::get_legacy_plugin_root() );

		return str_starts_with( wp_normalize_path( $path ), $legacy_root );
	}

	/**
	 * Convert an absolute file path to a public URL.
	 *
	 * @param string $file_path Absolute file path.
	 * @return string
	 */
	public static function path_to_public_url( string $file_path ): string {
		$file_path = wp_normalize_path( $file_path );
		$uploads   = wp_upload_dir();
		$basedir   = wp_normalize_path( (string) $uploads['basedir'] );

		if ( str_starts_with( $file_path, $basedir ) ) {
			$relative = ltrim( substr( $file_path, strlen( $basedir ) ), '/' );

			return trailingslashit( $uploads['baseurl'] ) . $relative;
		}

		$plugin_path = wp_normalize_path( WE_MAVE_VIDEO_PATH );
		if ( str_starts_with( $file_path, $plugin_path ) ) {
			$relative = ltrim( substr( $file_path, strlen( $plugin_path ) ), '/' );

			return trailingslashit( WE_MAVE_VIDEO_URL ) . $relative;
		}

		return '';
	}

	/**
	 * Repair stored asset metadata after a plugin update or legacy install.
	 *
	 * @return void
	 */
	public static function repair_installation_state(): void {
		$asset = Options::get_asset();
		$path  = (string) ( $asset['path'] ?? '' );
		$entry = (string) ( $asset['entry'] ?? 'mave-components.esm.js' );

		if ( '' !== $path && self::install_path_is_usable( $path, $entry ) ) {
			return;
		}

		if ( '' !== $path && self::is_legacy_plugin_path( $path ) && is_dir( $path ) ) {
			$migrated = self::migrate_directory( $path );
			if ( '' !== $migrated ) {
				$asset['path'] = $migrated;
				Options::update_asset( $asset );
				return;
			}
		}

		$version = (string) ( $asset['version'] ?? '' );
		if ( '' !== $version ) {
			$uploads_path = self::get_version_path( $version );
			if ( self::install_path_is_usable( $uploads_path, $entry ) ) {
				$asset['path'] = $uploads_path;
				Options::update_asset( $asset );
				return;
			}
		}

		if ( '' !== $version ) {
			$legacy_path = self::get_legacy_plugin_root() . sanitize_file_name( $version ) . '/';
			if ( is_dir( $legacy_path ) ) {
				$migrated = self::migrate_directory( $legacy_path );
				if ( '' !== $migrated ) {
					$asset['path'] = $migrated;
					Options::update_asset( $asset );
				}
			}
		}
	}

	/**
	 * Whether an install directory contains a usable entry file.
	 *
	 * @param string $path  Install directory.
	 * @param string $entry Configured entry filename.
	 * @return bool
	 */
	public static function install_path_is_usable( string $path, string $entry ): bool {
		if ( '' === $path || ! is_dir( $path ) ) {
			return false;
		}

		$base = trailingslashit( $path );

		return file_exists( $base . 'mave-components.esm.js' )
			|| ( '' !== $entry && file_exists( $base . $entry ) )
			|| file_exists( $base . 'dist/index.js' );
	}

	/**
	 * Move a version directory from the plugin folder into uploads.
	 *
	 * @param string $source_dir Legacy source directory.
	 * @return string New directory path or empty string on failure.
	 */
	private static function migrate_directory( string $source_dir ): string {
		$source_dir = trailingslashit( wp_normalize_path( $source_dir ) );
		$version    = basename( rtrim( $source_dir, '/' ) );
		$target_dir = self::get_version_path( $version );

		if ( ! wp_mkdir_p( self::get_root() ) ) {
			return '';
		}

		if ( is_dir( $target_dir ) ) {
			self::delete_directory( $target_dir );
		}

		if ( @rename( $source_dir, $target_dir ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			return $target_dir;
		}

		return self::copy_directory( $source_dir, $target_dir ) ? $target_dir : '';
	}

	/**
	 * Recursively copy a directory.
	 *
	 * @param string $source Source directory.
	 * @param string $target Target directory.
	 * @return bool
	 */
	private static function copy_directory( string $source, string $target ): bool {
		if ( ! wp_mkdir_p( $target ) ) {
			return false;
		}

		$entries = scandir( $source );
		if ( false === $entries ) {
			return false;
		}

		foreach ( $entries as $entry ) {
			if ( '.' === $entry || '..' === $entry ) {
				continue;
			}

			$source_path = trailingslashit( $source ) . $entry;
			$target_path = trailingslashit( $target ) . $entry;

			if ( is_dir( $source_path ) ) {
				if ( ! self::copy_directory( $source_path, $target_path ) ) {
					return false;
				}
				continue;
			}

			if ( ! copy( $source_path, $target_path ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Delete the uploads storage root.
	 *
	 * @return void
	 */
	public static function delete_storage_root(): void {
		self::delete_directory( self::get_root() );
	}

	/**
	 * Recursively delete a directory.
	 *
	 * @param string $directory Directory path.
	 * @return void
	 */
	private static function delete_directory( string $directory ): void {
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

			self::delete_directory( trailingslashit( $directory ) . $entry );
		}

		rmdir( $directory );
	}
}

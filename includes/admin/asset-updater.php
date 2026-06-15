<?php
/**
 * Downloads and manages self-hosted @maveio/components assets.
 *
 * @package Webentwicklerin\WeMaveVideo
 */

declare(strict_types=1);

namespace Webentwicklerin\WeMaveVideo\Admin;

use Webentwicklerin\WeMaveVideo\Options;
use Webentwicklerin\WeMaveVideo\Vendor_Storage;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles npm registry checks and local asset storage.
 */
final class Asset_Updater {

	private const REGISTRY_URL    = 'https://registry.npmjs.org/@maveio/components/latest';
	private const CDN_ESM_URL     = 'https://cdn.jsdelivr.net/npm/@maveio/components@%s/+esm';
	public const OFFICIAL_CDN_URL = 'https://cdn.video-dns.com/npm/@maveio/components/+esm';
	private const ENTRY_RELATIVE = 'mave-components.esm.js';
	private const LEGACY_ENTRY   = 'dist/index.js';

	/**
	 * Vendor root in uploads (persists across plugin updates).
	 *
	 * @return string
	 */
	public function get_vendor_root(): string {
		return Vendor_Storage::get_root();
	}

	/**
	 * Fetch latest package metadata from npm.
	 *
	 * @return array<string, mixed>|\WP_Error
	 */
	public function fetch_latest_metadata() {
		$response = wp_remote_get(
			self::REGISTRY_URL,
			array(
				'timeout' => 30,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code ) {
			return new \WP_Error(
				'we_mave_video_registry_error',
				sprintf(
					/* translators: %d: HTTP status code */
					__( 'npm registry returned HTTP %d.', 'we-mave-video' ),
					$code
				)
			);
		}

		$body = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $body ) || empty( $body['version'] ) ) {
			return new \WP_Error(
				'we_mave_video_registry_invalid',
				__( 'npm registry response was invalid.', 'we-mave-video' )
			);
		}

		return $body;
	}

	/**
	 * Check for a newer remote version.
	 *
	 * @return array<string, mixed>|\WP_Error
	 */
	public function check_for_update() {
		$metadata = $this->fetch_latest_metadata();
		if ( is_wp_error( $metadata ) ) {
			return $metadata;
		}

		$asset          = Options::get_asset();
		$remote_version = (string) $metadata['version'];
		$local_version  = (string) ( $asset['version'] ?? '' );

		$asset['last_check_at'] = time();
		$asset['error_message'] = '';
		Options::update_asset( $asset );

		return array(
			'remote_version'   => $remote_version,
			'local_version'    => $local_version,
			'update_available' => '' === $local_version
				|| version_compare( $remote_version, $local_version, '>' )
				|| $this->needs_bundle_migration(),
		);
	}

	/**
	 * Install or update the local browser bundle files.
	 *
	 * @param string $version Target version.
	 * @return true|\WP_Error
	 */
	public function install_version( string $version ) {
		$bundle = $this->fetch_esm_bundle( $version );
		if ( is_wp_error( $bundle ) ) {
			return $bundle;
		}

		$dependencies = $this->extract_dynamic_dependencies( $bundle, $version );
		$bundle       = $this->rewrite_bundle_imports( $bundle, $version );

		$target_dir  = $this->get_vendor_root() . sanitize_file_name( $version ) . '/';
		$target_file = $target_dir . self::ENTRY_RELATIVE;
		$previous    = Options::get_asset();

		if ( file_exists( $target_dir ) ) {
			$this->delete_directory( $target_dir );
		}

		if ( ! wp_mkdir_p( $target_dir ) ) {
			return new \WP_Error(
				'we_mave_video_mkdir_failed',
				__( 'Could not create the local vendor directory.', 'we-mave-video' )
			);
		}

		foreach ( $dependencies as $relative_path ) {
			$downloaded = $this->download_dependency( $version, $relative_path, $target_dir . $relative_path );
			if ( is_wp_error( $downloaded ) ) {
				$this->delete_directory( $target_dir );
				return $downloaded;
			}
		}

		$written = file_put_contents( $target_file, $bundle );
		if ( false === $written ) {
			$this->delete_directory( $target_dir );
			return new \WP_Error(
				'we_mave_video_write_failed',
				__( 'Could not write the player bundle file.', 'we-mave-video' )
			);
		}

		if ( ! $this->bundle_is_valid( $bundle, $dependencies, $target_dir ) ) {
			$this->delete_directory( $target_dir );
			return new \WP_Error(
				'we_mave_video_bundle_invalid',
				__( 'Downloaded player bundle appears incomplete or invalid.', 'we-mave-video' )
			);
		}

		$old_version = (string) ( $previous['version'] ?? '' );
		if ( '' !== $old_version && $old_version !== $version ) {
			$old_dir = $this->get_vendor_root() . sanitize_file_name( $old_version ) . '/';
			if ( file_exists( $old_dir ) ) {
				$this->delete_directory( $old_dir );
			}
		}

		Options::update_asset(
			array(
				'version'       => $version,
				'path'          => $target_dir,
				'entry'         => self::ENTRY_RELATIVE,
				'updated_at'    => time(),
				'last_check_at' => time(),
				'status'        => 'ready',
				'error_message' => '',
			)
		);

		return true;
	}

	/**
	 * Check and optionally install the latest version.
	 *
	 * @param bool $force_install Force install even if versions match.
	 * @return array<string, mixed>|\WP_Error
	 */
	public function maybe_update( bool $force_install = false ) {
		$check = $this->check_for_update();
		if ( is_wp_error( $check ) ) {
			$asset = Options::get_asset();
			$asset['status']        = 'error';
			$asset['error_message'] = $check->get_error_message();
			$asset['last_check_at'] = time();
			Options::update_asset( $asset );
			return $check;
		}

		if ( ! $force_install && ! $check['update_available'] ) {
			$asset = Options::get_asset();
			$asset['status']        = 'ready';
			$asset['error_message'] = '';
			Options::update_asset( $asset );

			return array(
				'updated'          => false,
				'remote_version'   => $check['remote_version'],
				'local_version'    => $check['local_version'],
				'update_available' => false,
			);
		}

		$installed = $this->install_version( $check['remote_version'] );
		if ( is_wp_error( $installed ) ) {
			$asset = Options::get_asset();
			$asset['status']        = 'error';
			$asset['error_message'] = $installed->get_error_message();
			Options::update_asset( $asset );
			return $installed;
		}

		return array(
			'updated'          => true,
			'remote_version'   => $check['remote_version'],
			'local_version'    => $check['local_version'],
			'update_available' => true,
		);
	}

	/**
	 * Whether the front end should load the official CDN bundle.
	 *
	 * @return bool
	 */
	public function is_using_cdn(): bool {
		$settings = Options::get_settings();

		return ! empty( $settings['load_from_cdn'] );
	}

	/**
	 * Get the script URL for the current front-end request.
	 *
	 * @return string
	 */
	public function get_script_url(): string {
		if ( $this->is_using_cdn() ) {
			return self::OFFICIAL_CDN_URL;
		}

		return $this->get_entry_url();
	}

	/**
	 * Get public URL to the installed entry script.
	 *
	 * @return string
	 */
	public function get_entry_url(): string {
		$asset = Options::get_asset();
		$entry = (string) ( $asset['entry'] ?? self::ENTRY_RELATIVE );
		$version = (string) ( $asset['version'] ?? '' );

		if ( '' !== $version ) {
			$entry_file = $this->resolve_entry_file(
				Vendor_Storage::get_version_path( $version ),
				$entry
			);

			if ( '' !== $entry_file ) {
				$url = Vendor_Storage::get_entry_public_url( $version, $entry_file );
				if ( '' !== $url ) {
					return $url;
				}
			}
		}

		$path = (string) ( $asset['path'] ?? '' );

		if ( '' === $path ) {
			return '';
		}

		$entry_file = $this->resolve_entry_file( $path, $entry );
		if ( '' === $entry_file ) {
			return '';
		}

		return Vendor_Storage::path_to_public_url( trailingslashit( $path ) . $entry_file );
	}

	/**
	 * Resolve the usable entry file for an installed version.
	 *
	 * @param string $path  Version directory.
	 * @param string $entry Configured entry path.
	 * @return string
	 */
	private function resolve_entry_file( string $path, string $entry ): string {
		$base = trailingslashit( $path );

		if ( file_exists( $base . self::ENTRY_RELATIVE ) ) {
			return self::ENTRY_RELATIVE;
		}

		if ( '' !== $entry && self::LEGACY_ENTRY !== $entry && file_exists( $base . $entry ) ) {
			return $entry;
		}

		if ( file_exists( $base . self::LEGACY_ENTRY ) ) {
			return self::LEGACY_ENTRY;
		}

		return '';
	}

	/**
	 * Whether a usable local asset is available.
	 *
	 * @return bool
	 */
	public function is_ready(): bool {
		return '' !== $this->get_entry_url();
	}

	/**
	 * Whether the installed files need to be replaced with a complete bundle.
	 *
	 * @return bool
	 */
	private function needs_bundle_migration(): bool {
		$asset = Options::get_asset();
		$path  = (string) ( $asset['path'] ?? '' );

		if ( '' === $path ) {
			return false;
		}

		$base = trailingslashit( $path );

		if ( file_exists( $base . self::LEGACY_ENTRY ) && ! file_exists( $base . self::ENTRY_RELATIVE ) ) {
			return true;
		}

		if ( file_exists( $base . self::ENTRY_RELATIVE ) && ! file_exists( $base . 'dist/themes/default.js' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Extract lazily loaded module paths from the CDN bundle.
	 *
	 * @param string $bundle  Downloaded main bundle.
	 * @param string $version Package version.
	 * @return array<int, string>
	 */
	private function extract_dynamic_dependencies( string $bundle, string $version ): array {
		$pattern = sprintf(
			'#["\']\/npm\/@maveio\/components@%s\/([^"\']+?)\/\+esm["\']#',
			preg_quote( $version, '#' )
		);

		$matches = array();
		preg_match_all( $pattern, $bundle, $matches );

		if ( empty( $matches[1] ) ) {
			return array();
		}

		$paths = array_map(
			static function ( string $path ): string {
				return ltrim( str_replace( '\\', '/', $path ), '/' );
			},
			$matches[1]
		);

		return array_values( array_unique( $paths ) );
	}

	/**
	 * Rewrite CDN import paths to local relative module paths.
	 *
	 * @param string $bundle  Downloaded main bundle.
	 * @param string $version Package version.
	 * @return string
	 */
	private function rewrite_bundle_imports( string $bundle, string $version ): string {
		$pattern = sprintf(
			'#(["\'])\/npm\/@maveio\/components@%s\/([^"\']+?)\/\+esm\1#',
			preg_quote( $version, '#' )
		);

		return (string) preg_replace( $pattern, '$1./$2$1', $bundle );
	}

	/**
	 * Download one lazily loaded module and store it locally.
	 *
	 * @param string $version       Package version.
	 * @param string $relative_path Relative file path inside the package.
	 * @param string $target_path   Absolute local target path.
	 * @return true|\WP_Error
	 */
	private function download_dependency( string $version, string $relative_path, string $target_path ) {
		$response = wp_remote_get(
			sprintf(
				'https://cdn.jsdelivr.net/npm/@maveio/components@%s/%s/+esm',
				rawurlencode( $version ),
				ltrim( str_replace( '\\', '/', $relative_path ), '/' )
			),
			array(
				'timeout' => 120,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code ) {
			return new \WP_Error(
				'we_mave_video_download_failed',
				sprintf(
					/* translators: 1: file path, 2: HTTP status code */
					__( 'Could not download %1$s (HTTP %2$d).', 'we-mave-video' ),
					$relative_path,
					$code
				)
			);
		}

		$content = (string) wp_remote_retrieve_body( $response );
		if ( strlen( $content ) < 100 ) {
			return new \WP_Error(
				'we_mave_video_download_failed',
				sprintf(
					/* translators: %s: file path */
					__( 'Downloaded file %s appears empty.', 'we-mave-video' ),
					$relative_path
				)
			);
		}

		$directory = dirname( $target_path );
		if ( ! wp_mkdir_p( $directory ) ) {
			return new \WP_Error(
				'we_mave_video_mkdir_failed',
				sprintf(
					/* translators: %s: directory path */
					__( 'Could not create directory %s.', 'we-mave-video' ),
					$directory
				)
			);
		}

		$written = file_put_contents( $target_path, $content );
		if ( false === $written ) {
			return new \WP_Error(
				'we_mave_video_write_failed',
				sprintf(
					/* translators: %s: file path */
					__( 'Could not write file %s.', 'we-mave-video' ),
					$relative_path
				)
			);
		}

		return true;
	}

	/**
	 * Fetch the bundled browser ESM file from jsDelivr.
	 *
	 * @param string $version Package version.
	 * @return string|\WP_Error
	 */
	private function fetch_esm_bundle( string $version ) {
		$response = wp_remote_get(
			sprintf( self::CDN_ESM_URL, rawurlencode( $version ) ),
			array(
				'timeout' => 120,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code ) {
			return new \WP_Error(
				'we_mave_video_download_failed',
				sprintf(
					/* translators: %d: HTTP status code */
					__( 'Could not download the player bundle (HTTP %d).', 'we-mave-video' ),
					$code
				)
			);
		}

		return (string) wp_remote_retrieve_body( $response );
	}

	/**
	 * Validate that the downloaded bundle contains the player component.
	 *
	 * @param string $bundle Downloaded JavaScript bundle.
	 * @return bool
	 */
	private function bundle_is_valid( string $bundle, array $dependencies, string $target_dir ): bool {
		if ( strlen( $bundle ) < 100000 || ! str_contains( $bundle, 'mave-player' ) ) {
			return false;
		}

		if ( preg_match( '#["\']\/npm\/@maveio\/components@#', $bundle ) ) {
			return false;
		}

		foreach ( $dependencies as $relative_path ) {
			if ( ! file_exists( trailingslashit( $target_dir ) . $relative_path ) ) {
				return false;
			}
		}

		return ! empty( $dependencies );
	}

	/**
	 * Recursively delete a directory.
	 *
	 * @param string $directory Directory path.
	 * @return void
	 */
	private function delete_directory( string $directory ): void {
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

			$this->delete_directory( trailingslashit( $directory ) . $entry );
		}

		rmdir( $directory );
	}
}

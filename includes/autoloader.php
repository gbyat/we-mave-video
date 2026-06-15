<?php
/**
 * Autoloader for kebab-case class files.
 *
 * @package Webentwicklerin\WeMaveVideo
 */

declare(strict_types=1);

namespace Webentwicklerin\WeMaveVideo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Maps namespaced classes to lowercase kebab-case file paths.
 */
final class Autoloader {

	/**
	 * Register the autoloader.
	 *
	 * @return void
	 */
	public static function register(): void {
		spl_autoload_register( array( self::class, 'load' ) );
	}

	/**
	 * Load a class file.
	 *
	 * @param string $class Fully qualified class name.
	 * @return void
	 */
	public static function load( string $class ): void {
		$prefix = __NAMESPACE__ . '\\';

		if ( 0 !== strpos( $class, $prefix ) ) {
			return;
		}

		$relative  = substr( $class, strlen( $prefix ) );
		$parts     = explode( '\\', $relative );
		$classfile = array_pop( $parts );
		$filename  = strtolower( str_replace( '_', '-', $classfile ) ) . '.php';

		$directories = array_map( 'strtolower', $parts );
		$path        = self::get_includes_path();

		if ( ! empty( $directories ) ) {
			$path .= implode( '/', $directories ) . '/';
		}

		$file = $path . $filename;

		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}

	/**
	 * Resolve the includes directory.
	 *
	 * @return string
	 */
	private static function get_includes_path(): string {
		if ( defined( 'WE_MAVE_VIDEO_PATH' ) ) {
			return WE_MAVE_VIDEO_PATH . 'includes/';
		}

		return trailingslashit( __DIR__ );
	}
}

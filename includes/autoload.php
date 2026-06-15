<?php
/**
 * Composer autoload bootstrap.
 *
 * @package Webentwicklerin\WeMaveVideo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/autoloader.php';

\Webentwicklerin\WeMaveVideo\Autoloader::register();

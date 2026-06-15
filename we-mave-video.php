<?php

/**
 * Plugin Name:       WE Mave Video
 * Plugin URI:        https://github.com/gbyat/we-mave-video
 * Description:       Self-hosted mave.io video player with shortcode, snippet generator, and optional block.
 * Version: 1.1.3
 * Requires at least: 6.5
 * Requires PHP:      8.0
 * Author:            webentwicklerin, Gabriele Laesser
 * Author URI:        https://webentwicklerin.at
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Update URI:        https://github.com/gbyat/we-mave-video
 * Text Domain:       we-mave-video
 * Domain Path:       /languages
 *
 * @package Webentwicklerin\WeMaveVideo
 */

if (! defined('ABSPATH')) {
	exit;
}

define( 'WE_MAVE_VIDEO_VERSION', '1.1.3' );
define('WE_MAVE_VIDEO_FILE', __FILE__);
define('WE_MAVE_VIDEO_PATH', plugin_dir_path(__FILE__));
define('WE_MAVE_VIDEO_URL', plugin_dir_url(__FILE__));
define('WE_MAVE_VIDEO_GITHUB_REPO', 'gbyat/we-mave-video');

$autoload = WE_MAVE_VIDEO_PATH . 'vendor/autoload.php';
if (file_exists($autoload)) {
	require_once $autoload;
} else {
	require_once WE_MAVE_VIDEO_PATH . 'includes/autoload.php';
}

register_activation_hook(__FILE__, array(\Webentwicklerin\WeMaveVideo\Plugin::class, 'activate'));
register_deactivation_hook(__FILE__, array(\Webentwicklerin\WeMaveVideo\Plugin::class, 'deactivate'));

add_action(
	'plugins_loaded',
	static function (): void {
		\Webentwicklerin\WeMaveVideo\Plugin::instance()->init();
	}
);

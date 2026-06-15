<?php
/**
 * Detects player usage in post content.
 *
 * @package Webentwicklerin\WeMaveVideo
 */

declare(strict_types=1);

namespace Webentwicklerin\WeMaveVideo\Frontend;

use Webentwicklerin\WeMaveVideo\Integrations\Borlabs_Cookie;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Scans content for shortcodes or raw mave-player markup.
 */
final class Content_Detector {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'wp', array( $this, 'detect_in_main_query' ) );
	}

	/**
	 * Detect player usage in the main queried post.
	 *
	 * @return void
	 */
	public function detect_in_main_query(): void {
		if ( is_admin() || ! is_singular() ) {
			return;
		}

		$post = get_queried_object();
		if ( ! $post instanceof \WP_Post ) {
			return;
		}

		if ( $this->content_has_player( $post->post_content ) ) {
			$this->mark_script_needed();
			return;
		}

		if ( $this->meta_has_player( (int) $post->ID ) ) {
			$this->mark_script_needed();
		}
	}

	/**
	 * Mark the player script for the current request.
	 *
	 * @return void
	 */
	private function mark_script_needed(): void {
		if ( Borlabs_Cookie::is_enabled() ) {
			Script_Loader::mark_borlabs_deferred();
			return;
		}

		Script_Loader::mark_required();
	}

	/**
	 * Detect player usage in post meta (page builders such as Enfold).
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	private function meta_has_player( int $post_id ): bool {
		$meta = get_post_meta( $post_id );

		if ( ! is_array( $meta ) ) {
			return false;
		}

		foreach ( $meta as $values ) {
			if ( ! is_array( $values ) ) {
				continue;
			}

			foreach ( $values as $value ) {
				if ( ! is_string( $value ) || '' === $value ) {
					continue;
				}

				if ( $this->content_has_player( $value ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Check whether content references a player.
	 *
	 * @param string $content Post content.
	 * @return bool
	 */
	public function content_has_player( string $content ): bool {
		if ( has_shortcode( $content, 'we_mave_player' ) ) {
			return true;
		}

		if ( (bool) preg_match( '/<mave-player\b/i', $content ) ) {
			return true;
		}

		return (bool) preg_match( '/<!--\s+wp:we-mave-video\/player\b/', $content );
	}
}

<?php
/**
 * Gutenberg block for mave player embeds.
 *
 * @package Webentwicklerin\WeMaveVideo
 */

declare(strict_types=1);

namespace Webentwicklerin\WeMaveVideo\Blocks;

use Webentwicklerin\WeMaveVideo\Frontend\Player_Renderer;
use Webentwicklerin\WeMaveVideo\Frontend\Script_Loader;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the we-mave-video/player block.
 */
final class Player_Block {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_block' ) );
	}

	/**
	 * Register block type from block.json.
	 *
	 * @return void
	 */
	public function register_block(): void {
		$block_dir = WE_MAVE_VIDEO_PATH . 'assets/blocks/player/';

		register_block_type(
			$block_dir,
			array(
				'render_callback' => array( $this, 'render' ),
			)
		);
	}

	/**
	 * Render block output on the front end.
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 * @return string
	 */
	public function render( array $attributes ): string {
		Script_Loader::mark_required();

		$mapped = array(
			'embed'        => (string) ( $attributes['embedId'] ?? '' ),
			'aspect_ratio' => (string) ( $attributes['aspectRatio'] ?? '' ),
			'width'        => (string) ( $attributes['width'] ?? '' ),
			'height'       => (string) ( $attributes['height'] ?? '' ),
			'autoplay'     => (string) ( $attributes['autoplay'] ?? '' ),
			'controls'     => (string) ( $attributes['controls'] ?? '' ),
			'color'        => (string) ( $attributes['color'] ?? '' ),
			'opacity'      => (string) ( $attributes['opacity'] ?? '' ),
			'loop'         => ! empty( $attributes['loop'] ) ? '1' : '',
			'poster'       => (string) ( $attributes['poster'] ?? '' ),
			'subtitles'    => (string) ( $attributes['subtitles'] ?? '' ),
			'theme'        => (string) ( $attributes['theme'] ?? '' ),
			'quality'      => (string) ( $attributes['quality'] ?? '' ),
			'audiotracks'  => (string) ( $attributes['audiotracks'] ?? '' ),
		);

		$output = Player_Renderer::render( $mapped );

		if ( '' === $output ) {
			return '';
		}

		return '<div class="wp-block-we-mave-video-player">' . $output . '</div>';
	}
}

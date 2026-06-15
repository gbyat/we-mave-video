<?php
/**
 * Shortcode handler for mave player embeds.
 *
 * @package Webentwicklerin\WeMaveVideo
 */

declare(strict_types=1);

namespace Webentwicklerin\WeMaveVideo\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers and renders [we_mave_player].
 */
final class Shortcode {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_shortcode( 'we_mave_player', array( $this, 'render' ) );
	}

	/**
	 * Render the shortcode output.
	 *
	 * @param array<string, string>|string $atts Shortcode attributes.
	 * @return string
	 */
	public function render( $atts ): string {
		$atts = shortcode_atts(
			array(
				'embed'        => '',
				'aspect_ratio' => '',
				'aspect-ratio' => '',
				'width'        => '',
				'height'       => '',
				'autoplay'     => '',
				'controls'     => '',
				'color'        => '',
				'opacity'      => '',
				'loop'         => '',
				'poster'       => '',
				'subtitles'    => '',
				'theme'        => '',
				'quality'      => '',
				'audiotracks'  => '',
			),
			$atts,
			'we_mave_player'
		);

		if ( '' !== $atts['aspect-ratio'] && '' === $atts['aspect_ratio'] ) {
			$atts['aspect_ratio'] = $atts['aspect-ratio'];
		}

		unset( $atts['aspect-ratio'] );

		return Player_Renderer::render( $atts );
	}
}

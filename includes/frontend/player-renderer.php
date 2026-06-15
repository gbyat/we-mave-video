<?php
/**
 * Renders mave-player markup.
 *
 * @package Webentwicklerin\WeMaveVideo
 */

declare(strict_types=1);

namespace Webentwicklerin\WeMaveVideo\Frontend;

use Webentwicklerin\WeMaveVideo\Player_Attributes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shared renderer for shortcode and block output.
 */
final class Player_Renderer {

	/**
	 * Render a mave-player element.
	 *
	 * @param array<string, mixed> $attributes Raw player attributes.
	 * @return string
	 */
	public static function render( array $attributes ): string {
		$sanitized = Player_Attributes::sanitize( $attributes );

		if ( empty( $sanitized['embed'] ) ) {
			return '';
		}

		Script_Loader::mark_required();

		$html_attrs = Player_Attributes::to_html_attributes( $sanitized );
		$player     = sprintf(
			'<mave-player %s></mave-player>',
			$html_attrs
		);

		return sprintf(
			'<div class="we-mave-video-player">%s</div>',
			wp_kses( $player, self::allowed_html() )
		);
	}

	/**
	 * Allowed HTML for mave-player output.
	 *
	 * @return array<string, array<string, bool>>
	 */
	private static function allowed_html(): array {
		$attrs = array(
			'embed'        => true,
			'aspect-ratio' => true,
			'width'        => true,
			'height'       => true,
			'autoplay'     => true,
			'controls'     => true,
			'color'        => true,
			'opacity'      => true,
			'loop'         => true,
			'poster'       => true,
			'subtitles'    => true,
			'theme'        => true,
			'quality'      => true,
			'audiotracks'  => true,
		);

		return array(
			'mave-player' => $attrs,
		);
	}
}

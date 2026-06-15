<?php
/**
 * Player attribute definitions and sanitization.
 *
 * @package Webentwicklerin\WeMaveVideo
 */

declare(strict_types=1);

namespace Webentwicklerin\WeMaveVideo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Validates and normalizes mave-player attributes.
 */
final class Player_Attributes {

	/**
	 * Attribute map: internal key => HTML attribute name.
	 *
	 * @return array<string, string>
	 */
	public static function attribute_map(): array {
		return array(
			'embed'        => 'embed',
			'aspect_ratio' => 'aspect-ratio',
			'width'        => 'width',
			'height'       => 'height',
			'autoplay'     => 'autoplay',
			'controls'     => 'controls',
			'color'        => 'color',
			'opacity'      => 'opacity',
			'loop'         => 'loop',
			'poster'       => 'poster',
			'subtitles'    => 'subtitles',
			'theme'        => 'theme',
			'quality'      => 'quality',
			'audiotracks'  => 'audiotracks',
		);
	}

	/**
	 * Sanitize embed ID.
	 *
	 * @param string $embed Embed ID.
	 * @return string
	 */
	public static function sanitize_embed( string $embed ): string {
		$embed = sanitize_text_field( $embed );
		$embed = preg_replace( '/[^a-zA-Z0-9_-]/', '', $embed );

		return is_string( $embed ) ? $embed : '';
	}

	/**
	 * Sanitize and merge attributes with global defaults.
	 *
	 * @param array<string, mixed> $attributes Raw attributes.
	 * @return array<string, string>
	 */
	public static function sanitize( array $attributes ): array {
		$settings = Options::get_settings();
		$defaults = $settings['player_defaults'] ?? array();

		if ( ! is_array( $defaults ) ) {
			$defaults = array();
		}

		$merged = array_merge( $defaults, $attributes );
		$result = array();

		$embed = self::sanitize_embed( (string) ( $merged['embed'] ?? '' ) );
		if ( '' !== $embed ) {
			$result['embed'] = $embed;
		}

		$aspect_ratio = sanitize_text_field( (string) ( $merged['aspect_ratio'] ?? '' ) );
		if ( '' !== $aspect_ratio && preg_match( '/^\d+\s*\/\s*\d+$/', $aspect_ratio ) ) {
			$result['aspect_ratio'] = str_replace( ' ', '', $aspect_ratio );
		}

		$width  = absint( $merged['width'] ?? 0 );
		$height = absint( $merged['height'] ?? 0 );
		if ( $width > 0 ) {
			$result['width'] = (string) $width;
		}
		if ( $height > 0 ) {
			$result['height'] = (string) $height;
		}

		$autoplay = sanitize_key( (string) ( $merged['autoplay'] ?? 'false' ) );
		if ( in_array( $autoplay, array( 'false', 'always', 'lazy' ), true ) && 'false' !== $autoplay ) {
			$result['autoplay'] = $autoplay;
		}

		$controls = sanitize_key( (string) ( $merged['controls'] ?? 'full' ) );
		if ( in_array( $controls, array( 'full', 'big', 'none' ), true ) ) {
			$result['controls'] = $controls;
		}

		$color = sanitize_hex_color( (string) ( $merged['color'] ?? '' ) );
		if ( is_string( $color ) && '' !== $color ) {
			$result['color'] = $color;

			$opacity = $merged['opacity'] ?? '';
			if ( '' !== $opacity && is_numeric( $opacity ) ) {
				$opacity_value = (float) $opacity;
				if ( $opacity_value >= 0 && $opacity_value <= 1 ) {
					$result['opacity'] = (string) $opacity_value;
				}
			}
		}

		if ( ! empty( $merged['loop'] ) && filter_var( $merged['loop'], FILTER_VALIDATE_BOOLEAN ) ) {
			$result['loop'] = 'loop';
		}

		$poster = esc_url_raw( (string) ( $merged['poster'] ?? '' ) );
		if ( '' !== $poster ) {
			$result['poster'] = $poster;
		}

		$subtitles = sanitize_text_field( (string) ( $merged['subtitles'] ?? '' ) );
		if ( '' !== $subtitles ) {
			$result['subtitles'] = $subtitles;
		}

		$theme = sanitize_key( (string) ( $merged['theme'] ?? '' ) );
		if ( '' !== $theme ) {
			$result['theme'] = $theme;
		}

		$quality = sanitize_key( (string) ( $merged['quality'] ?? '' ) );
		if ( in_array( $quality, array( 'sd', 'hd', 'fhd', 'qhd', 'uhd' ), true ) ) {
			$result['quality'] = $quality;
		}

		$audiotracks = sanitize_key( (string) ( $merged['audiotracks'] ?? 'auto' ) );
		if ( in_array( $audiotracks, array( 'auto', 'off' ), true ) && 'auto' !== $audiotracks ) {
			$result['audiotracks'] = $audiotracks;
		}

		return $result;
	}

	/**
	 * Build HTML attribute string for mave-player.
	 *
	 * @param array<string, string> $attributes Sanitized attributes.
	 * @return string
	 */
	public static function to_html_attributes( array $attributes ): string {
		$parts = array();

		foreach ( self::attribute_map() as $key => $html_name ) {
			if ( ! isset( $attributes[ $key ] ) || '' === $attributes[ $key ] ) {
				continue;
			}

			$value = $attributes[ $key ];

			if ( 'loop' === $key ) {
				$parts[] = 'loop';
				continue;
			}

			$parts[] = sprintf(
				'%s="%s"',
				esc_attr( $html_name ),
				esc_attr( $value )
			);
		}

		return implode( ' ', $parts );
	}
}

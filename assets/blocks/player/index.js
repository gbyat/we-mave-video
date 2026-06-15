( function ( wp ) {
	'use strict';

	var registerBlockType = wp.blocks.registerBlockType;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var PanelBody = wp.components.PanelBody;
	var TextControl = wp.components.TextControl;
	var SelectControl = wp.components.SelectControl;
	var ToggleControl = wp.components.ToggleControl;
	var Placeholder = wp.components.Placeholder;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var el = wp.element.createElement;
	var __ = wp.i18n.__;

	registerBlockType( 'we-mave-video/player', {
		edit: function ( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var blockProps = useBlockProps( {
				className: 'we-mave-video-player-block',
			} );

			return el(
				'div',
				blockProps,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __( 'Player settings', 'we-mave-video' ), initialOpen: true },
						el( TextControl, {
							label: __( 'Embed ID', 'we-mave-video' ),
							value: attributes.embedId,
							onChange: function ( value ) {
								setAttributes( { embedId: value } );
							},
						} ),
						el( TextControl, {
							label: __( 'Aspect ratio', 'we-mave-video' ),
							help: __( 'Example: 16/9', 'we-mave-video' ),
							value: attributes.aspectRatio,
							onChange: function ( value ) {
								setAttributes( { aspectRatio: value } );
							},
						} ),
						el( TextControl, {
							label: __( 'Width (px)', 'we-mave-video' ),
							value: attributes.width,
							onChange: function ( value ) {
								setAttributes( { width: value } );
							},
						} ),
						el( TextControl, {
							label: __( 'Height (px)', 'we-mave-video' ),
							value: attributes.height,
							onChange: function ( value ) {
								setAttributes( { height: value } );
							},
						} ),
						el( SelectControl, {
							label: __( 'Autoplay', 'we-mave-video' ),
							value: attributes.autoplay || '',
							options: [
								{ label: __( 'Use global default', 'we-mave-video' ), value: '' },
								{ label: __( 'Off', 'we-mave-video' ), value: 'false' },
								{ label: __( 'Always', 'we-mave-video' ), value: 'always' },
								{ label: __( 'When in view', 'we-mave-video' ), value: 'lazy' },
							],
							onChange: function ( value ) {
								setAttributes( { autoplay: value } );
							},
						} ),
						el( SelectControl, {
							label: __( 'Controls', 'we-mave-video' ),
							value: attributes.controls || '',
							options: [
								{ label: __( 'Use global default', 'we-mave-video' ), value: '' },
								{ label: __( 'Full', 'we-mave-video' ), value: 'full' },
								{ label: __( 'Big', 'we-mave-video' ), value: 'big' },
								{ label: __( 'None', 'we-mave-video' ), value: 'none' },
							],
							onChange: function ( value ) {
								setAttributes( { controls: value } );
							},
						} ),
						el( TextControl, {
							label: __( 'Controls color', 'we-mave-video' ),
							value: attributes.color,
							onChange: function ( value ) {
								setAttributes( { color: value } );
							},
						} ),
						attributes.color
							? el( TextControl, {
								label: __( 'Color opacity', 'we-mave-video' ),
								type: 'number',
								min: 0,
								max: 1,
								step: 0.1,
								value: attributes.opacity,
								onChange: function ( value ) {
									setAttributes( { opacity: value } );
								},
							} )
							: null,
						el( ToggleControl, {
							label: __( 'Loop', 'we-mave-video' ),
							checked: !! attributes.loop,
							onChange: function ( value ) {
								setAttributes( { loop: value } );
							},
						} ),
						el( TextControl, {
							label: __( 'Poster URL', 'we-mave-video' ),
							value: attributes.poster,
							onChange: function ( value ) {
								setAttributes( { poster: value } );
							},
						} ),
						el( TextControl, {
							label: __( 'Subtitles', 'we-mave-video' ),
							help: __( 'Example: en, de or none', 'we-mave-video' ),
							value: attributes.subtitles,
							onChange: function ( value ) {
								setAttributes( { subtitles: value } );
							},
						} ),
						el( TextControl, {
							label: __( 'Theme', 'we-mave-video' ),
							value: attributes.theme,
							onChange: function ( value ) {
								setAttributes( { theme: value } );
							},
						} ),
						el( SelectControl, {
							label: __( 'Quality', 'we-mave-video' ),
							value: attributes.quality || '',
							options: [
								{ label: __( 'Use global default', 'we-mave-video' ), value: '' },
								{ label: __( 'SD', 'we-mave-video' ), value: 'sd' },
								{ label: __( 'HD', 'we-mave-video' ), value: 'hd' },
								{ label: __( 'FHD', 'we-mave-video' ), value: 'fhd' },
								{ label: __( 'QHD', 'we-mave-video' ), value: 'qhd' },
								{ label: __( 'UHD', 'we-mave-video' ), value: 'uhd' },
							],
							onChange: function ( value ) {
								setAttributes( { quality: value } );
							},
						} ),
						el( SelectControl, {
							label: __( 'Audio tracks', 'we-mave-video' ),
							value: attributes.audiotracks || '',
							options: [
								{ label: __( 'Use global default', 'we-mave-video' ), value: '' },
								{ label: __( 'Auto', 'we-mave-video' ), value: 'auto' },
								{ label: __( 'Off', 'we-mave-video' ), value: 'off' },
							],
							onChange: function ( value ) {
								setAttributes( { audiotracks: value } );
							},
						} )
					)
				),
				el(
					Placeholder,
					{
						icon: 'video-alt3',
						label: __( 'Mave Player', 'we-mave-video' ),
						instructions: attributes.embedId
							? __( 'Preview: mave-player embed', 'we-mave-video' ) + ' "' + attributes.embedId + '"'
							: __( 'Add an embed ID in the block settings.', 'we-mave-video' ),
					}
				)
			);
		},
		save: function () {
			return null;
		},
	} );
} )( window.wp );

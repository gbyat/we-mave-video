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
						{ title: 'Player settings', initialOpen: true },
						el( TextControl, {
							label: 'Embed ID',
							value: attributes.embedId,
							onChange: function ( value ) {
								setAttributes( { embedId: value } );
							},
						} ),
						el( TextControl, {
							label: 'Aspect ratio',
							help: 'Example: 16/9',
							value: attributes.aspectRatio,
							onChange: function ( value ) {
								setAttributes( { aspectRatio: value } );
							},
						} ),
						el( TextControl, {
							label: 'Width (px)',
							value: attributes.width,
							onChange: function ( value ) {
								setAttributes( { width: value } );
							},
						} ),
						el( TextControl, {
							label: 'Height (px)',
							value: attributes.height,
							onChange: function ( value ) {
								setAttributes( { height: value } );
							},
						} ),
						el( SelectControl, {
							label: 'Autoplay',
							value: attributes.autoplay || '',
							options: [
								{ label: 'Use global default', value: '' },
								{ label: 'Off', value: 'false' },
								{ label: 'Always', value: 'always' },
								{ label: 'When in view', value: 'lazy' },
							],
							onChange: function ( value ) {
								setAttributes( { autoplay: value } );
							},
						} ),
						el( SelectControl, {
							label: 'Controls',
							value: attributes.controls || '',
							options: [
								{ label: 'Use global default', value: '' },
								{ label: 'Full', value: 'full' },
								{ label: 'Big', value: 'big' },
								{ label: 'None', value: 'none' },
							],
							onChange: function ( value ) {
								setAttributes( { controls: value } );
							},
						} ),
						el( TextControl, {
							label: 'Controls color',
							value: attributes.color,
							onChange: function ( value ) {
								setAttributes( { color: value } );
							},
						} ),
						attributes.color
							? el( TextControl, {
								label: 'Color opacity',
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
							label: 'Loop',
							checked: !! attributes.loop,
							onChange: function ( value ) {
								setAttributes( { loop: value } );
							},
						} ),
						el( TextControl, {
							label: 'Poster URL',
							value: attributes.poster,
							onChange: function ( value ) {
								setAttributes( { poster: value } );
							},
						} ),
						el( TextControl, {
							label: 'Subtitles',
							help: 'Example: en, de or none',
							value: attributes.subtitles,
							onChange: function ( value ) {
								setAttributes( { subtitles: value } );
							},
						} ),
						el( TextControl, {
							label: 'Theme',
							value: attributes.theme,
							onChange: function ( value ) {
								setAttributes( { theme: value } );
							},
						} ),
						el( SelectControl, {
							label: 'Quality',
							value: attributes.quality || '',
							options: [
								{ label: 'Use global default', value: '' },
								{ label: 'SD', value: 'sd' },
								{ label: 'HD', value: 'hd' },
								{ label: 'FHD', value: 'fhd' },
								{ label: 'QHD', value: 'qhd' },
								{ label: 'UHD', value: 'uhd' },
							],
							onChange: function ( value ) {
								setAttributes( { quality: value } );
							},
						} ),
						el( SelectControl, {
							label: 'Audio tracks',
							value: attributes.audiotracks || '',
							options: [
								{ label: 'Use global default', value: '' },
								{ label: 'Auto', value: 'auto' },
								{ label: 'Off', value: 'off' },
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
						label: 'Mave Player',
						instructions: attributes.embedId
							? 'Preview: mave-player embed "' + attributes.embedId + '"'
							: 'Add an embed ID in the block settings.',
					}
				)
			);
		},
		save: function () {
			return null;
		},
	} );
} )( window.wp );

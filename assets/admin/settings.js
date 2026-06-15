( function () {
	'use strict';

	function toggleOpacityRow() {
		var colorInput = document.getElementById( 'we-mave-color' );
		var opacityRow = document.getElementById( 'we-mave-opacity-row' );

		if ( ! colorInput || ! opacityRow ) {
			return;
		}

		opacityRow.style.display = colorInput.value.trim() ? '' : 'none';
	}

	function updateSnippets() {
		var embedInput = document.getElementById( 'we-mave-snippet-embed' );
		var shortcodeOutput = document.getElementById( 'we-mave-snippet-shortcode' );
		var markupOutput = document.getElementById( 'we-mave-snippet-markup' );

		if ( ! embedInput || ! shortcodeOutput || ! markupOutput ) {
			return;
		}

		var embedId = embedInput.value.trim().replace( /[^a-zA-Z0-9_-]/g, '' );

		if ( ! embedId ) {
			shortcodeOutput.value = '';
			markupOutput.value = '';
			return;
		}

		shortcodeOutput.value = '[we_mave_player embed="' + embedId + '"]';
		markupOutput.value = '<mave-player embed="' + embedId + '"></mave-player>';
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		var colorInput = document.getElementById( 'we-mave-color' );
		var embedInput = document.getElementById( 'we-mave-snippet-embed' );

		toggleOpacityRow();
		updateSnippets();

		if ( colorInput ) {
			colorInput.addEventListener( 'input', toggleOpacityRow );
			colorInput.addEventListener( 'change', toggleOpacityRow );
		}

		if ( embedInput ) {
			embedInput.addEventListener( 'input', updateSnippets );
			embedInput.addEventListener( 'change', updateSnippets );
		}
	} );
}() );

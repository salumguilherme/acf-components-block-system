/**
 * ACBS brand text colour - TinyMCE 4 plugin.
 *
 * Wraps the selection in <span class="text-{key}"> using TinyMCE's formatter, rather than
 * the inline `style="color:#hex"` its built-in colour button writes. See the module's
 * docblock for why that distinction matters.
 *
 * NOT part of the webpack pipeline. This file is hand-edited in place, like the four ACF
 * admin scripts beside it - a build step would only obscure a file that has to stay
 * readable against TinyMCE 4.9's own source.
 *
 * @version 1.0.0
 * @since   1.0.0
 */
( function ( tinymce ) {
	'use strict';

	tinymce.PluginManager.add( 'acbstextcolour', function ( editor ) {

		var FORMAT  = 'acbs_text_colour';
		var COLUMNS = 4;

		var colours = parseColours( editor.settings.acbs_text_colours );
		var current = null;

		// No palette means no button. The PHP side already refuses to add the button in
		// that case; this is the other half, for an init assembled by something else.
		if ( ! colours.length ) {
			return;
		}

		/**
		 * The palette arrives as a JSON string, because _WP_Editors::_parse_init() passes
		 * a "[...]" value through unquoted and quotes everything else. A future WordPress
		 * that hands us a real array should not break the button, hence both branches.
		 */
		function parseColours( raw ) {

			if ( Array.isArray( raw ) ) {
				return raw;
			}

			if ( typeof raw !== 'string' || raw === '' ) {
				return [];
			}

			try {
				var parsed = JSON.parse( raw );
				return Array.isArray( parsed ) ? parsed : [];
			} catch ( e ) {
				return [];
			}

		}

		editor.on( 'init', function () {
			editor.formatter.register( FORMAT, {
				inline: 'span',
				classes: 'text-%value',
				// Follows the selection into links, so colouring a sentence containing an
				// <a> colours the link text too rather than skipping it.
				links: true
			} );
		} );

		/**
		 * Remove every colour we know about.
		 *
		 * Each value is removed by name, rather than relying on the formatter's
		 * `remove_similar`: applying one colour over another has to leave ONE span, and
		 * being explicit about it is the difference between that and a nest of them.
		 * Removing a format that is not applied is a no-op, so the loop is cheap.
		 */
		function clearColour() {

			for ( var i = 0; i < colours.length; i++ ) {
				editor.formatter.remove( FORMAT, { value: colours[ i ].key }, null, true );
			}

		}

		function applyColour( key ) {

			editor.undoManager.transact( function () {
				editor.focus();
				clearColour();

				if ( key ) {
					editor.formatter.apply( FORMAT, { value: key } );
				}

				editor.nodeChanged();
			} );

		}

		/**
		 * The swatch grid.
		 *
		 * Structure and class names are copied from TinyMCE's own textcolor plugin so the
		 * panel inherits the skin's grid styling instead of needing a parallel set of
		 * rules. The one change is the data attribute: `data-acbs-colour` carries the
		 * CLASS KEY, where TinyMCE's carries a hex.
		 */
		function renderGrid() {

			var html = '<table class="mce-grid mce-grid-border mce-colorbutton-grid acbs-colour-grid" role="list" cellspacing="0"><tbody>';
			var i, colour;

			for ( i = 0; i < colours.length; i++ ) {

				if ( i % COLUMNS === 0 ) {
					html += '<tr>';
				}

				colour = colours[ i ];

				html += '<td class="mce-grid-cell">' +
					'<div role="option" tabindex="-1"' +
					' data-acbs-colour="' + escapeAttr( colour.key ) + '"' +
					' data-acbs-hex="' + escapeAttr( colour.hex ) + '"' +
					' style="background-color:' + escapeAttr( colour.hex ) + '"' +
					' title="' + escapeAttr( colour.label ) + '"></div>' +
					'</td>';

				if ( i % COLUMNS === COLUMNS - 1 ) {
					html += '</tr>';
				}

			}

			// Pad the last row so the grid stays rectangular when the palette is not a
			// multiple of COLUMNS - a theme returning five colours should not get a
			// ragged panel.
			var remainder = colours.length % COLUMNS;

			if ( remainder !== 0 ) {

				for ( i = remainder; i < COLUMNS; i++ ) {
					html += '<td></td>';
				}

				html += '</tr>';

			}

			html += '</tbody></table>' +
				'<div class="acbs-colour-clear">' +
				'<button type="button" data-acbs-colour="" tabindex="-1">' +
				editor.translate( 'No colour' ) +
				'</button></div>';

			return html;

		}

		function escapeAttr( value ) {
			return String( value === undefined || value === null ? '' : value )
				.replace( /&/g, '&amp;' )
				.replace( /"/g, '&quot;' )
				.replace( /</g, '&lt;' )
				.replace( />/g, '&gt;' );
		}

		editor.addButton( 'acbstextcolour', {
			type: 'colorbutton',
			tooltip: 'Brand text colour',
			icon: 'acbs-text-colour',

			panel: {
				role: 'application',
				ariaRemember: true,
				html: renderGrid,
				onclick: function ( e ) {

					// The swatch is a <div>, the clear control a <button>; either can be
					// clicked on a descendant, so walk up to whichever carries the attribute.
					var target = e.target;

					while ( target && target !== this.getEl() && ! target.hasAttribute( 'data-acbs-colour' ) ) {
						target = target.parentNode;
					}

					if ( ! target || ! target.hasAttribute || ! target.hasAttribute( 'data-acbs-colour' ) ) {
						return;
					}

					var key = target.getAttribute( 'data-acbs-colour' );
					var hex = target.getAttribute( 'data-acbs-hex' );

					applyColour( key );

					current = key || null;

					// ColorButton keeps a preview swatch under its icon. hide() first, then
					// paint: the panel's parent is the button, and reading it before the
					// panel closes is the documented order in TinyMCE's own plugin.
					this.hide();

					var button = this.parent();

					if ( button ) {
						if ( key ) {
							button.color( hex );
						} else {
							button.resetColor();
						}
					}

				}
			},

			// The main half of the split button re-applies the last colour chosen, the way
			// WordPress's own colour button does. With nothing chosen yet there is nothing
			// to re-apply, so it opens the panel instead of silently doing nothing.
			onclick: function () {

				if ( current ) {
					applyColour( current );
					return;
				}

				this.showPanel();

			}
		} );

	} );

}( window.tinymce ) );

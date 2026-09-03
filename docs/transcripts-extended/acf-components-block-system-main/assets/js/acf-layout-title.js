/**
 * Inline layout rename for page_sections.
 *
 * HAND-WRITTEN, not part of the webpack pipeline - assets/js/*.js is where the ACF admin
 * helpers live and the build leaves them alone.
 *
 * ACF Pro already renames layouts: a hidden `acf_fc_layout_custom_label` input per row,
 * saved with the post rather than over ajax, reachable from the "More layout actions"
 * menu. This file adds a faster way in - an edit button on the row handle - and delegates
 * the actual rename to ACF's own `renameLayout()`, so there is one implementation and one
 * place the value is stored. Nothing here writes to the database or to a field of its own.
 *
 * THREE THINGS HERE ARE NOT ARBITRARY:
 *
 * 1. The button is a SIBLING of `.acf-fc-layout-title`, never a child. ACF refreshes the
 *    title over ajax whenever a row is collapsed, and it does so with
 *    `$title.html( response )` - anything inside that element is destroyed. Sitting
 *    beside it, the button survives.
 *
 * 2. The click listener is bound on `document` in the CAPTURE phase. `.acf-fc-layout-handle`
 *    carries `data-name="collapse-layout"`, and ACF listens for that with a delegated
 *    handler on the field element - which is BELOW document in the tree, so a bubble-phase
 *    listener here would run after ACF had already collapsed the row. Capturing lets
 *    stopPropagation() run before the event reaches ACF at all.
 *
 * 3. Nothing is bound per row, so rows added from the cloner need no wiring. The button
 *    is injected into the clone template as well as the live rows, so a new row arrives
 *    with one already in place.
 *
 * @version 1.0.0
 * @since   1.0.0
 */
( function ( window, document ) {
	'use strict';

	var cfg = window.acbsLayoutTitle || {};

	if ( ! cfg.fieldKey ) {
		return;
	}

	var FIELD = '.acf-field-flexible-content[data-key="' + cfg.fieldKey + '"]';
	var BUTTON = 'acbs-layout-rename';
	var INPUT = 'acbs-layout-rename-input';
	var EDITING = 'acbs-is-renaming';

	function fieldOf( el ) {
		var field = el.closest( '.acf-field-flexible-content' );

		// Scoped by key rather than by "is a flexible content field": a site is free to
		// register its own, and this feature is page_sections' alone.
		return field && field.getAttribute( 'data-key' ) === cfg.fieldKey ? field : null;
	}

	/**
	 * Adds the button to one layout, once.
	 */
	function inject( layout ) {
		if ( layout.querySelector( ':scope > .acf-fc-layout-actions-wrap .' + BUTTON ) ) {
			return;
		}

		var handle = layout.querySelector( '.acf-fc-layout-handle' );
		var title = layout.querySelector( '.acf-fc-layout-title' );

		if ( ! handle || ! title ) {
			return;
		}

		var button = document.createElement( 'button' );

		button.type = 'button';
		button.className = BUTTON;
		button.setAttribute( 'aria-label', cfg.i18n && cfg.i18n.rename ? cfg.i18n.rename : 'Rename layout' );
		button.title = button.getAttribute( 'aria-label' );

		// After the original-title rather than immediately after the title: when a row
		// carries a manual rename ACF shows the computed one in brackets right there, and
		// a button wedged between the two reads as a break in one label.
		var after = layout.querySelector( '.acf-fc-layout-original-title' ) || title;

		after.parentNode.insertBefore( button, after.nextSibling );
	}

	function injectAll( root ) {
		var scope = root && root.querySelectorAll ? root : document;
		var fields = scope.querySelectorAll ? scope.querySelectorAll( FIELD ) : [];

		// `root` may itself be, or sit inside, the field - a newly appended row does.
		if ( root && root.closest && fieldOf( root ) ) {
			Array.prototype.forEach.call( root.querySelectorAll( '.layout' ), inject );

			if ( root.classList && root.classList.contains( 'layout' ) ) {
				inject( root );
			}
		}

		Array.prototype.forEach.call( fields, function ( field ) {
			// Marks the field for the stylesheet. Everything this feature draws is scoped
			// to this class rather than to the key, so the CSS stays a static file.
			field.classList.add( 'acbs-fc' );

			// The clone template is included on purpose: ACF builds a new row by copying
			// that node, so a button placed here arrives with every row the editor adds.
			Array.prototype.forEach.call( field.querySelectorAll( '.layout' ), inject );
		} );
	}

	/**
	 * The rename itself, handed to ACF.
	 */
	function applyRename( layout, value ) {
		var field = fieldOf( layout );
		var instance = field && window.acf && window.acf.getField ? window.acf.getField( window.jQuery( field ) ) : null;

		if ( instance && 'function' === typeof instance.renameLayout ) {
			instance.renameLayout( window.jQuery( layout ), value );
			return;
		}

		// Fallback for an ACF whose model no longer exposes renameLayout. Mirrors what
		// that method does, including the change event - without it the post can be left
		// without an unsaved-changes prompt and the rename is lost on navigate away.
		var hidden = layout.querySelector( '.acf-fc-layout-custom-label' );
		var title = layout.querySelector( '.acf-fc-layout-title' );

		if ( hidden ) {
			hidden.value = value;
			hidden.dispatchEvent( new Event( 'change', { bubbles: true } ) );
		}

		if ( title ) {
			title.textContent = value;
		}

		layout.setAttribute( 'data-renamed', value.length ? '1' : '0' );
	}

	function stopEditing( layout, commit ) {
		var input = layout.querySelector( '.' + INPUT );

		if ( ! input ) {
			return;
		}

		var value = input.value.trim();
		var previous = input.getAttribute( 'placeholder' ) || '';

		input.parentNode.removeChild( input );
		layout.classList.remove( EDITING );

		if ( ! commit ) {
			return;
		}

		// Empty is "I changed my mind", not "clear the name" - clearing is what ACF's own
		// rename dialog is for. And a value equal to what is already displayed is skipped
		// rather than written: storing it would pin the row to a custom label that happens
		// to match today's computed title, so the title would stop following the heading
		// the moment an editor changed it.
		if ( '' === value || value === previous ) {
			return;
		}

		applyRename( layout, value );
	}

	function startEditing( layout ) {
		if ( layout.classList.contains( EDITING ) ) {
			return;
		}

		var title = layout.querySelector( '.acf-fc-layout-title' );

		if ( ! title ) {
			return;
		}

		var input = document.createElement( 'input' );

		input.type = 'text';
		input.className = INPUT;

		// The current name is the PLACEHOLDER, not the value: the field opens empty so a
		// rename is typed rather than edited, and leaving it empty is a no-op.
		input.setAttribute( 'placeholder', title.textContent.trim() );

		layout.classList.add( EDITING );
		title.parentNode.insertBefore( input, title );

		input.focus();

		input.addEventListener( 'blur', function () {
			stopEditing( layout, true );
		} );

		input.addEventListener( 'keydown', function ( e ) {
			if ( 'Escape' === e.key ) {
				e.preventDefault();
				stopEditing( layout, false );
				return;
			}

			if ( 'Enter' === e.key ) {
				e.preventDefault();
				// Committing through blur keeps one path rather than two.
				input.blur();
			}
		} );

		// The handle is a drag target and a collapse toggle; neither should fire from a
		// click inside the input.
		input.addEventListener( 'click', function ( e ) {
			e.stopPropagation();
		} );

		input.addEventListener( 'mousedown', function ( e ) {
			e.stopPropagation();
		} );
	}

	// Capture phase - see note 2 at the top of this file.
	document.addEventListener(
		'click',
		function ( e ) {
			var button = e.target && e.target.closest ? e.target.closest( '.' + BUTTON ) : null;

			if ( ! button || ! fieldOf( button ) ) {
				return;
			}

			e.preventDefault();
			e.stopPropagation();

			var layout = button.closest( '.layout' );

			if ( layout ) {
				startEditing( layout );
			}
		},
		true
	);

	// The handle initiates a drag on mousedown, which would otherwise start the moment
	// the button is pressed and swallow the click entirely.
	document.addEventListener(
		'mousedown',
		function ( e ) {
			if ( e.target && e.target.closest && e.target.closest( '.' + BUTTON ) ) {
				e.stopPropagation();
			}
		},
		true
	);

	if ( window.acf && window.acf.addAction ) {
		window.acf.addAction( 'ready', function () {
			injectAll( document );
		} );

		// Rows added by the cloner, and fields revealed later by conditional logic.
		window.acf.addAction( 'append', function ( $el ) {
			injectAll( $el && $el[ 0 ] ? $el[ 0 ] : document );
		} );
	} else if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', function () {
			injectAll( document );
		} );
	} else {
		injectAll( document );
	}
} )( window, document );

/**
 * Row: Columned Content
 *
 * Only does anything for columns with `column_accordion` ticked; a row of plain columns
 * loads this, finds nothing to wire, and costs one querySelectorAll.
 *
 * SELF-CONTAINED, and deliberately so. This briefly imported a shared module with the
 * accordions row, which is tidier by one measure and wrong by a more important one: the
 * two are different components that happen to animate the same way today. A column
 * toggle is independent of its neighbours, an accordions row is a group with one open at
 * a time, and their markup, classes and design all differ. Sharing the animation coupled
 * a bug fix or a timing change in one row to the behaviour of the other, on a page where
 * both usually appear. The duplication here is a few dozen lines of well-understood code;
 * the coupling was an invisible dependency between two rows an editor thinks of as
 * unrelated.
 *
 * The initial state is rendered by PHP from `column_accordion_initial_status`, not set
 * here: a panel that starts open and is closed by a script flashes, and one that starts
 * closed and is opened by a script arrives late.
 *
 * @version 1.1.0
 * @since   1.0.0
 */
( function ( window, document ) {
	'use strict';

	var acbs = window.acbs;

	if ( ! acbs || ! acbs.onRowReady ) {
		return;
	}

	function prefersReduced() {
		return !! ( window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches );
	}

	function isOpen( trigger ) {
		return 'true' === trigger.getAttribute( 'aria-expanded' );
	}

	/**
	 * The panel a trigger controls.
	 *
	 * getElementById rather than querySelector('#' + id): an id can legally contain
	 * characters that are not valid in a CSS identifier, and querySelector would throw.
	 */
	function panelOf( trigger ) {
		var id = trigger.getAttribute( 'aria-controls' );

		return id ? document.getElementById( id ) : null;
	}

	function duration( panel ) {
		var value = ( window.getComputedStyle( panel ).transitionDuration || '0s' ).split( ',' )[ 0 ].trim();
		var ms = parseFloat( value ) || 0;

		return /ms$/.test( value ) ? ms : ms * 1000;
	}

	/**
	 * The state a panel must end in, whether or not the animation actually happened.
	 * Idempotent, because two things race to call it.
	 */
	function finish( trigger, panel ) {
		if ( panel.acbsTimer ) {
			window.clearTimeout( panel.acbsTimer );
			panel.acbsTimer = null;
		}

		if ( isOpen( trigger ) ) {
			// Back to auto, so the panel grows with its content or a resize instead of
			// staying pinned to the height it had when it opened.
			panel.style.height = '';
		} else {
			panel.hidden = true;
			panel.style.height = '';
		}
	}

	/**
	 * Guarantees finish() runs. transitionend is not a promise that something ends - it
	 * does not fire when the height did not really change, when the tab is hidden and the
	 * compositor never advances, or when anything has zeroed the duration. The CLOSED case
	 * is the one that bites: without finish(), `hidden` is never set, so a collapsed panel
	 * keeps its links in the tab order at zero height.
	 */
	function settle( trigger, panel ) {
		if ( panel.acbsTimer ) {
			window.clearTimeout( panel.acbsTimer );
		}

		panel.acbsTimer = window.setTimeout( function () {
			finish( trigger, panel );
		}, duration( panel ) + 80 );
	}

	function open( trigger, panel, animate ) {
		trigger.setAttribute( 'aria-expanded', 'true' );
		panel.hidden = false;

		if ( ! animate ) {
			panel.style.height = '';
			return;
		}

		panel.style.height = '0px';

		// Forces the 0px to be committed before the target height is set; without the read
		// both writes land in the same frame and there is nothing to transition from.
		void panel.offsetHeight;

		panel.style.height = panel.scrollHeight + 'px';
		settle( trigger, panel );
	}

	function close( trigger, panel, animate ) {
		trigger.setAttribute( 'aria-expanded', 'false' );

		if ( ! animate ) {
			panel.hidden = true;
			panel.style.height = '';
			return;
		}

		panel.style.height = panel.scrollHeight + 'px';
		void panel.offsetHeight;
		panel.style.height = '0px';
		settle( trigger, panel );
	}

	/**
	 * EACH COLUMN IS ITS OWN TOGGLE. There is no group behaviour and that is the field's
	 * intent rather than an omission: two columns may be open at once, and opening one
	 * must not touch its neighbour.
	 */
	function initColumn( trigger ) {
		if ( trigger.dataset.acbsColumnAccordion ) {
			return;
		}

		var panel = panelOf( trigger );

		if ( ! panel ) {
			return;
		}

		trigger.dataset.acbsColumnAccordion = '1';

		panel.addEventListener( 'transitionend', function ( e ) {
			if ( e.target === panel && 'height' === e.propertyName ) {
				finish( trigger, panel );
			}
		} );

		trigger.addEventListener( 'click', function () {
			var animate = ! prefersReduced();
			var willOpen = ! isOpen( trigger );

			if ( willOpen ) {
				open( trigger, panel, animate );
			} else {
				close( trigger, panel, animate );
			}

			acbs.doAction( 'columned_content/toggle', trigger, panel, willOpen );
		} );
	}

	acbs.onRowReady( 'columned_content', function ( row ) {
		var triggers = row.querySelectorAll( '.fl-column-accordion-trigger' );

		for ( var i = 0; i < triggers.length; i++ ) {
			initColumn( triggers[ i ] );
		}

		acbs.doAction( 'columned_content/init', row );
	} );
} )( window, document );

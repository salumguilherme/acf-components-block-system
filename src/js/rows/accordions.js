/**
 * Row: Accordions
 *
 * Enqueued as `acbs-row-accordions` only on a page that rendered an accordions row, and it
 * depends on `acbs-rows`, so window.acbs is guaranteed by the time this runs.
 *
 * Subscribes through acbs.onRowReady() rather than listening for the event directly: this
 * script and the runtime are both footer scripts, and while the dependency graph does
 * order them, onRowReady() replays rows announced before it subscribed. That makes this
 * correct whether it loads before or after the announcement instead of relying on the
 * order holding - see src/js/rows.js for why the runtime keeps that list at all.
 *
 * SELF-CONTAINED. This briefly shared its animation with columned_content's per-column
 * toggle. They are not the same component - this one is a GROUP, where opening an item
 * closes its siblings, and that one is a set of independent columns - so the sharing
 * coupled two unrelated rows that usually appear on the same page. The duplicated code is
 * a few dozen well-understood lines; the coupling was a dependency nobody would look for.
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
	 * getElementById rather than querySelector('#' + id): an id built from an editor-set
	 * section id can legally contain characters that are not valid in a CSS identifier,
	 * and querySelector would throw on them.
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

	/** The state a panel must end in, whether or not the animation happened. Idempotent. */
	function finish( trigger, panel ) {
		if ( panel.acbsTimer ) {
			window.clearTimeout( panel.acbsTimer );
			panel.acbsTimer = null;
		}

		if ( isOpen( trigger ) ) {
			panel.style.height = '';
		} else {
			panel.hidden = true;
			panel.style.height = '';
		}
	}

	/**
	 * Guarantees finish() runs. transitionend is not a promise that something ends - it
	 * does not fire when the height did not really change (an answer that is empty
	 * animates 0px to 0px), when the tab is hidden and the compositor never advances, or
	 * when anything has zeroed the duration. The CLOSED case is the one that bites:
	 * without finish(), `hidden` is never set, so a collapsed panel keeps its links in the
	 * tab order at zero height and a keyboard user tabs into content they cannot see.
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

		// Forces the 0px to be committed before the target height is set.
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

		// From `auto` there is nothing to transition from, so the current height is pinned
		// first and released on the next frame.
		panel.style.height = panel.scrollHeight + 'px';
		void panel.offsetHeight;
		panel.style.height = '0px';
		settle( trigger, panel );
	}

	function initGroup( group ) {
		if ( group.dataset.acbsAccordion ) {
			return;
		}

		group.dataset.acbsAccordion = '1';

		var triggers = group.querySelectorAll( '.fl-accordion-trigger' );

		// Opt out of single-open with data-multiple on the container. Single-open is the
		// default because that is what the Bootstrap version did through data-bs-parent,
		// so an existing page keeps behaving the way its editor expects.
		var single = ! group.hasAttribute( 'data-multiple' );

		Array.prototype.forEach.call( triggers, function ( trigger ) {
			var panel = panelOf( trigger );

			if ( ! panel ) {
				return;
			}

			panel.addEventListener( 'transitionend', function ( e ) {
				if ( e.target === panel && 'height' === e.propertyName ) {
					finish( trigger, panel );
				}
			} );

			trigger.addEventListener( 'click', function () {
				var animate = ! prefersReduced();
				var willOpen = ! isOpen( trigger );

				if ( willOpen && single ) {
					Array.prototype.forEach.call( triggers, function ( other ) {
						if ( other === trigger || ! isOpen( other ) ) {
							return;
						}

						var otherPanel = panelOf( other );

						if ( otherPanel ) {
							close( other, otherPanel, animate );
						}
					} );
				}

				if ( willOpen ) {
					open( trigger, panel, animate );
				} else {
					close( trigger, panel, animate );
				}

				acbs.doAction( 'accordions/toggle', trigger, panel, willOpen );
			} );
		} );

		acbs.doAction( 'accordions/init', group );
	}

	acbs.onRowReady( 'accordions', function ( row ) {
		var groups = row.querySelectorAll( '.fl-accordions' );

		for ( var i = 0; i < groups.length; i++ ) {
			initGroup( groups[ i ] );
		}
	} );
} )( window, document );

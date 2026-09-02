/**
 * Row: Accordions
 *
 * Enqueued as `acbs-row-accordions` only on a page that rendered an accordions row, and
 * it depends on `acbs-rows`, so window.acbs is guaranteed by the time this runs.
 *
 * Subscribes through acbs.onRowReady() rather than listening for the event directly:
 * this script and the runtime are both footer scripts, and while the dependency graph
 * does order them, onRowReady() replays rows announced before it subscribed. That makes
 * this correct whether it loads before or after the announcement instead of relying on
 * the order holding - see src/js/rows.js for why the runtime keeps that list at all.
 *
 * Panels animate on height rather than on `max-height`. A max-height transition has to
 * guess a ceiling: too low silently clips a long answer, too high spends most of the
 * transition's duration animating through empty space, so the panel appears to pause and
 * then snap. Measuring scrollHeight costs one forced layout per toggle and is exact.
 * Height is handed back to `auto` on transitionend so an open panel reflows with the
 * window rather than staying pinned to the pixel height it had when it opened.
 *
 * @version 1.0.0
 * @since   1.0.0
 */
( function ( window, document ) {
	'use strict';

	var acbs = window.acbs;

	if ( ! acbs || ! acbs.onRowReady ) {
		return;
	}

	var REDUCED = '(prefers-reduced-motion: reduce)';

	function prefersReduced() {
		return !! ( window.matchMedia && window.matchMedia( REDUCED ).matches );
	}

	function panelOf( trigger ) {
		var id = trigger.getAttribute( 'aria-controls' );

		// getElementById rather than querySelector('#'+id): an id generated from an
		// editor-set section id can legally contain characters that are not valid in a
		// CSS identifier, and querySelector would throw on them.
		return id ? document.getElementById( id ) : null;
	}

	function open( trigger, panel, animate ) {
		trigger.setAttribute( 'aria-expanded', 'true' );
		panel.hidden = false;

		if ( ! animate ) {
			panel.style.height = '';
			return;
		}

		panel.style.height = '0px';

		// Forces the 0px to be committed before the target height is set. Without the
		// read, both writes land in the same frame and the browser transitions from
		// nothing - the panel simply appears.
		void panel.offsetHeight;

		panel.style.height = panel.scrollHeight + 'px';
	}

	function close( trigger, panel, animate ) {
		trigger.setAttribute( 'aria-expanded', 'false' );

		if ( ! animate ) {
			panel.hidden = true;
			panel.style.height = '';
			return;
		}

		// From `auto` there is nothing to transition from, so the current height is
		// pinned first, then released on the next frame.
		panel.style.height = panel.scrollHeight + 'px';
		void panel.offsetHeight;
		panel.style.height = '0px';
	}

	/**
	 * The state a panel must end in once its animation is over, whether or not the
	 * animation actually happened. Idempotent, because two things race to call it.
	 *
	 * The trigger is passed in rather than found by walking back up the DOM: the panel's
	 * relationship to its trigger is expressed by aria-controls, not by being someone's
	 * next sibling, and a theme is free to override the template and nest the two
	 * differently.
	 */
	function finish( trigger, panel ) {
		if ( panel.acbsTimer ) {
			window.clearTimeout( panel.acbsTimer );
			panel.acbsTimer = null;
		}

		if ( 'true' === trigger.getAttribute( 'aria-expanded' ) ) {
			// Back to auto so the panel can grow with its content or a resize, instead of
			// staying pinned to the pixel height it had when it opened.
			panel.style.height = '';
		} else {
			panel.hidden = true;
			panel.style.height = '';
		}
	}

	/**
	 * Milliseconds in the panel's height transition, read from the stylesheet so the
	 * duration is not written down twice.
	 */
	function duration( panel ) {
		var value = window.getComputedStyle( panel ).transitionDuration || '0s';

		// Only the first duration matters: `height` is the only property in transition.
		value = value.split( ',' )[ 0 ].trim();

		var ms = parseFloat( value ) || 0;

		return /ms$/.test( value ) ? ms : ms * 1000;
	}

	/**
	 * Arms the fallback that guarantees finish() runs.
	 *
	 * WHY THIS IS NOT PARANOIA. transitionend is not a promise that something ends; it is
	 * an event fired when a transition actually ran. It does not fire when the height did
	 * not really change (an accordion whose answer is empty animates 0px to 0px), when the
	 * tab is hidden and the compositor never advances the transition, or when anything on
	 * the page has zeroed the duration. In every one of those cases the panel is left
	 * mid-flight, and the CLOSED case is the one that matters: without finish(), `hidden`
	 * is never set, so a collapsed panel keeps its links in the tab order at zero height
	 * and a keyboard user tabs into content they cannot see.
	 *
	 * Found because the verification browser ran with the pane hidden, which is exactly
	 * the throttled-compositor case.
	 */
	function settle( trigger, panel ) {
		if ( panel.acbsTimer ) {
			window.clearTimeout( panel.acbsTimer );
		}

		// A small margin over the declared duration, so the timer only ever wins when
		// transitionend is genuinely not coming.
		panel.acbsTimer = window.setTimeout( function () {
			finish( trigger, panel );
		}, duration( panel ) + 80 );
	}

	function watch( trigger, panel ) {
		panel.addEventListener( 'transitionend', function ( e ) {
			if ( e.target !== panel || 'height' !== e.propertyName ) {
				return;
			}

			finish( trigger, panel );
		} );
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

			watch( trigger, panel );

			trigger.addEventListener( 'click', function () {
				var animate = ! prefersReduced();
				var isOpen = 'true' === trigger.getAttribute( 'aria-expanded' );

				if ( isOpen ) {
					close( trigger, panel, animate );
				} else {
					if ( single ) {
						Array.prototype.forEach.call( triggers, function ( other ) {
							if ( other === trigger || 'true' !== other.getAttribute( 'aria-expanded' ) ) {
								return;
							}

							var otherPanel = panelOf( other );

							if ( otherPanel ) {
								close( other, otherPanel, animate );

								if ( animate ) {
									settle( other, otherPanel );
								}
							}
						} );
					}

					open( trigger, panel, animate );
				}

				if ( animate ) {
					settle( trigger, panel );
				}

				acbs.doAction( 'accordions/toggle', trigger, panel, ! isOpen );
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

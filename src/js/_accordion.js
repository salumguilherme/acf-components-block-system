/**
 * Shared disclosure behaviour.
 *
 * Underscore-prefixed and outside src/js/rows/, so the build's glob does not turn it into
 * an entry of its own: it is only ever pulled into a row script by import, and webpack
 * inlines it there.
 *
 * It exists because two rows now need the same panel animation - `accordions`, where the
 * items are a group with one open at a time, and `columned_content`, where each column is
 * an independent toggle. The GROUP behaviour differs and stays with each row; what is
 * shared is the part that took two attempts to get right, and which should not exist
 * twice: measuring the panel, animating it, and guaranteeing it reaches a correct end
 * state.
 *
 * @version 1.0.0
 * @since   1.0.0
 */

/**
 * The panel a trigger controls, resolved through aria-controls.
 *
 * getElementById rather than querySelector('#' + id): an id built from an editor-set
 * value can legally contain characters that are not valid in a CSS identifier, and
 * querySelector would throw on them.
 */
export function panelOf( trigger ) {
	var id = trigger.getAttribute( 'aria-controls' );

	return id ? document.getElementById( id ) : null;
}

export function prefersReduced() {
	return !! ( window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches );
}

export function isOpen( trigger ) {
	return 'true' === trigger.getAttribute( 'aria-expanded' );
}

/** Milliseconds in the panel's height transition, read from the stylesheet. */
function duration( panel ) {
	var value = ( window.getComputedStyle( panel ).transitionDuration || '0s' ).split( ',' )[ 0 ].trim();
	var ms = parseFloat( value ) || 0;

	return /ms$/.test( value ) ? ms : ms * 1000;
}

/**
 * The state a panel must end in once its animation is over, whether or not the animation
 * actually happened. Idempotent, because two things race to call it.
 */
export function finish( trigger, panel ) {
	if ( panel.acbsTimer ) {
		window.clearTimeout( panel.acbsTimer );
		panel.acbsTimer = null;
	}

	if ( isOpen( trigger ) ) {
		// Back to auto so the panel can grow with its content or a resize, rather than
		// staying pinned to the pixel height it had when it opened.
		panel.style.height = '';
	} else {
		panel.hidden = true;
		panel.style.height = '';
	}
}

/**
 * Arms the fallback that guarantees finish() runs.
 *
 * NOT PARANOIA. transitionend is not a promise that something ends; it is an event fired
 * when a transition actually ran. It does not fire when the height did not really change
 * (a panel whose content is empty animates 0px to 0px), when the tab is hidden and the
 * compositor never advances, or when anything has zeroed the duration. In each of those
 * the panel is left mid-flight, and the CLOSED case is the one that bites: without
 * finish(), `hidden` is never set, so a collapsed panel keeps its links in the tab order
 * at zero height and a keyboard user tabs into content they cannot see.
 */
function settle( trigger, panel ) {
	if ( panel.acbsTimer ) {
		window.clearTimeout( panel.acbsTimer );
	}

	panel.acbsTimer = window.setTimeout( function () {
		finish( trigger, panel );
	}, duration( panel ) + 80 );
}

export function open( trigger, panel, animate ) {
	trigger.setAttribute( 'aria-expanded', 'true' );
	panel.hidden = false;

	if ( ! animate ) {
		panel.style.height = '';
		return;
	}

	panel.style.height = '0px';

	// Forces the 0px to be committed before the target height is set. Without the read,
	// both writes land in the same frame and the browser transitions from nothing.
	void panel.offsetHeight;

	panel.style.height = panel.scrollHeight + 'px';
	settle( trigger, panel );
}

export function close( trigger, panel, animate ) {
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

/**
 * Wires one trigger/panel pair. `onToggle( trigger, panel, willOpen, animate )` runs
 * BEFORE the pair itself moves, which is where a group implements "close the others".
 */
export function bind( trigger, panel, onToggle ) {
	panel.addEventListener( 'transitionend', function ( e ) {
		if ( e.target === panel && 'height' === e.propertyName ) {
			finish( trigger, panel );
		}
	} );

	trigger.addEventListener( 'click', function () {
		var animate = ! prefersReduced();
		var willOpen = ! isOpen( trigger );

		if ( onToggle ) {
			onToggle( trigger, panel, willOpen, animate );
		}

		if ( willOpen ) {
			open( trigger, panel, animate );
		} else {
			close( trigger, panel, animate );
		}
	} );
}

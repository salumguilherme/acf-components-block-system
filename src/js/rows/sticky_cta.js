/**
 * Row: Sticky CTA
 *
 * Slides a bar in over the page once its trigger fires, and stops doing so once the
 * editor's display allowance is spent. The SECTION is the bar - see the row sheet - so
 * everything here is applied to the element `onRowReady` hands over, and the configuration
 * is read from data attributes on the `.fl-sticky-cta` inside it.
 *
 * THREE CLASSES ARE THE WHOLE INTERFACE WITH THE STYLESHEET:
 *
 *   fl-sticky-active   the section is floating. Positions it and translates it out of
 *                      view, and reveals the close tab. Nothing in the sheet floats
 *                      without it, so no JavaScript means an ordinary in-flow section.
 *   fl-sticky-in       translate back to 0. Added a frame later so the transition runs.
 *   fl-sticky-spent    the allowance is gone AND the row is a top bar, which cannot
 *                      degrade to in-flow because it is `position: fixed`.
 *
 * CLOSING RETURNS THE ROW TO THE FLOW rather than removing it, which the brief is explicit
 * about: the section keeps its background, padding and content and simply stops floating.
 *
 * WHY THE SLIDE NEEDS TWO FRAMES. `fl-sticky-active` sets both the transition and the
 * hidden transform. Adding `fl-sticky-in` in the same frame means the browser never has a
 * before-state to interpolate from and the bar appears instantly. Two rAFs is the reliable
 * way to say "next frame, after style resolution".
 *
 * WHY THE WAY BACK OUT CARRIES A TIMER. Removing `fl-sticky-in` animates the bar out, and
 * `fl-sticky-active` can only come off once that has finished or the row jumps back into
 * the flow mid-slide. `transitionend` is not a promise that a transition ends - it does not
 * fire when the value did not really change, when the tab is hidden and the compositor
 * never advances, or when something has zeroed the duration. accordions shipped depending
 * on it and left panels mid-flight. So both, whichever arrives first.
 *
 * ONE BAR AT A TIME, IN DOCUMENT ORDER. A row may only show while every row above it has
 * finished - closed, spent, or carrying a trigger that can never fire. Note what that means
 * and is meant to mean: a first bar still waiting on `after_s 30` blocks a second bar whose
 * trigger is `load` for those thirty seconds, because the first one can still be displayed.
 * The rule is the brief's, and it is an ordering rule rather than a mutex, which is why
 * there is no lock here - settle() simply re-asks the question every time anything changes.
 *
 * COUNTING IS PER SHOW, NOT PER CLOSE, so a visitor who ignores the bar spends the
 * allowance just as one who dismisses it does.
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

	var STORE_PREFIX = 'acbs:sticky:';
	var DAY_MS = 24 * 60 * 60 * 1000;

	/** Every managed row on the page, in document order. */
	var rows = [];

	// ----------------------------------------------------------------------------------
	// Storage
	//
	// per_session is sessionStorage, which expires itself when the browser session ends.
	// per_day is localStorage plus an expiry STAMP rather than a cookie: it is the same
	// behaviour, and it sends nothing on any request - a custom cookie can punch a hole in
	// a page cache, since plenty of CDN and server configs bypass the cache for requests
	// carrying cookies they do not recognise.
	//
	// EVERY ACCESS CAN THROW. Reading localStorage raises in a browser set to block site
	// data, not merely return null. A failure is treated as "no count recorded", which lets
	// the bar show - the permissive direction, because the alternative is a CTA that
	// silently never appears on a hardened browser.
	// ----------------------------------------------------------------------------------

	function store( period ) {
		try {
			return 'per_day' === period ? window.localStorage : window.sessionStorage;
		} catch ( e ) {
			return null;
		}
	}

	function readCount( key, period ) {
		var s = store( period );

		if ( ! s ) {
			return 0;
		}

		try {
			var raw = s.getItem( STORE_PREFIX + key );

			if ( ! raw ) {
				return 0;
			}

			var data = JSON.parse( raw );

			// An expired per_day window is indistinguishable from never having counted.
			if ( data && data.exp && Date.now() > data.exp ) {
				s.removeItem( STORE_PREFIX + key );

				return 0;
			}

			return data && data.n ? data.n : 0;
		} catch ( e ) {
			return 0;
		}
	}

	function bumpCount( key, period ) {
		var s = store( period );

		if ( ! s ) {
			return;
		}

		try {
			var n = readCount( key, period ) + 1;
			var data = { n: n };

			// Only per_day needs a stamp. sessionStorage already ends with the session, and
			// writing an expiry there would be a second, weaker answer to the same question.
			if ( 'per_day' === period ) {
				var existing = null;

				try {
					existing = JSON.parse( s.getItem( STORE_PREFIX + key ) );
				} catch ( e ) {
					existing = null;
				}

				// The window starts at the FIRST show and is not extended by later ones,
				// otherwise a visitor who keeps triggering the bar never reaches the end of
				// the day and "3 per day" becomes "3 per 24 hours of inactivity".
				data.exp = existing && existing.exp ? existing.exp : Date.now() + DAY_MS;
			}

			s.setItem( STORE_PREFIX + key, JSON.stringify( data ) );
		} catch ( e ) {
			// Quota or a blocked store. Nothing to do: the count simply does not persist.
		}
	}

	// ----------------------------------------------------------------------------------
	// Reading the configuration
	// ----------------------------------------------------------------------------------

	/**
	 * A distance in pixels from `x_value`.
	 *
	 * The field is one text input serving three different kinds of value, so the parsing
	 * lives here. A bare number is pixels; `vh` and `%` are read against the viewport
	 * height, because "scroll half a screen" is the more useful way to say it on a phone.
	 * Anything unparseable returns null and the caller disables the row rather than
	 * treating NaN as zero, which would show the bar immediately - the opposite of what a
	 * mistyped threshold should do.
	 */
	function distance( value ) {
		var text = String( value || '' ).trim().toLowerCase();
		var n = parseFloat( text );

		if ( isNaN( n ) || n < 0 ) {
			return null;
		}

		if ( text.indexOf( 'vh' ) > -1 || text.indexOf( '%' ) > -1 ) {
			return window.innerHeight * ( n / 100 );
		}

		return n;
	}

	function seconds( value ) {
		var n = parseFloat( String( value || '' ).trim() );

		return isNaN( n ) || n < 0 ? null : n * 1000;
	}

	// ----------------------------------------------------------------------------------
	// One row
	// ----------------------------------------------------------------------------------

	function Row( section, config, ordinal ) {
		this.section = section;
		this.bar = config.bar;
		this.position = config.position;
		this.trigger = config.trigger;
		this.value = config.value;
		this.limit = config.limit;
		this.period = config.period;

		// The storage key. Deliberately NOT derived from the ACF row index, which renumbers
		// whenever an editor disables a row above it (CLAUDE.md 05.4). The section's own id
		// is used when an editor set one, because that is a name a person chose and it
		// survives reordering; otherwise the page path plus this row's ordinal among the
		// sticky CTAs on the page. That fallback moves if the page is re-ordered, and the
		// cost of it moving is one extra appearance, which is why it is acceptable here and
		// would not be for an anchor target.
		this.key = section.id
			? 'id:' + section.id
			: window.location.pathname + ':' + ordinal;

		this.wants = false;
		this.shown = false;
		this.closed = false;
		this.disabled = false;
		this.timer = null;
		this.observer = null;
		this.onScroll = null;

		this.spent = this.limit > 0 && readCount( this.key, this.period ) >= this.limit;

		// A spent top bar has nowhere to fall back to: it is position: fixed, so rendering
		// it in the flow would drop a bar into the middle of the page. The sheet hides it
		// outright on this class. A spent BOTTOM bar needs no class at all - never adding
		// fl-sticky-active leaves it exactly where the document puts it.
		if ( this.spent && 'top' === this.position ) {
			section.classList.add( 'fl-sticky-spent' );
		}
	}

	/**
	 * Whether this row is even a candidate right now.
	 *
	 * The breakpoint test reads the COMPUTED display rather than re-deriving display_on
	 * from the class list, so the stylesheet stays the single answer to "is this row shown
	 * at this width" and the two cannot disagree.
	 */
	Row.prototype.available = function () {
		if ( this.spent || this.closed || this.disabled ) {
			return false;
		}

		return 'none' !== window.getComputedStyle( this.section ).display;
	};

	/** Finished for the purposes of the one-bar-at-a-time rule. */
	Row.prototype.finished = function () {
		return this.closed || this.spent || this.disabled || ! this.available();
	};

	Row.prototype.arm = function () {
		var self = this;

		if ( ! this.available() ) {
			return;
		}

		if ( 'load' === this.trigger ) {
			this.request();

			return;
		}

		if ( 'after_s' === this.trigger ) {
			var ms = seconds( this.value );

			if ( null === ms ) {
				this.disabled = true;

				return;
			}

			this.timer = window.setTimeout( function () {
				self.request();
			}, ms );

			return;
		}

		if ( 'scroll_y' === this.trigger ) {
			var threshold = distance( this.value );

			if ( null === threshold ) {
				this.disabled = true;

				return;
			}

			// An obsolete threshold. If the visitor has to scroll further than the row's own
			// position in the document, then by the time the trigger fires the row is already
			// on screen or behind them, and sliding a copy of it over the viewport is noise.
			// Measured while the row is still in the flow, which it is until it activates.
			var offset = this.section.getBoundingClientRect().top + window.pageYOffset;

			if ( threshold > offset ) {
				this.disabled = true;

				return;
			}

			this.onScroll = function () {
				if ( window.pageYOffset >= threshold ) {
					self.disarm();
					self.request();
				}
			};

			window.addEventListener( 'scroll', this.onScroll, { passive: true } );
			this.onScroll();

			return;
		}

		if ( 'on_el' === this.trigger ) {
			var selector = String( this.value || '' ).trim();
			var target = null;

			// A selector an editor typed is untrusted as a SELECTOR, not as markup:
			// querySelector throws on a syntax error rather than returning null, and an
			// unhandled throw here would take the whole row script down with it.
			try {
				target = selector ? document.querySelector( selector ) : null;
			} catch ( e ) {
				target = null;
			}

			// Nothing to watch. The brief says the row is disabled, which is right: waiting
			// forever on an element that is not on this page would leave the row blocking
			// every sticky CTA below it.
			if ( ! target ) {
				this.disabled = true;

				return;
			}

			if ( ! window.IntersectionObserver ) {
				this.request();

				return;
			}

			this.observer = new window.IntersectionObserver( function ( entries ) {
				for ( var i = 0; i < entries.length; i++ ) {
					if ( entries[ i ].isIntersecting ) {
						self.disarm();
						self.request();

						return;
					}
				}
			} );

			this.observer.observe( target );

			return;
		}

		// An unknown trigger. Treat it as `load` rather than disabling the row: a value the
		// script does not recognise is a field that has grown a new choice, and a bar that
		// shows is a better failure than one that silently never does.
		this.request();
	};

	Row.prototype.disarm = function () {
		if ( this.timer ) {
			window.clearTimeout( this.timer );
			this.timer = null;
		}

		if ( this.observer ) {
			this.observer.disconnect();
			this.observer = null;
		}

		if ( this.onScroll ) {
			window.removeEventListener( 'scroll', this.onScroll );
			this.onScroll = null;
		}
	};

	/** "I would like to show now." Whether it happens is settle()'s decision. */
	Row.prototype.request = function () {
		this.wants = true;
		settle();
	};

	Row.prototype.show = function () {
		if ( this.shown || ! this.available() ) {
			return;
		}

		this.shown = true;
		this.disarm();

		bumpCount( this.key, this.period );

		// Counting at the moment of showing is what makes the allowance mean "appearances",
		// so re-reading it here settles whether this was the last one. A bottom bar that has
		// just spent its last appearance still shows this time and simply will not next time.
		if ( this.limit > 0 && readCount( this.key, this.period ) >= this.limit ) {
			this.spent = true;
		}

		this.section.classList.add( 'fl-sticky-active' );

		// A FORCED STYLE FLUSH, NOT requestAnimationFrame, and the difference is not
		// stylistic. `fl-sticky-active` carries both the transition and the hidden
		// transform, so the bar needs a resolved before-state to interpolate from before
		// `fl-sticky-in` lands - add both in one go and it appears instantly with no slide.
		// The obvious way to wait is a double rAF, and it is wrong here: rAF DOES NOT FIRE
		// IN A HIDDEN OR BACKGROUND TAB. Measured on this row - `rafFired: false` after
		// 600ms with `document.visibilityState === 'hidden'` - so a visitor who opens the
		// page in a background tab gets `fl-sticky-active` from the timer, no
		// `fl-sticky-in`, and a bar parked off-screen until the tab is looked at.
		//
		// Reading offsetHeight forces style and layout synchronously, so the before-state is
		// resolved whether or not the browser is painting frames. Same blind spot as the
		// transitionend in accordions, reached from the other direction.
		//
		// The class then lands on a timer rather than immediately, which gives the bar a
		// beat before it starts moving. setTimeout is the right primitive for that and rAF
		// would not be: a background tab throttles a timer to a second or so, but it still
		// fires, where rAF simply does not run at all.
		void this.section.offsetHeight;
		const self = this;
		setTimeout(() => {
			self.section.classList.add( 'fl-sticky-in' );
		}, 50);

		this.watchSettle();

		acbs.doAction( 'sticky_cta/shown', this.section, this );
	};

	/**
	 * Hides the close tab once the bar has settled into its own place in the document.
	 *
	 * `position: sticky` stops floating on its own once the page has scrolled to where the
	 * row actually lives: it resolves to its natural position and the bar becomes an
	 * ordinary section at the end of the page. At that point it is not covering anything, so
	 * a close button has nothing to close - leaving it there offers to dismiss a section that
	 * is simply part of the page.
	 *
	 * ONLY A BOTTOM BAR CAN SETTLE. A top bar is `position: fixed`, permanently over the
	 * content with no in-flow position to arrive at, so its tab stays for as long as it is
	 * shown and this does nothing.
	 *
	 * THE TEST IS THE RENDERED BOX, not an offset computed at init. While the bar is stuck
	 * its bottom edge sits on the viewport bottom; once it settles, that edge rises above it.
	 * Reading the real box is immune to anything above the row reflowing - a web font
	 * landing, an image decoding, an accordion opening - where a remembered document offset
	 * silently goes stale. It also covers a page short enough that the row never floats at
	 * all, where the tab should be hidden from the moment it shows.
	 *
	 * `getBoundingClientRect()` INCLUDES TRANSFORMS, which is why this is armed only after
	 * the bar is shown: while it is still translated out it measures as floating whatever the
	 * scroll position, so arming it earlier would latch the wrong answer.
	 */
	Row.prototype.watchSettle = function () {
		var self = this;

		if ( 'bottom' !== this.position ) {
			return;
		}

		var settled = null;

		this.onSettle = function () {
			var now = self.section.getBoundingClientRect().bottom < window.innerHeight - 1;

			// Only touch the class list when the answer actually changes. The read is one
			// element's box, which is cheap; a write on every scroll event is what turns a
			// cheap listener into layout thrash.
			if ( now === settled ) {
				return;
			}

			settled = now;
			self.section.classList.toggle( 'fl-sticky-settled', now );
		};

		window.addEventListener( 'scroll', this.onSettle, { passive: true } );
		window.addEventListener( 'resize', this.onSettle, { passive: true } );

		// THE FIRST EVALUATION HAS TO WAIT FOR THE SLIDE, and this is the reason it is a
		// timer rather than a straight call. watchSettle() runs from show(), while the bar
		// is still translated out of view - and getBoundingClientRect() includes that
		// transform, so measuring now latches "floating" whatever the scroll position.
		//
		// Scrolling would correct it, so this only matters when the visitor never scrolls:
		// a page short enough that the row is already at its own place, where the tab
		// should be hidden from the start and would otherwise sit there until the first
		// scroll event. Derived from the element's own duration so it follows the token.
		window.setTimeout( this.onSettle, duration( this.section ) + 120 );
	};

	Row.prototype.releaseSettle = function () {
		if ( ! this.onSettle ) {
			return;
		}

		window.removeEventListener( 'scroll', this.onSettle );
		window.removeEventListener( 'resize', this.onSettle );
		this.onSettle = null;
		this.section.classList.remove( 'fl-sticky-settled' );
	};

	Row.prototype.close = function () {
		var self = this;

		if ( this.closed ) {
			return;
		}

		this.closed = true;
		this.disarm();
		this.section.classList.remove( 'fl-sticky-in' );

		var settled = false;

		var land = function () {
			if ( settled ) {
				return;
			}

			settled = true;
			self.section.removeEventListener( 'transitionend', onEnd );
			window.clearTimeout( fallback );

			// Back into the flow. A spent top bar is hidden by the sheet instead, which its
			// fl-sticky-spent class already says.
			self.section.classList.remove( 'fl-sticky-active' );
			self.releaseSettle();

			acbs.doAction( 'sticky_cta/closed', self.section, self );

			// The row above has finished, so whatever is below it may now have its turn.
			settle();
		};

		var onEnd = function ( event ) {
			if ( event.target === self.section && 'transform' === event.propertyName ) {
				land();
			}
		};

		this.section.addEventListener( 'transitionend', onEnd );

		// The duration-derived safety net. Read off the element so it follows
		// --fl-sticky-duration and the reduced-motion override rather than repeating either.
		var fallback = window.setTimeout( land, duration( this.section ) + 60 );
	};

	/** The element's own transition duration, in ms, including a delay if one is set. */
	function duration( el ) {
		var style = window.getComputedStyle( el );
		var parse = function ( value ) {
			var first = String( value || '' ).split( ',' )[ 0 ].trim();
			var n = parseFloat( first );

			if ( isNaN( n ) ) {
				return 0;
			}

			return first.indexOf( 'ms' ) > -1 ? n : n * 1000;
		};

		return parse( style.transitionDuration ) + parse( style.transitionDelay );
	}

	// ----------------------------------------------------------------------------------
	// The one-bar-at-a-time rule
	// ----------------------------------------------------------------------------------

	function settle() {
		for ( var i = 0; i < rows.length; i++ ) {
			var row = rows[ i ];

			if ( row.shown && ! row.closed ) {
				// Something is on screen. Nothing below it may take a turn until it goes.
				return;
			}

			if ( row.finished() ) {
				continue;
			}

			// The first row that is still a candidate holds the floor whether or not its own
			// trigger has fired yet - which is the brief's rule, not an accident: "only the
			// first one that can still be displayed slides in".
			if ( row.wants ) {
				row.show();
			}

			return;
		}
	}

	// ----------------------------------------------------------------------------------
	// Wiring
	// ----------------------------------------------------------------------------------

	function init( section ) {
		if ( section.dataset.acbsStickyCta ) {
			return;
		}

		var bar = section.querySelector( '.fl-sticky-cta' );

		if ( ! bar ) {
			return;
		}

		section.dataset.acbsStickyCta = '1';

		var row = new Row(
			section,
			{
				bar: bar,
				position: bar.getAttribute( 'data-position' ) || 'bottom',
				trigger: bar.getAttribute( 'data-trigger' ) || 'load',
				value: bar.getAttribute( 'data-trigger-value' ) || '',
				limit: parseInt( bar.getAttribute( 'data-display-count' ), 10 ) || 0,
				period: bar.getAttribute( 'data-display-period' ) || 'per_session',
			},
			rows.length
		);

		rows.push( row );

		var close = bar.querySelector( '[data-acbs-sticky-close]' );

		if ( close ) {
			close.addEventListener( 'click', function ( event ) {
				event.preventDefault();
				row.close();
			} );
		}

		row.arm();
		settle();
	}

	acbs.onRowReady( 'sticky_cta', function ( section ) {
		init( section );
	} );
} )( window, document );

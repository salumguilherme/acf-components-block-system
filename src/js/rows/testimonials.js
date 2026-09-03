/**
 * Row: Testimonials
 *
 * Turns the testimonials grid into a Swiper carousel on TABLET AND MOBILE ONLY, and only
 * where the template emitted `.fl-testimonials-slider` - which it does when Content Box is
 * set to Card. Desktop keeps the CSS grid untouched.
 *
 * WHY matchMedia AND NOT SWIPER'S OWN `breakpoints`. Swiper takes `enabled` per breakpoint
 * and that looks like the obvious way to switch itself off above 992px. It is not, and the
 * reason is in swiper-core.mjs: `disable()` sets `this.enabled = false` and unsets the grab
 * cursor, and that is the whole method. It does not undo `updateSlides()`, which has
 * already written a pixel `width` onto the inline style of every slide - and an inline
 * style beats any stylesheet, so the desktop grid would come back with each of its items
 * pinned to a tablet slide's width. `destroy( true, true )` is the only thing that clears
 * them: its clean-styles pass calls `removeAttribute('style')` on the container, the
 * wrapper and every slide.
 *
 * That last detail is the constraint on this row's markup: NOTHING in the slider may carry
 * an inline style of its own, because destroying the carousel strips the whole attribute
 * rather than the properties Swiper set. The template emits none, deliberately.
 *
 * SWIPER IS POINTED AT THE ROW'S OWN CLASS NAMES through `wrapperClass` and `slideClass`,
 * rather than `swiper-wrapper` and `swiper-slide` being added to the markup. Two reasons,
 * and the second is the real one:
 *
 *   1. The `<li>` is rendered by rows/testimonials/item.php, which cannot know whether
 *      cards are on - `layout_display` is a section field and does not resolve from inside
 *      the repeater loop. Adding the class there is not available; adding it from here
 *      means the row visibly reflows once the footer script runs.
 *   2. With the vendor class names absent, Swiper's own stylesheet has nothing to match, so
 *      it is not imported at all and the row sheet states the dozen layout declarations it
 *      actually needs inside the tablet/mobile media query. That is what keeps the grid a
 *      grid at desktop without a specificity fight, and it keeps a second copy of
 *      swiper.css off any page that also has an image_gallery.
 *
 * THE GAP IS READ OFF THE GRID before the carousel exists, as a computed `column-gap` in
 * pixels. Swiper does its own width arithmetic and needs a number, so this is the one
 * measurement that has to cross from CSS into JS - and taking it from `column-gap` rather
 * than from a custom property means it follows whatever `--fl-grid-gap` resolves to,
 * including a theme's own value, with no second token to keep in step. The row sheet zeroes
 * the CSS gap while the carousel is live, so the spacing is Swiper's alone and never both.
 *
 * The column counts arrive as data attributes with their fallbacks already resolved by the
 * template, so this file knows neither the project's breakpoints nor the grid's step-down
 * rules. It adds the peek and nothing else.
 *
 * @version 1.0.0
 * @since   1.0.0
 */
import Swiper from 'swiper';

( function ( window ) {
	'use strict';

	var acbs = window.acbs;

	if ( ! acbs || ! acbs.onRowReady ) {
		return;
	}

	/**
	 * Tablet and mobile together. The stylesheets use a max-width cascade and so does this,
	 * which is the opposite of Swiper's own min-width breakpoints - the one inside the
	 * Swiper config below is min-width because that is Swiper's, not ours.
	 */
	var BELOW_DESKTOP = '(max-width: 991.98px)';

	/**
	 * How much of the next testimonial shows: a sliver, enough to say the strip continues.
	 * Well under the gallery's 0.4, because a quote is text rather than a picture - a
	 * substantial fraction of a card reads as a sentence cut off mid-word, where an edge
	 * reads as an edge.
	 *
	 * Nothing else needs changing when this moves. The cards' equal height comes from
	 * flexbox in the row sheet, not from any arithmetic here.
	 */
	var PEEK = 0.1;

	/** A resolved column count from a data attribute. */
	function columns( el, name, fallback ) {
		var n = parseInt( el.getAttribute( name ), 10 );

		return n > 0 ? n : fallback;
	}

	/**
	 * The grid's own gap, in pixels.
	 *
	 * `column-gap` computes to a pixel length whatever the author wrote, so unlike a raw
	 * custom property there is no unit to misparse - `--fl-grid-gap: 1.5rem` read directly
	 * would give parseFloat 1.5 and a 1.5px gap, silently.
	 */
	function gap( el ) {
		var n = parseFloat( window.getComputedStyle( el ).columnGap );

		return isNaN( n ) ? 24 : n;
	}

	function activate( el ) {
		if ( el.acbsSwiper ) {
			return;
		}

		var list = el.querySelector( '.fl-testimonials' );

		if ( ! list ) {
			return;
		}

		var tablet = columns( el, 'data-columns-sm', 2 );
		var mobile = columns( el, 'data-columns-xs', 1 );

		el.acbsSwiper = new Swiper( el, {
			wrapperClass: 'fl-testimonials',
			slideClass: 'fl-testimonial',
			spaceBetween: gap( list ),
			slidesPerView: mobile + PEEK,
			// BOTH STEPS ARE DECLARED, including the 0 that looks redundant next to the
			// base slidesPerView above. Swiper falls back to its original params when no
			// breakpoint matches, so `768` alone reads as enough - and measured at 375px
			// after a resize down from tablet, the slides kept their tablet width and
			// params.slidesPerView still reported the tablet value. An explicit 0 makes
			// mobile a breakpoint Swiper has to match rather than a fallback it has to
			// remember, which is also the shape image_gallery already uses.
			//
			// These are MIN-widths - Swiper's convention, the opposite of the max-width
			// cascade every stylesheet here uses, and the opposite of BELOW_DESKTOP above.
			breakpoints: {
				0: { slidesPerView: mobile + PEEK },
				768: { slidesPerView: tablet + PEEK },
			},
		} );

		acbs.doAction( 'testimonials/slider', el.acbsSwiper, el );
	}

	function deactivate( el ) {
		if ( ! el.acbsSwiper ) {
			return;
		}

		// Both flags on purpose: the second is the clean-styles pass that strips the inline
		// widths Swiper wrote onto every slide. Without it the desktop grid inherits them.
		el.acbsSwiper.destroy( true, true );
		el.acbsSwiper = null;

		acbs.doAction( 'testimonials/slider_destroyed', el );
	}

	function watch( el ) {
		if ( el.dataset.acbsTestimonials ) {
			return;
		}

		el.dataset.acbsTestimonials = '1';

		var query = window.matchMedia( BELOW_DESKTOP );

		var apply = function () {
			if ( query.matches ) {
				activate( el );
			} else {
				deactivate( el );
			}
		};

		// addEventListener on a MediaQueryList is the current API; addListener is the
		// deprecated one that older Safari has instead. Both are checked because the
		// fallback costs two lines and the row is otherwise inert on that browser.
		if ( query.addEventListener ) {
			query.addEventListener( 'change', apply );
		} else if ( query.addListener ) {
			query.addListener( apply );
		}

		apply();
	}

	acbs.onRowReady( 'testimonials', function ( row ) {
		var sliders = row.querySelectorAll( '.fl-testimonials-slider' );

		for ( var i = 0; i < sliders.length; i++ ) {
			watch( sliders[ i ] );
		}
	} );
} )( window );

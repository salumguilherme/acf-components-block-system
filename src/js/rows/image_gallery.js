/**
 * Row: Image Gallery
 *
 * A Swiper carousel that overflows its container on purpose: the first slide lines up with
 * the left edge of `.fl-container` and the rest run out past the right edge. Swiper still
 * measures against the container, so the slide widths come out of the container's width
 * and the alignment holds; the sheet only has to stop Swiper clipping what leaves it.
 *
 * WHY THE COLUMN COUNTS ARRIVE AS DATA. The section already carries
 * fl-loop-grid-columns-{n} and its -sm / -xs steps, and this could read them off the
 * class list. It does not, because those describe a CSS grid and Swiper needs the same
 * numbers as a slidesPerView per breakpoint, with tablet and mobile carrying an extra 0.4
 * so the next slide peeks in and the strip reads as continuing. Deriving that here would
 * mean this file knowing both the project's breakpoints and the +0.4 rule; taking three
 * numbers means it knows neither.
 *
 * Swiper's breakpoints are MIN-width, the opposite of the max-width cascade the
 * stylesheets use, so they are written out here rather than mirrored from the Sass:
 * 0 is mobile, 768 up is tablet, 992 up is desktop.
 *
 * LAZY LOADING is native. Swiper 14 has no lazy module: it watches images carrying
 * `loading="lazy"`, which wp_get_attachment_image() emits, and preloads the neighbouring
 * slides according to lazyPreloadPrevNext.
 *
 * Only the FreeMode module is imported. Swiper's full bundle is most of a slider library
 * this row does not use - no pagination, no navigation, no autoplay - and importing the
 * modules by name is what lets the bundler drop the rest.
 *
 * THE LIGHTBOX is PhotoSwipe, pointed at `.swiper-wrapper` because that is the element
 * whose direct children are the slides.
 *
 * `pswpModule` IS THE IMPORTED MODULE, not the `() => import('photoswipe')` the docs lead
 * with. That dynamic form is the right default for an app that code-splits, and the wrong
 * one here: webpack would emit a second chunk that nothing enqueues and that would be
 * fetched from a publicPath this build never sets. PhotoSwipe explicitly supports passing
 * the module itself, which keeps the row to one file.
 *
 * The carousel is moved to follow the lightbox on `change` rather than only on close. That
 * is not tidiness: PhotoSwipe's open and close animations zoom to and from the THUMBNAIL,
 * so if the visitor pages to an image whose slide has scrolled out of view, there is
 * nothing on screen for the closing animation to land on. Keeping the strip in step means
 * there always is.
 *
 * THE OPENING `change` IS SKIPPED, and that is the whole point of the `opening` flag.
 * PhotoSwipe dispatches `change` from inside `init()`, between `beforeOpen` and
 * `afterInit`, so the very first one is not the visitor paging: it is the slide they just
 * clicked. Acting on it scrolls the strip out from under the thumbnail the opening zoom is
 * animating from, which reads as the track lurching sideways as the lightbox appears.
 *
 * @version 1.0.0
 * @since   1.0.0
 */
import Swiper from 'swiper';
import { FreeMode } from 'swiper/modules';
import PhotoSwipe from 'photoswipe';
import PhotoSwipeLightbox from 'photoswipe/lightbox';

( function ( window ) {
	'use strict';

	var acbs = window.acbs;

	if ( ! acbs || ! acbs.onRowReady ) {
		return;
	}

	/** A column count from a data attribute, falling back to the design's arrangement. */
	function columns( el, name, fallback ) {
		var n = parseInt( el.getAttribute( name ), 10 );

		return n > 0 ? n : fallback;
	}

	/**
	 * The gap between slides, read from CSS so a theme can change it in the one place it
	 * would expect to. Swiper needs it as a number because it does the width arithmetic
	 * itself - it cannot read a stylesheet - so this is the one measurement that has to
	 * cross from CSS into JS.
	 *
	 * The property is declared UNITLESS in the sheet and read as pixels. parseFloat turns
	 * "2rem" into 2, so a value carrying a unit would silently become a 2px gap rather
	 * than failing loudly; a bare number cannot be misread.
	 */
	function gap( el ) {
		var value = window.getComputedStyle( el ).getPropertyValue( '--fl-gallery-gap' );
		var n = parseFloat( value );

		return isNaN( n ) ? 32 : n;
	}

	function init( el ) {
		if ( el.dataset.acbsGallery ) {
			return;
		}

		el.dataset.acbsGallery = '1';

		var desktop = columns( el, 'data-columns', 3 );
		var tablet = columns( el, 'data-columns-sm', 2 );
		var mobile = columns( el, 'data-columns-xs', 1 );

		// The .4 that makes the next slide peek in below desktop. Desktop shows whole
		// slides because the overflow past the container already says "there is more".
		var PEEK = 0.4;

		var wrapper = el.querySelector( '.swiper-wrapper' );

		var swiper = new Swiper( el, {
			modules: [ FreeMode ],
			freeMode: true,
			loop: false,
			spaceBetween: gap( el ),
			slidesPerView: mobile + PEEK,
			lazyPreloadPrevNext: 2,
			breakpoints: {
				0: { slidesPerView: mobile + PEEK },
				768: { slidesPerView: tablet + PEEK },
				992: { slidesPerView: desktop },
			},
			on: {
				init: function () {
					if ( ! wrapper ) {
						return;
					}

					var lightbox = new PhotoSwipeLightbox( {
						gallery: wrapper,
						children: '.fl-gallery-slide',
						pswpModule: PhotoSwipe,
					} );

					// True only for the `change` PhotoSwipe fires while opening, which
					// reports the slide the visitor just clicked rather than a slide they
					// have paged to. Both hooks are on the LIGHTBOX, so they re-arm for
					// every open.
					var opening = false;

					lightbox.on( 'beforeOpen', function () {
						opening = true;
					} );

					lightbox.on( 'afterInit', function () {
						opening = false;
					} );

					// Keeps the strip under the lightbox in step, so the zoom-out on close
					// always has a thumbnail on screen to land on.
					lightbox.on( 'change', function () {
						if ( opening ) {
							return;
						}

						swiper.slideTo( lightbox.pswp.currIndex, 0 );
					} );

					lightbox.init();

					acbs.doAction( 'image_gallery/lightbox', lightbox, swiper, el );
				},
			},
		} );

		acbs.doAction( 'image_gallery/init', swiper, el );
	}

	acbs.onRowReady( 'image_gallery', function ( row ) {
		var galleries = row.querySelectorAll( '.fl-gallery' );

		for ( var i = 0; i < galleries.length; i++ ) {
			init( galleries[ i ] );
		}
	} );
} )( window );

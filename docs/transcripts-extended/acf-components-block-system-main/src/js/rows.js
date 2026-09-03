/**
 * ACBS row runtime.
 *
 * The one script the plugin always loads when a row renders. It does two things: it
 * announces every row on the page so scripts can attach behaviour to it, and it carries a
 * small action bus so the plugin and a theme can talk without either importing the other.
 *
 * Modelled on ACF's own JS API, deliberately, because that is the vocabulary anyone
 * working on this stack already has - acf.addAction / acf.doAction / acf.didAction. It is
 * a good deal smaller: no priorities, no contexts, no filters. Those exist in ACF because
 * ACF has hundreds of internal callers ordering themselves against each other. Here there
 * are two parties, so priority is a knob nobody would turn.
 *
 * WHY onRowReady() IS NOT JUST addAction().
 *
 * ACF's actions do not replay. `doAction` records that a name has fired and moves on:
 *
 *     var c = {};
 *     acf.doAction  = function(t){ c[t]=1; hooks.doAction.apply(this,arguments); c[t]=0; };
 *     acf.didAction = function(t){ return c[t] !== undefined; };
 *
 * A late subscriber gets nothing, and ACF's answer is to make the caller branch on
 * didAction() itself - which acf.Model does in one line. That works for a one-shot
 * lifecycle event like `ready`, because the question "has it happened" has a single
 * boolean answer.
 *
 * Rows are not one-shot. They are a set of ELEMENTS, and "has it happened" has one answer
 * per element, which a boolean cannot carry. It matters here because the plugin's row
 * script and a theme's row script are both footer scripts whose relative order comes out
 * of a dependency graph the theme author does not control: subscribe with a plain
 * listener and whether you see a row is down to enqueue order. So onRowReady() keeps the
 * list of rows already announced and replays them for a late subscriber. Attaching
 * behaviour to a row is then order-independent by construction rather than by luck.
 *
 * @version 1.0.0
 * @since   1.0.0
 */
( function ( window, document ) {
	'use strict';

	/**
	 * The row wrapper's layout class. Note this is read off the CLASS rather than a
	 * data attribute, and that is not an aesthetic choice.
	 *
	 * templates/wrapper.php is overridable, and a theme template REPLACES the plugin's
	 * rather than layering on it. A theme that copied wrapper.php last month would never
	 * grow a new `data-acbs-layout` attribute, so every row it renders would silently stop
	 * initialising - the exact failure this codebase keeps producing. `fl-type-{layout}`
	 * comes from Wrapper::classes() in PHP, which a copied template still calls.
	 */
	var TYPE_PREFIX = 'fl-type-';

	/** Marks an element as announced, so a second init() pass does not double-fire. */
	var INIT_FLAG = 'acbsRow';

	var actions = {};
	var fired = {};

	/** Rows announced so far, so a late subscriber can be caught up. */
	var announced = [];

	/** onRowReady subscribers: { layout: string|null, fn: Function }. */
	var watchers = [];

	/**
	 * Runs a callback without letting it take the rest of the page down with it. One
	 * theme script throwing should not stop the remaining rows initialising.
	 */
	function safely( fn, args ) {
		try {
			fn.apply( null, args );
		} catch ( e ) {
			if ( window.console && window.console.error ) {
				window.console.error( 'ACBS: a row callback threw.', e );
			}
		}
	}

	function addAction( name, fn ) {
		if ( 'string' !== typeof name || 'function' !== typeof fn ) {
			return api;
		}

		( actions[ name ] = actions[ name ] || [] ).push( fn );

		return api;
	}

	function removeAction( name, fn ) {
		var list = actions[ name ];

		if ( list ) {
			for ( var i = list.length - 1; i >= 0; i-- ) {
				if ( list[ i ] === fn ) {
					list.splice( i, 1 );
				}
			}
		}

		return api;
	}

	function doAction( name ) {
		var args = Array.prototype.slice.call( arguments, 1 );
		var list = actions[ name ];

		fired[ name ] = true;

		if ( list ) {
			// Iterated over a copy: a callback that unsubscribes itself, which is the
			// normal shape of a run-once listener, would otherwise shorten the array
			// mid-loop and skip the callback after it.
			list = list.slice();

			for ( var i = 0; i < list.length; i++ ) {
				safely( list[ i ], args );
			}
		}

		return api;
	}

	function didAction( name ) {
		return true === fired[ name ];
	}

	/**
	 * The layout name of a row element, or '' if it carries no fl-type- class.
	 */
	function layoutOf( el ) {
		var classes = el.classList;

		for ( var i = 0; i < classes.length; i++ ) {
			if ( 0 === classes[ i ].indexOf( TYPE_PREFIX ) ) {
				return classes[ i ].slice( TYPE_PREFIX.length );
			}
		}

		return '';
	}

	/**
	 * Attach a callback to every row of a layout, whenever it was announced.
	 *
	 * Pass no layout (or null) to receive every row. The callback takes
	 * ( element, layout ).
	 */
	function onRowReady( layout, fn ) {
		if ( 'function' === typeof layout ) {
			fn = layout;
			layout = null;
		}

		if ( 'function' !== typeof fn ) {
			return api;
		}

		watchers.push( { layout: layout, fn: fn } );

		// Catch up on rows that were announced before this subscriber existed. Without
		// this, whether a theme script sees a row depends on footer enqueue order.
		for ( var i = 0; i < announced.length; i++ ) {
			if ( null === layout || announced[ i ].layout === layout ) {
				safely( fn, [ announced[ i ].el, announced[ i ].layout ] );
			}
		}

		return api;
	}

	/**
	 * Announce one row element.
	 */
	function initRow( el ) {
		if ( ! el || el.dataset[ INIT_FLAG ] ) {
			return;
		}

		var layout = layoutOf( el );

		if ( '' === layout ) {
			return;
		}

		el.dataset[ INIT_FLAG ] = layout;

		var entry = { el: el, layout: layout };

		announced.push( entry );

		for ( var i = 0; i < watchers.length; i++ ) {
			if ( null === watchers[ i ].layout || watchers[ i ].layout === layout ) {
				safely( watchers[ i ].fn, [ el, layout ] );
			}
		}

		// A DOM event as well as the callback, so a listener that would rather not know
		// about window.acbs at all still has a way in. Bubbles, so document-level
		// delegation works; not cancelable, because there is nothing to cancel.
		el.dispatchEvent(
			new CustomEvent( 'acbs/row/ready', {
				bubbles: true,
				detail: entry,
			} )
		);

		doAction( 'row/ready/' + layout, el, layout );
	}

	/**
	 * Announce every row under `root`. Safe to call again: an element is only ever
	 * announced once.
	 *
	 * Public, because a theme that injects rows after load - a filtered listing, a modal -
	 * has no other way to tell the runtime they exist.
	 */
	function initRows( root ) {
		var scope = root || document;
		var found = scope.querySelectorAll( '.fl-acbs .fl-section' );

		for ( var i = 0; i < found.length; i++ ) {
			initRow( found[ i ] );
		}

		doAction( 'rows/ready', scope );

		return api;
	}

	var api = {
		addAction: addAction,
		removeAction: removeAction,
		doAction: doAction,
		didAction: didAction,
		onRowReady: onRowReady,
		initRow: initRow,
		initRows: initRows,
		layoutOf: layoutOf,
		rows: function () {
			return announced.slice();
		},
	};

	// Merged rather than assigned: this script is a dependency of the per-row scripts, so
	// it runs first, but a theme is free to define window.acbs earlier still.
	window.acbs = window.acbs || {};

	for ( var key in api ) {
		if ( Object.prototype.hasOwnProperty.call( api, key ) && ! window.acbs[ key ] ) {
			window.acbs[ key ] = api[ key ];
		}
	}

	api = window.acbs;

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', function () {
			initRows();
		} );
	} else {
		// Footer script on an already-parsed document: every row is in the DOM above us.
		initRows();
	}
} )( window, document );

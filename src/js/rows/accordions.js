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
 * The panel animation lives in _accordion.js, shared with columned_content. What stays
 * here is the only thing that differs: this row is a GROUP, where opening one item closes
 * the others.
 *
 * @version 1.0.1
 * @since   1.0.0
 */
import { bind, panelOf, isOpen, close } from '../_accordion.js';

( function ( window ) {
	'use strict';

	var acbs = window.acbs;

	if ( ! acbs || ! acbs.onRowReady ) {
		return;
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

			bind( trigger, panel, function ( self, ownPanel, willOpen, animate ) {
				if ( ! willOpen || ! single ) {
					return;
				}

				Array.prototype.forEach.call( triggers, function ( other ) {
					if ( other === self || ! isOpen( other ) ) {
						return;
					}

					var otherPanel = panelOf( other );

					if ( otherPanel ) {
						close( other, otherPanel, animate );
					}
				} );
			} );

			trigger.addEventListener( 'click', function () {
				acbs.doAction( 'accordions/toggle', trigger, panel, isOpen( trigger ) );
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
} )( window );

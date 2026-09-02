/**
 * Row: Columned Content
 *
 * Only does anything for columns with `column_accordion` ticked; a row of plain columns
 * loads this and finds nothing to wire, which costs a querySelectorAll and no more.
 *
 * EACH COLUMN IS ITS OWN ACCORDION. There is no group behaviour here and that is the
 * field's intent rather than an omission: the toggle is per column, so two columns may be
 * open at once and opening one must not touch its neighbour. That is the whole difference
 * from the accordions row, which is why the panel animation is imported rather than
 * copied.
 *
 * The initial state is rendered by PHP from `column_accordion_initial_status`, not set
 * here: a panel that starts open and is closed by JS on load flashes, and one that starts
 * closed and is opened by JS arrives late for anyone reading without scripts.
 *
 * @version 1.0.0
 * @since   1.0.0
 */
import { bind, panelOf, isOpen } from '../_accordion.js';

( function ( window ) {
	'use strict';

	var acbs = window.acbs;

	if ( ! acbs || ! acbs.onRowReady ) {
		return;
	}

	function initColumn( trigger ) {
		if ( trigger.dataset.acbsAccordion ) {
			return;
		}

		var panel = panelOf( trigger );

		if ( ! panel ) {
			return;
		}

		trigger.dataset.acbsAccordion = '1';

		bind( trigger, panel );

		trigger.addEventListener( 'click', function () {
			acbs.doAction( 'columned_content/toggle', trigger, panel, isOpen( trigger ) );
		} );
	}

	acbs.onRowReady( 'columned_content', function ( row ) {
		var triggers = row.querySelectorAll( '.fl-column-accordion-trigger' );

		for ( var i = 0; i < triggers.length; i++ ) {
			initColumn( triggers[ i ] );
		}

		acbs.doAction( 'columned_content/init', row );
	} );
} )( window );

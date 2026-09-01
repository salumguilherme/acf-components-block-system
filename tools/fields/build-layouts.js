#!/usr/bin/env node
/**
 * Turns the supplied fields-list.json into the PHP array literal that
 * Page_Content::get_base_layouts() returns.
 *
 * Generated rather than hand-transcribed because 12 layouts of ACF definitions is a lot
 * of typing to get subtly wrong, and because the transformations below are rules that
 * should be visible and re-runnable rather than applied once by hand:
 *
 *   1. Grid & Display fields are STRIPPED. layout_columns, layout_columns_sm,
 *      layout_columns_xs, columns_alignment, layout_display, layout_display_bg and
 *      layout_display_bg_colour all move to the Grid_Display component, which injects
 *      them per layout. Leaving them here would render each field twice.
 *   2. `columns_alignment` is renamed `layout_columns_alignment` on the way, so the whole
 *      group shares one prefix.
 *   3. A field named `_buttons_` is a PLACEHOLDER, not a real field - the JSON types it
 *      as `text`. Each is replaced by a clone of the Buttons component.
 *   4. Layout `stats_copy` is renamed `icon_list`, matching its label and its repeater.
 *   5. Layout `icon_leaders` is DROPPED. It was removed from the plugin - its job is done
 *      by columned_content, which has an icon of its own - so a source JSON that still
 *      carries it should not reintroduce it.
 *   6. Keys are emitted as supplied. They are fresh and do not match the previous set;
 *      layouts and fields are matched by NAME, not key.
 *
 *   node tools/fields/build-layouts.js /path/to/fields-list.json
 */

const fs = require( 'fs' );

const GRID_FIELDS = [
	'layout_columns',
	'layout_columns_sm',
	'layout_columns_xs',
	'columns_alignment',
	'layout_display',
	'layout_display_bg',
	'layout_display_bg_colour',
];

const RENAME_LAYOUT = { stats_copy: 'icon_list' };

/**
 * Layouts the plugin no longer has. A source JSON predating their removal still carries
 * them, and regenerating from it would put them back - silently, since a layout array is
 * long enough that one extra entry does not stand out in a diff.
 */
const DROP_LAYOUT = new Set( [ 'icon_leaders' ] );
const BUTTONS_GROUP_KEY = 'group_b99bcf0767134';

/** ACF settings that are pure editor noise in a locally registered field. */
const DROP_KEYS = new Set( [ 'aria-label', 'allow_in_bindings', 'rows_per_page', 'parent_repeater' ] );

const isGrid = ( name ) => GRID_FIELDS.includes( name );

function buttonsClone( field ) {
	return {
		key: field.key,
		label: field.label || 'Buttons',
		name: 'buttons',
		type: 'clone',
		clone: [ BUTTONS_GROUP_KEY ],
		display: 'seamless',
		layout: 'block',
		prefix_label: 0,
		prefix_name: 0,
	};
}

function cleanField( field, layoutName ) {
	if ( field.name === '_buttons_' ) {
		return buttonsClone( field );
	}

	const out = {};

	for ( const [ k, v ] of Object.entries( field ) ) {
		if ( DROP_KEYS.has( k ) ) continue;
		if ( k === 'conditional_logic' && ( v === 0 || v === false ) ) continue;
		if ( k === 'instructions' && v === '' ) continue;
		if ( k === 'wrapper' && ! v.width && ! v.class && ! v.id ) continue;
		if ( k === 'sub_fields' ) {
			out[ k ] = v.map( ( sub ) => cleanField( sub, layoutName ) );
			continue;
		}
		out[ k ] = v;
	}

	return out;
}

function php( value, indent ) {
	const pad = '\t'.repeat( indent );
	const padIn = '\t'.repeat( indent + 1 );

	if ( value === null ) return 'null';
	if ( typeof value === 'boolean' ) return value ? 'true' : 'false';
	if ( typeof value === 'number' ) return String( value );
	if ( typeof value === 'string' ) return `'${ value.replace( /\\/g, '\\\\' ).replace( /'/g, "\\'" ) }'`;

	if ( Array.isArray( value ) ) {
		if ( ! value.length ) return '[]';
		return '[\n' + value.map( ( v ) => padIn + php( v, indent + 1 ) + ',' ).join( '\n' ) + '\n' + pad + ']';
	}

	const entries = Object.entries( value );
	if ( ! entries.length ) return '[]';

	return (
		'[\n' +
		entries
			.map( ( [ k, v ] ) => `${ padIn }${ /^\d+$/.test( k ) ? k : `'${ k }'` } => ${ php( v, indent + 1 ) },` )
			.join( '\n' ) +
		'\n' + pad + ']'
	);
}

function main() {
	const src = JSON.parse( fs.readFileSync( process.argv[ 2 ], 'utf8' ) );
	const layouts = {};
	const report = [];

	for ( const def of Object.values( src ) ) {
		const name = RENAME_LAYOUT[ def.name ] || def.name;

		if ( DROP_LAYOUT.has( name ) ) {
			console.log( `  ${ name.padEnd( 24 ) }dropped (removed from the plugin)` );
			continue;
		}

		const kept = def.sub_fields.filter( ( f ) => ! isGrid( f.name ) );
		const stripped = def.sub_fields.filter( ( f ) => isGrid( f.name ) ).map( ( f ) => f.name );

		layouts[ def.key ] = {
			key: def.key,
			label: def.label,
			name,
			display: def.display || 'row',
			min: def.min ?? '',
			max: def.max ?? '',
			sub_fields: kept.map( ( f ) => cleanField( f, name ) ),
		};

		report.push( {
			name,
			renamedFrom: RENAME_LAYOUT[ def.name ] ? def.name : null,
			own: kept.map( ( f ) => ( f.name === '_buttons_' ? 'buttons (clone)' : f.name ) ),
			grid: stripped.map( ( n ) => ( n === 'columns_alignment' ? 'layout_columns_alignment' : n ) ),
		} );
	}

	fs.writeFileSync( 'dist/layouts.php.txt', php( layouts, 3 ) + '\n' );
	fs.writeFileSync( 'dist/grid-membership.json', JSON.stringify( report, null, '\t' ) );

	console.log( `  ${ report.length } layouts -> dist/layouts.php.txt` );
	for ( const r of report ) {
		console.log(
			`  ${ r.name.padEnd( 24 ) }${ r.renamedFrom ? `(was ${ r.renamedFrom }) ` : '' }own: ${ r.own.join( ', ' ) || '—' }`
		);
		console.log( `  ${ ''.padEnd( 24 ) }grid: ${ r.grid.join( ', ' ) || '— none' }` );
	}
}

main();

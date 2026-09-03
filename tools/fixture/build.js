#!/usr/bin/env node
/**
 * Builds a static preview of every row wrapper variant and every finished row template,
 * as one HTML file that opens in a browser with no WordPress involved.
 *
 * Why this exists: the row markup is generated inside an ACF loop on a real page, so the
 * only way to see the CSS was to have real content. This detaches the two - the markup
 * below is copied from the templates by hand and the stylesheets are the real build
 * output, so it shows exactly what a browser will do with them.
 *
 * It is a dev tool. It writes into ./dist, which is gitignored, and `tools/` is not in
 * the release packager's SHIP_DIRS, so none of it can reach a site.
 *
 *   node tools/fixture/build.js     then open dist/fixture.html
 */

const fs = require( 'fs' );
const path = require( 'path' );

const ROOT = path.resolve( __dirname, '../..' );
const OUT = path.join( ROOT, 'dist', 'fixture.html' );

/**
 * Kept in step by hand with Colour_Palette::choices(). A mismatch here is the fixture
 * lying about the editor, so check both when either moves.
 */
const BACKGROUNDS = [
	'default', 'white', 'light', 'lighter', 'accent-1', 'accent-2',
	'dark', 'darker', 'primary', 'secondary', 'tertiary',
];

// Card backgrounds are the same palette plus `custom`, which has no rule - it arrives as
// an inline --fl-card-box-bg on the section.
const CARD_BACKGROUNDS = [ ...BACKGROUNDS, 'custom' ];

const PADDINGS = [ 'default', 'sm', 'lg', 'top', 'bottom', 'top-sm', 'bottom-sm', 'none' ];
const COLUMNS = [ 1, 2, 3, 4, 5, 6, 7, 8 ];
const ALIGNMENTS = [ 'default', 'center', 'right' ];
const BUTTON_STYLES = [ 'primary', 'secondary', 'tertiary', 'white' ];

/**
 * The real build output, in the order the plugin enqueues it: the scoped Bootstrap, then
 * structure (which depends on it), then one sheet per finished row.
 */
function stylesheets() {
	const files = [
		'assets/css/rows-bootstrap.css',
		'assets/css/structure.css',
		...fs
			.readdirSync( path.join( ROOT, 'assets/css/rows' ) )
			.filter( ( f ) => f.endsWith( '.css' ) )
			.map( ( f ) => `assets/css/rows/${ f }` ),
	];

	return files
		.map( ( file ) => {
			const full = path.join( ROOT, file );

			if ( ! fs.existsSync( full ) ) {
				console.error( `  MISSING ${ file } - run npm run build first` );
				process.exit( 1 );
			}

			return `/* ---- ${ file } ---- */\n${ inlineSvgUrls(
				fs.readFileSync( full, 'utf8' )
			) }`;
		} )
		.join( '\n' );
}

/**
 * Rewrites `url(../../svg/name.svg)` to a data URI.
 *
 * A row sheet's SVG references are relative to the BUILT stylesheet - assets/css/rows/ up
 * to assets/svg/ - which is correct when a browser fetches that file. This fixture INLINES
 * the CSS into a <style>, and a relative url() in a style element resolves against the
 * DOCUMENT, so `../../svg/` climbs out of dist/ and lands on /svg/. The request 404s and
 * the icon is simply absent - no error, and every surrounding rule still applies, so it
 * reads as "the icon has no styling" rather than "the file is not there". accordions'
 * chevrons disappeared exactly this way.
 *
 * Inlining rather than repointing the path keeps the fixture self-contained, which is the
 * same reason its placeholder images are data URIs.
 */
function inlineSvgUrls( css ) {
	return css.replace(
		/url\(\s*["']?\.\.\/\.\.\/svg\/([\w-]+\.svg)["']?\s*\)/g,
		( match, name ) => {
			const file = path.join( ROOT, 'assets/svg', name );

			if ( ! fs.existsSync( file ) ) {
				console.error( `  MISSING assets/svg/${ name } - referenced by a row sheet` );
				process.exit( 1 );
			}

			return `url("data:image/svg+xml;utf8,${ encodeURIComponent(
				fs.readFileSync( file, 'utf8' )
			) }")`;
		}
	);
}

/** A placeholder image, inline so the fixture needs no network. */
const IMG = ( label ) =>
	'data:image/svg+xml;utf-8,' +
	encodeURIComponent(
		`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 400"><rect width="400" height="400" fill="#c8cfda"/><text x="200" y="210" font-family="sans-serif" font-size="34" fill="#48536b" text-anchor="middle">${ label }</text></svg>`
	);

/**
 * The wrapper classes Module::layout_wrapper_classes() would produce. Kept in the same
 * order as that method so a divergence is easy to spot by eye.
 */
function wrapperClasses( a ) {
	const classes = [ 'fl-section', `fl-bg-${ a.bg }`, `fl-type-${ a.layout }`, 'fl-item' ];

	if ( a.padding && a.padding !== 'default' ) classes.push( `fl-p-${ a.padding }` );
	if ( a.paddingXs && a.paddingXs !== 'default' ) classes.push( `fl-p-xs-${ a.paddingXs }` );
	if ( a.columns ) classes.push( `fl-loop-grid-columns-${ a.columns }` );
	if ( a.columnsSm ) classes.push( `fl-loop-grid-columns-sm-${ a.columnsSm }` );
	if ( a.columnsXs ) classes.push( `fl-loop-grid-columns-xs-${ a.columnsXs }` );
	if ( a.align ) classes.push( `fl-loop-grid-columns-align-${ a.align }` );
	if ( a.imageFit ) classes.push( `fl-image-fit-${ a.imageFit }` );

	// Content Box. The card colour rides on the section; the card itself is .fl-card,
	// which each item template emits - see Module::layout_wrapper_classes().
	if ( a.cardBg ) {
		classes.push( 'fl-card-box' );
		classes.push( `fl-card-bg-${ a.cardBg }` );
	}

	return classes.join( ' ' );
}

/** `custom` has no rule; the colour arrives inline, exactly as the module emits it. */
const wrapperStyle = ( a ) =>
	a.cardBg === 'custom' ? ' style="--fl-card-box-bg: #d8e6df"' : '';

/** templates/wrapper.php */
const section = ( attrs, inner ) =>
	`<section class="${ wrapperClasses( attrs ) }"${ wrapperStyle( attrs ) }>\n<div class="fl-container container">\n${ inner }\n</div>\n</section>`;

/**
 * templates/parts/intro.php
 *
 * ONE wysiwyg, not a title field plus a content field. section_title was removed and the
 * heading now lives inside section_content, so both arguments render into the same
 * .fl-intro-content - there is no .fl-intro-title any more. The two-argument signature is
 * kept because every caller below reads better for it, not because the fields are still
 * separate.
 */
const intro = ( heading, content ) =>
	! heading && ! content
		? ''
		: `<header class="fl-intro"><div class="fl-intro-content">${
				heading ? `<h2>${ heading }</h2>` : ''
		  }${ content ? `<p>${ content }</p>` : '' }</div></header>`;

/** templates/parts/buttons.php */
const buttons = ( styles = [ 'primary', 'secondary' ] ) =>
	`<div class="fl-buttons">${ styles
		.map( ( s, i ) =>
			`<a class="btn btn-${ i % 2 ? 'outline-' : '' }${ s }" href="#">${
				i % 2 ? 'Outline' : 'Solid'
			} ${ s }</a>`
		)
		.join( '' ) }</div>`;

/**
 * templates/rows/columned_content/item.php
 *
 * Replaces the old icon_leaders item, which this fixture used as its grid and alignment
 * demonstrator until that layout was removed. columned_content is the right stand-in: it
 * is the layout that took over the icon, and it carries the same full Grid & Display set.
 *
 * .fl-card is always present - the card styling hangs off `.fl-card-box .fl-card`, so the
 * switch belongs to the section, exactly as the real template has it.
 */
const column = ( n, align = 'default' ) =>
	`<li class="fl-column fl-card fl-align-${ align }"><span class="fl-column-media"><img class="fl-column-icon" src="${ IMG(
		'icon'
	) }" alt=""></span><div class="fl-column-content"><h3>Column ${ n }</h3><p>Supporting copy for item ${ n }.</p></div></li>`;

/** templates/rows/icon_list/item.php */
const iconListItem = ( n ) =>
	`<li class="fl-icon-list-item fl-card"><span class="fl-icon-list-media"><img class="fl-icon-list-icon" src="${ IMG(
		'icon'
	) }" alt=""></span><span class="fl-icon-list-content">Icon list entry ${ n }</span></li>`;

/** templates/rows/stats/item.php */
const statItem = ( n, align = 'default', withButtons = false ) =>
	`<li class="fl-stat fl-card fl-align-${ align }"><p class="fl-stat-figure"><span class="fl-stat-value">${
		n * 12
	}%</span><span class="fl-stat-subtitle">Subtitle ${ n }</span></p><div class="fl-stat-content"><p>What the figure means, in a sentence.</p></div>${
		withButtons ? buttons( [ 'primary' ] ) : ''
	}</li>`;

/** templates/rows/testimonials/item.php */
const testimonialItem = ( n, align = 'default' ) =>
	`<li class="fl-testimonial fl-card fl-align-${ align }"><figure class="fl-testimonial-figure"><blockquote class="fl-testimonial-quote"><p>A quotation from a satisfied person, number ${ n }.</p></blockquote><figcaption class="fl-testimonial-author"><span class="fl-testimonial-name">Author ${ n }</span><span class="fl-testimonial-role">Role, Organisation</span></figcaption></figure></li>`;

/** templates/rows/cta.php */
const cta = ( type = 'columns' ) =>
	`<div class="fl-cta fl-card fl-cta-${ type }"><div class="fl-cta-content"><p>A call to action, laid out ${ type }.</p></div>${ buttons() }</div>`;

/** templates/rows/contact_page_form.php */
const enquiry = () =>
	`<div class="fl-enquiry fl-card"><div class="fl-enquiry-content"><p>Tell us what you need.</p></div><div class="fl-enquiry-form"><p><em>[form plugin output]</em></p></div></div>`;

/**
 * templates/rows/accordions.php
 *
 * The fixture is static - it links the built CSS and runs no JS - so the row's script
 * never gets to open anything. The FIRST item is therefore rendered in its open state,
 * which is what the plugin's own JS would produce: aria-expanded="true" and no `hidden`
 * on the panel. Without that the fixture would show four headers and no answer, and the
 * open state - the brand-green title and the flipped chevron, which is most of what this
 * row's sheet actually does - would go unchecked.
 */
const accordions = ( id ) =>
	`<div class="fl-accordions" id="${ id }">${ [ 1, 2, 3 ]
		.map( ( n ) => {
			const open = 1 === n;

			return `<div class="fl-accordion-item"><h3 class="fl-accordion-heading"><button class="fl-accordion-trigger" type="button" id="${ id }-${ n }-label" aria-expanded="${ open }" aria-controls="${ id }-${ n }"><span class="fl-accordion-title">Question ${ n }${ open ? ' (open)' : '' }</span><span class="fl-accordion-icon" aria-hidden="true"></span></button></h3><div class="fl-accordion-panel" id="${ id }-${ n }" role="region" aria-labelledby="${ id }-${ n }-label"${ open ? '' : ' hidden' }><div class="fl-accordion-body"><p>The answer.</p></div></div></div>`;
		} )
		.join( '' ) }</div>`;

const grid = ( extraClass, items ) => `<ul class="fl-grid ${ extraClass }">\n${ items.join( '\n' ) }\n</ul>`;

function build() {
	const parts = [];
	const label = ( text ) =>
		parts.push( `</div><h2 class="fx-label">${ text }</h2><div class="acbs fl-acbs">` );

	label( 'Backgrounds &mdash; section_bg, the shared Colour_Palette' );
	for ( const bg of BACKGROUNDS ) {
		parts.push(
			section(
				{ layout: 'columned_content', bg, padding: 'sm' },
				intro( `fl-bg-${ bg }`, 'Body copy, a <a href="#">link</a>, and a heading, to show inherited colour.' )
			)
		);
	}

	label( 'Padding steps &mdash; vertical_padding, alternating ground so the box is visible' );
	PADDINGS.forEach( ( padding, i ) => {
		parts.push(
			section(
				{ layout: 'columned_content', bg: i % 2 ? 'light' : 'lighter', padding },
				intro( `fl-p-${ padding }`, '' )
			)
		);
	} );

	label( 'Grid columns &mdash; 1 through 8, on columned_content' );
	for ( const columns of COLUMNS ) {
		parts.push(
			section(
				{ layout: 'columned_content', bg: 'default', padding: 'sm', columns },
				intro( `${ columns } column${ columns > 1 ? 's' : '' }`, '' ) +
					grid( 'fl-columns', Array.from( { length: columns }, ( _, i ) => column( i + 1 ) ) )
			)
		);
	}

	label( 'Responsive columns &mdash; resize the window: 4 desktop / 3 tablet / 2 mobile' );
	parts.push(
		section(
			{ layout: 'columned_content', bg: 'light', padding: 'sm', columns: 4, columnsSm: 3, columnsXs: 2 },
			intro( 'columns 4 / sm 3 / xs 2', 'Explicit per-breakpoint values, not a derived step-down.' ) +
				grid( 'fl-columns', Array.from( { length: 4 }, ( _, i ) => column( i + 1 ) ) )
		)
	);

	label( 'Alignment &mdash; layout_columns_alignment' );
	for ( const align of ALIGNMENTS ) {
		parts.push(
			section(
				{ layout: 'columned_content', bg: 'default', padding: 'sm', columns: 3, align },
				intro( `align: ${ align }`, 'The intro stays centred whatever this is; the columns follow it.' ) +
					grid( 'fl-columns', [ column( 1 ), column( 2 ), column( 3 ) ] ) +
					buttons()
			)
		);
	}

	label( 'Cards on a REPEATER layout &mdash; one card per item, intro outside' );
	for ( const cardBg of CARD_BACKGROUNDS ) {
		parts.push(
			section(
				{ layout: 'icon_list', bg: 'lighter', padding: 'sm', columns: 3, cardBg },
				intro( `card: ${ cardBg }`, 'This intro must sit OUTSIDE the cards below.' ) +
					grid( 'fl-icon-list', [ 1, 2, 3 ].map( iconListItem ) )
			)
		);
	}

	label( 'Cards on a SINGLE-CONTENT layout &mdash; one card around the content' );
	for ( const cardBg of [ 'light', 'primary', 'custom' ] ) {
		parts.push(
			section(
				{ layout: 'cta', bg: 'default', padding: 'sm', cardBg },
				intro( `cta, card: ${ cardBg }`, 'The intro is outside the card.' ) + cta( 'columns' )
			)
		);
	}
	parts.push(
		section( { layout: 'cta', bg: 'default', padding: 'sm' }, intro( 'cta, no card', '' ) + cta( 'stacked' ) )
	);

	label( 'Buttons &mdash; button_style with button_outline off and on' );
	parts.push(
		section(
			{ layout: 'cta', bg: 'default', padding: 'sm' },
			intro( 'Bootstrap classes compiled from the brand palette', '' ) +
				buttons( BUTTON_STYLES ) +
				buttons( [ ...BUTTON_STYLES ].reverse() )
		)
	);

	label( 'stats &mdash; per-item alignment and per-item buttons' );
	parts.push(
		section(
			{ layout: 'stats', bg: 'default', padding: 'sm', columns: 3, cardBg: 'accent-1' },
			intro( 'Stats', '' ) +
				grid( 'fl-stats', [
					statItem( 1, 'default', true ),
					statItem( 2, 'center' ),
					statItem( 3, 'right' ),
				] )
		)
	);

	label( 'testimonials' );
	parts.push(
		section(
			{ layout: 'testimonials', bg: 'light', padding: 'sm', columns: 2, cardBg: 'default' },
			intro( 'Testimonials', 'Card set to Transparent: padding and rounding, no fill.' ) +
				// The slider wrapper the template emits whenever Content Box is Card. It is
				// inert here and drawn as a grid on purpose: every rule in the carousel half
				// of the row sheet is gated on `swiper-initialized`, which Swiper adds on
				// init, and the fixture runs no JS. So this section shows what a visitor with
				// JavaScript off sees at every width, which is the fallback worth having in
				// front of us - the carousel itself has to be checked in a browser.
				'<div class="fl-testimonials-slider" data-columns-sm="2" data-columns-xs="1">' +
				grid( 'fl-testimonials', [ testimonialItem( 1 ), testimonialItem( 2, 'center' ) ] ) +
				'</div>'
		)
	);

	label( 'accordions &mdash; own script and chevron masks, no Content Box on this layout. First item drawn open; the fixture runs no JS' );
	parts.push(
		section(
			{ layout: 'accordions', bg: 'default', padding: 'sm' },
			intro( 'Frequently asked', '' ) + accordions( 'fx-accordion' )
		)
	);

	label( 'contact_page_form' );
	parts.push(
		section(
			{ layout: 'contact_page_form', bg: 'lighter', padding: 'sm', cardBg: 'light' },
			intro( 'Enquiry', '' ) + enquiry()
		)
	);

	label( 'Edge cases' );
	parts.push(
		section(
			{ layout: 'icon_list', bg: 'default', padding: 'sm', columns: 3 },
			intro( 'Intro with no items', 'A row whose repeater is empty renders the intro and no list.' )
		)
	);
	parts.push(
		section(
			{ layout: 'columned_content', bg: 'default', padding: 'sm', columns: 3 },
			grid( 'fl-columns', [ column( 1 ), column( 2 ), column( 3 ) ] )
		)
	);

	const html = `<!doctype html>
<html lang="en-AU">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ACBS row fixture</title>
<style>
/* Build output, inlined. The fixture is deliberately one self-contained file: relative
   stylesheet paths do not resolve when it is opened through a preview pane or moved, and a
   fixture that silently loses its CSS is worse than no fixture. Order matches the enqueue
   dependency chain: Bootstrap, structure, then the row sheets. */
${ stylesheets() }
</style>
<style>
	/* Fixture chrome only. Nothing here ships, and nothing here is inside the wrapper. */
	body { margin: 0; font: 16px/1.5 system-ui, sans-serif; background: #e9edf3; color: #1c2434; }
	.fx-label { margin: 0; padding: 1rem 1.5rem; background: #1c2434; color: #fff; font-size: 0.95rem; font-weight: 600; position: sticky; top: 0; z-index: 5; }
	.fx-note { padding: 1.5rem; background: #fffbe6; border-bottom: 1px solid #e6dca8; }
	.fx-note code { background: rgba(0,0,0,0.06); padding: 0.1em 0.35em; }
</style>
</head>
<body>
<div class="fx-note">
	<strong>Static fixture.</strong> Markup copied from the row templates, stylesheets are the real
	build output. Everything below sits inside one <code>.acbs.fl-acbs</code> wrapper, exactly as
	<code>Renderer::wrap()</code> emits it &mdash; so this also proves the Bootstrap scope holds:
	this yellow box and the black labels are outside the wrapper and must stay unstyled by Bootstrap.
	Regenerate with <code>node tools/fixture/build.js</code>.
</div>
<div class="acbs fl-acbs">
${ parts.join( '\n\n' ) }
</div>
</body>
</html>
`;

	fs.mkdirSync( path.dirname( OUT ), { recursive: true } );
	fs.writeFileSync( OUT, html );

	console.log( `  Fixture written to ${ path.relative( ROOT, OUT ) }` );
	console.log( `  ${ parts.filter( ( p ) => p.startsWith( '<section' ) ).length } sections` );
}

build();

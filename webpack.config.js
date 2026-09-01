/**
 * Asset build for the ACF Components Block System.
 *
 * Sources live in ./src, compiled output lands in ./assets/js and ./assets/css.
 * Every module - and, from phase 04, every row - gets its own entry so its JS
 * and CSS stay independently enqueueable: Rows\Assets only enqueues the sheet
 * for a layout that is actually on the page, and bundling everything together
 * would throw that away.
 *
 * Only files under ./src are built. The hand-written scripts already sitting in
 * assets/js (the ACF admin helpers) are not part of this pipeline and are left
 * exactly as they are.
 *
 *   npm run dev        watch, development mode, source maps
 *   npm run build:dev  one-off development build
 *   npm run build      minified production build
 */

const fs = require( 'fs' );
const path = require( 'path' );
const MiniCssExtractPlugin = require( 'mini-css-extract-plugin' );
const prefixSelector = require( 'postcss-prefix-selector' );
const CssMinimizerPlugin = require( 'css-minimizer-webpack-plugin' );
const RemoveEmptyScriptsPlugin = require( 'webpack-remove-empty-scripts' );

/**
 * The single wrapper the renderer puts around every set of rows. Bootstrap is rewritten
 * to live behind this, and nothing else on the page is touched.
 *
 * Two classes, not one, so the scoped rules outweigh the theme's own unscoped Bootstrap
 * on specificity - the site's copy still cascades into our markup, and ours has to win.
 */
const SCOPE_SELECTOR = '.acbs.fl-acbs';

/**
 * Copies src/svg/*.svg to assets/svg, so the button icons ship with the plugin.
 *
 * Hand-written rather than copy-webpack-plugin: it is a flat directory copy of a handful
 * of files, and a dependency whose whole job is `readdir` + `writeFile` is a dependency to
 * keep updated for no gain. Emitting them as webpack ASSETS rather than writing to disk
 * directly is what puts them under output.path and inside the build's own accounting.
 *
 * The directory is cleaned on every build so a renamed or deleted icon does not leave a
 * stale file behind - Button_Icons::ICONS and this directory have to agree, and a
 * leftover file makes a broken choice look like it works.
 */
class CopySvgPlugin {
	constructor( from, to ) {
		this.from = from;
		this.to = to;
	}

	apply( compiler ) {
		compiler.hooks.thisCompilation.tap( 'CopySvgPlugin', ( compilation ) => {
			compilation.hooks.processAssets.tap(
				{
					name: 'CopySvgPlugin',
					stage: compiler.webpack.Compilation.PROCESS_ASSETS_STAGE_ADDITIONAL,
				},
				() => {
					if ( ! fs.existsSync( this.from ) ) {
						return;
					}

					for ( const file of fs.readdirSync( this.from ) ) {
						if ( ! file.endsWith( '.svg' ) ) {
							continue;
						}

						const source = fs.readFileSync( path.join( this.from, file ) );

						compilation.emitAsset(
							`${ this.to }/${ file }`,
							new compiler.webpack.sources.RawSource( source )
						);

						// Rebuild when one of them changes, so `npm run dev` picks up an
						// edited icon instead of watching only the Sass.
						compilation.fileDependencies.add( path.join( this.from, file ) );
					}

					compilation.contextDependencies.add( this.from );
				}
			);
		} );
	}
}

/**
 * Selectors that describe the document rather than an element inside it. Prefixing them
 * as descendants would produce '.acbs.fl-acbs body', which matches nothing - so they are
 * collapsed onto the wrapper itself, which is the nearest thing our subtree has to a root.
 * That is what keeps Bootstrap's --bs-* custom properties and its base typography alive.
 */
const ROOT_SELECTORS = [ ':root', 'html', 'body', ':host' ];

function rowEntries() {
	const dir = path.resolve( __dirname, 'src/css/rows' );

	if ( ! fs.existsSync( dir ) ) {
		return {};
	}

	return Object.fromEntries(
		fs
			.readdirSync( dir )
			.filter( ( file ) => /\.(sa|sc)ss$/.test( file ) && ! file.startsWith( '_' ) )
			.map( ( file ) => [
				`css/rows/${ path.parse( file ).name }`,
				`./src/css/rows/${ file }`,
			] )
	);
}

module.exports = ( env, argv ) => {
	const isProduction = 'production' === argv.mode;

	return {
		entry: {
			// Bootstrap, confined to the row wrapper by SCOPE_SELECTOR below.
			'css/rows-bootstrap': './src/css/rows-bootstrap.scss',

			// The plugin's own always-loaded row structure: section, container,
			// backgrounds, padding steps, grid and intro.
			'css/structure': './src/css/structure.scss',

			// One entry per row stylesheet, discovered rather than listed, so adding a
			// layout's CSS is one new file and no config change. Output paths line up
			// with what Rows\Assets looks for: assets/css/rows/{layout}.css.
			...rowEntries(),
		},

		output: {
			path: path.resolve( __dirname, 'assets' ),
			filename: '[name].js',
			// Global clean stays off - assets/js holds hand-written admin scripts that are
			// not part of this build and must survive it. The row stylesheets ARE fully
			// generated, though, so that one directory is cleaned: without it, deleting
			// src/css/rows/{layout}.scss leaves its compiled CSS behind and the release
			// packager happily ships a sheet for a layout that no longer exists.
			clean: {
				keep: ( asset ) =>
					! asset.startsWith( 'css/rows/' ) && ! asset.startsWith( 'svg/' ),
			},
		},

		// WordPress already enqueues jQuery. Bundling a second copy would be
		// both wasteful and a source of subtle plugin conflicts.
		externals: {
			jquery: 'jQuery',
		},

		module: {
			rules: [
				{
					test: /\.jsx?$/,
					exclude: /node_modules/,
					use: {
						loader: 'babel-loader',
						options: {
							presets: [
								[
									'@babel/preset-env',
									{
										targets: '> 0.25%, last 3 versions, not dead',
									},
								],
							],
						},
					},
				},
				{
					test: /\.(sa|sc|c)ss$/,
					use: [
						MiniCssExtractPlugin.loader,
						{
							loader: 'css-loader',
							options: {
								// Data URIs and theme-relative paths are meant
								// to be emitted verbatim, not resolved.
								url: false,
								importLoaders: 2,
								sourceMap: ! isProduction,
							},
						},
						{
							loader: 'postcss-loader',
							options: {
								postcssOptions: ( loaderContext ) => {
									const plugins = [ [ 'postcss-preset-env', { stage: 3 } ] ];

									// Only the Bootstrap entry gets scoped. structure.scss and
									// the row sheets are ours and already write their own
									// selectors, so prefixing them would double the scope.
									if ( /rows-bootstrap\.scss$/.test( loaderContext.resourcePath ) ) {
										plugins.push(
											prefixSelector( {
												prefix: SCOPE_SELECTOR,
												transform( prefix, selector, prefixedSelector ) {
													const trimmed = selector.trim();

													// IDEMPOTENCE, and not optional. postcss can
													// re-visit a rule whose selector a plugin has
													// just rewritten, and this transform then sees
													// its own output as input - which produced
													// '.acbs.fl-acbs .acbs.fl-acbs mark' in 105
													// selectors before this guard existed. Those
													// match nothing, and nothing errors: the
													// container gutters and all of reboot's element
													// styling simply went missing.
													if ( trimmed === prefix || trimmed.startsWith( `${ prefix } ` ) ) {
														return selector;
													}

													// Document-level selectors collapse onto the
													// wrapper - see ROOT_SELECTORS.
													if ( ROOT_SELECTORS.includes( trimmed ) ) {
														return prefix;
													}

													// '*' and its pseudo-element forms are the
													// box-sizing reset; as descendants they are
													// correct, but the wrapper itself needs it too.
													if ( trimmed.startsWith( '*' ) ) {
														return `${ prefixedSelector }, ${ prefix }`;
													}

													return prefixedSelector;
												},
											} )
										);
									}

									return { plugins };
								},
								sourceMap: ! isProduction,
							},
						},
						{
							loader: 'sass-loader',
							options: {
								sourceMap: ! isProduction,
							},
						},
					],
				},
			],
		},

		plugins: [
			// The CSS-only entries would otherwise each emit an empty .js file.
			new RemoveEmptyScriptsPlugin(),
			new MiniCssExtractPlugin( {
				filename: '[name].css',
			} ),
			new CopySvgPlugin( path.resolve( __dirname, 'src/svg' ), 'svg' ),
		],

		optimization: {
			minimizer: [
				'...',
				new CssMinimizerPlugin( {
					minimizerOptions: {
						preset: [
							'default',
							{
								// Bootstrap embeds its form-control indicators as URL-encoded
								// inline SVG data URIs. cssnano's svgo pass cannot parse the
								// percent-encoded form and emits a warning per occurrence - 26
								// of them - while leaving the value untouched. Turning the pass
								// off removes the noise without changing a byte of output.
								svgo: false,
							},
						],
					},
				} ),
			],
		},

		devtool: isProduction ? false : 'source-map',

		performance: {
			hints: false,
		},

		stats: 'minimal',
	};
};

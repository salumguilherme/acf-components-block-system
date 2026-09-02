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
const SCOPE_SELECTOR = '.acbs';

/**
 * Copies a flat directory of files into the build output.
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
class CopyFilesPlugin {
	constructor( from, to, match = () => true ) {
		this.from = from;
		this.to = to;
		this.match = match;
	}

	apply( compiler ) {
		compiler.hooks.thisCompilation.tap( 'CopyFilesPlugin', ( compilation ) => {
			compilation.hooks.processAssets.tap(
				{
					name: 'CopyFilesPlugin',
					stage: compiler.webpack.Compilation.PROCESS_ASSETS_STAGE_ADDITIONAL,
				},
				() => {
					if ( ! fs.existsSync( this.from ) ) {
						return;
					}

					for ( const file of fs.readdirSync( this.from ) ) {
						if ( ! this.match( file ) ) {
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

/**
 * One entry per file in a row directory, discovered rather than listed, so adding a
 * layout's CSS or JS is one new file and no config change.
 *
 * Output paths line up with what Rows\Assets looks for - assets/css/rows/{layout}.css
 * and assets/js/rows/{layout}.js - because that class derives both from the layout name
 * and checks the file exists before registering a handle. A file here and a layout of
 * that name are the whole contract; there is no list to keep in step.
 */
function rowEntries( kind, test ) {
	const dir = path.resolve( __dirname, `src/${ kind }/rows` );

	if ( ! fs.existsSync( dir ) ) {
		return {};
	}

	return Object.fromEntries(
		fs
			.readdirSync( dir )
			.filter( ( file ) => test.test( file ) && ! file.startsWith( '_' ) )
			.map( ( file ) => [
				`${ kind }/rows/${ path.parse( file ).name }`,
				`./src/${ kind }/rows/${ file }`,
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

			// One entry per row stylesheet - see rowEntries().
			...rowEntries( 'css', /\.(sa|sc)ss$/ ),

			// The row runtime: the action bus and the code that announces each row on the
			// page. Always enqueued when any row renders, and every per-row script
			// depends on it.
			'js/rows': './src/js/rows.js',

			// One entry per row script, same discovery rule as the sheets above.
			...rowEntries( 'js', /\.jsx?$/ ),
		},

		output: {
			path: path.resolve( __dirname, 'assets' ),
			filename: '[name].js',
			// Global clean stays off - assets/js holds hand-written admin scripts that are
			// not part of this build and must survive it. The row directories ARE fully
			// generated, though, so those are cleaned: without it, deleting
			// src/css/rows/{layout}.scss leaves its compiled CSS behind and the release
			// packager happily ships assets for a layout that no longer exists.
			//
			// Note how narrow this is. `js/rows/` is cleaned and `js/` is not, because
			// the two directories have different owners: everything under js/rows/ comes
			// out of this build, while assets/js/*.js is hand-written and predates it.
			clean: {
				keep: ( asset ) =>
					! asset.startsWith( 'css/rows/' ) &&
					! asset.startsWith( 'js/rows/' ) &&
					! asset.startsWith( 'svg/' ),
			},
		},

		// WordPress already enqueues jQuery. Bundling a second copy would be
		// both wasteful and a source of subtle plugin conflicts.
		externals: {
			jquery: 'jQuery',
		},

		module: {
			rules: [
				// Row and runtime JS. `browserslist` is not set anywhere in this repo, so
				// the target list is stated here rather than inherited from a default that
				// would change under us on a dependency bump.
				//
				// The three babel packages are all real devDependencies as of 02/09/2026.
				// They were not, for a while: this rule existed while the pipeline had no
				// JS entries at all, so nothing ever exercised it, and the first row script
				// turned a config that had always "worked" into a hard build failure. If
				// this rule is ever removed again, remove the packages with it - a loader
				// named in config and absent from package.json is a build that breaks for
				// the next person rather than for whoever wrote it.
				//
				// Worth knowing before anyone debugs it: against this target list babel is
				// currently a NO-OP. Every browser it resolves to in 2026 supports the
				// syntax the row scripts use, so the output is byte-identical to the input
				// and webpack's compareBeforeEmit then skips the write entirely, leaving
				// the built files with an old mtime. That is correct, not a broken loader -
				// verified by pinning `targets: 'ie 11'`, which does transpile.
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
			new CopyFilesPlugin( path.resolve( __dirname, 'src/svg' ), 'svg', ( f ) => f.endsWith( '.svg' ) ),
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

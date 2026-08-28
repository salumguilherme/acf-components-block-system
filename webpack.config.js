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

const path = require( 'path' );
const MiniCssExtractPlugin = require( 'mini-css-extract-plugin' );
const CssMinimizerPlugin = require( 'css-minimizer-webpack-plugin' );
const RemoveEmptyScriptsPlugin = require( 'webpack-remove-empty-scripts' );

module.exports = ( env, argv ) => {
	const isProduction = 'production' === argv.mode;

	return {
		entry: {
			// Plugin-wide frontend stylesheet. Phase 04 splits this into
			// css/structure plus one css/rows/{layout} entry per row.
			'css/frontend': './src/css/frontend.sass',
		},

		output: {
			path: path.resolve( __dirname, 'assets' ),
			filename: '[name].js',
			clean: false,
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
								postcssOptions: {
									plugins: [ [ 'postcss-preset-env', { stage: 3 } ] ],
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
		],

		optimization: {
			minimizer: [ '...', new CssMinimizerPlugin() ],
		},

		devtool: isProduction ? false : 'source-map',

		performance: {
			hints: false,
		},

		stats: 'minimal',
	};
};

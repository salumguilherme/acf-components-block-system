#!/usr/bin/env node
/**
 * ERDC release packager.
 *
 *   npm run release            bumps the patch number
 *   npm run release -- minor   bumps the minor number (also: major, patch)
 *   npm run release -- 1.2.0   sets an explicit version
 *   npm run release -- --no-bump   keeps the current version
 *
 * Runs the production webpack build, writes the new version into the plugin
 * header, ACBS_VERSION, readme.txt and package.json, then stages only the
 * files the plugin actually ships and zips them into ./dist.
 */

const fs = require( 'fs' );
const path = require( 'path' );
const { execFileSync } = require( 'child_process' );

const ROOT = path.resolve( __dirname, '..' );
const SLUG = 'acf-components-block-system';
const MAIN_FILE = `${ SLUG }.php`;

// Everything the shipped plugin needs, and nothing else.
const SHIP_DIRS = [ 'assets', 'modules', 'core', 'templates', 'vendor' ];
const SHIP_FILES = [ MAIN_FILE, 'plugin.php', 'readme.txt' ];

// Cruft that lives inside the shipped folders but must not be packaged.
const EXCLUDE = [ '.DS_Store', '*.map', '.git*', 'node_modules/', '*.log', 'src', 'CLAUDE.md', 'bin/', 'bin', '.idea*', 'package.json', 'webpack.config.js'];

const read = ( file ) => fs.readFileSync( path.join( ROOT, file ), 'utf8' );
const write = ( file, body ) => fs.writeFileSync( path.join( ROOT, file ), body );
const log = ( msg ) => console.log( msg );

function fail( msg ) {
	console.error( `\n  Release aborted: ${ msg }\n` );
	process.exit( 1 );
}

/** Current version, taken from the plugin header - the single source of truth. */
function currentVersion() {
	const match = read( MAIN_FILE ).match( /^\s*\*\s*Version:\s*(\d+\.\d+\.\d+)\s*$/m );
	if ( ! match ) {
		fail( `could not read the Version header from ${ MAIN_FILE }` );
	}
	return match[ 1 ];
}

function nextVersion( current, arg ) {
	if ( ! arg || arg === '--no-bump' ) {
		return arg === '--no-bump' ? current : nextVersion( current, 'patch' );
	}
	if ( /^\d+\.\d+\.\d+$/.test( arg ) ) {
		return arg;
	}
	const [ major, minor, patch ] = current.split( '.' ).map( Number );
	switch ( arg ) {
		case 'major': return `${ major + 1 }.0.0`;
		case 'minor': return `${ major }.${ minor + 1 }.0`;
		case 'patch': return `${ major }.${ minor }.${ patch + 1 }`;
		default: return fail( `unrecognised version argument "${ arg }" - use major, minor, patch, --no-bump or an explicit x.y.z` );
	}
}

/**
 * Replace once and only once. A silent no-op here would ship a plugin whose
 * header and constant disagree, so a missed pattern is a hard failure.
 */
function replaceOnce( file, body, pattern, replacement, label ) {
	const matches = body.match( new RegExp( pattern.source, pattern.flags.replace( 'g', '' ) + 'g' ) );
	if ( ! matches || matches.length !== 1 ) {
		fail( `expected exactly one ${ label } in ${ file }, found ${ matches ? matches.length : 0 }` );
	}
	return body.replace( pattern, replacement );
}

function setVersion( version ) {
	let main = read( MAIN_FILE );
	main = replaceOnce( MAIN_FILE, main, /^(\s*\*\s*Version:\s*)\d+\.\d+\.\d+\s*$/m, `$1${ version }`, 'Version header' );
	main = replaceOnce( MAIN_FILE, main, /(define\(\s*'ACBS_VERSION'\s*,\s*')\d+\.\d+\.\d+(')/, `$1${ version }$2`, 'ACBS_VERSION define' );
	write( MAIN_FILE, main );

	const readme = read( 'readme.txt' );
	write( 'readme.txt', replaceOnce( 'readme.txt', readme, /^(Stable tag:\s*)\S+\s*$/m, `$1${ version }`, 'Stable tag' ) );

	const pkg = JSON.parse( read( 'package.json' ) );
	pkg.version = version;
	write( 'package.json', JSON.stringify( pkg, null, 2 ) + '\n' );

	log( `  Version set to ${ version } in ${ MAIN_FILE } (header + ACBS_VERSION), readme.txt and package.json` );
}

function run( command, args ) {
	execFileSync( command, args, { cwd: ROOT, stdio: 'inherit' } );
}

function build() {
	log( '\n  Building assets (npm run build)...\n' );
	run( process.platform === 'win32' ? 'npm.cmd' : 'npm', [ 'run', 'build' ] );
}

function pack( version ) {
	const dist = path.join( ROOT, 'dist' );
	const stage = path.join( dist, SLUG );
	const zipPath = path.join( dist, `${ SLUG }.zip` );

	fs.rmSync( stage, { recursive: true, force: true } );
	fs.mkdirSync( stage, { recursive: true } );

	for ( const entry of [ ...SHIP_DIRS, ...SHIP_FILES ] ) {
		if ( ! fs.existsSync( path.join( ROOT, entry ) ) ) {
			fail( `required plugin path "${ entry }" is missing` );
		}
	}

	const excludeArgs = EXCLUDE.flatMap( ( pattern ) => [ '--exclude', pattern ] );
	// Trailing slash on the source copies the folder's contents into a folder
	// of the same name inside the staging directory.
	run( 'rsync', [ '-a', ...excludeArgs, ...SHIP_DIRS.map( ( d ) => `${ d }/` ).map( ( d, i ) => d ), stage ].flat() );

	process.exit( 0 );
}

function main() {
	const arg = process.argv[ 2 ];
	const current = currentVersion();
	const version = nextVersion( current, arg );

	log( `\n  ERDC release  ${ current } -> ${ version }` );

	build();
	setVersion( version );
	pack( version );
}

main();

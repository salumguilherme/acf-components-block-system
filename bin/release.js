#!/usr/bin/env node
/**
 * ACBS release packager.
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

// The working copy is still the forked ERDC directory, and renaming it in place would
// mean renaming the plugin WordPress currently has active on every dev install. So the
// rename happens on the way out instead: the source keeps its old name, the package gets
// the new one.
//
// This is safe for sites already running the old folder. The update checker renames the
// extracted directory to match the INSTALLED one before WordPress copies it into place
// (Puc\v5p6\UpdateChecker::fixDirectoryName), so an existing install updates inside its
// current folder rather than gaining a second directory. Only a fresh manual install of
// the zip gets the new name.
const SOURCE_SLUG = 'elementor-repeater-and-dynamic-conditions-addon';
const SOURCE_MAIN_FILE = `${ SOURCE_SLUG }.php`;

const SLUG = 'acf-components-block-system';
const MAIN_FILE = `${ SLUG }.php`;

// Everything the shipped plugin needs, and nothing else. The main file is handled
// separately, because it is the one file whose name changes between source and package.
const SHIP_DIRS = [ 'assets', 'modules', 'core', 'templates', 'vendor' ];
const SHIP_FILES = [ 'plugin.php', 'readme.txt' ];

// Cruft that lives inside the shipped folders but must not be packaged.
const EXCLUDE = [ '.DS_Store', '*.map', '.git*', 'node_modules/', '*.log', 'src', 'CLAUDE.md', 'bin/', 'bin', '.idea*', 'package.json', 'package-lock.json', 'webpack.config.js'];

const read = ( file ) => fs.readFileSync( path.join( ROOT, file ), 'utf8' );
const write = ( file, body ) => fs.writeFileSync( path.join( ROOT, file ), body );
const log = ( msg ) => console.log( msg );

function fail( msg ) {
	console.error( `\n  Release aborted: ${ msg }\n` );
	process.exit( 1 );
}

/** Current version, taken from the plugin header - the single source of truth. */
function currentVersion() {
	const match = read( SOURCE_MAIN_FILE ).match( /^\s*\*\s*Version:\s*(\d+\.\d+\.\d+)\s*$/m );
	if ( ! match ) {
		fail( `could not read the Version header from ${ SOURCE_MAIN_FILE }` );
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
	let main = read( SOURCE_MAIN_FILE );
	main = replaceOnce( SOURCE_MAIN_FILE, main, /^(\s*\*\s*Version:\s*)\d+\.\d+\.\d+\s*$/m, `$1${ version }`, 'Version header' );
	main = replaceOnce( SOURCE_MAIN_FILE, main, /(define\(\s*'ACBS_VERSION'\s*,\s*')\d+\.\d+\.\d+(')/, `$1${ version }$2`, 'ACBS_VERSION define' );
	write( SOURCE_MAIN_FILE, main );

	const readme = read( 'readme.txt' );
	write( 'readme.txt', replaceOnce( 'readme.txt', readme, /^(Stable tag:\s*)\S+\s*$/m, `$1${ version }`, 'Stable tag' ) );

	const pkg = JSON.parse( read( 'package.json' ) );
	pkg.version = version;
	write( 'package.json', JSON.stringify( pkg, null, 2 ) + '\n' );

	log( `  Version set to ${ version } in ${ SOURCE_MAIN_FILE } (header + ACBS_VERSION), readme.txt and package.json` );
}

function run( command, args, cwd = ROOT ) {
	execFileSync( command, args, { cwd, stdio: 'inherit' } );
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

	for ( const entry of [ ...SHIP_DIRS, ...SHIP_FILES, SOURCE_MAIN_FILE ] ) {
		if ( ! fs.existsSync( path.join( ROOT, entry ) ) ) {
			fail( `required plugin path "${ entry }" is missing` );
		}
	}

	const excludeArgs = EXCLUDE.flatMap( ( pattern ) => [ '--exclude', pattern ] );

	// NO trailing slash on the sources. `rsync -a assets/ dest` copies the CONTENTS of
	// assets into dest; `rsync -a assets dest` copies the folder itself. With the slash,
	// every ship directory lands flattened into the staging root and the package is
	// unusable - modules/, core/ and templates/ all disappear as directories.
	run( 'rsync', [ '-a', ...excludeArgs, ...SHIP_DIRS, stage ] );

	for ( const file of SHIP_FILES ) {
		fs.copyFileSync( path.join( ROOT, file ), path.join( stage, file ) );
	}

	// The one rename: the main file takes the shipped slug's name. WordPress identifies a
	// plugin as "directory/main-file.php", so the folder and the file have to move
	// together or the plugin's identity is half-changed.
	fs.copyFileSync( path.join( ROOT, SOURCE_MAIN_FILE ), path.join( stage, MAIN_FILE ) );

	verifyStage( stage, version );

	fs.rmSync( zipPath, { force: true } );
	// Zipped from dist, so the archive contains a single top-level SLUG directory - which
	// is what both WordPress's installer and the update checker expect to find.
	run( 'zip', [ '-rq', `${ SLUG }.zip`, SLUG, '-x', '*.DS_Store' ], dist );

	log( `\n  Staged  ${ path.relative( ROOT, stage ) }` );
	log( `  Packaged ${ path.relative( ROOT, zipPath ) }` );
	log( `\n  Plugin folder and main file renamed ${ SOURCE_SLUG } -> ${ SLUG }\n` );
}

/**
 * Last line of defence before the zip. Each of these has a specific failure it is
 * catching, rather than being a general "does it look right".
 */
function verifyStage( stage, version ) {
	// The rename actually happened, and the old name did not tag along - two main files
	// in one plugin folder means WordPress lists the plugin twice.
	if ( ! fs.existsSync( path.join( stage, MAIN_FILE ) ) ) {
		fail( `the staged package has no ${ MAIN_FILE }` );
	}
	if ( fs.existsSync( path.join( stage, SOURCE_MAIN_FILE ) ) ) {
		fail( `the staged package still contains ${ SOURCE_MAIN_FILE } as well as ${ MAIN_FILE }` );
	}

	// rsync copied folders rather than flattening their contents.
	for ( const dir of SHIP_DIRS ) {
		if ( ! fs.statSync( path.join( stage, dir ), { throwIfNoEntry: false } )?.isDirectory() ) {
			fail( `the staged package is missing the "${ dir }" directory - check the rsync source arguments` );
		}
	}

	// The staged header is the version we just wrote, so a stale copy cannot be shipped.
	const header = fs.readFileSync( path.join( stage, MAIN_FILE ), 'utf8' );
	if ( ! new RegExp( `^\\s*\\*\\s*Version:\\s*${ version.replace( /\./g, '\\.' ) }\\s*$`, 'm' ).test( header ) ) {
		fail( `the staged ${ MAIN_FILE } does not declare version ${ version }` );
	}

	// The update checker resolves its own file and slug from ACBS__FILE__. If anything
	// hardcodes the source name again, the released build points at a file that is not
	// there and updates stop working silently.
	const bootstrap = fs.readFileSync( path.join( stage, 'plugin.php' ), 'utf8' );
	if ( bootstrap.includes( SOURCE_MAIN_FILE ) ) {
		fail( `plugin.php hardcodes ${ SOURCE_MAIN_FILE }; it must derive the path from ACBS__FILE__ so the rename holds` );
	}
}

function main() {
	const arg = process.argv[ 2 ];
	const current = currentVersion();
	const version = nextVersion( current, arg );

	log( `\n  ACBS release  ${ current } -> ${ version }` );

	build();
	setVersion( version );
	pack( version );
}

main();

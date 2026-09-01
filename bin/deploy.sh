#!/usr/bin/env bash
#
# Push the built plugin to a remote WordPress install over rsync/SSH.
#
# This is the fast path for iterating on a staging site: build, then send only the bytes
# that actually changed. It replaces "npm run package, download the zip, upload it through
# wp-admin", which reinstalls the whole plugin every time - and which, on a plugin whose
# update path once deleted the entire directory, is a heavier hammer than a CSS change
# deserves.
#
# NO SITE DETAILS LIVE IN THIS REPO. Everything host-specific is read from environment
# variables, loaded from a config file OUTSIDE the working copy:
#
#     ~/.config/acbs/deploy.env          (default; override with ACBS_DEPLOY_ENV)
#
# Required:
#   ACBS_DEPLOY_TARGET    user@host:/abs/path/to/wp-content/plugins/<plugin-folder>
#
# Optional:
#   ACBS_DEPLOY_SSH_OPTS  extra ssh flags, e.g. "-p 2222 -i ~/.ssh/id_staging"
#   ACBS_DEPLOY_AFTER     a command to run on the server after a successful sync, e.g. a
#                         cache purge. Left empty by default: this script does not guess
#                         at your host's purge command.
#   ACBS_DEPLOY_MAIN_FILE the main plugin file's name ON THE SERVER. Defaults to the
#                         packaged name, because bin/release.js renames it on the way out.
#
# Usage:
#   bin/deploy.sh                 build, then sync
#   bin/deploy.sh -n              dry run: show what WOULD change, send nothing
#   bin/deploy.sh --no-build      sync what is already in assets/, skip webpack
#   bin/deploy.sh --delete        also remove remote files that no longer exist locally
#   bin/deploy.sh --main          include the main plugin file (only needed on a version bump)
#
# --delete is opt-in on purpose. Manual uploads cannot delete, which is how a removed
# template once survived on a server and fataled the page; --delete fixes that, but it is
# also the one flag here that can remove something you meant to keep. Run it with -n first.
#

set -euo pipefail

ROOT="$( cd "$( dirname "${BASH_SOURCE[0]}" )/.." && pwd )"
cd "$ROOT"

BUILD=1
DRY=0
DELETE=0
MAIN=0

for arg in "$@"; do
	case "$arg" in
		-n|--dry-run) DRY=1 ;;
		--no-build)   BUILD=0 ;;
		--delete)     DELETE=1 ;;
		--main)       MAIN=1 ;;
		-h|--help)    sed -n '2,40p' "$0" | sed 's/^# \{0,1\}//'; exit 0 ;;
		*) echo "  Unknown option: $arg  (try --help)" >&2; exit 2 ;;
	esac
done

# ---------------------------------------------------------------------------------------
# Config, from outside the repo
# ---------------------------------------------------------------------------------------

ENV_FILE="${ACBS_DEPLOY_ENV:-$HOME/.config/acbs/deploy.env}"

if [ -f "$ENV_FILE" ]; then
	# shellcheck disable=SC1090
	set -a; . "$ENV_FILE"; set +a
fi

if [ -z "${ACBS_DEPLOY_TARGET:-}" ]; then
	cat >&2 <<EOF

  No deploy target configured.

  Create $ENV_FILE with:

      ACBS_DEPLOY_TARGET="user@host:/path/to/wp-content/plugins/acf-components-block-system"

  Optionally also ACBS_DEPLOY_SSH_OPTS and ACBS_DEPLOY_AFTER. See bin/deploy.sh --help.

EOF
	exit 1
fi

SSH_OPTS="${ACBS_DEPLOY_SSH_OPTS:-}"
MAIN_FILE_REMOTE="${ACBS_DEPLOY_MAIN_FILE:-acf-components-block-system.php}"
MAIN_FILE_LOCAL="elementor-repeater-and-dynamic-conditions-addon.php"

# Reuse one SSH connection across the rsync calls and the post-command, so a deploy is one
# handshake rather than three.
#
# The control socket is a Unix domain socket, and sun_path caps out at 104 bytes on macOS
# (108 on Linux) - a hard kernel limit, not a preference. $TMPDIR on macOS is a ~49-char
# per-user path, %C expands to a 40-char hash, and ssh appends ~18 more for the temporary
# socket it binds while connecting: 120 bytes, and the connection dies with
# "path too long for Unix domain socket" and an unhelpful rsync EOF behind it.
#
# So the socket goes in ~/.ssh/cm (short, user-owned, and the usual convention) and the
# length is checked before use. If it still will not fit, multiplexing is dropped rather
# than the deploy failing - it is an optimisation, and a slower deploy beats a broken one.
CTL_DIR="$HOME/.ssh/cm"
CTL="$CTL_DIR/a-%C"
# ${#CTL} counts the literal "%C"; the socket gets 40 chars there instead, plus ssh's suffix.
CTL_LEN=$(( ${#CTL} - 2 + 40 + 18 ))

if mkdir -p "$CTL_DIR" 2>/dev/null && [ "$CTL_LEN" -lt 104 ]; then
	chmod 700 "$CTL_DIR" 2>/dev/null || true
	SSH_CMD="ssh -o ControlMaster=auto -o ControlPath=$CTL -o ControlPersist=30s $SSH_OPTS"
else
	SSH_CMD="ssh $SSH_OPTS"
fi

# ---------------------------------------------------------------------------------------
# What ships
#
# Kept deliberately in step with bin/release.js. If SHIP_DIRS or EXCLUDE changes there,
# change it here too - a file that ships in the zip but not over rsync (or the reverse) is
# a difference between staging and a release that nobody will think to look for.
# ---------------------------------------------------------------------------------------

SHIP_DIRS=( assets modules core templates vendor )
SHIP_FILES=( plugin.php readme.txt )

EXCLUDES=(
	--exclude '.DS_Store'
	--exclude '*.map'
	--exclude '.git*'
	--exclude 'node_modules/'
	--exclude '*.log'
	--exclude 'src'
	--exclude 'CLAUDE.md'
	--exclude 'bin'
	--exclude '.idea*'
	--exclude '.claude'
	--exclude 'package.json'
	--exclude 'package-lock.json'
	--exclude 'webpack.config.js'
)

# ---------------------------------------------------------------------------------------

if [ "$BUILD" = "1" ]; then
	echo
	echo "  Building (npm run build)..."
	npm run build --silent
fi

for entry in "${SHIP_DIRS[@]}" "${SHIP_FILES[@]}"; do
	[ -e "$ROOT/$entry" ] || { echo "  Missing required path: $entry" >&2; exit 1; }
done

RSYNC_OPTS=( -rlptz --itemize-changes --human-readable )
[ "$DRY" = "1" ]    && RSYNC_OPTS+=( --dry-run )
[ "$DELETE" = "1" ] && RSYNC_OPTS+=( --delete )

echo
echo "  Target:  $ACBS_DEPLOY_TARGET"
echo "  Sending: ${SHIP_DIRS[*]} ${SHIP_FILES[*]}$( [ "$MAIN" = "1" ] && echo " $MAIN_FILE_REMOTE" )"
[ "$DRY" = "1" ]    && echo "  DRY RUN - nothing will be written"
[ "$DELETE" = "1" ] && echo "  --delete is ON: remote files missing locally will be removed"
echo

# Trailing slashes matter: "assets" (no slash) sends the directory itself, which is what
# puts assets/ at the target rather than its contents.
rsync "${RSYNC_OPTS[@]}" "${EXCLUDES[@]}" -e "$SSH_CMD" \
	"${SHIP_DIRS[@]}" "${SHIP_FILES[@]}" \
	"$ACBS_DEPLOY_TARGET/"

# The main file is the one path whose NAME differs between the working copy and an
# installed plugin, so it cannot ride along with the rest. It only changes on a version
# bump, hence opt-in.
if [ "$MAIN" = "1" ]; then
	echo
	rsync "${RSYNC_OPTS[@]}" -e "$SSH_CMD" \
		"$MAIN_FILE_LOCAL" \
		"$ACBS_DEPLOY_TARGET/$MAIN_FILE_REMOTE"
fi

if [ "$DRY" = "1" ]; then
	echo
	echo "  Dry run complete. Re-run without -n to send."
	exit 0
fi

if [ -n "${ACBS_DEPLOY_AFTER:-}" ]; then
	HOST="${ACBS_DEPLOY_TARGET%%:*}"
	echo
	echo "  Running post-deploy command on $HOST..."
	# shellcheck disable=SC2086
	$SSH_CMD "$HOST" "${ACBS_DEPLOY_AFTER}" || echo "  (post-deploy command failed - files were still sent)"
fi

echo
echo "  Done."
echo

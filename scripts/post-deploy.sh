#!/usr/bin/env bash
#
# post-deploy.sh - runs on the cPanel server after "Deploy HEAD Commit".
# Called by .cpanel.yml. Never deletes or moves anything outside the app.
#
# Needs (checked, aborts loudly if missing):
#   - PHP 8.2+ at one of the known ea-php paths
#   - vendor/autoload.php   (only on the `release` branch, which ships vendor/)
#   - .env                  (created by hand on the server, never in git)

set -eu

APP="/home/scaninca/repositories/signin_ca"
DOCROOT="/home/scaninca/public_html"

cd "$APP"
mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions \
         storage/framework/views storage/logs bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache

exec >>"$APP/storage/logs/deploy.log" 2>&1
echo
echo "=== deploy $(date '+%Y-%m-%d %H:%M:%S') commit $(git rev-parse --short HEAD 2>/dev/null || echo '?') ==="

fail() { echo "DEPLOY ABORTED: $*"; exit 1; }

PHP=""
for candidate in \
    /opt/cpanel/ea-php82/root/usr/bin/php \
    /opt/cpanel/ea-php83/root/usr/bin/php \
    /opt/cpanel/ea-php84/root/usr/bin/php \
    /usr/local/bin/ea-php82; do
  if [ -x "$candidate" ]; then PHP="$candidate"; break; fi
done
[ -n "$PHP" ] || fail "no PHP 8.2+ binary found in the known ea-php paths; edit scripts/post-deploy.sh"
"$PHP" -r 'exit(PHP_VERSION_ID >= 80200 ? 0 : 1);' || fail "$PHP is older than PHP 8.2"
echo "php: $PHP ($("$PHP" -r 'echo PHP_VERSION;'))"

[ -f vendor/autoload.php ] || fail "vendor/autoload.php missing. Deploy the 'release' branch, not 'main'."
[ -f .env ]               || fail ".env missing in $APP"
[ -f public/build/manifest.json ] || echo "WARN: public/build/manifest.json missing (Vite assets not shipped)"

"$PHP" artisan migrate --force

# One-off pilot seed. Runs only if you created /home/scaninca/repositories/signin_ca/.run-seed-pilot
# containing the owner agent's email on its first line. The marker is deleted after a successful seed;
# if the seed fails the deploy aborts and the marker stays. The command is idempotent.
SEED_MARKER="$APP/.run-seed-pilot"
if [ -f "$SEED_MARKER" ]; then
  SEED_AGENT="$(head -n1 "$SEED_MARKER" | tr -d '\r\n ')"
  if [ -n "$SEED_AGENT" ]; then
    "$PHP" artisan scanin:seed-pilot --agent="$SEED_AGENT"
    rm -f "$SEED_MARKER"
    echo "seed: pilot event seeded, marker removed"
  else
    echo "WARN seed: $SEED_MARKER is empty (put the owner agent's email on line 1); skipped"
  fi
fi

[ -e public/storage ] || "$PHP" artisan storage:link
"$PHP" artisan optimize
"$PHP" artisan queue:restart

# Document root: public_html must point at <app>/public. Only ever create a
# missing link; an existing real directory is left alone for a human to rename.
TARGET="$APP/public"
if [ -L "$DOCROOT" ]; then
  if [ "$(readlink "$DOCROOT")" = "$TARGET" ]; then
    echo "docroot: public_html -> $TARGET (ok)"
  else
    echo "WARN docroot: public_html is a symlink to $(readlink "$DOCROOT"), expected $TARGET"
  fi
elif [ -e "$DOCROOT" ]; then
  echo "NOTE docroot: public_html is a real directory and was NOT touched."
  echo "      Rename it to public_html.old in File Manager, then deploy again."
else
  ln -s "$TARGET" "$DOCROOT"
  echo "docroot: created public_html -> $TARGET"
fi

echo "=== deploy finished ==="

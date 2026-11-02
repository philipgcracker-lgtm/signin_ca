#!/usr/bin/env bash
#
# deploy.sh - ship ScanIn (scanin.ca) to cPanel over SSH.
#
# Run from the project root on your machine (Git Bash is fine; rsync is NOT
# needed locally, only on the server).
#
#   ./deploy.sh --dry-run        preflight + show what would be shipped, change nothing
#   ./deploy.sh                  test, build assets, upload, migrate, cache
#   ./deploy.sh --skip-tests     same, without running php artisan test
#   ./deploy.sh --install-cron   also add the one-minute scheduler cron if missing
#
# One-time server setup (cPanel):
#   1. Domains > scanin.ca: set Document Root to  <DEPLOY_PATH>/public
#      (e.g. /home/USER/scanin/public). Do not deploy into public_html.
#   2. MultiPHP Manager: PHP 8.2+ for scanin.ca. Enable extensions: pdo_mysql,
#      mbstring, openssl, tokenizer, xml, ctype, fileinfo, bcmath.
#   3. MySQL Databases: create the DB and user (Canadian host), grant all.
#   4. Create <DEPLOY_PATH>/.env on the server (copy .env.example) with:
#        APP_ENV=production  APP_DEBUG=false  APP_URL=https://scanin.ca
#        DB_* credentials, QUEUE_CONNECTION=database, SESSION_DRIVER=database,
#        CACHE_STORE=database, MAIL_* settings, and an APP_KEY
#        (generate with `php artisan key:generate --show` locally).
#   5. Copy .deploy.env.example to .deploy.env and fill it in.
#   6. SSH key auth for the deploy user (the script uses BatchMode, no prompts).
#
# Notes:
#   - public/.htaccess is NOT overwritten once it exists, because cPanel writes
#     its PHP-version handler into it. It is seeded from the repo on first deploy.
#     If Laravel's rewrite rules ever change, merge them by hand.
#   - .env, storage/ and public/.well-known (AutoSSL) are never touched.
#   - If a migration fails the site is left in maintenance mode on purpose.
#     Fix it, then run:  php artisan up   (or re-run this script).

set -euo pipefail
cd "$(dirname "${BASH_SOURCE[0]}")"

DRY_RUN=0
SKIP_TESTS=0
INSTALL_CRON=0

die()  { echo "deploy: $*" >&2; exit 1; }
step() { echo; echo "==> $*"; }

for arg in "$@"; do
  case "$arg" in
    --dry-run)      DRY_RUN=1 ;;
    --skip-tests)   SKIP_TESTS=1 ;;
    --install-cron) INSTALL_CRON=1 ;;
    -h|--help)      sed -n '2,/^set -euo/p' "$0" | sed '$d'; exit 0 ;;
    *)              die "unknown option: $arg (try --help)" ;;
  esac
done

[ -f .deploy.env ] || die ".deploy.env not found. Copy .deploy.env.example and fill it in."
# shellcheck disable=SC1091
source .deploy.env

: "${DEPLOY_HOST:?set DEPLOY_HOST in .deploy.env}"
: "${DEPLOY_USER:?set DEPLOY_USER in .deploy.env}"
: "${DEPLOY_PATH:?set DEPLOY_PATH in .deploy.env}"
DEPLOY_PORT="${DEPLOY_PORT:-22}"
PHP_BIN="${PHP_BIN:-php}"
COMPOSER_CMD="${COMPOSER_CMD:-composer}"
HEALTH_URL="${HEALTH_URL:-https://scanin.ca/login}"

case "$DEPLOY_PATH" in
  /*) ;;
  *)  die "DEPLOY_PATH must be an absolute path" ;;
esac
case "$DEPLOY_PATH" in
  *" "*) die "DEPLOY_PATH must not contain spaces" ;;
esac

SSH=(ssh -p "$DEPLOY_PORT" -o BatchMode=yes -o ConnectTimeout=15 "$DEPLOY_USER@$DEPLOY_HOST")
STAGE="${DEPLOY_PATH}.incoming"

# ---------------------------------------------------------------- preflight
step "Preflight"
"${SSH[@]}" "true" || die "cannot SSH to $DEPLOY_USER@$DEPLOY_HOST:$DEPLOY_PORT"
"${SSH[@]}" "command -v rsync >/dev/null" || die "rsync is not installed on the server"
"${SSH[@]}" "$PHP_BIN -r 'exit(PHP_VERSION_ID >= 80200 ? 0 : 1);'" \
  || die "server PHP ($PHP_BIN) is older than 8.2; set PHP_BIN in .deploy.env"
"${SSH[@]}" "test -f '$DEPLOY_PATH/.env'" \
  || die "$DEPLOY_PATH/.env is missing on the server. Create it first (see header)."
echo "ok: ssh, rsync, php >= 8.2, server .env present"

# ------------------------------------------------------------ test + build
if [ "$SKIP_TESTS" -eq 0 ]; then
  step "Tests"
  php artisan test
else
  step "Tests skipped (--skip-tests)"
fi

step "Build assets"
npm ci
npm run build
[ -f public/build/manifest.json ] || die "public/build/manifest.json missing after build"

# -------------------------------------------------------------------- pack
TAR_EXCLUDES=(
  --exclude=./.git
  --exclude=./.claude
  --exclude=./.remember
  --exclude=./node_modules
  --exclude=./vendor
  --exclude=./storage
  --exclude=./bootstrap/cache
  --exclude=./tests
  --exclude=./research
  --exclude=./PRD.md
  --exclude=./SPEC.md
  --exclude=./.env
  --exclude=./.env.*
  --exclude=./.deploy.env
  --exclude=./.deploy.env.example
  --exclude=./.phpunit.result.cache
  --exclude=./database/*.sqlite
  --exclude=./deploy.sh
)

if [ "$DRY_RUN" -eq 1 ]; then
  step "Dry run: files that would be uploaded"
  tar -cf - "${TAR_EXCLUDES[@]}" . | tar -tf - | grep -v '/$' | head -40
  echo "... ($(tar -cf - "${TAR_EXCLUDES[@]}" . | tar -tf - | grep -vc '/$') files total)"
  echo
  echo "Dry run complete. Nothing was changed on the server."
  exit 0
fi

step "Upload to $DEPLOY_USER@$DEPLOY_HOST:$STAGE"
"${SSH[@]}" "rm -rf '$STAGE' && mkdir -p '$STAGE'"
tar -czf - "${TAR_EXCLUDES[@]}" . | "${SSH[@]}" "tar -xzf - -C '$STAGE'"

# ------------------------------------------------------------------ remote
step "Release on server"
REMOTE_ARGS="$(printf '%q ' "$DEPLOY_PATH" "$STAGE" "$PHP_BIN" "$COMPOSER_CMD" "$INSTALL_CRON")"

"${SSH[@]}" "bash -s -- $REMOTE_ARGS" <<'REMOTE'
set -euo pipefail
APP="$1"; STAGE="$2"; PHP="$3"; COMPOSER="$4"; INSTALL_CRON="$5"

cd "$APP"
mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions \
         storage/framework/views storage/logs bootstrap/cache

# Maintenance mode only if the app is already installed.
if [ -f vendor/autoload.php ]; then
  $PHP artisan down --retry=60 || true
fi

# Sync code. Keep server-owned files: .env, storage, vendor, build cache,
# AutoSSL challenge dir, storage symlink, cPanel-managed .htaccess.
rsync -a --delete \
  --exclude=/.env \
  --exclude=/storage \
  --exclude=/vendor \
  --exclude=/bootstrap/cache \
  --exclude=/public/.well-known \
  --exclude=/public/storage \
  --exclude=/public/.htaccess \
  "$STAGE"/ "$APP"/

# Seed public/.htaccess once; never overwrite the cPanel-edited copy.
[ -f "$APP/public/.htaccess" ] || cp "$STAGE/public/.htaccess" "$APP/public/.htaccess"
rm -rf "$STAGE"

chmod -R ug+rwX storage bootstrap/cache

$COMPOSER install --no-dev --optimize-autoloader --no-interaction --prefer-dist
$PHP artisan migrate --force
[ -e public/storage ] || $PHP artisan storage:link
$PHP artisan optimize
$PHP artisan queue:restart
$PHP artisan up

# Scheduler cron (every minute). Check, and install only when asked.
CRON_LINE="* * * * * cd $APP && $PHP artisan schedule:run >> /dev/null 2>&1"
if crontab -l 2>/dev/null | grep -q "artisan schedule:run"; then
  echo "cron: scheduler entry present"
elif [ "$INSTALL_CRON" = "1" ]; then
  ( crontab -l 2>/dev/null; echo "$CRON_LINE" ) | crontab -
  echo "cron: installed"
else
  echo "cron: MISSING. Re-run with --install-cron, or add this in cPanel > Cron Jobs:"
  echo "  $CRON_LINE"
fi

$PHP artisan schedule:list
REMOTE

# ------------------------------------------------------------------- smoke
step "Smoke test $HEALTH_URL"
code="$(curl -s -o /dev/null -w '%{http_code}' --max-time 20 "$HEALTH_URL" || true)"
if [ "$code" = "200" ]; then
  echo "ok: HTTP 200"
else
  die "expected HTTP 200 from $HEALTH_URL, got '$code'. Check storage/logs/laravel.log on the server."
fi

echo
echo "Deployed."

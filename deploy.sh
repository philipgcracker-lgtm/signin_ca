#!/usr/bin/env bash
#
# deploy.sh - deploy ScanIn (scanin.ca) to cPanel: git pull + composer + migrate.
#
# Run from the project root on your machine (Git Bash is fine).
#
#   ./deploy.sh --dry-run        preflight + show what would go out, change nothing
#   ./deploy.sh                  test, build assets, pull on server, migrate, cache
#   ./deploy.sh --skip-tests     same, without running php artisan test
#   ./deploy.sh --install-cron   also add the one-minute scheduler cron if missing
#
# What it does:
#   local   : requires a clean tree on DEPLOY_BRANCH, already pushed to origin;
#             runs php artisan test; builds Vite assets (public/build is gitignored,
#             so it is uploaded separately; cPanel needs no Node).
#   server  : git pull --ff-only, composer install --no-dev, php artisan migrate
#             --force, optimize, queue:restart, with maintenance mode around it.
#
# One-time server setup (cPanel):
#   1. Domains > scanin.ca: set Document Root to  <DEPLOY_PATH>/public
#      (e.g. /home/USER/scanin/public). Do not use public_html.
#   2. MultiPHP Manager: PHP 8.2+ for scanin.ca. Enable pdo_mysql, mbstring,
#      openssl, tokenizer, xml, ctype, fileinfo, bcmath.
#   3. MySQL Databases: create the DB and user (Canadian host), grant all.
#   4. Clone the repo into DEPLOY_PATH over SSH (add the server's public key as a
#      read-only deploy key on the git host first):
#        git clone git@HOST:OWNER/REPO.git /home/USER/scanin
#   5. Create <DEPLOY_PATH>/.env (copy .env.example): APP_ENV=production,
#      APP_DEBUG=false, APP_URL=https://scanin.ca, DB_* credentials,
#      QUEUE_CONNECTION=database, SESSION_DRIVER=database, CACHE_STORE=database,
#      MAIL_* settings, and an APP_KEY (`php artisan key:generate --show` locally).
#   6. Copy .deploy.env.example to .deploy.env and fill it in.
#   7. SSH key auth for the deploy user (the script uses BatchMode, no prompts).
#
# Notes:
#   - cPanel writes its PHP-version handler into public/.htaccess. The script marks
#     that file skip-worktree on the server so git leaves your copy alone. If the
#     repo's public/.htaccess ever changes, the pull will stop; merge it by hand.
#   - .env and storage/ are untracked and never touched.
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
DEPLOY_BRANCH="${DEPLOY_BRANCH:-main}"
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
STAGE="${DEPLOY_PATH}.build-incoming"

# ------------------------------------------------------------ local checks
step "Local git state"
git rev-parse --git-dir >/dev/null 2>&1 || die "not a git repository"
git remote get-url origin >/dev/null 2>&1 \
  || die "no 'origin' remote. Add one (git remote add origin <url>) and push first."
[ "$(git rev-parse --abbrev-ref HEAD)" = "$DEPLOY_BRANCH" ] \
  || die "current branch is not $DEPLOY_BRANCH"
git diff --quiet && git diff --cached --quiet \
  || die "uncommitted changes in tracked files. Commit or stash first."
git fetch -q origin "$DEPLOY_BRANCH"
LOCAL_SHA="$(git rev-parse HEAD)"
[ "$LOCAL_SHA" = "$(git rev-parse "origin/$DEPLOY_BRANCH")" ] \
  || die "HEAD is not the same as origin/$DEPLOY_BRANCH. Push (or pull) first."
echo "ok: clean, on $DEPLOY_BRANCH, in sync with origin at ${LOCAL_SHA:0:7}"

# --------------------------------------------------------- server preflight
step "Server preflight"
"${SSH[@]}" "true" || die "cannot SSH to $DEPLOY_USER@$DEPLOY_HOST:$DEPLOY_PORT"
"${SSH[@]}" "command -v git >/dev/null" || die "git is not installed on the server"
"${SSH[@]}" "$PHP_BIN -r 'exit(PHP_VERSION_ID >= 80200 ? 0 : 1);'" \
  || die "server PHP ($PHP_BIN) is older than 8.2; set PHP_BIN in .deploy.env"
"${SSH[@]}" "test -d '$DEPLOY_PATH/.git'" \
  || die "$DEPLOY_PATH is not a git clone. Clone the repo there first (see header)."
"${SSH[@]}" "test -f '$DEPLOY_PATH/.env'" \
  || die "$DEPLOY_PATH/.env is missing on the server. Create it first (see header)."
REMOTE_SHA="$("${SSH[@]}" "cd '$DEPLOY_PATH' && git rev-parse HEAD")"
echo "ok: ssh, git, php >= 8.2, clone and .env present"
echo "server is at ${REMOTE_SHA:0:7}, deploying ${LOCAL_SHA:0:7}"

if [ "$DRY_RUN" -eq 1 ]; then
  step "Dry run: commits that would be deployed"
  if git cat-file -e "$REMOTE_SHA^{commit}" 2>/dev/null; then
    git log --oneline "$REMOTE_SHA..HEAD" | head -30
    echo "($(git rev-list --count "$REMOTE_SHA..HEAD") commits)"
  else
    echo "server commit ${REMOTE_SHA:0:7} is not in your local history; cannot list."
  fi
  echo
  echo "Dry run complete. Nothing was changed on the server."
  exit 0
fi

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

step "Upload public/build to $DEPLOY_USER@$DEPLOY_HOST:$STAGE"
"${SSH[@]}" "rm -rf '$STAGE' && mkdir -p '$STAGE'"
tar -czf - -C public/build . | "${SSH[@]}" "tar -xzf - -C '$STAGE'"

# ------------------------------------------------------------------ remote
step "Release on server"
REMOTE_ARGS="$(printf '%q ' "$DEPLOY_PATH" "$STAGE" "$PHP_BIN" "$COMPOSER_CMD" "$DEPLOY_BRANCH" "$LOCAL_SHA" "$INSTALL_CRON")"

"${SSH[@]}" "bash -s -- $REMOTE_ARGS" <<'REMOTE'
set -euo pipefail
APP="$1"; STAGE="$2"; PHP="$3"; COMPOSER="$4"; BRANCH="$5"; SHA="$6"; INSTALL_CRON="$7"

cd "$APP"
mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions \
         storage/framework/views storage/logs bootstrap/cache

# cPanel edits public/.htaccess (PHP handler); keep git away from the server copy.
git update-index --skip-worktree public/.htaccess 2>/dev/null || true

if [ -n "$(git status --porcelain --untracked-files=no)" ]; then
  echo "deploy: server working tree has local changes to tracked files:" >&2
  git status --short --untracked-files=no >&2
  rm -rf "$STAGE"
  exit 1
fi

# Maintenance mode only if the app is already installed.
if [ -f vendor/autoload.php ]; then
  $PHP artisan down --retry=60 || true
fi

git fetch origin "$BRANCH"
git checkout "$BRANCH"
git pull --ff-only origin "$BRANCH"
[ "$(git rev-parse HEAD)" = "$SHA" ] || { echo "deploy: server HEAD is not $SHA after pull" >&2; exit 1; }

# Built assets (gitignored, uploaded separately).
rm -rf public/build
mv "$STAGE" public/build

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
echo "Deployed ${LOCAL_SHA:0:7}."

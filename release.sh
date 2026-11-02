#!/usr/bin/env bash
#
# release.sh - build the deployable `release` branch locally.
#
# The cPanel host has no Composer, Node or shell, so the release branch is
# `main` plus the built vendor/ (no-dev) and public/build/. cPanel Git tracks
# `release`; deploy = Update from Remote + Deploy HEAD Commit.
#
#   ./release.sh               test, build, commit to local `release`; does NOT push
#   ./release.sh --push        same, then push `release` to origin
#   ./release.sh --skip-tests  skip php artisan test
#
# Your working copy is untouched: vendor/ stays with dev deps. The release is
# assembled in a separate git worktree (default ~/.scanin-release, override with
# RELEASE_WORKTREE). History is only ever appended (merge main, add a build
# commit), so cPanel's "Update from Remote" always fast-forwards.

set -euo pipefail
cd "$(dirname "${BASH_SOURCE[0]}")"
ROOT="$PWD"
BRANCH_SRC="main"
BRANCH_REL="release"
WT="${RELEASE_WORKTREE:-$HOME/.scanin-release}"
COMPOSER_CMD="${COMPOSER_CMD:-composer}"
PUSH=0
SKIP_TESTS=0

die()  { echo "release: $*" >&2; exit 1; }
step() { echo; echo "==> $*"; }

for arg in "$@"; do
  case "$arg" in
    --push)       PUSH=1 ;;
    --skip-tests) SKIP_TESTS=1 ;;
    -h|--help)    sed -n '2,/^set -euo/p' "$0" | sed '$d'; exit 0 ;;
    *)            die "unknown option: $arg (try --help)" ;;
  esac
done

step "Source checks"
git remote get-url origin >/dev/null 2>&1 || die "no 'origin' remote"
[ "$(git rev-parse --abbrev-ref HEAD)" = "$BRANCH_SRC" ] || die "not on $BRANCH_SRC"
git diff --quiet && git diff --cached --quiet || die "uncommitted changes in tracked files"
git fetch -q origin
SRC_SHA="$(git rev-parse HEAD)"
[ "$SRC_SHA" = "$(git rev-parse "origin/$BRANCH_SRC")" ] || die "HEAD is not the same as origin/$BRANCH_SRC; push first"
echo "ok: $BRANCH_SRC at ${SRC_SHA:0:7}, in sync with origin"

if [ "$SKIP_TESTS" -eq 0 ]; then
  step "Tests"
  php artisan test
fi

step "Build assets (output is gitignored in your working copy)"
npm ci
npm run build
[ -f public/build/manifest.json ] || die "public/build/manifest.json missing after build"

step "Prepare release worktree: $WT"
HAVE_REMOTE_REL=0
git rev-parse --verify -q "origin/$BRANCH_REL" >/dev/null && HAVE_REMOTE_REL=1
if [ ! -e "$WT/.git" ]; then
  if [ "$HAVE_REMOTE_REL" -eq 1 ]; then
    git worktree add -B "$BRANCH_REL" "$WT" "origin/$BRANCH_REL"
  else
    git worktree add -b "$BRANCH_REL" "$WT" "$BRANCH_SRC"
  fi
fi
[ "$(git -C "$WT" rev-parse --abbrev-ref HEAD)" = "$BRANCH_REL" ] || die "$WT is not on $BRANCH_REL"
[ -z "$(git -C "$WT" status --porcelain --untracked-files=no)" ] || die "$WT has uncommitted changes to tracked files"
if [ "$HAVE_REMOTE_REL" -eq 1 ]; then
  git -C "$WT" merge --ff-only "origin/$BRANCH_REL" || die "local $BRANCH_REL has diverged from origin/$BRANCH_REL"
fi

step "Merge $BRANCH_SRC into $BRANCH_REL"
git -C "$WT" merge --no-edit "$SRC_SHA"

step "Install production dependencies in the worktree"
rm -rf "$WT/vendor" "$WT/public/build"
( cd "$WT" && $COMPOSER_CMD install --no-dev --optimize-autoloader --no-interaction --prefer-dist )
mkdir -p "$WT/public/build"
cp -R "$ROOT/public/build/." "$WT/public/build/"
[ -f "$WT/vendor/autoload.php" ] || die "vendor/autoload.php missing in worktree"
[ -d "$WT/vendor/phpunit" ] && die "dev dependencies leaked into vendor/"

step "Commit build"
git -C "$WT" add -f -A vendor public/build
if git -C "$WT" diff --cached --quiet; then
  echo "no build changes to commit"
else
  git -C "$WT" commit -q -m "Release ${SRC_SHA:0:7}: build vendor and assets"
fi

step "Result"
git -C "$WT" log --oneline -4
echo "release HEAD: $(git -C "$WT" rev-parse --short HEAD)"
echo "tracked files: $(git -C "$WT" ls-files | wc -l)"
git -C "$WT" diff --stat "origin/$BRANCH_REL" 2>/dev/null | tail -1 || true

if [ "$PUSH" -eq 1 ]; then
  step "Push $BRANCH_REL"
  git -C "$WT" push origin "$BRANCH_REL"
else
  echo
  echo "Not pushed. To push:  ./release.sh --push   (or: git -C \"$WT\" push origin $BRANCH_REL)"
fi

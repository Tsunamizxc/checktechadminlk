#!/usr/bin/env bash
set -euo pipefail

REPO_DIR="${REPO_DIR:-/var/www/chectech_cur_usr/data/deploy/checktechadminlk}"
WP_ROOT="${WP_ROOT:-/var/www/chectech_cur_usr/data/www/chectechadm.cursiva-dev.ru}"
TOKEN_FILE="${GITHUB_TOKEN_FILE:-/root/.config/lktech/github_token}"
REPO_SLUG="Tsunamizxc/checktechadminlk"

if [ -f "$TOKEN_FILE" ]; then
  # shellcheck disable=SC1090
  GH_TOKEN="$(tr -d '\r\n' < "$TOKEN_FILE")"
else
  GH_TOKEN="${GITHUB_TOKEN:-}"
fi

mkdir -p "$REPO_DIR"
cd "$REPO_DIR"

if [ -n "${GH_TOKEN:-}" ]; then
  ORIGIN="https://x-access-token:${GH_TOKEN}@github.com/${REPO_SLUG}.git"
elif [ -f "${DEPLOY_KEY:-/root/.ssh/chectechadm_deploy}" ]; then
  KEY="${DEPLOY_KEY:-/root/.ssh/chectechadm_deploy}"
  export GIT_SSH_COMMAND="ssh -i $KEY -o IdentitiesOnly=yes -o StrictHostKeyChecking=accept-new"
  ORIGIN="git@github.com:${REPO_SLUG}.git"
else
  echo "No GitHub token or deploy key configured" >&2
  exit 1
fi

if [ ! -d .git ]; then
  git clone "$ORIGIN" "$REPO_DIR"
fi

git remote set-url origin "$ORIGIN"
git fetch origin
git reset --hard origin/main

# scrub token from remote URL in local config display
git remote set-url origin "https://github.com/${REPO_SLUG}.git"
if [ -n "${GH_TOKEN:-}" ]; then
  git remote set-url origin "https://x-access-token:${GH_TOKEN}@github.com/${REPO_SLUG}.git"
fi

rsync -a --delete \
  "$REPO_DIR/wp-content/themes/lktech-crm/" \
  "$WP_ROOT/wp-content/themes/lktech-crm/"

rsync -a --delete \
  "$REPO_DIR/wp-content/plugins/lktech-crm/" \
  "$WP_ROOT/wp-content/plugins/lktech-crm/"

mkdir -p "$WP_ROOT/deploy"
cp -f "$REPO_DIR/deploy/hook.php" "$WP_ROOT/deploy/hook.php"
cp -f "$REPO_DIR/deploy/sync.sh" "$WP_ROOT/deploy/sync.sh"
chmod +x "$WP_ROOT/deploy/sync.sh" "$REPO_DIR/deploy/sync.sh" || true

chown -R chectech_cur_usr:chectech_cur_usr \
  "$WP_ROOT/wp-content/themes/lktech-crm" \
  "$WP_ROOT/wp-content/plugins/lktech-crm" \
  "$WP_ROOT/deploy"

echo "synced $(date -u +%Y-%m-%dT%H:%M:%SZ)"

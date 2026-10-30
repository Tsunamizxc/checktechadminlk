#!/usr/bin/env bash
set -euo pipefail

REPO_DIR="${REPO_DIR:-/var/www/chectech_cur_usr/data/deploy/checktechadminlk}"
WP_ROOT="${WP_ROOT:-/var/www/chectech_cur_usr/data/www/chectechadm.cursiva-dev.ru}"
KEY="${DEPLOY_KEY:-/root/.ssh/chectechadm_deploy}"
export GIT_SSH_COMMAND="ssh -i $KEY -o IdentitiesOnly=yes -o StrictHostKeyChecking=accept-new"

mkdir -p "$REPO_DIR"
cd "$REPO_DIR"

if [ ! -d .git ]; then
  git clone git@github.com:Tsunamizxc/checktechadminlk.git "$REPO_DIR"
fi

git remote set-url origin git@github.com:Tsunamizxc/checktechadminlk.git
git fetch origin
git reset --hard origin/main

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

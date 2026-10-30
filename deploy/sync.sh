#!/usr/bin/env bash
set -euo pipefail

REPO_DIR="${REPO_DIR:-/var/www/chectech_cur_usr/data/deploy/checktechadminlk}"
WP_ROOT="${WP_ROOT:-/var/www/chectech_cur_usr/data/www/chectechadm.cursiva-dev.ru}"
TMP="/tmp/checktechadminlk-sync"

mkdir -p "$REPO_DIR" "$TMP"
cd "$REPO_DIR"

# 1) Try public GitHub zip (no auth)
if curl -fsSL -o "$TMP/main.zip" "https://github.com/Tsunamizxc/checktechadminlk/archive/refs/heads/main.zip"; then
  rm -rf "$TMP/src"
  mkdir -p "$TMP/src"
  unzip -q -o "$TMP/main.zip" -d "$TMP/src"
  SRC="$(find "$TMP/src" -maxdepth 1 -type d -name 'checktechadminlk-*' | head -n 1)"
  if [ -n "$SRC" ]; then
    rsync -a --delete --exclude .git "$SRC/" "$REPO_DIR/"
  fi
elif [ -d .git ]; then
  # 2) Fallback: git pull (needs credentials/deploy key for private repo)
  git fetch --all || true
  git reset --hard origin/main || git reset --hard main || true
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

#!/usr/bin/env bash
set -euo pipefail

REPO_DIR="${REPO_DIR:-/var/www/chectech_cur_usr/data/deploy/checktechadminlk}"
WP_ROOT="${WP_ROOT:-/var/www/chectech_cur_usr/data/www/chectechadm.cursiva-dev.ru}"

cd "$REPO_DIR"
git fetch --all
git reset --hard origin/main

rsync -a --delete \
  "$REPO_DIR/wp-content/themes/lktech-crm/" \
  "$WP_ROOT/wp-content/themes/lktech-crm/"

rsync -a --delete \
  "$REPO_DIR/wp-content/plugins/lktech-crm/" \
  "$WP_ROOT/wp-content/plugins/lktech-crm/"

chown -R chectech_cur_usr:chectech_cur_usr \
  "$WP_ROOT/wp-content/themes/lktech-crm" \
  "$WP_ROOT/wp-content/plugins/lktech-crm"

echo "synced $(date -u +%Y-%m-%dT%H:%M:%SZ)"

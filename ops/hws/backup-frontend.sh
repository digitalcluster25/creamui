#!/usr/bin/env bash
set -euo pipefail

HOST="${HWS_HOST:-69.62.121.157}"
USER_NAME="${HWS_USER:-root}"
PORT="${HWS_PORT:-22}"
REMOTE_DIR="${HWS_REMOTE_DIR:-/opt/hws-frontend}"
STAMP="$(date +%Y%m%d-%H%M%S)"
TARGET_DIR="${HWS_BACKUP_DIR:-$(pwd)/backups/hws-frontend-prod-$STAMP}"

mkdir -p "$TARGET_DIR"

rsync -az \
  --delete \
  --exclude 'node_modules' \
  --exclude '.next' \
  -e "ssh -p $PORT" \
  "$USER_NAME@$HOST:$REMOTE_DIR/" \
  "$TARGET_DIR/"

printf 'Backup saved to %s\n' "$TARGET_DIR"

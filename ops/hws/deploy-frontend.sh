#!/usr/bin/env bash
set -euo pipefail

HOST="${HWS_HOST:-69.62.121.157}"
USER_NAME="${HWS_USER:-root}"
PORT="${HWS_PORT:-22}"
REMOTE_DIR="${HWS_REMOTE_DIR:-/opt/hws-frontend}"
PM2_APP="${HWS_PM2_APP:-hws-frontend}"
TMP_DIR="/tmp/hws-frontend-deploy"
ARCHIVE_NAME="frontend-release.tgz"
ARCHIVE_PATH="$TMP_DIR/$ARCHIVE_NAME"

ROOT_DIR="$(cd "$(dirname "$0")/../.." && pwd)"
cd "$ROOT_DIR"

mkdir -p "$TMP_DIR"
export COPYFILE_DISABLE=1
tar -C frontend -czf "$ARCHIVE_PATH" \
  --exclude='./.env.local' \
  --exclude='./.git' \
  --exclude='./.next' \
  --exclude='./node_modules' \
  --exclude='./tsconfig.tsbuildinfo' \
  .

ssh -p "$PORT" "$USER_NAME@$HOST" "mkdir -p '$TMP_DIR' '$REMOTE_DIR'"
scp -P "$PORT" "$ARCHIVE_PATH" "$USER_NAME@$HOST:$ARCHIVE_PATH"

ssh -p "$PORT" "$USER_NAME@$HOST" "
  set -euo pipefail
  workdir=\$(mktemp -d '$TMP_DIR/release-XXXXXX')
  tar -xzf '$ARCHIVE_PATH' -C \"\$workdir\"
  rsync -az --delete \
    --exclude '.env.local' \
    --exclude '.git' \
    --exclude '.next' \
    --exclude 'node_modules' \
    \"\$workdir/\" '$REMOTE_DIR/'
  cd '$REMOTE_DIR'
  npm ci
  npm run build
  pm2 restart '$PM2_APP'
  rm -rf \"\$workdir\" '$ARCHIVE_PATH'
"

printf 'Deployed frontend to %s:%s\n' "$HOST" "$REMOTE_DIR"

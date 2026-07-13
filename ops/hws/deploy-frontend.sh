#!/usr/bin/env bash
set -euo pipefail

HOST="${HWS_HOST:-69.62.121.157}"
USER_NAME="${HWS_USER:-root}"
PORT="${HWS_PORT:-22}"
REMOTE_DIR="${HWS_REMOTE_DIR:-/opt/hws-frontend}"
PM2_APP="${HWS_PM2_APP:-hws-frontend}"

ROOT_DIR="$(cd "$(dirname "$0")/../.." && pwd)"
cd "$ROOT_DIR"

ssh -p "$PORT" "$USER_NAME@$HOST" "mkdir -p '$REMOTE_DIR'"

rsync -az --delete \
  -e "ssh -p $PORT" \
  --exclude '.env.local' \
  --exclude '.git' \
  --exclude '.next' \
  --exclude 'node_modules' \
  --exclude 'tsconfig.tsbuildinfo' \
  frontend/ "$USER_NAME@$HOST:$REMOTE_DIR/"

ssh -p "$PORT" "$USER_NAME@$HOST" "
  set -euo pipefail
  python3 - <<'PY'
from pathlib import Path
p = Path('$REMOTE_DIR/.env.local')
text = p.read_text() if p.exists() else ''
lines = [line for line in text.splitlines() if not line.startswith('NEXT_PUBLIC_SITE_URL=')]
lines.append('NEXT_PUBLIC_SITE_URL=https://hws.shopping')
p.write_text('\\n'.join(lines).rstrip() + '\\n')
PY
  cd '$REMOTE_DIR'
  docker compose up -d --build --remove-orphans
  if pm2 describe '$PM2_APP' >/dev/null 2>&1; then
    pm2 delete '$PM2_APP'
  fi
"

printf 'Deployed frontend to %s:%s\n' "$HOST" "$REMOTE_DIR"

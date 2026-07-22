#!/usr/bin/env bash
set -euo pipefail

HOST="${HWS_HOST:-69.62.121.157}"
USER_NAME="${HWS_USER:-root}"
PORT="${HWS_PORT:-22}"
REMOTE_DIR="${HWS_REMOTE_DIR:-/opt/hws-frontend}"
CONTAINER_NAME="${HWS_CONTAINER_NAME:-hws-frontend}"

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

rsync -az --delete \
  -e "ssh -p $PORT" \
  --exclude '.DS_Store' \
  wp-plugins/hws-graphql-bridge/ "$USER_NAME@$HOST:$REMOTE_DIR/wp-plugins/hws-graphql-bridge/"

ssh -p "$PORT" "$USER_NAME@$HOST" "
  set -euo pipefail
  python3 - <<'PY'
from pathlib import Path
p = Path('$REMOTE_DIR/.env.local')
text = p.read_text() if p.exists() else ''
lines = [line for line in text.splitlines() if not line.startswith('NEXT_PUBLIC_SITE_URL=')]
lines.append('NEXT_PUBLIC_SITE_URL=https://hws.shopping')
lines = [line for line in lines if not line.startswith('NEXT_PUBLIC_WP_GRAPHQL_URL=')]
lines.append('NEXT_PUBLIC_WP_GRAPHQL_URL=http://wordpress/graphql')
p.write_text('\\n'.join(lines).rstrip() + '\\n')
PY
  cd '$REMOTE_DIR'
  docker rm -f '$CONTAINER_NAME' >/dev/null 2>&1 || true
  docker compose up -d --build --remove-orphans
  docker cp '$REMOTE_DIR/wp-plugins/hws-graphql-bridge/.' wpsandbox-wordpress-1:/var/www/html/wp-content/plugins/hws-graphql-bridge/
"

printf 'Deployed frontend to %s:%s\n' "$HOST" "$REMOTE_DIR"

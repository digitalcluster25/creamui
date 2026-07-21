#!/usr/bin/env bash
set -euo pipefail

HOST="${HWS_HOST:-69.62.121.157}"
USER_NAME="${HWS_USER:-root}"
PORT="${HWS_PORT:-22}"
REMOTE_DIR="${HWS_STAGING_REMOTE_DIR:-/opt/hws-staging}"
STAGING_HOST="${HWS_STAGING_HOST:-staging.hws.shopping}"
WP_GRAPHQL_URL="${HWS_STAGING_WP_GRAPHQL_URL:-https://wpsandbox.spaces.community/graphql}"
SITE_URL="${HWS_STAGING_SITE_URL:-https://${STAGING_HOST}}"

ROOT_DIR="$(cd "$(dirname "$0")/../.." && pwd)"
TEMP_DIR="$(mktemp -d)"
ARCHIVE="$TEMP_DIR/hws-staging.tgz"
ENV_FILE="$TEMP_DIR/.env.staging"
trap 'rm -rf "$TEMP_DIR"' EXIT

export COPYFILE_DISABLE=1
tar -C "$ROOT_DIR" -czf "$ARCHIVE" \
  --exclude='./frontend/.next' \
  --exclude='./frontend/node_modules' \
  --exclude='./frontend/.env*.local' \
  frontend ops/hws/staging/docker-compose.yml ops/hws/apply_taxonomy_restructure.php

cat > "$ENV_FILE" <<EOF
NEXT_PUBLIC_SITE_URL=$SITE_URL
NEXT_PUBLIC_WP_GRAPHQL_URL=$WP_GRAPHQL_URL
STAGING_HOST=$STAGING_HOST
NEXT_REVALIDATE_SECRET=${NEXT_REVALIDATE_SECRET:-}
TELEGRAM_BOT_TOKEN=${TELEGRAM_BOT_TOKEN:-}
TELEGRAM_CHAT_ID=${TELEGRAM_CHAT_ID:-}
EOF

ssh -p "$PORT" "$USER_NAME@$HOST" "mkdir -p '$REMOTE_DIR' /tmp/hws-staging-deploy"
scp -P "$PORT" "$ARCHIVE" "$USER_NAME@$HOST:/tmp/hws-staging-deploy/hws-staging.tgz"
scp -P "$PORT" "$ENV_FILE" "$USER_NAME@$HOST:$REMOTE_DIR/.env.staging"

ssh -p "$PORT" "$USER_NAME@$HOST" "REMOTE_DIR='$REMOTE_DIR' bash -s" <<'REMOTE_SCRIPT'
set -euo pipefail
workdir="$(mktemp -d /tmp/hws-staging-deploy/release-XXXXXX)"
trap 'rm -rf "$workdir" /tmp/hws-staging-deploy/hws-staging.tgz' EXIT
docker network inspect wpsandbox_internal >/dev/null
tar -xzf /tmp/hws-staging-deploy/hws-staging.tgz -C "$workdir"
rm -rf "$REMOTE_DIR/frontend"
mkdir -p "$REMOTE_DIR"
mv "$workdir/frontend" "$REMOTE_DIR/frontend"
cp "$workdir/ops/hws/staging/docker-compose.yml" "$REMOTE_DIR/docker-compose.yml"
chmod 600 "$REMOTE_DIR/.env.staging"
cd "$REMOTE_DIR"
docker compose --env-file .env.staging -f docker-compose.yml up -d --build --remove-orphans
docker compose --env-file .env.staging -f docker-compose.yml ps
docker cp "$workdir/ops/hws/apply_taxonomy_restructure.php" wpsandbox-wordpress-1:/tmp/apply_taxonomy_restructure.php
docker exec wpsandbox-wordpress-1 php /tmp/apply_taxonomy_restructure.php run
REMOTE_SCRIPT

printf 'Staging deployed to %s\n' "$SITE_URL"

# HWS staging E2E

## What is configured

- `ops/hws/staging/docker-compose.yml` runs a separate `hws-staging-frontend` container.
- The container joins the existing external Docker network `wpsandbox_internal`. Docker DNS therefore resolves the existing WordPress service as `wordpress`, which is required by the Next.js image rewrite.
- Traefik serves the staging container at `https://staging.hws.shopping`.
- `.github/workflows/e2e-staging.yml` deploys staging on `main` changes affecting the frontend or E2E configuration, checks `/api/health`, runs `npm run test:e2e`, and uploads Playwright traces/reports.
- `playwright.config.ts` supports both the existing local server and an external URL through `E2E_BASE_URL`.

## Required GitHub configuration

The `staging` environment must have:

- `HWS_SSH_KEY` — private key allowed to deploy to the server;
- `HWS_HOST` — optional, defaults to `69.62.121.157`;
- `HWS_USER` — optional, defaults to `root`;
- `HWS_PORT` — optional, defaults to `22`;
- `NEXT_REVALIDATE_SECRET` — optional runtime secret for the revalidation endpoint.

The workflow does not print secret values and never stores `.env.staging` in Git.

## Current limitations

- WordPress data is shared with the existing `wpsandbox` instance; this is a staging frontend, not an isolated WordPress database.
- Production deployment remains unchanged. The existing production workflow targets the old PM2-based deployment and must be reconciled with the current Docker-based server before enabling an automatic production gate.

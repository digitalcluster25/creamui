# HWS Claude Code Handoff — 2026-07-11

## Branch

- current branch: `feat/hws-vvd-wave-1-import`

## Scope already done in this branch

- EOS parser normalization fixed and reimported live
- catalog canonical/noindex logic added and deployed live
- GraphQL bridge extended for reliable variant option slugs
- brand landing pages added on frontend:
  - `/brands`
  - `/brands/[slug]`
- product page brand link now points to `/brands/{slug}`
- first audit sprint started for import completion tracking

## New audit infrastructure added

Directories:

- `data/audit/source/`
- `data/audit/woo/`
- `data/audit/diff/`
- `data/audit/final/`
- `ops/hws/audit/`

Scripts:

- `ops/hws/audit/export_woo_brand_audit.php`
- `ops/hws/audit/compare_source_to_woo.py`
- `ops/hws/audit/build_source_manifest_easysteam.py`

## EasySteam audit status

Artifacts:

- source manifest:
  - `data/audit/source/easysteam.json`
- live Woo snapshot:
  - `data/audit/woo/easysteam.json`
- diff report:
  - `data/audit/diff/easysteam.json`

Current numbers:

- `source_total = 173`
- `woo_total = 173`
- `matched = 112`
- `matched_but_broken = 61`
- `source_only = 0`
- `woo_only = 0`

Current broken reason counts:

- `missing_attributes = 46`
- `missing_full_description = 12`
- `missing_image = 3`

Interpretation:

- EasySteam SKU import coverage is currently complete by count
- main remaining work is repair/quality, not missing product import

## Live environment access pattern

- SSH host: `69.62.121.157`
- user: `root`
- WordPress container: `wpsandbox-wordpress-1`
- WordPress root: `/var/www/html`

Working pattern for WP CLI scripts:

1. `scp` file to host `/tmp`
2. `docker cp` into `wpsandbox-wordpress-1:/tmp`
3. run inside container:

```bash
docker exec wpsandbox-wordpress-1 sh -lc '
  HWS_AUDIT_BRAND=easysteam \
  HWS_AUDIT_OUTPUT_DIR=/var/www/html/data/audit/woo \
  wp --allow-root eval-file /tmp/export_woo_brand_audit.php --path=/var/www/html
'
```

Important:

- `export_woo_brand_audit.php` must use `HWS_AUDIT_OUTPUT_DIR` or fallback to `ABSPATH/data/audit/woo`
- do not compute output path from `__DIR__` because script runs from `/tmp` in container

## Files changed in this sprint

Audit:

- `ops/hws/audit/export_woo_brand_audit.php`
- `ops/hws/audit/compare_source_to_woo.py`
- `ops/hws/audit/build_source_manifest_easysteam.py`
- `data/audit/source/easysteam.json`
- `data/audit/woo/easysteam.json`
- `data/audit/diff/easysteam.json`

Frontend / SEO / brand pages:

- `frontend/app/brands/[slug]/page.tsx`
- `frontend/components/sections/brands-directory/BrandsDirectory.tsx`
- `frontend/components/sections/product-page/ProductPage.tsx`
- `frontend/lib/types/productPage.ts`
- `frontend/lib/wp/mappers.ts`
- `frontend/lib/seo/catalog.ts`
- `frontend/app/catalog/page.tsx`
- `frontend/app/catalog/[category]/page.tsx`
- `frontend/components/sections/catalog-seo/*`
- `frontend/components/sections/catalog-overview/*`
- related query / type / catalog files

Parser / importer / bridge work already present in tree:

- `ops/hws/build_eos_wave_payload.py`
- `ops/hws/build_vvd_wave_payload.py`
- `ops/hws/import_vvd_wave.php`
- `ops/hws/import_supplier_wave.php`
- `wp-plugins/hws-graphql-bridge/hws-graphql-bridge.php`

## Immediate next steps for Claude Code

### 1. Finish EasySteam repair cycle

Implement:

- `ops/hws/audit/repair_brand_descriptions.php`
- `ops/hws/audit/repair_brand_media.php`
- `ops/hws/audit/repair_brand_categories.php`

Then:

1. repair EasySteam exceptions
2. rerun Woo snapshot
3. rerun diff
4. verify counts go down

### 2. Build manifests for remaining brands

Use already generated payloads as source-of-truth first:

- VVD:
  - `data/import/generated/hws-vvd-wave-1-electric-and-steam.json`
- Sangens:
  - `data/import/generated/hws-sangens-wave-1-detailed.json`
- EOS:
  - `data/import/generated/hws-eos-wave-1-detailed.json`
  - `data/import/generated/hws-eos-wave-2-controls-and-steam-detailed.json`

Recommended next scripts:

- `ops/hws/audit/build_source_manifest_vvd.py`
- `ops/hws/audit/build_source_manifest_sangens.py`
- `ops/hws/audit/build_source_manifest_eos.py`

### 3. Then run same audit loop

For each brand:

1. build source manifest
2. export live Woo snapshot
3. compare source to Woo
4. repair
5. rerun audit

## Things to ignore

Do not commit:

- `.codebase-memory/`
- `ops/hws/__pycache__/`
- `ops/hws/audit/__pycache__/`

## Truth constraints

- do not claim 100% import complete until all brands have:
  - source manifest
  - Woo snapshot
  - diff report
  - repaired exceptions
  - rerun audit proving closure

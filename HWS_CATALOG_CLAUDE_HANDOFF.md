# HWS Catalog Claude Handoff

Updated: `2026-07-10`

## Purpose

Этот файл нужен как полный handoff для нового чата / Claude Code, чтобы без восстановления контекста по памяти продолжить:

- организацию каталога HWS
- taxonomy / categories / attributes / filters
- supplier mapping
- парсинг и импорт товаров
- сверку backend `wpsandbox` и storefront `hwsstore`

Не опираться на память. Опора только на этот файл + перечисленные ниже документы и файлы.

## Canonical source documents

Прочитать в первую очередь:

1. [docs/hws-catalog-program.md](/Users/macbookpro/Coding/creamui/docs/hws-catalog-program.md)
2. [docs/hws-catalog-phase-1-taxonomy.md](/Users/macbookpro/Coding/creamui/docs/hws-catalog-phase-1-taxonomy.md)
3. [docs/hws-catalog-backend-audit.md](/Users/macbookpro/Coding/creamui/docs/hws-catalog-backend-audit.md)
4. [docs/hws-catalog-implementation-plan.md](/Users/macbookpro/Coding/creamui/docs/hws-catalog-implementation-plan.md)
5. [docs/hws-supplier-import-rollout-plan.md](/Users/macbookpro/Coding/creamui/docs/hws-supplier-import-rollout-plan.md)
6. [docs/hws-catalog-attribute-model.md](/Users/macbookpro/Coding/creamui/docs/hws-catalog-attribute-model.md)

По EasySteam отдельно:

7. [docs/hws-easysteam-mapping-plan.md](/Users/macbookpro/Coding/creamui/docs/hws-easysteam-mapping-plan.md)
8. [docs/hws-easysteam-extractor-runbook.md](/Users/macbookpro/Coding/creamui/docs/hws-easysteam-extractor-runbook.md)
9. [docs/hws-easysteam-import-matrix.csv](/Users/macbookpro/Coding/creamui/docs/hws-easysteam-import-matrix.csv)

## Locked catalog logic

Это не обсуждать заново без сильной причины. Это уже было согласовано.

### Top-level sections

Использовать именно эти верхние разделы:

1. `Печи для русской бани`
2. `Печи для сауны`
3. `Парогенераторы и хаммам`
4. `Коммерческие решения`
5. `Пульты и автоматика`
6. `Дымоходы и монтаж`
7. `Баки и теплообменники`
8. `Камни и облицовка`
9. `Аксессуары`
10. `Бренды`

Критичное правило:

- не разносить `Парогенераторы` и `Хаммам` в два отдельных top-level раздела
- не делать бренды top-level buying path
- не смешивать brand / room type / fuel type на одном уровне меню

### Canonical branch tree

Смотри:

- [docs/hws-catalog-phase-1-taxonomy.md](/Users/macbookpro/Coding/creamui/docs/hws-catalog-phase-1-taxonomy.md)

Ключевые ветки:

- `russian-bath-stoves`
- `sauna-stoves`
- `steam-generators-and-hammam`
- `commercial`
- `control-units`
- `chimneys-and-installation`
- `water-tanks-and-heat-exchangers`
- `stones-and-cladding`
- `accessories`
- `brands`

### Primary category rule

Каждый товар должен иметь:

- одну primary category
- фильтрационные атрибуты отдельно
- brand / series отдельно

Не использовать фильтры как замену архитектуре категорий.

## Supplier placement baseline

### VVD

Базовое размещение:

- `ПАРиЖАР` -> `russian-bath-stoves/steam-thermal`
- `ФутуРус` -> `russian-bath-stoves/steam-thermal`
- `Премьера Руса` -> `russian-bath-stoves/steam-thermal`
- `АЭГПП` -> `steam-generators-and-hammam/steam-generators`
- VVD control panels -> `control-units/for-steam-generators`
- VVD chimneys -> `chimneys-and-installation/chimneys`
- VVD tanks / heat hardware -> `water-tanks-and-heat-exchangers/*`

### EasySteam

Базовое размещение:

- `Анапа`, `Сочи`, `Геленджик`, `Южная` -> `russian-bath-stoves/wood`
- gas versions -> `russian-bath-stoves/gas`
- `VIVARTE` -> `russian-bath-stoves/electric`
- `Ялта` -> `sauna-stoves`
- `Анапа К`, `Сочи К`, `Геленджик К`, `Домна`, `Ялта К` -> commercial branches
- engineering / tanks / chimneys / burners / accessories -> соответствующие engineering branches

### Sangens

- retail heaters -> `sauna-stoves/electric-sauna-stoves`
- control systems -> `control-units/for-sauna-heaters`
- spa / snow / automation -> `commercial/*`

### EOS

- sauna heaters -> `sauna-stoves/electric-sauna-stoves`
- control units -> `control-units/for-sauna-heaters`
- steam room equipment -> `steam-generators-and-hammam/steam-room-equipment`
- commercial systems -> `commercial/*`

## Filter logic that was agreed

### Russian bath stoves

- fuel type
- equipment type
- steam-room volume
- power
- facing / cladding
- material
- brand
- series
- price

### Sauna stoves

- power source
- home / commercial
- volume
- power
- voltage
- design
- brand
- series
- price

### Steam generators and hammam

- equipment type
- home / commercial
- power
- voltage
- room volume
- brand
- series
- price

Главное правило:

- filters branch-aware
- сначала subcategory navigation / hubs
- потом filters
- не один глобальный filter schema на весь каталог

## Current real project state

### What is already done

- canonical catalog structure approved in docs
- top-level conflict `Парогенераторы` vs `Хаммам` уже логически снят
- локальные skills созданы:
  - `hws-catalog-program`
  - `hws-catalog-backend`
  - `hws-catalog-content-parser`
  - `hws-catalog-frontend`
  - `hws-catalog-seo`
- frontend header/footer already shifted toward new category tree
- EasySteam import pipeline already exists and ранее использовался
- EasySteam товары уже импортировались в `wpsandbox`

### Important backend notes from audit

Смотри:

- [docs/hws-catalog-backend-audit.md](/Users/macbookpro/Coding/creamui/docs/hws-catalog-backend-audit.md)

Критичные выводы:

- `ВВД` brand slug в live backend кривой percent-encoded
- attributes still incomplete for final filters
- current Woo tree partially reworked, but not final everywhere
- missing / incomplete:
  - equipment type
  - voltage
  - clean series dimension
  - room type where needed
  - stable commercial/home dimension

## Files created in this session

### VVD parser / import files

- [ops/hws/build_vvd_wave_payload.py](/Users/macbookpro/Coding/creamui/ops/hws/build_vvd_wave_payload.py)
- [ops/hws/import_vvd_wave.php](/Users/macbookpro/Coding/creamui/ops/hws/import_vvd_wave.php)
- [data/import/hws-vvd-wave-1-electric-and-steam.json](/Users/macbookpro/Coding/creamui/data/import/hws-vvd-wave-1-electric-and-steam.json)
- [data/import/generated/hws-vvd-wave-1-electric-and-steam.json](/Users/macbookpro/Coding/creamui/data/import/generated/hws-vvd-wave-1-electric-and-steam.json)
- [data/import/generated/hws-vvd-wave-1-electric-and-steam-smoke.json](/Users/macbookpro/Coding/creamui/data/import/generated/hws-vvd-wave-1-electric-and-steam-smoke.json)

### Old VVD manual artifact

- [artifacts_vvd_product.json](/Users/macbookpro/Coding/creamui/artifacts_vvd_product.json)

Использовать как reference shape для VVD offer/media/docs semantics.

## What the new VVD parser does

`build_vvd_wave_payload.py`:

- обходит series pages VVD
- собирает product cards
- идет в product detail
- парсит:
  - title
  - breadcrumbs
  - short description
  - full description html/text
  - gallery
  - documents
  - videos
  - summary/full specs
  - option_groups
  - current offer block

Current VVD wave result:

- 8 series
- 30 products

Covered series:

- `Парогенераторы для бани`
- `ПАРиЖАР`
- `Премьера Руса паротермальные`
- `ФутуРус`
- `Премьера`
- `Премьера Руса электрические`
- `Премьера ПРОФИ`
- `Пульты для электрических печей`

## What the new VVD importer does

`import_vvd_wave.php`:

- читает VVD payload
- рассчитывает catalog USD price через `_hws_usd_rub_rate` по аналогии с EasySteam
- создает / обновляет Woo products
- assigns:
  - product_cat
  - product_brand
  - product_type
  - `_sku`
  - `_price`
  - `_regular_price`
  - `_hws_source_payload`
  - `_hws_specs_html`
  - `_hws_specs_groups`
  - `_hws_source_brand`
  - `_hws_source_supplier`
  - `_hws_source_url`
  - `_hws_usd_rub_rate`
  - `_hws_price_currency`
  - `_hws_base_price_rub`
  - `_hws_possible_variation_count`
- optionally sideloads gallery media

Important:

- importer was updated to support env fallback because `wp eval-file` inside `wpsandbox-wordpress-1` did not reliably pass positional args
- envs supported:
  - `HWS_PAYLOAD_PATH`
  - `HWS_IMPORT_MODE`
  - `HWS_IMPORT_LIMIT`
  - `HWS_IMPORT_SERIES`
  - `HWS_IMPORT_MEDIA`

## Remote environment that was actually used

### Access

SSH worked:

- host: `69.62.121.157`
- user: `root`

Confirmed:

- `ssh root@69.62.121.157 'echo ok'` -> works

### WordPress container

Confirmed live container:

- `wpsandbox-wordpress-1`

Confirmed WP root:

- `/var/www/html`

Confirmed `wp` exists inside container:

- `/usr/local/bin/wp`

### Working pattern for import

1. `scp` importer + payload to server `/tmp`
2. `docker cp` files into `wpsandbox-wordpress-1:/tmp`
3. run:

```bash
docker exec wpsandbox-wordpress-1 sh -lc '
  HWS_PAYLOAD_PATH=/tmp/hws-vvd-wave-1-electric-and-steam.json
  HWS_IMPORT_MODE=dry-run
  HWS_IMPORT_LIMIT=0
  HWS_IMPORT_MEDIA=no-media
  wp --allow-root eval-file /tmp/import_vvd_wave.php --path=/var/www/html
'
```

## What was verified live in wpsandbox

### VVD dry-run

A full dry-run for VVD wave 1 was successfully executed after fixes.

Result:

- `processed=30`
- no fatal import errors
- all 8 series passed

### Important fixes already made after first dry-run

1. Fixed wrong category path for VVD control units
   - was: `control-units/for-electric-heaters`
   - corrected to: `control-units/for-steam-generators`

2. Fixed duplicate SKU issue
   - raw `offer_id` was not globally unique across VVD lines
   - importer was changed to build SKU from `slug + offer_id`

### Live control import that already happened

A real import of series `ФутуРус` with media was executed.

Result:

- `processed=3`
- `created=3`
- `updated=0`
- `dry_run=no`

### Critical note about current live `ФутуРус`

Immediately after that control import, one more bug was found:

- product slug was generated as `vvd-vvd-...`
- reason: importer used `slug = 'vvd-' . sanitize_title($sku)` while SKU itself already starts with `VVD-`

This was fixed locally after the control import:

- importer now strips leading `VVD-` before building post slug

But:

- this slug fix was patched locally
- it was **not yet re-deployed and re-run** on live `wpsandbox` after the user interrupted

So current live state is:

- 3 `ФутуРус` products already exist in Woo
- they likely still have wrong double-prefix slugs
- they need an immediate update rerun with the patched importer

### RESOLVED 2026-07-10

- patched importer re-uploaded, `ФутуРус` re-run (`updated=3`), slugs corrected to single `vvd-` prefix (post_ids 249360/249369/249378, matched by `_sku`), thumbnails + galleries attached
- full VVD wave 1 imported live with media: `processed=30 created=27 updated=3`, no fatal errors
- live VVD product count = 30; brand term `vvd` (term_id 82, name `ВВД`) slug already normalized, no percent-encoding
- OPEN: 2 `ПАРиЖАР Про` products price=0 (post_ids 249421, 249429) — parser missed offer price → see open-issue #2 (task spawned)

## Immediate next actions for Claude Code

Do these in order.

### 1. Re-read core docs

Read:

- `docs/hws-catalog-program.md`
- `docs/hws-catalog-phase-1-taxonomy.md`
- `docs/hws-catalog-backend-audit.md`
- `docs/hws-catalog-implementation-plan.md`
- `docs/hws-supplier-import-rollout-plan.md`

### 2. Check working tree

Current local uncommitted files:

- `data/import/generated/hws-vvd-wave-1-electric-and-steam-smoke.json`
- `data/import/generated/hws-vvd-wave-1-electric-and-steam.json`
- `data/import/hws-vvd-wave-1-electric-and-steam.json`
- `ops/hws/build_vvd_wave_payload.py`
- `ops/hws/import_vvd_wave.php`

Ignore:

- `.codebase-memory/`

### 3. Re-upload patched importer and rerun `ФутуРус`

Need:

- upload patched `import_vvd_wave.php`
- rerun real import only for `ФутуРус`
- it should update existing 3 products and correct slugs

Use:

```bash
docker exec wpsandbox-wordpress-1 sh -lc '
  HWS_PAYLOAD_PATH=/tmp/hws-vvd-wave-1-electric-and-steam.json
  HWS_IMPORT_MODE=run
  HWS_IMPORT_LIMIT=0
  HWS_IMPORT_SERIES=ФутуРус
  HWS_IMPORT_MEDIA=download-media
  wp --allow-root eval-file /tmp/import_vvd_wave.php --path=/var/www/html
'
```

Then verify:

- post slugs
- `_sku`
- gallery attached
- product pages render

### 4. If `ФутуРус` update is clean, import the full VVD wave

Recommended:

- run full `run` import for all 30 products
- likely with `download-media`

Then verify:

- product count
- category assignments
- images on frontend
- short / full descriptions
- docs and videos in payload

### 5. Clean up VVD brand slug in backend

From backend audit:

- current `ВВД` brand slug was percent-encoded

Need:

- normalize brand term slug to `vvd`
- ensure imported VVD products attach to normalized brand term
- verify GraphQL / brand pages do not leak encoded slug

### 6. Finish branch-aware filter implementation

This remains open at product/catalog architecture level.

Need:

- implement per-branch filters in frontend
- not one generic filter scheme

Priority branches:

1. `russian-bath-stoves`
2. `sauna-stoves`
3. `steam-generators-and-hammam`
4. then engineering branches

### 7. Finish attribute model in Woo

Still incomplete according to docs.

Need to finalize:

- equipment type
- voltage
- series
- room type
- commercial / home usage

This is necessary before full storefront filter correctness.

### 8. Continue supplier rollout after VVD

Canonical order from rollout plan:

1. finish EasySteam retail / commercial / engineering normalization
2. VVD core bath + steam-generator lines
3. Sangens retail electric + control
4. EOS sauna + steam engineering

## Open issues / risks

### 1. VVD offer matrix is still partial

Current VVD parser captures:

- current_offer
- option_groups

But not yet a full normalized combination matrix of all offer permutations like the older manual VVD artifact suggested.

Impact:

- real Woo variations are still not created
- current system still depends on `_hws_source_payload`
- exact option price switching may remain incomplete for some VVD items

Future improvement:

- extend VVD parser to build full offer combination matrix
- then either:
  - create exact frontend variant entries
  - or create actual Woo variations if needed

### 2. Some VVD products have zero price in source block

Dry-run showed some `ПАРиЖАР` items coming with `price=0 USD`.

Need:

- inspect those source pages
- see whether price lives in another offer state or hidden selector
- patch parser if necessary

### 3. Live `ФутуРус` already imported before slug fix

This is the first thing to repair.

### 4. Frontend/storefront still not complete

Even if backend imports are correct, remaining frontend work still includes:

- category hub blocks
- subcategory-first UX
- branch-specific filters
- brand/series landing logic
- SEO intro / footer blocks

## HWS-specific operating rules

These were important during the work and should stay in force:

- do not invent mapping where ambiguity exists
- taxonomy is source of truth, not frontend hacks
- one product -> one primary category
- filters are secondary to branch architecture
- do not split `Парогенераторы` and `Хаммам` again
- do not use brand pages as replacement for buying intent navigation

## Suggested execution plan for the very next Claude session

1. Read this handoff + listed docs.
2. Inspect local diff.
3. Re-upload patched VVD importer.
4. Re-run `ФутуРус` live update with media.
5. Verify corrected slugs on live Woo.
6. Run full VVD live import.
7. Verify on live frontend / product pages / GraphQL.
8. Fix remaining VVD price-zero cases if found.
9. Move back to attribute completion + branch-aware filters.

## If a new chat starts with very low context

Paste this instruction:

`Read /Users/macbookpro/Coding/creamui/HWS_CATALOG_CLAUDE_HANDOFF.md first, then continue from the exact next action list without rethinking the approved HWS catalog structure.`


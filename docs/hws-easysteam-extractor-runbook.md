# HWS EasySteam Extractor Runbook

Updated: `2026-07-09`

## Purpose

This runbook explains how to build a detailed `EasySteam` payload from the wave manifest before writing anything into WooCommerce.

## Script

- [build_easysteam_wave_payload.py](/Users/macbookpro/Coding/creamui/ops/hws/build_easysteam_wave_payload.py)

## Input

- [hws-easysteam-wave-1.json](/Users/macbookpro/Coding/creamui/data/import/hws-easysteam-wave-1.json)

## Output

- [hws-easysteam-wave-1-detailed.json](/Users/macbookpro/Coding/creamui/data/import/generated/hws-easysteam-wave-1-detailed.json)

## What the extractor does

1. Reads the wave manifest.
2. Visits each series page.
3. Extracts visible product cards from the series.
4. Visits SKU pages or `show` pages.
5. Extracts:
   - title
   - article
   - price
   - option groups
   - specs groups
   - documents
   - images
6. Produces one normalized detailed payload for the next import step.

## Current safe mode

Current generated payload was built in limited mode:
- `maxProductsPerSeries = 4`
- `fetchTimeoutSeconds = 12`

Reason:
- this gives a stable first generated dataset without waiting for the entire supplier branch to finish
- it is enough to validate parser logic before full extraction or direct import

## Command

```bash
rtk python3 /Users/macbookpro/Coding/creamui/ops/hws/build_easysteam_wave_payload.py \
  --manifest /Users/macbookpro/Coding/creamui/data/import/hws-easysteam-wave-1.json \
  --output /Users/macbookpro/Coding/creamui/data/import/generated/hws-easysteam-wave-1-detailed.json \
  --max-products-per-series 4
```

## Next use

After validating the detailed payload:
- increase extraction coverage
- or feed the detailed payload into a WooCommerce import script

## Verification checklist

- generated JSON is valid
- each included series has cards
- extracted product pages have no parser errors
- `VIVARTE` is included under the electric bath branch
- generated payload still respects the wave manifest scope


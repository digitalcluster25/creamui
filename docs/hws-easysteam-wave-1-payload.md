# HWS EasySteam Wave 1 Payload

Updated: `2026-07-09`

## Decision

Wave 1 imports only the private retail bath stove lines of `EasySteam`.

Included:
- `Анапа`
- `Сочи`
- `Геленджик`
- `Южная`
- `VIVARTE`

Excluded from wave 1:
- `Ялта`
- commercial lines
- accessories
- engineering equipment
- `MONTFORT`

## Affected stream

- content / parser
- backend

## Source of truth

- [hws-easysteam-mapping-plan.md](/Users/macbookpro/Coding/creamui/docs/hws-easysteam-mapping-plan.md)
- [hws-easysteam-import-matrix.csv](/Users/macbookpro/Coding/creamui/docs/hws-easysteam-import-matrix.csv)
- [hws-catalog-attribute-model.md](/Users/macbookpro/Coding/creamui/docs/hws-catalog-attribute-model.md)
- [hws-easysteam-wave-1.json](/Users/macbookpro/Coding/creamui/data/import/hws-easysteam-wave-1.json)

## Payload purpose

This payload is the parser-ready manifest for the first actual `EasySteam` import wave.

It defines:
- source series URLs
- target HWS categories
- default brand / series / intent attributes
- source priority for fields
- expected product type
- document candidates

## Import behavior

For wave 1 each series should be imported as:
- one parent WooCommerce product per discovered supplier SKU family
- `variable` product when the source exposes option groups
- with `_hws_source_payload` preserved
- with primary `product_cat` fixed by the manifest

## Field priority

1. SKU page
2. series page
3. listing page
4. PDF only for technical fallback

## Exact entities affected

- `product_cat`
- `product_brand`
- `pa_series`
- `pa_equipment-type`
- `pa_room-type`
- `pa_fuel-type`
- `pa_usage-class`
- `pa_steam-room-volume`
- `pa_cladding-type`
- `pa_cladding-material`
- `_hws_source_payload`
- `_hws_specs_html`
- `_hws_specs_groups`

## Verification checklist

- each imported parent belongs to exactly one HWS primary category
- each imported parent has `product_brand = EasySteam`
- each imported parent has correct `pa_series`
- `VIVARTE` stays in `russian-bath-stoves/electric-bath-stoves`
- no `Ялта` or commercial line enters wave 1 by mistake


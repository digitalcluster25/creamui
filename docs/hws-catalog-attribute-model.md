# HWS Catalog Attribute Model

Updated: `2026-07-09`

## Decision

Catalog filters must be driven by WooCommerce global attributes, not by frontend-only logic and not by ad hoc parsing on individual product pages.

## Affected stream

- backend
- content / parser
- frontend

## Source of truth

- live WooCommerce in `wpsandbox`
- product attributes exposed through WooGraphQL

## Live status

### Existing attributes reused

- `pa_fuel-type` — `Тип топлива`
- `pa_steam-room-volume` — `Объем парной`
- `pa_power` — `Номинальная потребляемая мощность`
- `pa_cladding-type` — `Тип облицовки`
- `pa_cladding-material` — `Материал облицовки`
- `pa_facing` — `Облицовочный материал`
- `pa_connection-type` — `Тип подключения`
- `pa_usage-class` — `Класс использования`

### New attributes created on live backend

- `pa_equipment-type` — `Тип оборудования`
- `pa_room-type` — `Тип помещения`
- `pa_voltage` — `Напряжение`
- `pa_series` — `Серия`

### Existing attributes not to use as catalog-level filters

- `pa_color`
- `pa_leg-material`

These may remain product-specific, but they are not part of the canonical catalog filter architecture.

## Canonical attribute roles

### `pa_equipment-type`

Use for separating product nature inside a branch.

Seeded term set on live backend:
- `wood-bath-stove`
- `gas-bath-stove`
- `electric-bath-stove`
- `bath-sauna-stove`
- `commercial-bath-stove`
- `commercial-bath-sauna-stove`
- `fireplace-stove`
- `water-tank`
- `heat-exchanger`
- `mounting-element`
- `convection-element`
- `gas-burner`
- `economizer`
- `chimney`
- `heater-stones`
- `natural-stone-product`
- `bathing-accessory`
- `pouring-device`
- `aroma-and-steam`
- `wood-storage`

### `pa_room-type`

Use for top-level room intent and mixed-use products.

Seeded term set on live backend:
- `russian-bath`
- `sauna`
- `bath-and-sauna`
- `hammam`
- `commercial-bath`
- `commercial-bath-and-sauna`

### `pa_voltage`

Use for electric equipment filters and import normalization.

Seeded term set on live backend:
- `220v`
- `380v`

### `pa_series`

Use for brand series landing pages and brand-within-category filtering.

Do not pre-seed globally with supplier noise.
Fill it during imports with normalized series names only.

## Branch -> filter model

### `russian-bath-stoves`

Required filters:
- `pa_fuel-type`
- `pa_equipment-type`
- `pa_steam-room-volume`
- `pa_power`
- `pa_facing` or `pa_cladding-material`
- `product_brand`
- `pa_series`

### `sauna-stoves`

Required filters:
- `pa_equipment-type`
- `pa_usage-class`
- `pa_steam-room-volume`
- `pa_power`
- `pa_voltage`
- `product_brand`
- `pa_series`

### `steam-generators-and-hammam`

Required filters:
- `pa_equipment-type`
- `pa_usage-class`
- `pa_power`
- `pa_voltage`
- `pa_steam-room-volume`
- `product_brand`
- `pa_series`

### `commercial`

Required filters:
- `pa_equipment-type`
- `pa_room-type`
- `pa_power`
- `pa_steam-room-volume`
- `pa_voltage` where applicable
- `product_brand`
- `pa_series`

### Engineering branches

`chimneys-and-installation`, `water-tanks-and-heat-exchangers`, `stones-and-cladding`, `accessories`:
- `pa_equipment-type` is the primary structured filter
- add secondary filters only when the imported data justifies them

## Primary category rule for imports

Every imported product must have:
- one primary `product_cat`
- one `product_brand`
- `pa_series` only when series is real and stable
- attributes used for filtering only if the value is explicitly known

Do not:
- assign several sibling category branches as primary discovery paths
- fake missing values just to make filters look full
- use attributes as a replacement for category architecture

## GraphQL note

WooGraphQL already exposes product attributes on concrete product types.

Frontend queries must request attributes through inline fragments on product union members such as:
- `SimpleProduct`
- `VariableProduct`

## Exact entities affected

- live WooCommerce attributes in `wpsandbox`
- future import payloads for `VVD`, `EasySteam`, `Sangens`, `EOS`
- frontend filter composition on category pages

## Implementation order

1. Keep the new attributes as the canonical model.
2. Normalize supplier matrices against these attributes.
3. Import products with primary category plus attribute values.
4. Only after that build branch-aware frontend filters from GraphQL data.

## Verification checklist

- new attributes exist in live WooCommerce
- `equipment-type`, `room-type`, `voltage`, `series` are available for imports
- product imports do not overload irrelevant attributes
- frontend filter design uses only attributes justified by the active catalog branch


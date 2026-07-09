# HWS EasySteam Mapping Plan

Updated: `2026-07-09`

## Decision

`EasySteam` нужно импортировать не по меню поставщика, а по HWS catalog tree.

Источник поставщика разбивается на 4 типа сущностей:
- listing page
- series category page
- product SKU page
- passport / PDF / docs

Импорт делаем в 2 слоя:
- сначала `series / group mapping`
- потом `SKU import`

## Affected stream

- content / parser
- backend

## Source of truth

- supplier site `easysteam.ru`
- live WooCommerce taxonomy and attributes in `wpsandbox`
- [hws-catalog-attribute-model.md](/Users/macbookpro/Coding/creamui/docs/hws-catalog-attribute-model.md)
- [hws-easysteam-import-matrix.csv](/Users/macbookpro/Coding/creamui/docs/hws-easysteam-import-matrix.csv)

## What to take from where

### 1. Listing pages

Use for:
- supplier navigation structure
- series list
- rough intent
- initial volume ranges
- first-pass mapping into HWS categories

Primary URLs:
- [Печи](https://easysteam.ru/products/stoves)
- [Дополнительное оборудование](https://easysteam.ru/products/optional-equipment)
- [Аксессуары](https://easysteam.ru/products/accessories)

Take from listing pages:
- series name
- group name
- supplier intent label
- rough volume statement

Do not take from listing pages:
- final long description
- full specs
- final media set
- final documents set

### 2. Series category pages

Use for:
- series normalization
- list of SKUs inside series
- variant families
- category text for brand/series landing pages

Examples:
- [Анапа](https://easysteam.ru/products/stoves/pechi/anapa)
- [Ялта 15](https://easysteam.ru/products/category/yalta-15)
- [Ялта 25 К](https://easysteam.ru/products/category/yalta-25-k)

Take from series pages:
- normalized series title
- series intro text
- list of SKU cards
- variant families such as year, cladding, casing
- category breadcrumbs if they clarify intended use

Map into HWS:
- `brand` = `EasySteam`
- `series` = normalized series name
- `product_cat` = one primary HWS branch from the approved matrix

### 3. SKU pages

Use for:
- final product title
- actual specs
- price
- gallery
- downloadable files
- exact attribute values

Examples:
- [Анапа базовая модель](https://easysteam.ru/products/product/1000026)
- [Печь Ялта 15/2024 в облицовке из камня](https://easysteam.ru/products/product/1006155)
- [Бак для воды для натрубного теплообменника 50 л.](https://easysteam.ru/products/product/1003830)
- [Обливное устройство КАСКАД 20/2025 в кожухе сетке Хай-Тек](https://easysteam.ru/products/product/1013849)

Take from SKU pages:
- exact product title
- article / supplier SKU
- price
- gallery images
- long description
- structured spec block
- option groups
- downloadable files and docs

Map into WooCommerce:
- `post_title`
- `product_brand = EasySteam`
- `product_cat = one primary HWS category`
- `product short / long description`
- attributes:
  - `pa_series`
  - `pa_equipment-type`
  - `pa_room-type`
  - `pa_fuel-type`
  - `pa_usage-class`
  - `pa_steam-room-volume`
  - `pa_power`
  - `pa_voltage` when available
  - `pa_cladding-type` or `pa_cladding-material` when available
- media gallery
- documents / attachments

### 4. Passports and PDFs

Use for:
- technical verification
- missing dimensions
- compatibility
- installation and safety data

Examples:
- [Инструкция Анапа, Сочи](https://easysteam.ru/files/shares/Documents/Passports/Anapa%2CSochi_100523_1.pdf)
- [Инструкция Ялта в камне](https://easysteam.ru/files/shares/Documents/Passports/Yalta_v_kamne10052023.pdf)

Take from PDFs only when:
- the SKU page misses a parameter
- dimensions or mounting rules need verification
- the product page exposes only marketing text

Do not:
- prefer PDF over SKU page when SKU page already gives exact current values

## Where each supplier branch goes in HWS

### Stoves

`Печи для русской бани`:
- `Анапа` -> `russian-bath-stoves/wood-bath-stoves`
- `Сочи` -> `russian-bath-stoves/wood-bath-stoves`
- `Геленджик` -> `russian-bath-stoves/wood-bath-stoves`
- `Южная` -> `russian-bath-stoves/wood-bath-stoves`
- `VIVARTE` -> `russian-bath-stoves/electric-bath-stoves`

`Печи для русской бани и сауны`:
- `Ялта 15/25/35/40` -> `sauna-stoves`

Rule:
- even if supplier says `баня и сауна`, HWS keeps `Ялта` as sauna-first primary placement

`Печи для коммерческих бань`:
- `Анапа К`
- `Сочи К`
- `Геленджик К`
- `Домна 45/60/80 К`
- `Домна 90/120 К ТВИН`

All go to:
- `commercial/commercial-bath-stoves`

`Печи для коммерческих бань и саун`:
- `Ялта 15/25/35/40/50/60/80/100 К`

All go to:
- `commercial/commercial-sauna-heaters`

`Печи-камины`:
- `MONTFORT`

Rule:
- do not import in first wave
- mark as deferred because it is outside the core HWS bath/sauna phase-1 catalog

### Optional equipment

- `Баки для воды` -> `water-tanks-and-heat-exchangers/water-tanks`
- `Теплообменные устройства` -> `water-tanks-and-heat-exchangers/heat-exchangers`
- `Лист перекрытия` -> `chimneys-and-installation/mounting-elements`
- `Конвекционные дверки` -> `chimneys-and-installation/convection-elements`
- `Газовые горелки` -> `chimneys-and-installation/gas-burners`
- `Экономайзеры` -> `water-tanks-and-heat-exchangers/economizers`
- `Дымоходы` -> `chimneys-and-installation/chimneys`
- `Камни для каменки` -> `stones-and-cladding/heater-stones`
- `Изделия из природного камня` -> `stones-and-cladding/natural-stone-products`

### Accessories

- `Запарник` -> `accessories/aroma-and-steam`
- `Обливные устройства` -> `accessories/pouring-devices`
- `Термоионатор` -> `accessories/aroma-and-steam`
- `Ковши` -> `accessories/bathing-accessories`
- `Опахало` -> `accessories/bathing-accessories`
- `Шапки для бани` -> `accessories/bathing-accessories`
- `Дровница` -> `accessories/wood-storage`

## Field mapping plan

### WooCommerce category

Take from:
- approved HWS mapping matrix

Never take directly from:
- supplier breadcrumb as final truth

### Brand

Always:
- `product_brand = EasySteam`

### Series

Take from:
- series page title
- series grouping on listing page

Normalize:
- `Анапа`
- `Сочи`
- `Геленджик`
- `Южная`
- `VIVARTE`
- `Ялта`
- `Анапа К`
- `Сочи К`
- `Геленджик К`
- `Домна`
- `Домна ТВИН`
- `Ялта К`

### Product title

Take from:
- SKU page H1

Do not synthesize title from series page if SKU page exists.

### Volume

Take priority:
1. SKU specs
2. series page
3. listing teaser

Map to:
- `pa_steam-room-volume`

### Fuel type

Take from:
- SKU spec block
- title hints only if spec block is absent

Map to:
- `pa_fuel-type`

Expected values:
- `wood`
- `gas`
- mixed variants only when the source explicitly says so

### Equipment type

Assign from HWS model, not from raw supplier wording.

Map to:
- `pa_equipment-type`

Examples:
- `Анапа` -> `wood-bath-stove`
- `VIVARTE` -> `electric-bath-stove`
- `Ялта` -> `bath-sauna-stove`
- `Домна 60 К` -> `commercial-bath-stove`
- `Обливное устройство` -> `pouring-device`

### Room type

Assign from HWS intent:
- `Русская баня` -> `russian-bath`
- `Баня и сауна` -> `bath-and-sauna`
- `Коммерческая баня` -> `commercial-bath`
- `Коммерческая баня и сауна` -> `commercial-bath-and-sauna`

Map to:
- `pa_room-type`

### Usage class

Assign from branch:
- retail series -> `private`
- commercial lines -> `commercial`

Map to:
- `pa_usage-class`

### Voltage

Take from:
- SKU specs only

Map to:
- `pa_voltage`

Do not infer voltage for electric products if the source page does not state it.

### Cladding and materials

Take from:
- SKU option groups
- spec block

Map to:
- `pa_cladding-type`
- `pa_cladding-material`
- `pa_facing` when the imported product logic already uses it

## Import order

1. Import `series matrix` and validate category placement.
2. Import first stove wave:
   - `Анапа`
   - `Сочи`
   - `Геленджик`
   - `Южная`
   - `VIVARTE`
3. Import sauna-first `Ялта` wave.
4. Import commercial bath wave:
   - `Анапа К`
   - `Сочи К`
   - `Геленджик К`
   - `Домна`
5. Import commercial sauna wave:
   - `Ялта К`
6. Import engineering groups.
7. Import accessories.
8. Leave `MONTFORT` deferred.

## Exact entities affected

- `product_cat`
- `product_brand`
- `pa_series`
- `pa_equipment-type`
- `pa_room-type`
- `pa_fuel-type`
- `pa_usage-class`
- `pa_steam-room-volume`
- `pa_power`
- `pa_voltage`
- `pa_cladding-type`
- `pa_cladding-material`
- product media
- product docs

## Implementation order

1. Lock the mapping in this plan.
2. Build parser payloads for `series` and `SKU`.
3. Import first wave into `wpsandbox`.
4. Verify categories and attributes in GraphQL.
5. Only after that build frontend branch filters against real imported data.

## Verification checklist

- every EasySteam item has one primary HWS category
- `Ялта` stays sauna-first in primary placement
- `Ялта К` stays in commercial sauna branch
- accessory and engineering items are not mixed into stove branches
- title, series, brand, volume, and docs come from the correct source layer
- no SKU is imported only from listing-page teaser text when a deeper SKU page exists


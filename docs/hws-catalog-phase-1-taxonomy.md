# HWS Catalog Phase 1: Taxonomy and Supplier Mapping

## Scope

This document locks the canonical HWS catalog tree for:
- frontend navigation
- WooCommerce taxonomy
- supplier content mapping
- SEO landing architecture

It must be treated as the source document for Phase 1 decisions.

## Top-level sections

Use exactly these top-level sections:

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

Rules:
- do not split `Парогенераторы` and `Хаммам` into separate top-level peers
- do not make brands top-level buying paths
- do not mix room type, fuel type, and brand at the same menu level

## URL tree

### 1. Russian bath stoves

`/catalog/russian-bath-stoves/`
- `/electric/`
- `/wood/`
- `/gas/`
- `/steam-thermal/`
- `/commercial/`
- `/by-volume/`

### 2. Sauna stoves

`/catalog/sauna-stoves/`
- `/electric/`
- `/gas/`
- `/design/`
- `/commercial/`
- `/for-home/`
- `/by-volume/`

### 3. Steam generators and hammam

`/catalog/steam-generators-and-hammam/`
- `/steam-generators/`
- `/steam-room-equipment/`
- `/control-systems/`
- `/commercial/`

### 4. Commercial

`/catalog/commercial/`
- `/commercial-bath-stoves/`
- `/commercial-sauna-heaters/`
- `/steam-generators/`
- `/spa-systems/`
- `/automation/`

### 5. Control units

`/catalog/control-units/`
- `/for-sauna-heaters/`
- `/for-steam-generators/`
- `/for-spa-systems/`

### 6. Chimneys and installation

`/catalog/chimneys-and-installation/`
- `/chimneys/`
- `/mounting-elements/`
- `/convection-elements/`
- `/gas-burners/`

### 7. Water tanks and heat exchangers

`/catalog/water-tanks-and-heat-exchangers/`
- `/water-tanks/`
- `/heat-exchangers/`
- `/economizers/`

### 8. Stones and cladding

`/catalog/stones-and-cladding/`
- `/heater-stones/`
- `/natural-stone-products/`
- `/cladding/`
- `/decor/`

### 9. Accessories

`/catalog/accessories/`
- `/bathing-accessories/`
- `/pouring-devices/`
- `/aroma-and-steam/`
- `/wood-storage/`

### 10. Brands

`/brands/`
- `/vvd/`
- `/easysteam/`
- `/sangens/`
- `/eos/`

## Primary category rules

Every product must have:
- one primary category
- zero or more attributes for filtering
- optional brand and series landing relations

Do not use multiple primary categories for broad discovery.

Examples:
- a steam generator must not primarily live under standard stove listings
- a control unit must not primarily live under heaters
- a chimney item must not primarily live under accessories

## Supplier mapping matrix

### VVD

Primary branches:
- `russian-bath-stoves/electric`
- `russian-bath-stoves/steam-thermal`
- `steam-generators-and-hammam/steam-generators`
- `control-units/for-steam-generators`
- `chimneys-and-installation`
- `water-tanks-and-heat-exchangers`

Series / groups:
- `ПАРиЖАР` -> `russian-bath-stoves/steam-thermal`
- `ФутуРус` -> `russian-bath-stoves/steam-thermal`
- `Премьера Руса` -> `russian-bath-stoves/steam-thermal`
- `АЭГПП` -> `steam-generators-and-hammam/steam-generators`
- VVD control panels -> `control-units/for-steam-generators`
- VVD chimneys -> `chimneys-and-installation/chimneys`
- VVD tanks and related hardware -> `water-tanks-and-heat-exchangers`

### EasySteam

Primary branches:
- `russian-bath-stoves/wood`
- `russian-bath-stoves/gas`
- `russian-bath-stoves/electric`
- `sauna-stoves`
- `commercial/commercial-bath-stoves`
- `chimneys-and-installation`
- `water-tanks-and-heat-exchangers`
- `stones-and-cladding`
- `accessories`

Series / groups:
- `Анапа` -> `russian-bath-stoves/wood`
- `Сочи` -> `russian-bath-stoves/wood`
- `Геленджик` -> `russian-bath-stoves/wood`
- `Южная` -> `russian-bath-stoves/wood`
- gas versions of bath series -> `russian-bath-stoves/gas`
- `VIVARTE` -> `russian-bath-stoves/electric`
- `Ялта` -> `sauna-stoves`
- `Анапа К`, `Сочи К`, `Геленджик К`, `Домна` lines -> `commercial/commercial-bath-stoves`
- burners, chimneys, tanks, heat exchangers -> corresponding engineering sections
- bathing accessories -> `accessories`

### Sangens

Primary branches:
- `sauna-stoves/electric`
- `commercial/commercial-sauna-heaters`
- `commercial/spa-systems`
- `steam-generators-and-hammam/control-systems`
- `control-units/for-sauna-heaters`
- `stones-and-cladding/decor`

Series / groups:
- `Series L` -> `sauna-stoves/electric`
- `Series W` -> `sauna-stoves/electric`
- `Spa System X1/X3/X5` -> `commercial/spa-systems`
- Sangens control systems -> `control-units/for-sauna-heaters`
- decorative frames and facing extras -> `stones-and-cladding/decor`

Note:
- Sangens may appear in Russian bath messaging on some source pages, but the HWS default retail placement remains sauna-first unless a separate validated bath branch is approved later.

### EOS

Primary branches:
- `sauna-stoves/electric`
- `commercial/commercial-sauna-heaters`
- `steam-generators-and-hammam/steam-room-equipment`
- `control-units/for-sauna-heaters`
- `accessories`

Series / groups:
- `Standard`, `Classic`, `Design` -> `sauna-stoves/electric`
- `Commercial small-scale projects` -> `commercial/commercial-sauna-heaters`
- `Commercial large-scale projects` -> `commercial/commercial-sauna-heaters`
- `Steam room equipment` -> `steam-generators-and-hammam/steam-room-equipment`
- `Sauna control units` -> `control-units/for-sauna-heaters`
- `Sauna accessories` -> `accessories`

Engineering-only groups:
- `Infrared systems`
- `Coloured light`
- `Dosing systems`
- `Flake ice machines`
- `Safety systems`

These should not be mixed into core retail stove listings. Keep them in project or engineering-oriented sections if and when they are exposed.

## Filter matrix

### Russian bath stoves

- fuel type
- equipment type
- steam-room volume
- power
- facing
- material
- brand
- series
- price

### Sauna stoves

- power source
- home/commercial
- volume
- power
- voltage
- design
- brand
- series
- price

### Steam generators and hammam

- equipment type
- home/commercial
- power
- voltage
- room volume
- brand
- series
- price

### Control units

- compatible brand
- compatible series
- supported power band
- control type
- price

### Engineering sections

- compatibility
- material
- dimension or diameter where relevant
- brand
- price

## SEO landing baseline

Index:
- top-level category pages
- meaningful subcategory pages
- brand hubs
- selected category + brand landings
- selected series landings

Do not index:
- random filter combinations
- sort states
- pagination states as standalone targets

Initial priority landings:
- `/catalog/russian-bath-stoves/`
- `/catalog/russian-bath-stoves/steam-thermal/`
- `/catalog/sauna-stoves/electric/`
- `/catalog/steam-generators-and-hammam/steam-generators/`
- `/catalog/commercial/commercial-bath-stoves/`
- `/catalog/commercial/commercial-sauna-heaters/`
- `/brands/vvd/`
- `/brands/easysteam/`
- `/brands/sangens/`
- `/brands/eos/`

## Phase 1 exit criteria

Phase 1 is complete when:
- catalog top-level sections are locked
- URL slugs are locked
- supplier placement is locked at category and series level
- primary category rules are locked
- filter matrix is approved
- no unresolved duplication remains in top-level intent structure

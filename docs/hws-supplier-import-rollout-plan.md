# HWS Supplier Import Rollout Plan

Updated: `2026-07-09`

## Decision

Импорт идёт не по брендам и не по меню поставщиков, а по HWS intent tree.

Следующий рабочий порядок:
1. `EasySteam` retail sauna lines
2. `EasySteam` commercial stove lines
3. `EasySteam` engineering and accessories
4. `VVD` core bath + steam-generator lines
5. `Sangens` retail electric + control
6. `EOS` sauna + steam room engineering

Такой порядок сохраняет UX и SEO-логику HWS:
- сначала основные retail stove branches
- потом commercial branches
- потом engineering and accessory branches
- потом premium / secondary suppliers

## Affected stream

- content / parser
- backend
- later frontend / SEO after data fill

## Source of truth

- supplier assortment pages
- live WooCommerce taxonomy in `wpsandbox`
- [hws-catalog-phase-1-taxonomy.md](/Users/macbookpro/Coding/creamui/docs/hws-catalog-phase-1-taxonomy.md)
- [hws-catalog-attribute-model.md](/Users/macbookpro/Coding/creamui/docs/hws-catalog-attribute-model.md)
- [hws-easysteam-import-matrix.csv](/Users/macbookpro/Coding/creamui/docs/hws-easysteam-import-matrix.csv)

## Exact entities affected

### Wave 2: EasySteam retail sauna

Source URLs:
- [Ялта 15](https://easysteam.ru/products/category/yalta-15)
- [Ялта 25](https://easysteam.ru/products/category/yalta-25)
- [Ялта 35](https://easysteam.ru/products/category/yalta-35)
- [Ялта 40](https://easysteam.ru/products/category/yalta-40)

Primary placement:
- `Ялта 15` -> `sauna-stoves`
- `Ялта 25` -> `sauna-stoves`
- `Ялта 35` -> `sauna-stoves`
- `Ялта 40` -> `sauna-stoves`

Reason:
- supplier explicitly positions the line as `печи для бани и сауны`
- series text says it has an integrated steam generator and can work on gas
- therefore HWS must not force this line into `electric-sauna-stoves`
- HWS keeps sauna-first primary placement and leaves mixed mode in attributes

Attribute baseline:
- `brand = EasySteam`
- `series = Ялта`
- `pa_equipment-type = bath-sauna-stove`
- `pa_room-type = bath-and-sauna`
- `pa_usage-class = private`
- `pa_fuel-type = mixed` only if confirmed on SKU/spec level
- `pa_cladding-material`, `pa_power`, `pa_steam-room-volume`, `pa_voltage` from SKU specs

### Wave 3: EasySteam commercial stoves

Source root:
- [Печи](https://easysteam.ru/products/stoves)

Primary placement:
- `Анапа К` -> `commercial/commercial-bath-stoves`
- `Сочи К` -> `commercial/commercial-bath-stoves`
- `Геленджик К` -> `commercial/commercial-bath-stoves`
- `Домна 45/60/80 К` -> `commercial/commercial-bath-stoves`
- `Домна 90/120 К ТВИН` -> `commercial/commercial-bath-stoves`
- `Ялта 15/25/35/40/50/60/80/100 К` -> `commercial/commercial-sauna-heaters`

Reason:
- on supplier side these are already split into `коммерческие бани` and `коммерческие бани и сауны`
- on HWS they must stay under one commercial parent with separate bath vs sauna child branches

### Wave 4: EasySteam engineering and accessories

Source URLs:
- [Дополнительное оборудование](https://easysteam.ru/products/optional-equipment)
- [Аксессуары](https://easysteam.ru/products/accessories)

Primary placement:
- `Баки для воды` -> `water-tanks-and-heat-exchangers/water-tanks`
- `Теплообменные устройства` -> `water-tanks-and-heat-exchangers/heat-exchangers`
- `Экономайзеры` -> `water-tanks-and-heat-exchangers/economizers`
- `Дымоходы` -> `chimneys-and-installation/chimneys`
- `Лист перекрытия` -> `chimneys-and-installation/mounting-elements`
- `Конвекционные дверки` -> `chimneys-and-installation/convection-elements`
- `Газовые горелки` -> `chimneys-and-installation/gas-burners`
- `Камни для каменки` -> `stones-and-cladding/heater-stones`
- `Изделия из природного камня` -> `stones-and-cladding/natural-stone-products`
- `Обливные устройства` -> `accessories/pouring-devices`
- `Запарник`, `Термоионатор` -> `accessories/aroma-and-steam`
- `Ковши`, `Опахало`, `Шапки для бани` -> `accessories/bathing-accessories`
- `Дровница` -> `accessories/wood-storage`

### Wave 5: VVD core import

Source root:
- [VVD catalog](https://vvd.su/product/)

Primary placement:
- `ПАРиЖАР`, `ФутуРус`, `Премьера Руса` -> `russian-bath-stoves/steam-thermal`
- electric bath lines -> `russian-bath-stoves/electric-bath-stoves`
- `АЭГПП` and related lines -> `steam-generators-and-hammam/steam-generators`
- VVD control panels -> `control-units/for-steam-generators`
- VVD chimneys -> `chimneys-and-installation/chimneys`
- VVD tanks / additional hardware -> engineering branches by item type

### Wave 6: Sangens retail and control

Source root:
- [Sangens catalog](https://sangens.com/ru/catalog/)

Primary placement:
- `Серия Л`, `Серия ЛС`, `Серия В` -> `sauna-stoves/electric-sauna-stoves`
- `Блок`, `Система управления 3П/3В/4В Про` -> `control-units/for-sauna-heaters`
- `Обливное` -> `accessories/pouring-devices`
- `Снег` -> `commercial/spa-systems`
- `Спа система` -> `commercial/spa-systems`
- `Свежий воздух` -> `commercial/automation`

Reason:
- current Sangens catalog exposes category, series, room volume, power, mode, material, control and price filters on one page
- retail heaters should stay sauna-first
- integration / wellness systems should not pollute heater listings

### Wave 7: EOS sauna and steam engineering

Source root:
- [EOS products](https://www.eos-sauna.com/en/products)

Primary placement:
- `Sauna Heaters` -> `sauna-stoves/electric-sauna-stoves`
- `Sauna control units` -> `control-units/for-sauna-heaters`
- `Steam room equipment` -> `steam-generators-and-hammam/steam-room-equipment`
- `Commercial small-scale projects` -> `commercial/commercial-sauna-heaters`
- `Commercial large-scale projects` -> `commercial/commercial-sauna-heaters`
- `Sauna Accessories` -> `accessories`

Secondary / deferred engineering:
- `Infrared systems`
- `Coloured light`
- `Dosing systems`
- `Flake ice machines`
- `Safety systems`

These should be loaded only after HWS decides whether to expose them as public retail categories or keep them under commercial engineering.

## Implementation order

1. Finish manifest and payload generation for `EasySteam wave 2`.
2. Import `Yalta` SKUs into `sauna-stoves` with mixed-use attributes.
3. Verify GraphQL and storefront category pages.
4. Prepare `EasySteam wave 3` manifest for commercial lines.
5. Import engineering / accessory groups only after core retail + commercial heaters are stable.
6. Start `VVD` with a separate normalized matrix before touching Sangens or EOS.

## Verification checklist

- every imported product has one primary category only
- no mixed-use series is forced into a wrong fuel-specific child category
- engineering products do not leak into heater branches
- commercial products do not sit in retail stove listings
- brand and series are attributes / taxonomies, not top-level buying paths
- category logic matches supplier mapping docs and live `wpsandbox`

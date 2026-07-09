# HWS Catalog Backend Audit

Audit date: `2026-07-09`

Source:
- live `wpsandbox` WordPress / WooCommerce
- GraphQL endpoint `https://wpsandbox.spaces.community/graphql`
- `wp-cli` inside `wpsandbox-wordpress-1`

## Current product categories

| term_id | slug | name | parent_id | count |
| --- | --- | --- | --- | --- |
| 58 | `russian-bath-stoves` | Печи для русской бани | 0 | 3 |
| 147 | `electric-bath-stoves` | Электрические печи для бани | 58 | 2 |
| 149 | `gas-bath-stoves` | Газовые печи для бани | 58 | 1 |
| 148 | `wood-bath-stoves` | Дровяные печи для бани | 58 | 1 |
| 59 | `sauna-stoves` | Печи для сауны | 0 | 1 |
| 150 | `electric-sauna-stoves` | Электрические печи для сауны | 59 | 1 |
| 151 | `wood-sauna-stoves` | Дровяные печи для сауны | 59 | 0 |
| 146 | `hammam-stoves` | Печи для хаммама | 0 | 1 |
| 152 | `electric-hammam-stoves` | Электрические печи для хаммама | 146 | 1 |
| 62 | `steam-generators` | Парогенераторы | 0 | 1 |
| 156 | `electric-steam-generators` | Электрические парогенераторы | 62 | 1 |
| 61 | `commercial-bath-stoves` | Коммерческие печи | 0 | 0 |
| 153 | `electric-commercial-stoves` | Электрические коммерческие печи | 61 | 0 |
| 155 | `gas-commercial-stoves` | Газовые коммерческие печи | 61 | 0 |
| 154 | `wood-commercial-stoves` | Дровяные коммерческие печи | 61 | 0 |
| 63 | `bath-accessories` | Аксессуары для бани | 0 | 0 |

## Current product brands

| term_id | slug | name | count |
| --- | --- | --- | --- |
| 57 | `easysteam` | EasySteam | 1 |
| 81 | `sangens` | Sangens | 1 |
| 82 | `%d0%b2%d0%b2%d0%b4` | ВВД | 1 |

Note:
- VVD brand slug is currently percent-encoded and should be normalized before it becomes part of stable URL strategy.

## Current product attributes

| id | slug | name |
| --- | --- | --- |
| 4 | `pa_fuel-type` | Тип топлива |
| 5 | `pa_steam-room-volume` | Объем парной |
| 6 | `pa_power` | Номинальная потребляемая мощность |
| 7 | `pa_cladding-type` | Тип облицовки |
| 8 | `pa_cladding-material` | Материал облицовки |
| 9 | `pa_connection-type` | Тип подключения |
| 10 | `pa_usage-class` | Класс использования |
| 13 | `pa_facing` | Облицовочный материал |
| 11 | `pa_color` | color |
| 12 | `pa_leg-material` | leg-material |

## Gap summary

Already available and useful:
- fuel type
- steam-room volume
- power
- usage class
- cladding/facing-related attributes

Missing or not yet explicit for the target catalog:
- equipment type
- room type as a stable dimension where needed
- voltage
- clean series dimension
- canonical commercial/home exposure across all brands

## Current -> target migration table

| Current slug | Current name | Target action | Target branch |
| --- | --- | --- | --- |
| `russian-bath-stoves` | Печи для русской бани | keep | `russian-bath-stoves` |
| `electric-bath-stoves` | Электрические печи для бани | keep | `russian-bath-stoves/electric` |
| `gas-bath-stoves` | Газовые печи для бани | keep | `russian-bath-stoves/gas` |
| `wood-bath-stoves` | Дровяные печи для бани | keep | `russian-bath-stoves/wood` |
| `sauna-stoves` | Печи для сауны | keep | `sauna-stoves` |
| `electric-sauna-stoves` | Электрические печи для сауны | keep | `sauna-stoves/electric` |
| `wood-sauna-stoves` | Дровяные печи для сауны | keep for now | `sauna-stoves/wood` if retained, otherwise merge later |
| `hammam-stoves` | Печи для хаммама | rename/merge | `steam-generators-and-hammam` |
| `electric-hammam-stoves` | Электрические печи для хаммама | rename/merge | `steam-generators-and-hammam/steam-room-equipment` |
| `steam-generators` | Парогенераторы | merge upward | `steam-generators-and-hammam` |
| `electric-steam-generators` | Электрические парогенераторы | keep as child under new parent | `steam-generators-and-hammam/steam-generators` |
| `commercial-bath-stoves` | Коммерческие печи | rename/expand | `commercial` |
| `electric-commercial-stoves` | Электрические коммерческие печи | keep under renamed parent | `commercial/commercial-bath-stoves` or split by equipment after content audit |
| `gas-commercial-stoves` | Газовые коммерческие печи | keep under renamed parent | `commercial/commercial-bath-stoves` |
| `wood-commercial-stoves` | Дровяные коммерческие печи | keep under renamed parent | `commercial/commercial-bath-stoves` |
| `bath-accessories` | Аксессуары для бани | rename/expand | `accessories` |

## New target branches to create

These do not exist yet and should be added during Phase 2:

- `steam-generators-and-hammam`
- `steam-generators-and-hammam/steam-room-equipment`
- `steam-generators-and-hammam/control-systems`
- `steam-generators-and-hammam/commercial`
- `commercial/commercial-sauna-heaters`
- `commercial/spa-systems`
- `commercial/automation`
- `control-units`
- `control-units/for-sauna-heaters`
- `control-units/for-steam-generators`
- `control-units/for-spa-systems`
- `chimneys-and-installation`
- `chimneys-and-installation/chimneys`
- `chimneys-and-installation/mounting-elements`
- `chimneys-and-installation/convection-elements`
- `chimneys-and-installation/gas-burners`
- `water-tanks-and-heat-exchangers`
- `water-tanks-and-heat-exchangers/water-tanks`
- `water-tanks-and-heat-exchangers/heat-exchangers`
- `water-tanks-and-heat-exchangers/economizers`
- `stones-and-cladding`
- `stones-and-cladding/heater-stones`
- `stones-and-cladding/natural-stone-products`
- `stones-and-cladding/cladding`
- `stones-and-cladding/decor`
- `brands`

## Recommended immediate backend actions

1. Normalize brand slugs, especially `ВВД`.
2. Introduce the merged parent `steam-generators-and-hammam`.
3. Move `hammam-stoves` and `steam-generators` children under the new merged parent.
4. Rename `commercial-bath-stoves` parent to the broader `commercial` intent.
5. Add missing engineering sections before importing more supplier content.

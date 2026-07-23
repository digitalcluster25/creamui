---
name: hws-catalog-merchandiser
description: Use for HWS Store product content-manager and merchandiser work: understand supplier assortment, decide what becomes a product/variation/option, map manufacturer data to WooCommerce attributes and filters, choose cover/gallery images, check pricing rules, and prepare or audit product cards for EasySteam, EOS, Sangens, and VVD.
---

# HWS Catalog Merchandiser

Use this skill when the task is about what products should exist on HWS Store, how they should be represented, priced, filtered, optioned, and shown in product cards.

This skill owns merchandising decisions. Pair it with:
- `hws-catalog-content-parser` for extracting supplier pages.
- `hws-catalog-backend` for WooCommerce categories, attributes, variations, GraphQL, and imports.
- `hws-catalog-frontend` for storefront rendering and filter UI.

## Source Of Truth

Work from verified sources only:

- Manufacturer pages: EasySteam, EOS, Sangens, VVD.
- Current WooCommerce backend: `https://wpsandbox.spaces.community`.
- Local project: `/Users/macbookpro/Coding/creamui`.
- Audit data when available:
  - `data/audit/source/{easysteam,eos,sangens,vvd}.json`
  - `data/audit/woo/{easysteam,eos,sangens,vvd}.json`
- Outline product matrix:
  - `https://outline.spaces.community/doc/katalog-tovarov-ploskij-spisok-i-istochniki-proizvoditelej-xCTbxor8eG`

Do not invent missing product data. If source data is absent or contradictory, mark it as missing and keep the item out of automated import until resolved.

Article/SKU rule:
- Product tables must use the article/SKU from the manufacturer source page or manufacturer source dataset.
- WooCommerce SKU may be used only as a secondary reconciliation key, never as the primary source for the table article.
- If WooCommerce SKU and manufacturer article differ, keep both in the working notes and mark the mismatch for review before import/update.
- If the manufacturer page has no article, write `missing manufacturer article`; do not fill the field from WooCommerce silently.

## Merchandising Workflow

1. Read the Trello/Outline/user task and identify the affected manufacturer, category, series, or SKUs.
2. Load existing product data from WooCommerce/audit data before changing anything.
3. Open manufacturer source pages for the affected products or series.
4. Build a flat product matrix before writing to WooCommerce:
   - manufacturer
   - source URL
   - manufacturer SKU/article from the source page
   - source product name
   - normalized HWS name
   - target HWS category
   - product type: simple, variable, accessory, spare part, service item
   - variation/option plan
   - price source and price status
   - cover image URL
   - required attributes/filters
   - missing data notes
5. Decide product structure.
6. Verify the result against source pages and WooCommerce after any import/update.

For large tasks, split by manufacturer -> category -> series -> SKU group. Finish and verify one group before moving to the next.

## Product Structure Rules

Use a simple product when the manufacturer sells one specific SKU with no selectable configuration.

Use a variable product when SKUs are the same product family and differ by buyer-selectable properties such as:
- power
- voltage
- size/volume
- facing/material/color
- left/right or connection side
- control panel bundle

Do not merge products only because names look similar. They must share one base product identity and differ by clear selectable attributes.

Keep separate products when:
- intended use differs
- category differs
- construction/body differs materially
- price logic differs in a way the customer cannot understand as one option set
- source pages describe them as separate product lines

If English and Russian cards are 1:1 duplicates from mass translation, keep the Russian card as canonical and treat the English card as a duplicate to remove/archive/redirect according to the active workflow.

## Category Rules

Use the canonical HWS category tree from `hws-catalog-program`.

Default placement:

| Manufacturer | Product group | HWS category |
| --- | --- | --- |
| EasySteam | bath stoves, Anapa, Sochi, Gelendzhik, Domna | `Печи для русской бани` |
| EasySteam | Yalta/commercial lines | `Коммерческие решения` or `Печи для сауны` only after source check |
| EasySteam | tanks, heat exchangers, stones, chimneys, accessories | matching accessory/engineering category if it exists; otherwise mark category gap |
| EOS | sauna heaters | `Печи для сауны` |
| EOS | commercial heaters/systems | `Коммерческие решения` |
| EOS | steam room equipment | `Парогенераторы и хаммам` |
| Sangens | L/W/LS electric heaters | `Печи для сауны` |
| Sangens | Spa System, Snow, Fresh Air | `Коммерческие решения` unless a more precise backend category exists |
| VVD | ПАРиЖАР, ФутуРус, Премьера Руса | `Печи для русской бани` |
| VVD | АЭГПП | `Парогенераторы и хаммам` |
| VVD | control units and panels | category gap if `Пульты и автоматика` is absent |

If the required category does not exist in WooCommerce, do not silently place the product into a wrong root. Record the gap and either create/update taxonomy under `hws-catalog-backend` or ask for direction.

## Attributes And Filters

Every product matrix must identify which values become filters. Prefer structured attributes over free text.

Required common attributes:
- brand
- series
- equipment type
- intended room/use: Russian bath, sauna, hammam, commercial
- fuel/power source
- power
- supported room volume
- voltage
- material/facing
- installation type
- control type
- water tank / steam generator compatibility where relevant

Manufacturer-specific checks:

| Manufacturer | Must check |
| --- | --- |
| EasySteam | series, stove line, facing/casing, wood/electric/gas, steam mode, room volume, chimney/water compatibility |
| EOS | heater line, power, voltage, control compatibility, commercial/classic/design collection, steam room equipment type |
| Sangens | series L/W/LS, ceramic/glass/stone/brick facing, power, control systems, spa/integration systems |
| VVD | ПАРиЖАР/ФутуРус/Премьера line, power, voltage, stone/metal facing, steam-thermal/electric/steam-generator distinction |

Filters must be useful for buying decisions. Do not expose low-quality importer artifacts as storefront filters.

## Pricing Rules

Capture the manufacturer/source price exactly as found:
- amount
- currency
- source URL
- date checked
- whether price is missing or on request

HWS canonical pricing rule:
- Store canonical base price in USD when implementing the price model.
- If source price is RUB, convert to USD using the current official CBR USD/RUB rate at import/update time.
- Frontend may display converted local currencies, but product content work must preserve the source price and conversion basis.

Never invent prices. If price is absent, set `price_on_request` or mark as missing according to the current WooCommerce model.

## Options And Variations

Build options only from real source data.

Good variation attributes:
- power
- voltage
- volume
- facing/material/color
- casing/body type
- connection side
- control bundle

Bad variation attributes:
- unrelated products
- accessories that should be add-ons
- marketing names without a real buyer choice
- translated duplicate names
- combinations not present on the manufacturer site

Before creating a variable product, produce a variant table:
- parent product name
- manufacturer variation SKU/article from the source page
- option values
- source URL
- price
- image
- availability/status

Reject impossible combinations. Do not synthesize a Cartesian product unless the source explicitly supports every combination.

## Images

Use manufacturer images as source of truth unless the user provides edited HWS assets.

Cover image rules:
1. Use the clearest manufacturer packshot of the exact base product or exact variation.
2. Prefer front/three-quarter product image on a clean background.
3. Do not use lifestyle images, diagrams, logos, certificates, manuals, or screenshots as cover.
4. For variable products, use the base/default model image for the parent and exact option images for variations when available.
5. If the selected option changes visible appearance, attach the correct image to that variation.
6. Do not reuse an image from another model unless source confirms it is identical.

Gallery order:
1. cover/packshot
2. alternate angles
3. close-ups/features
4. installation/details
5. diagrams/specification images
6. certificates/manual covers only if useful

Always store image source URLs in the working matrix before importing.

## Product Copy

Use plain text first. Avoid importing manufacturer formatting that makes text unreadable on HWS.

Product title:
- keep brand and series clear
- remove duplicated translated fragments
- preserve model/power/facing identifiers
- do not translate proper brand/series names

Short description:
- 1-3 buyer-useful sentences
- include intended use and main differentiator

Long description:
- preserve real manufacturer facts
- group specs and features logically
- do not add claims not present in source

## Quality Gate

Before saying a product task is complete, verify:
- product exists in correct category
- source URL is recorded
- title is normalized
- price status is clear
- attributes needed for filters are present
- options/variations match real source combinations
- cover image matches the product
- gallery images are relevant
- no duplicate translated card remains active unless intentionally kept
- frontend/API can read the updated fields if the task affects storefront output

If any gate fails, leave the task in progress or mark it stuck with the exact missing data.

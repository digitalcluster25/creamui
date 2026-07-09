---
name: hws-catalog-program
description: Use for any HWS catalog restructuring task that spans multiple streams: storefront UX, WordPress/WooCommerce taxonomy, supplier content ingestion, catalog SEO, filters, category tree, menu, or rollout planning. Load this first to choose the right worker skill and keep one canonical catalog structure.
---

# HWS Catalog Program

Use this skill as the entry point for HWS catalog work.

Project scope:
- storefront: `https://hwsstore.spaces.community`
- WordPress / WooCommerce / GraphQL source: `https://wpsandbox.spaces.community`
- frontend repo root: `/Users/macbookpro/Coding/creamui`
- WordPress server root: `/docker/wpsandbox`

## Canonical rules

- There must be one canonical catalog tree for frontend, backend, content, and SEO.
- Top-level navigation is built by user intent, not by brand and not by raw technical attributes.
- `Парогенераторы и хаммам` is one top-level section, not two separate peers.
- A product must have one primary catalog category.
- Cross-links, filters, and brand pages may expose the product elsewhere, but the primary listing must stay semantically correct.
- Temporary frontend overrides are allowed only when `wpsandbox` does not yet contain the required data.

## Streams

Choose exactly one primary stream for the task, then load the matching worker skill:

- `hws-catalog-frontend` for storefront navigation, catalog pages, filters UI, cards, breadcrumbs, and page composition.
- `hws-catalog-backend` for WordPress taxonomy, WooCommerce categories/attributes, GraphQL shaping, import targets, and source-of-truth rules.
- `hws-catalog-content-parser` for supplier assortment study, normalization, import mapping, brand/series mapping, and content fill.
- `hws-catalog-seo` for URL structure, indexation, metadata, landing pages, and internal linking.

If the task spans several streams:
1. Define the canonical catalog decision first.
2. Update backend taxonomy and data model second.
3. Update content mapping third.
4. Update frontend rendering fourth.
5. Finalize SEO rules last.

## Required output shape

For every non-trivial task, produce:
- decision
- affected stream
- source of truth
- exact entities affected
- implementation order
- verification checklist

## HWS target catalog

Use this as the default target unless the user explicitly changes it:

- `Печи для русской бани`
- `Печи для сауны`
- `Парогенераторы и хаммам`
- `Коммерческие решения`
- `Пульты и автоматика`
- `Дымоходы и монтаж`
- `Баки и теплообменники`
- `Камни и облицовка`
- `Аксессуары`
- `Бренды`

## Supplier placement baseline

- `VVD`: steam-thermal bath furnaces, electric bath furnaces, steam generators, control units, installation parts.
- `EasySteam`: bath furnaces, sauna-adjacent furnace lines, commercial bath furnaces, installation parts, accessories.
- `Sangens`: electric sauna heaters, spa systems, control systems, premium cladding/decor.
- `EOS`: sauna heaters, commercial sauna systems, steam room engineering, control units, engineering accessories.

## Validation

Before closing any task, verify:
- the same category logic is reflected in frontend, backend, content mapping, and SEO
- no duplicate top-level intent sections were introduced
- no product is primarily listed in a semantically wrong section
- `wpsandbox` remains the data source unless a temporary override is explicitly documented

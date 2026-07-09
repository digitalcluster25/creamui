---
name: hws-catalog-seo
description: Use for HWS catalog SEO tasks: URL design, indexation policy, landing-page strategy, brand hubs, series pages, facet rules, titles/H1/meta, breadcrumbs, internal linking, and catalog pages that must rank without creating SEO junk.
---

# HWS Catalog SEO

Use this skill when the task concerns search structure or SEO landing logic.

## SEO rules

- Index categories, meaningful subcategories, brand hubs, series pages, and a small curated set of demand-backed filter landings.
- Do not index arbitrary filter combinations.
- Keep URL structure aligned with the backend taxonomy.
- Use one landing per real search intent.
- Do not let the frontend expose crawlable junk states by default.

## Primary indexable pages

- `/catalog/russian-bath-stoves/`
- `/catalog/sauna-stoves/`
- `/catalog/steam-generators-and-hammam/`
- `/catalog/commercial/`
- `/catalog/control-units/`
- `/catalog/chimneys-and-installation/`
- `/catalog/water-tanks-and-heat-exchangers/`
- `/catalog/stones-and-cladding/`
- `/catalog/accessories/`
- `/brands/vvd/`
- `/brands/easysteam/`
- `/brands/sangens/`
- `/brands/eos/`

## Allowed secondary landings

Create only when there is real demand or clear business value:
- brand within a category
- major series within a category
- equipment type within a category
- high-value volume or power bands
- commercial variants

Examples:
- `sauna-stoves/electric/sangens`
- `russian-bath-stoves/steam-thermal/vvd`
- `steam-generators-and-hammam/steam-generators`

## Facet policy

- canonicalize filter junk to the nearest approved landing
- keep sort, pagination, and ephemeral filter states out of the index
- avoid crawlable combinations like brand + price + volume + availability unless explicitly approved

## On-page requirements

Every indexable landing should have:
- unique title
- unique H1
- intro copy
- visible subcategory or brand links
- breadcrumbs
- FAQ or supporting copy where useful

## Validation

- URL matches catalog logic
- no duplicate intent pages
- no top-level duplication like separate `хаммам` and `парогенераторы` peers when one parent intent is enough
- internal links reinforce the chosen hierarchy

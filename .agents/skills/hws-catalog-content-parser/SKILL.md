---
name: hws-catalog-content-parser
description: Use for HWS supplier-content work: analyze manufacturer assortments, normalize products, map brands and series into the HWS catalog tree, prepare import payloads, identify gaps in wpsandbox, and stage content for WooCommerce.
---

# HWS Catalog Content Parser

Use this skill when the task is about supplier assortment and product content.

Suppliers in scope:
- `VVD`
- `EasySteam`
- `Sangens`
- `EOS`

## Content rules

- Normalize supplier naming before import.
- Separate four things clearly: category, equipment type, brand, series.
- Do not let supplier navigation dictate HWS navigation.
- Put products into the HWS catalog by user buying intent, not by supplier website menu.
- Mark uncertain mappings explicitly instead of guessing.

## Required content fields

Per product or series capture:
- brand
- series
- product title
- equipment type
- intended use: bath / sauna / hammam / commercial
- fuel or power source
- power
- supported volume
- voltage
- material / facing
- short description
- long description
- key features
- images
- documents
- compatibility if relevant

## Mapping baseline

- supplier series pages may become HWS landing pages
- supplier single SKUs stay product pages
- supplier accessories must not be mixed into furnace categories
- engineering systems from EOS stay in engineering or commercial branches unless a direct retail path is explicitly defined

## Working order

1. Read supplier source pages.
2. Build normalized product matrix.
3. Map each item to one primary HWS category.
4. Note required attributes and missing backend fields.
5. Prepare import/update artifacts for `wpsandbox`.

## Output shape

Always produce:
- source URLs
- normalized item name
- proposed HWS category
- proposed brand
- proposed series
- confidence note if mapping is ambiguous

---
name: hws-catalog-backend
description: Use for HWS WordPress/WooCommerce catalog architecture tasks: taxonomy design, category tree, attributes, product primary category rules, GraphQL data shape, import targets, and source-of-truth changes in wpsandbox.spaces.community.
---

# HWS Catalog Backend

Use this skill for backend catalog structure and product data modeling.

Targets:
- WordPress / WooCommerce in `wpsandbox`
- GraphQL schema usage consumed by `frontend/`
- import and sync scripts under `/docker/wpsandbox` or repo helpers

## Backend rules

- WordPress taxonomy is the source of truth for catalog structure.
- The frontend must consume backend truth, not redefine taxonomy in code long-term.
- Every product needs one primary category.
- Secondary discoverability belongs in attributes, tags, related sections, or curated landing pages.
- Brand, series, voltage, power, volume, commercial/home, fuel type should be modeled as structured attributes where possible.

## Required category backbone

- `russian-bath-stoves`
- `sauna-stoves`
- `steam-generators-and-hammam`
- `commercial`
- `control-units`
- `chimneys-and-installation`
- `water-tanks-and-heat-exchangers`
- `stones-and-cladding`
- `accessories`
- `brands`

## Product placement baseline

- `VVD ПАРиЖАР / ФутуРус / Премьера Руса` -> `russian-bath-stoves/steam-thermal`
- `VVD АЭГПП` -> `steam-generators-and-hammam/steam-generators`
- `EasySteam Анапа / Сочи / Геленджик / Южная` -> `russian-bath-stoves`
- `EasySteam Ялта` -> `sauna-stoves`
- `Sangens Series L / Series W` -> `sauna-stoves/electric`
- `Sangens Spa System` -> `commercial/spa-systems` or `steam-generators-and-hammam` only if the user asks for that exposure
- `EOS heaters` -> `sauna-stoves/electric` or `commercial/commercial-sauna-heaters`

## Data workflow

1. Define or update taxonomy.
2. Define required attributes and term sets.
3. Map supplier products into categories and attributes.
4. Verify GraphQL exposes what frontend needs.
5. Remove frontend overrides only after backend data is complete.

## Verification

- category tree is internally consistent
- primary category exists for every migrated product
- attributes support intended filters
- GraphQL returns category, brand, series, price, images, documents, and variations correctly

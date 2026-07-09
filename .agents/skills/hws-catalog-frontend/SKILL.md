---
name: hws-catalog-frontend
description: Use for HWS storefront catalog UX tasks: top navigation, mega menu, category pages, subcategory hubs, filter layout, breadcrumbs, product cards, brand blocks, sorting, and catalog page rendering for hwsstore.spaces.community.
---

# HWS Catalog Frontend

Use this skill when the task changes how the catalog is presented on the storefront.

Targets:
- `frontend/`
- `UIkit/` only when needed as a reference or parallel block source

## Frontend rules

- Render the catalog by user intent first, then subtype, then brand/series.
- Never expose a flat menu made of intersecting leaf categories.
- Category pages must show subcategory entry points before deep filtering.
- Filters must reflect the current branch of the tree, not a global one-size-fits-all set.
- Brand pages are support pages, not the main navigation backbone.

## Required page anatomy

For main categories:
- H1
- breadcrumbs
- hero or intro block
- subcategory grid
- brand shortcuts
- filter panel
- product list
- SEO copy and FAQ near the bottom

For brand pages:
- brand intro
- series or product family shortcuts
- category shortcuts within that brand
- product list

## Filter behavior

`Печи для русской бани`:
- fuel
- equipment type
- steam-room volume
- power
- facing/material
- brand
- series
- price

`Печи для сауны`:
- electric/gas
- home/commercial
- volume
- power
- voltage
- design
- brand
- series
- price

`Парогенераторы и хаммам`:
- equipment type
- home/commercial
- power
- voltage
- room volume
- brand
- series
- price

## Implementation order

1. Confirm the canonical branch names and URLs.
2. Map navigation components to the new tree.
3. Map category templates and filter panels.
4. Remove temporary hardcoded leaf navigation that conflicts with the new tree.
5. Verify pages with live `wpsandbox` data where applicable.

## Do not do

- Do not invent new catalog branches from UI convenience alone.
- Do not expose filters as if they were permanent category structure.
- Do not keep irrelevant products in a listing just because a shared card component makes it easy.

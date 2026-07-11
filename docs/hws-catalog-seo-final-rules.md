# HWS Catalog SEO Final Rules

Updated: `2026-07-11`

This document is the final source of truth for catalog indexation, canonical behavior, and series handling.

## Canonical page types

Indexable by default:

- `/catalog`
- `/catalog/{top-branch}`
- `/catalog/{meaningful-child-category}`
- `/brands`
- `/brands/{brand}`

Not indexable by default:

- filtered category URLs
- filtered brand URLs
- sort states
- pagination query states
- future series query states

Conditionally indexable later:

- selected series landings, only by explicit allowlist

## Canonical rules

- Clean landing pages use a self-referencing canonical.
- Any query-driven state keeps the canonical on the clean landing URL.
- Filtered URLs must not create a separate indexable target.
- Series chip / series filter URLs stay canonicalized to the parent landing page.

## Robots rules

- Clean landing pages: `index, follow`
- Any URL with query params used for filtering, sorting, or series state: `noindex, follow`
- Googlebot follows links on filtered pages, but the page itself is not indexable

## Internal linking rules

- Catalog root links to top-level branches.
- Branch pages link to child categories and relevant brand shortcuts.
- Brand pages link back into the most relevant catalog categories.
- Child categories can show brand shortcuts when there are at least two relevant brands in the branch.
- Series is used only as a discovery layer inside category/brand pages, not as a top-level navigation axis.

## Series policy

Series stays `filter-first` in the current rollout.

Allowed:

- filter chip
- discovery shortcut
- brand/category support signal

Not allowed:

- automatic page generation for every series term
- top-level navigation
- replacing category hierarchy
- indexing query-driven series states

Series pages may be added only if all of these are true:

1. the series has real commercial demand
2. the series has stable naming
3. the page can carry unique content
4. the page does not duplicate a brand or category landing intent
5. the page is approved manually in mapping/SEO planning

## Implementation notes

- `buildCatalogRobots()` is the guardrail for query-state indexation.
- `CatalogSeo` remains the content block for branch and category SEO copy.
- The importer must keep `pa_series` normalized and human-readable.
- WooCommerce should not create duplicate pseudo-series from spelling variants.

## Current status

- root catalog is indexable
- branch and meaningful child category pages are indexable
- filtered states are noindex
- series is filter-only
- no mass-generated series pages


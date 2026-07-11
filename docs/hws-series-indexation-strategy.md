# HWS Series Indexation Strategy

Updated: `2026-07-11`

## Purpose

This document locks the SEO and UX role of `series` inside the HWS catalog.

It exists to prevent three common failures:

1. creating indexable junk from filter URLs
2. making brand pages and series pages compete for the same intent
3. rebuilding the same logic later in frontend, backend, and SEO as separate systems

## Current implementation truth

- categories are the main buying path
- brand pages are support landing pages
- `series` currently exists as an attribute and filter dimension
- filtered URLs are already canonicalized and noindexed
- there are no dedicated live `/series/...` pages yet

## Decision

For the current HWS rollout:

- `series` stays **filter-first**, not page-first
- `series` must **not** become a mass-generated indexable page type
- category URLs and brand URLs remain the indexable catalog backbone

This means:

- `/catalog/...?...series=...` stays `noindex, follow`
- `/brands/...?...series=...` stays `noindex, follow`
- no standalone series route is added by default

## When a series may deserve its own page

Create a dedicated series landing only if all conditions below are true:

1. the series is commercially real and stable
2. the series has clear search demand by name
3. the series contains enough products or configurations to justify a page
4. the page can carry unique intro copy, series-specific facts, and internal links
5. the page does not duplicate an existing brand or category landing intent

If one of these is missing, the series remains only:

- a product attribute
- a filter
- a discovery chip on brand/category pages

## Approved page hierarchy

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
- future pagination query params
- future series query params

Conditionally indexable later:

- selected series landings, only by explicit allowlist

## Series allowlist policy

If HWS decides to launch series pages later, use an allowlist model:

- no automatic page generation for every `pa_series` term
- each series page must be approved manually in mapping/SEO planning
- each page must be tied to one primary search intent:
  - category-within-brand
  - flagship commercial line
  - highly searched named line

Examples of possible future candidates:

- a flagship `EasySteam` line with real branded demand
- a `Sangens` line with enough products and content depth
- a commercial `EOS` range where the range itself is a search target

Examples of bad candidates:

- tiny accessory series
- internal grouping terms
- technical import buckets
- one-product pseudo-series

## UX role of series today

Series should currently be used in storefront only as:

- quick discovery chips
- branch-aware filter options
- optional grouping logic inside brand context

Series should not become:

- top navigation
- top-level catalog branch
- a replacement for category architecture

## Backend requirements

To keep this strategy clean:

- `pa_series` names must stay normalized and human-readable
- importer must not create duplicate pseudo-series for spelling variants
- every product still has one primary category independent of series

## Next implementation implication

Current action:

- keep series as filter-only for indexation purposes
- continue fixing term normalization in Woo where needed

Future action only after approval:

- design and ship allowlisted dedicated series templates
- add explicit metadata rules for those pages
- add internal linking rules from category and brand pages

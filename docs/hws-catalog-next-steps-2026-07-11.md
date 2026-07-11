# HWS Catalog Next Steps

Updated: `2026-07-11`

## Current truth

- import and audit closure is complete for `EasySteam`, `VVD`, `Sangens`, `EOS`
- category hub, subcategory grid, branch-aware filters, catalog SEO blocks, and brand pages are already implemented
- next real phase is `Phase E: SEO rollout` plus live storefront QA and small backend cleanup

## Priority order

### 1. SEO rollout

Owner:
- frontend + seo

Tasks:
1. lock metadata rules for:
   - `/catalog`
   - `/catalog/{branch}`
   - `/catalog/{child-category}`
   - `/brands`
   - `/brands/{brand}`
2. ensure filtered states are not indexable:
   - catalog filters
   - brand filters
   - sort and any future faceted URLs
3. review canonical behavior for:
   - clean landing URL
   - filtered URL
   - paginated URL if pagination becomes query-driven
4. formalize internal linking:
   - catalog root -> branch
   - branch -> child category
   - branch/category -> brand page
   - brand page -> category page
5. decide whether `series` gets:
   - dedicated landing pages
   - or stays as filter-only dimension

Current decision:
- for the current rollout, `series` stays filter-only for indexation purposes
- no mass-generated standalone series pages
- later, only allowlisted series pages may become indexable
- source of truth: [hws-series-indexation-strategy.md](/Users/macbookpro/Coding/creamui/docs/hws-series-indexation-strategy.md)

Acceptance:
- no indexable filter junk
- one canonical URL per landing intent
- brand pages and category pages do not compete for the same intent without a clear role split

### 2. Live storefront QA

Owner:
- frontend + QA

Tasks:
1. verify top branches on live frontend:
   - `/catalog`
   - `/catalog/russian-bath-stoves`
   - `/catalog/sauna-stoves`
   - `/catalog/steam-generators-and-hammam`
2. verify child-category behavior:
   - breadcrumbs
   - subcategory cards
   - product counts
   - empty states
3. verify filters per branch:
   - only relevant filters are shown
   - deep links restore UI state
   - chips and URL stay in sync
4. verify brand pages:
   - category shortcuts are relevant
   - filters do not expose junk states
   - product cards inherit correct category/brand context

Acceptance:
- no broken navigation path from root catalog to product
- no wrong filter set on any main branch
- no branch page without usable discovery path

### 3. Backend cleanup

Owner:
- backend

Tasks:
1. review GraphQL payload for anything still forcing frontend assumptions
2. verify primary category and attribute completeness on a sample from each brand
3. decide whether VVD variation matrix needs a dedicated follow-up task
4. clean any Woo legacy rows or duplicated catalog entities discovered during QA

Acceptance:
- frontend does not need undocumented workarounds
- taxonomy remains the source of truth

### 4. Optional expansion

Owner:
- content + backend

Tasks:
1. next supplier wave only after QA and SEO are stable
2. extend engineering/accessory branches if business wants more assortment depth

## Immediate working queue

1. fix SEO behavior for brand filter URLs
2. add metadata for `/brands`
3. run live QA checklist on main catalog branches
4. write final SEO rules for series landings: `indexable` or `filter-only`
5. only then prepare merge / PR to `main`

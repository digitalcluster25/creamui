# HWS Catalog Implementation Plan

Updated: `2026-07-09`

## Purpose

This is the working execution plan for the HWS catalog rebuild.

It is not a concept note. It is the task order, ownership split, dependencies, and acceptance checklist for the next implementation steps.

Related source docs:
- [hws-catalog-program.md](/Users/macbookpro/Coding/creamui/docs/hws-catalog-program.md)
- [hws-catalog-phase-1-taxonomy.md](/Users/macbookpro/Coding/creamui/docs/hws-catalog-phase-1-taxonomy.md)
- [hws-catalog-backend-audit.md](/Users/macbookpro/Coding/creamui/docs/hws-catalog-backend-audit.md)

## Current status

### Done

- canonical top-level catalog structure approved
- duplicate top-level split between `Парогенераторы` and `Хаммам` removed
- live WooCommerce category tree reworked to the new backbone
- initial live product recategorization completed for existing published products
- frontend header menu switched to the new category tree
- frontend footer links switched to the new category tree
- product card category label logic improved so child category can be shown instead of a random first category
- local skills created for `program`, `backend`, `frontend`, `content`, `seo`

### Partially done

- backend category tree exists, but attribute model is still incomplete
- frontend category pages work, but branch-specific filters are not implemented yet
- supplier assortment mapping is approved at a high level, but not yet converted into import-ready matrices
- SEO structure is defined, but indexation and landing implementation are not applied yet

### Not done

- normalized product matrix for all supplier lines
- attribute completion in WooCommerce
- branch-specific filter panels
- brand hubs and series landing pages
- SEO metadata, indexation rules, canonical rules, internal linking rollout

## Execution streams

### Stream 1. Backend

Skill:
- `.agents/skills/hws-catalog-backend`

Goal:
- make WordPress taxonomy and attributes the only long-term source of truth for catalog structure and filters

Tasks:
1. Finalize child branches under each top-level section where placeholders still exist.
2. Create or normalize missing attributes:
   - equipment type
   - voltage
   - series
   - room type where needed
   - commercial or home usage
3. Decide how primary category is stored and verified during imports.
4. Verify GraphQL returns parent and child categories, brands, prices, media, and attributes needed by the frontend.
5. Remove any remaining frontend assumptions that exist only because backend data is incomplete.

Acceptance:
- every imported product has one primary category
- backend exposes enough structure for filters without frontend hardcoding
- GraphQL shape is stable for catalog pages

### Stream 2. Content / parser

Skill:
- `.agents/skills/hws-catalog-content-parser`

Goal:
- turn supplier assortments into normalized import-ready catalog data

Tasks:
1. Build one normalized matrix per supplier:
   - brand
   - series
   - product title
   - primary HWS category
   - equipment type
   - fuel or power source
   - power
   - room volume
   - voltage
   - description
   - images
   - documents
2. Split supplier navigation from HWS navigation.
3. Mark ambiguous mappings explicitly instead of guessing.
4. Prepare import artifacts for WooCommerce.
5. Load products in waves by supplier or by branch.

Acceptance:
- every supplier SKU or series has one proposed primary HWS category
- no accessories or engineering items leak into stove listings
- content import payloads are ready for backend load

### Stream 3. Frontend

Skill:
- `.agents/skills/hws-catalog-frontend`

Goal:
- make storefront navigation and category pages follow the approved catalog tree and real backend data

Tasks:
1. Keep top navigation aligned with live taxonomy.
2. Add subcategory entry blocks on category pages before deep filters.
3. Replace generic filter UI with branch-specific filters:
   - `russian-bath-stoves`
   - `sauna-stoves`
   - `steam-generators-and-hammam`
   - later the engineering branches
4. Add category intro and bottom SEO blocks to major landings.
5. Add brand shortcuts on category pages where they help discovery.
6. Add empty-state and fallback behavior for branches with no products yet.

Acceptance:
- menu uses only approved top-level intents
- category pages show subcategory navigation before filter overload
- filters depend on current branch, not on one global schema
- listing composition stays semantically correct

### Stream 4. SEO

Skill:
- `.agents/skills/hws-catalog-seo`

Goal:
- convert the catalog tree into a clean indexable structure without facet junk

Tasks:
1. Lock final indexable pages:
   - top-level categories
   - meaningful child categories
   - brand hubs
   - selected series pages
2. Define canonical behavior for sort, pagination, and filter combinations.
3. Write metadata rules for category, brand, and series landings.
4. Add breadcrumbs and internal linking rules.
5. Define which filter states are noindex or canonicalized.

Acceptance:
- one landing per real search intent
- no duplicate intent pages
- no crawlable junk combinations

## Dependency order

Work must go in this order:

1. Backend category and attribute truth
2. Supplier normalization and import mapping
3. Frontend category templates and filters
4. SEO rollout on top of stable URLs and page types

Reason:
- if filters or SEO landings are built before backend truth and content mapping are stable, they will be wrong and will need to be rebuilt

## Concrete work queue

### Phase A. Stabilize backend data model

Owner:
- backend

Tasks:
1. Audit live attributes in WooCommerce.
2. Add missing attributes and agreed term sets.
3. Verify GraphQL exposure for those attributes.
4. Document the primary category rule for imports.

Exit:
- backend can fully describe the catalog without frontend taxonomy hacks

### Phase B. Build supplier matrices

Owner:
- content / parser

Tasks:
1. VVD matrix
2. EasySteam matrix
3. Sangens matrix
4. EOS matrix
5. ambiguous item register

Exit:
- import-ready mapping exists for all priority suppliers

### Phase C. Import products in controlled waves

Owner:
- backend + content

Suggested order:
1. EasySteam bath stove lines
2. Sangens sauna lines
3. VVD steam-thermal and steam generators
4. EOS sauna and commercial lines
5. accessories and engineering branches

Exit:
- live catalog has enough real products to support true category and filter QA

### Phase D. Rebuild category pages

Owner:
- frontend

Tasks:
1. main category hub template
2. subcategory grid block
3. branch-aware filters
4. brand shortcut block
5. SEO intro and FAQ block

Exit:
- top catalog branches are usable even with larger product counts

### Phase E. SEO rollout

Owner:
- seo

Tasks:
1. titles and H1 patterns
2. meta description patterns
3. canonical and noindex logic
4. internal linking blocks
5. brand and series landing strategy

Exit:
- catalog can scale without duplication or crawl waste

## Immediate next actions

These are the next actual tasks, in order:

1. Complete WooCommerce attribute model for filters.
2. Build the normalized import matrix for `EasySteam`.
3. Import the first large product wave into live `wpsandbox`.
4. Upgrade the category page template to show subcategories and branch-specific filters.
5. After real product density appears, apply SEO landing logic.

## Verification checklist

Before closing each phase, verify:

- the same category logic exists in backend, content mapping, frontend, and SEO rules
- `Парогенераторы и хаммам` remains one top-level intent
- every product has one semantically correct primary category
- filters reflect the current branch only
- no temporary frontend override remains undocumented


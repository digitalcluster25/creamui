# HWS Catalog Implementation Plan

## Purpose

This document turns the approved catalog program and Phase 1 taxonomy into an implementation sequence tied to the current codebase and the live backend contour.

## Current state summary

### Frontend

Current catalog storefront is driven by:
- [frontend/lib/wp/header.ts](/Users/macbookpro/Coding/creamui/frontend/lib/wp/header.ts)
- [frontend/lib/wp/queries.ts](/Users/macbookpro/Coding/creamui/frontend/lib/wp/queries.ts)
- [frontend/lib/wp/mappers.ts](/Users/macbookpro/Coding/creamui/frontend/lib/wp/mappers.ts)
- [frontend/app/catalog/page.tsx](/Users/macbookpro/Coding/creamui/frontend/app/catalog/page.tsx)
- [frontend/app/catalog/[category]/page.tsx](/Users/macbookpro/Coding/creamui/frontend/app/catalog/[category]/page.tsx)
- [frontend/components/sections/catalog/Catalog.tsx](/Users/macbookpro/Coding/creamui/frontend/components/sections/catalog/Catalog.tsx)
- [frontend/components/primitives/header/MainNav.tsx](/Users/macbookpro/Coding/creamui/frontend/components/primitives/header/MainNav.tsx)

Observed constraints:
- main navigation currently derives catalog items from the WP category tree
- category cards in the header depend on a hardcoded icon map
- category pages currently support only one real filter: brand
- product cards use the first returned WooCommerce category as the visible category label
- home category order is still tied to old slugs such as `hammam-stoves` and `commercial-bath-stoves`

### Backend

Live WordPress contour:
- host: `69.62.121.157`
- WordPress stack: `/docker/wpsandbox`
- frontend stack: `/opt/hws-frontend`

Observed backend facts:
- WordPress is Docker-based
- source taxonomy and product data live in `wpsandbox`
- the server contains import/update helpers inside `/docker/wpsandbox` and `wp-content`
- a custom brand-oriented commerce plugin already exists in the repo:
  [wp-plugins/hws-commerce-info/hws-commerce-info.php](/Users/macbookpro/Coding/creamui/wp-plugins/hws-commerce-info/hws-commerce-info.php)

### Content and import

Supplier content is partially represented by manual scripts and JSON artifacts on the server. The next step is to normalize supplier-to-category mapping before adding more content.

## Delivery order

Implementation must follow this order:

1. Backend taxonomy
2. Content normalization and import mapping
3. Frontend navigation and category rendering
4. SEO rollout

Do not start final frontend architecture before the backend category truth is stable.

## Stream 1: Backend taxonomy

### Goal

Make WooCommerce and GraphQL express the approved catalog tree directly.

### Tasks

1. Create or rename top-level product categories in `wpsandbox` to match Phase 1.
2. Create required child categories under each top-level section.
3. Add or normalize structured attributes:
   - equipment type
   - fuel type
   - room type
   - power
   - voltage
   - steam-room volume
   - home/commercial
   - series
4. Define one primary category per product.
5. Verify GraphQL returns the category tree and product assignments correctly.

### Affected places

Server-side:
- `/docker/wpsandbox`
- `/docker/wpsandbox/wp-content`
- WooCommerce taxonomy and attribute data in WordPress DB

Repo-side:
- `wp-plugins/` if additional admin or GraphQL exposure is needed

### Acceptance criteria

- all approved catalog branches exist in WooCommerce
- each mapped supplier product has exactly one primary branch
- GraphQL can power the new menu and category pages without frontend hacks

## Stream 2: Content and parser

### Goal

Normalize supplier assortments and align them with the approved category tree.

### Tasks

1. Build normalized supplier matrix:
   - brand
   - series
   - product
   - category
   - attributes
2. Mark ambiguous placements explicitly.
3. Prepare import/update artifacts for `wpsandbox`.
4. Fill missing content:
   - descriptions
   - specs
   - images
   - docs
   - brand/series relations
5. Verify imported products in GraphQL.

### Suppliers

- VVD
- EasySteam
- Sangens
- EOS

### Acceptance criteria

- every supplier line is mapped to an approved HWS branch
- no engineering item is accidentally mixed into retail stove branches
- no retail product remains uncategorized

## Stream 3: Frontend

### Goal

Make the storefront reflect the new catalog structure and backend truth.

### Tasks

1. Update header navigation generation in [frontend/lib/wp/header.ts](/Users/macbookpro/Coding/creamui/frontend/lib/wp/header.ts).
2. Replace outdated slug assumptions:
   - `hammam-stoves`
   - `steam-generators`
   - `bath-accessories`
   - any other old branch names
3. Update category query and category-page behavior:
   - support the new hierarchy
   - expose correct breadcrumbs
   - avoid semantically wrong parent links
4. Upgrade [frontend/components/sections/catalog/Catalog.tsx](/Users/macbookpro/Coding/creamui/frontend/components/sections/catalog/Catalog.tsx):
   - keep brand filter
   - add branch-relevant filters after backend attributes exist
   - separate subcategory navigation from filters
5. Fix product category labeling in [frontend/lib/wp/mappers.ts](/Users/macbookpro/Coding/creamui/frontend/lib/wp/mappers.ts) so visible category text does not depend on the first arbitrary category node.
6. Update home/category promo blocks that still assume the old structure.

### Acceptance criteria

- menu follows the approved top-level structure
- category pages render only relevant products
- filter UI depends on the current catalog branch
- category labels shown on cards are correct

## Stream 4: SEO

### Goal

Apply the approved crawlable URL and landing structure without generating facet junk.

### Tasks

1. Lock final slug set from Phase 1.
2. Define which branches are indexable.
3. Define which filter states must be canonicalized or noindexed.
4. Add SEO copy, H1, metadata, breadcrumbs, and FAQ blocks to key landings.
5. Build internal links between:
   - category hubs
   - brand pages
   - selected series pages
   - commercial landings

### Acceptance criteria

- indexable pages map to real search intents
- no duplicate top-level intent pages remain
- no random filter combinations are left open for indexing

## Concrete milestones

### Milestone A

Backend taxonomy ready in `wpsandbox`.

### Milestone B

Supplier mapping matrix imported or staged.

### Milestone C

Frontend menu and category pages migrated to the new tree.

### Milestone D

SEO landing rules and metadata applied.

## Immediate next actions

1. Audit existing WooCommerce categories and attributes in `wpsandbox`.
2. Compare live category slugs against Phase 1 target slugs.
3. Produce a migration table:
   - current slug
   - target slug
   - keep / rename / merge / remove
4. Only after that begin taxonomy changes and content remapping.

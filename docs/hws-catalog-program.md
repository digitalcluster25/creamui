# HWS Catalog Program

## Goal

Rebuild the HWS catalog so that:
- navigation is understandable to users
- WordPress taxonomy is the source of truth
- storefront, content, and SEO all follow one catalog tree
- supplier assortments fit cleanly into the structure without duplicates and semantic leaks

## Canonical top-level sections

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

## Workstreams

### 1. Backend

Scope:
- WooCommerce category tree
- attributes and term model
- primary category rules
- GraphQL data shape
- import targets in `wpsandbox`

Deliverables:
- approved category tree
- attribute matrix
- product-to-category mapping rules
- backend migration checklist

### 2. Content and parser

Scope:
- supplier assortment study
- normalization of titles, brands, series, equipment types
- content gaps in `wpsandbox`
- import/update payloads

Deliverables:
- normalized supplier matrix
- mapping table `supplier -> HWS category`
- staged import artifacts

### 3. Frontend

Scope:
- top navigation
- category and brand pages
- filter UX
- breadcrumbs
- listing correctness

Deliverables:
- new menu structure
- category page templates
- filter rules per branch
- storefront verification checklist

### 4. SEO

Scope:
- URL structure
- indexable landing pages
- facet rules
- metadata requirements
- internal linking

Deliverables:
- indexation rules
- approved landing list
- canonical rules
- on-page SEO checklist

## Phases

### Phase 1. Canonical structure

Decide:
- final top-level sections
- branch names
- URL slugs
- supplier placement baseline

Exit criteria:
- no duplicate top-level intents
- no unresolved conflicts between bath / sauna / hammam / commercial logic

### Phase 2. Backend taxonomy

Implement:
- WooCommerce categories
- attributes
- product primary categories
- GraphQL field checks

Exit criteria:
- backend can express the target catalog without frontend hacks

### Phase 3. Supplier mapping and content

Implement:
- normalized series and products
- mapped imports
- required descriptions, specs, images, docs

Exit criteria:
- `wpsandbox` holds the required source data for target sections

### Phase 4. Frontend rollout

Implement:
- menu
- category templates
- filter panels
- brand hubs
- corrected listing composition

Exit criteria:
- storefront reflects backend truth
- no semantically wrong products in key listings

### Phase 5. SEO rollout

Implement:
- metadata
- breadcrumbs
- internal links
- canonical and noindex rules
- curated landing pages

Exit criteria:
- crawlable structure matches target SEO architecture

## Supplier baseline

### VVD

- steam-thermal bath furnaces
- electric bath furnaces
- steam generators
- control units
- installation and water-heating accessories

### EasySteam

- bath furnaces
- bath-and-sauna furnace lines
- commercial bath furnaces
- chimneys, burners, tanks, heat exchangers
- accessories

### Sangens

- electric sauna heaters
- control units
- spa systems
- premium facing and decor

### EOS

- sauna heaters
- commercial sauna systems
- steam room engineering
- control units
- engineering accessories

## Rules that must hold

- A product has one primary category.
- Filters are not a replacement for category architecture.
- Brand pages support discovery, but do not replace intent-based navigation.
- `wpsandbox` is the long-term source of truth for product data.
- Temporary frontend overrides must be documented and removed after backend data is complete.

## Execution order

1. Lock the category tree.
2. Lock the backend taxonomy and attribute model.
3. Map supplier products and fill backend data.
4. Update storefront rendering.
5. Apply SEO rules and landing-page strategy.

## Skills

Use:
- `.agents/skills/hws-catalog-program`
- `.agents/skills/hws-catalog-backend`
- `.agents/skills/hws-catalog-content-parser`
- `.agents/skills/hws-catalog-frontend`
- `.agents/skills/hws-catalog-seo`

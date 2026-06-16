---
name: creamui-block-flow
description: Workflow for rebuilding Cream UI from reference screenshots and Ohio source. Use when user sends a block screenshot, asks to decompose it into primitives, add reusable primitives to the kit, assemble a reusable block, maintain separate primitives and blocks pages, and later compose full pages from approved blocks.
---

# Cream UI Block Flow

## Source Of Truth

1. User screenshot for the target block.
2. Ohio live source and CSS when exact values are needed.
3. Existing approved kit primitives and blocks.

Do not invent layout, tokens, or decorative parts when the reference or Ohio source already defines them.

## Build Order

For every new block:

1. Inspect the screenshot.
2. Split the block into primitives.
3. Group primitives by category.
4. Add missing reusable primitives to the kit.
5. Assemble the block only from primitives.
6. Fix exact grid, spacing, and sizing values in the specs page before calling the block done.
7. Add the block to the blocks page.
8. Keep the homepage and future pages composed from approved blocks only.

## Do

- Build from the reference block outward, not from a generic kit inward.
- Fix exact width, height, paddings, gaps, radii, and alignment before calling a block done.
- Record exact grid and spacing values in `specs.html`.
- Reuse only approved primitives when assembling a block.
- Add a new primitive only when the reference truly requires a missing reusable part.
- Keep blocks visually isolated on `blocks.html` only as much as needed to inspect them.
- Preserve original block width behavior:
  - if the source block is full-width, render it full-width
  - if the source block is container-bound, render it container-bound
- Treat live Ohio HTML/CSS as source of truth when screenshot and code disagree.

## Do Not

- Do not wrap source-faithful blocks in decorative showcase cards, frames, shadows, rounded inspectors, or demo wrappers unless the original block has them.
- Do not shrink, center, pad, or constrain a block for presentation convenience.
- Do not invent spacing values when they can be read from Ohio CSS or derived from the source structure.
- Do not merge page composition concerns into primitives.
- Do not create page-specific one-off classes when a reusable primitive is the correct layer.
- Do not mark a block complete if grid, spacing, or sizing still differ from the original.
- Do not optimize for “looks neat in the kit” over “matches original exactly”.
- Do not let the kit shell alter the source block geometry.

## Page Structure

Maintain these working pages during kit phase:

1. `index.html` — main page / preview page
2. `primitives.html` — reusable primitives only
3. `blocks.html` — reusable blocks only
4. `specs.html` — exact measurements, grid, spacing, and source mapping for approved blocks

Do not turn page-specific compositions into primitives.
Do not skip the primitives layer and code blocks directly with one-off markup.
Do not let page chrome distort the block under inspection.

## Primitive Rules

Every primitive must be:

- reusable
- named by role, not by one page
- placed into a clear category
- styled through shared CSS, not one-off inline styling

Suggested categories:

- layout
- typography
- buttons
- fields
- badges
- media
- cards
- navigation
- commerce
- utilities

## Block Rules

Every block must:

- be assembled from existing primitives first
- add new primitives only when truly missing
- avoid page-only hacks
- stay reusable for future pages
- have its exact grid, spacing, sizing, and source notes fixed in `specs.html`
- preserve original outer geometry before any kit presentation layer

## Page Phase

After the block library is approved:

1. Assemble homepage from blocks.
2. Assemble shop page from blocks.
3. Assemble product page from blocks.
4. Add missing primitives or blocks only when a page proves they are needed.

## Response Discipline

When working on a block:

- first identify primitives
- then add missing reusable parts
- then build the block
- then report what new primitives and block were added

Keep changes minimal and structural.

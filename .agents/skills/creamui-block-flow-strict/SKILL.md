---
name: creamui-block-flow-strict
description: >
  Единый строгий протокол CreamUI для screenshot-to-block, Ohio-to-kit,
  exact page extraction, primitive reuse/refine/create и strict 1:1 верстки.
  Использовать для любого запроса на создание, исправление, перенос или
  проверку HTML/CSS элемента, примитива, блока или extraction page в этом проекте.
---

# CreamUI Strict Block Flow

Это канонический skill для всей block/primitive работы в CreamUI.

Главная цель:
- исключить выдумывание
- исключить wrong-source mixing
- исключить итерационные черновики
- заставить сначала верификацию, потом exact extraction, потом код
- держать `primitives.html` реестром source-элементов, а `blocks.html` страницей композиций

Базовая формула:
- verify first
- source second
- primitive audit third
- code fourth
- local verification before reply

Если verification incomplete, code forbidden.

## 0. Execution Locks

Эти locks обязательны и имеют приоритет над желанием “ускорить”.

Нельзя:
- начинать код до завершения source extraction
- отвечать пользователю до локальной проверки
- заменять exact source values на локальные “похожие”
- использовать existing primitive как shortcut, пока не доказано exact match
- писать “готово”, если screenshot compare и mismatch log не завершены

Если любой lock не выполнен:
- implementation forbidden
- reply with completion forbidden

Короткое правило:
- no source chain -> no code
- no local check -> no reply
- no mismatch log -> no completion

## 1. Zero-Repeat Quality Gate

Этот протокол обязателен.

Если блок не совпадает с source, задача не завершена.

Никогда не использовать:
- “close enough”
- “looks similar”
- “good enough for now”

Разрешенные статусы:
- `matches source`
- `approved adaptation`
- `open mismatch explicitly listed`

Если mismatch есть и он не одобрен, нельзя писать, что задача завершена.

## 2. Core Principles

1. Never guess.
2. Never mix sources silently.
3. Never create a new primitive before auditing existing ones.
4. Never reuse a primitive before proving that structure, icon, behavior and geometry already match.
5. Never code before verification is complete.
6. Never show a knowingly broken draft when enough input already exists to finish in one pass.
7. Never close a task without a mismatch audit.
8. Never replace source icons with similar icons without proof.
9. Never rely on visual impression alone when geometry can be measured.
10. Visual approval on mocks always comes before real WPGraphQL wiring.

## 3. Delivery Pipeline

Стандартный pipeline проекта:
1. `kit`
2. `next components`
3. `mocked frontend`
4. `wpgraphql pages`

Интерпретация:
- `kit` = source primitives и approved blocks
- `next components` = React/Next перенос approved kit pieces
- `mocked frontend` = полные страницы на локальных mock data
- `wpgraphql pages` = те же approved страницы на real WPGraphQL

Жесткие правила:
- не подключать real WPGraphQL, пока page composition не утвержден визуально на mocks
- не смешивать visual reconstruction и live data integration в одном шаге

Короткое правило:
- visuals first
- mocks second
- real data last

## 4. Block Creation Variants

В этом проекте есть только два варианта создания blocks.

### Variant A — Copy Of Existing Block

Использовать, когда пользователь хочет:
- exact copy существующего блока
- exact Ohio behavior
- exact extraction from one source page
- rebuild уже существующего блока по source

Источник входа:
- exact source page
- optional screenshot для уточнения видимого target

Цель:
- найти exact source fragment
- проверить existing kit
- решить `reuse / refine / create`
- собрать block как copy existing source

### Variant B — Custom Block

Использовать, когда пользователь хочет новый block:
- по screenshot
- по description
- по screenshot + description

Источник входа:
- screenshot и/или description
- optional source references if explicitly given

Цель:
- извлечь из входа exact visible intent
- разложить block на primitives
- проверить existing kit
- решить `reuse / refine / create`
- собрать custom block без подмены задачи “копией похожего source блока”

## 5. Task Types

### Type A — Exact Page Extraction

Использовать, когда пользователь говорит:
- взять все элементы с одной exact page
- extract everything from page X
- собрать reusable registry from one named page

Цель:
- инвентаризировать только то, что реально есть на этой exact page
- не смешивать с другими Ohio pages
- не подменять source verified presence похожими primitives

### Type B — Store Adapt

Использовать, когда пользователь говорит:
- адаптируй Ohio structure под store context
- same pattern but for HWS / store
- preserve source pattern but rewrite content/context

Цель:
- сохранить structural base
- адаптировать только то, что разрешено пользователем или явно требуется

### Type C — Frontend Port

Использовать, когда пользователь хочет:
- перенести approved kit/block в Next.js
- создать React components
- собрать mocked pages до live data

Цель:
- сохранить approved visual behavior
- портировать только после kit approval

## 6. Modes

### `strict-1to1`

Использовать, когда пользователь хочет:
- exact copy
- exact Ohio behavior
- exact extraction from one page

Правила:
- source wins
- screenshot only clarifies visible target if it does not contradict exact source requirement
- creative adaptation forbidden

Hard rules:
- first pass must be `exact extraction first`
- manual approximation first is invalid
- if an analogue already exists and diverges from source, stop patching and rebuild from the real source fragment
- do not simplify DOM before the first accurate local source copy exists
- do not replace source composition with a “cleaner” local analogue

Правильный порядок:
1. lock exact Ohio page
2. isolate exact source fragment on that page
3. copy real composition first:
   - DOM hierarchy
   - source assets
   - inline SVG or source icon family
   - visible state classes
   - source typography structure
4. rebuild locally with minimal interpretation
5. compare local vs source
6. only then clean non-essential noise if output stays identical

### `store-adapt`

Использовать, когда пользователь хочет:
- same structure
- same pattern
- adapted to store context

Правила:
- screenshot + user rules define final output
- Ohio gives structural base
- text/context/labels/content may be adapted only if user requested it or adaptation is clearly required

### `custom`

Использовать, когда пользователь хочет custom block:
- по screenshot
- по description
- по screenshot + description

Правила:
- screenshot and/or description define the target
- source page is optional, not mandatory
- if user gave a source page for a sub-part, copy only that approved part exactly
- if no exact source page was provided, do not pretend the result is exact extraction
- still decompose into primitives and still run `reuse / refine / create`

## 7. Required Inputs

Перед кодом зафиксировать:
- main screenshot
- optional secondary screenshots
- description, if any
- exact source page, if any
- final target:
  - `strict-1to1`
  - `store-adapt`
  - `custom`
- explicit keep rules
- explicit do-not-copy rules

Если это materially changes the result и нельзя безопасно infer, спросить один раз.

### Approved Ohio Source Pages For Copying

Если задача в этом проекте требует копировать primitives или sub-parts 1:1 для кастомного блока, разрешенные source pages по умолчанию такие:
- [Demo19 Home](https://ohio.clbthemes.com/demo19/)
- [Demo19 Shop](https://ohio.clbthemes.com/demo19/shop/)
- [Demo19 Product Layout Chair A132](https://ohio.clbthemes.com/demo19/product/layout-chair-a132/)

Правило:
- сначала искать source element на этих страницах
- только найденный source element можно копировать 1:1 в primitive
- если user не указал другую source page, эти три страницы являются первичным каталогом для source-copy поиска

## 8. Source Of Truth Contract

Каждая block task должна lock:
- main screenshot and/or description
- exact source page, if any
- mode
- explicit copy rules
- explicit do-not-copy rules

Жесткие правила:
- если inputs не locked, build forbidden
- если они изменились mid-task, restart verification

Priority order:
1. explicit user instruction
2. main screenshot and description
3. exact named source page, if one exists
4. Ohio HTML/CSS from that same exact page, if one exists
5. existing approved kit primitives

Интерпретация:
- user instruction resolves conflicts
- screenshot and description define visible intent for custom work
- named source page defines what is allowed to exist for copy/extraction work
- existing primitives are implementation helpers, not proof

Короткое правило:
- similarity is not evidence
- verified source presence is evidence

### Source Data Extraction For Copy

Если element копируется 1:1, данные для него получать только так:
1. source HTML / DOM fragment
2. source CSS selectors that style this fragment
3. source root tokens / variables used by these selectors
4. source states:
   - hover
   - focus
   - active
   - selected
   - disabled
   - placeholder
   - open / expanded if applicable
5. source computed values if raw CSS chain is not enough to prove final rendering

Typography is part of source data, not a secondary detail.

Для text-bearing element обязательно отдельно извлечь:
- font-family
- font-size
- font-weight
- line-height
- letter-spacing
- text-transform
- text-decoration
- text color
- margins that define text rhythm

Обязательная логика:
- сначала определить exact element class / id / DOM path in source
- потом определить exact CSS selectors that affect this element
- потом вытащить all dependent tokens / variables
- потом проверить states on source
- только после этого переносить primitive locally

Hard rule:
- нельзя копировать element только по screenshot
- нельзя копировать element только по одному class name без проверки его CSS chain
- нельзя брать локальные computed values как source of truth, если source CSS и source DOM еще не проверены
- если source selector chain не найдена, element считается unresolved и не копируется как exact
- если typography chain не найдена, text element считается unresolved и не копируется как exact
- if an existing local modifier overrides source typography, it must be removed or rewritten before completion

Короткое правило:
- DOM + CSS + tokens + states = enough to copy
- screenshot alone = not enough to copy
- typography is mandatory inside that chain

## 9. Single-Pass Delivery Rule

Если пользователь уже дал:
- enough inputs to fully define the target
- exact mode
- explicit copy / keep / exclude rules

то iterative draft delivery запрещен.

Нельзя делать:
- “сначала приблизительно”
- “сначала черновик”
- “сначала посмотрим как получится”
- “сейчас соберу базу, потом добью”

Нужно делать один полный проход:
1. finish verification
2. extract exact source fragment
3. complete primitive audit
4. implement full target
5. verify locally
6. reply only after that

Hard failures:
- showing a knowingly partial or broken draft
- using “first approximation, then fix later” when the target is already defined
- consuming multiple turns on patch-by-patch visual guessing instead of one full pass
- reporting progress instead of completion when completion is feasible in the same turn
- using existing local CSS as a shortcut before verifying exact source values
- keeping a source mismatch “temporarily” while replying as if the block is already acceptable

Если текущий block already wrong:
- for copy mode: rebuild from the real source fragment
- for custom mode: rebuild from the locked target inputs
- respond only after local verification

## 10. Non-Negotiable Workflow

Пропуск шагов запрещен.

1. Identify task type
2. Lock source scope
3. Analyze screenshot/reference
4. Create block passport
5. Decompose into primitives
6. Verify source-page presence when applicable
7. Audit existing primitives
8. Decide `reuse / refine / create`
9. If material ambiguity remains and cannot be verified safely, ask once
10. Otherwise implement immediately
11. Verify locally
12. Report briefly

Additional hard gate:
- no step may be skipped because the result “already looks right”
- no local primitive may be refined before source values for the target element are explicitly identified

## 11. Step 1 — Lock Source Scope

Перед любым design/code decision ответить:
- what is the exact source?
- one page or multiple?
- is this extracting from a single page?
- is similarity allowed, or only verified presence?

Если user named one exact page:
- that page is the only allowed source
- no borrowing from other Ohio pages
- no “same thing but from another page”
- no assumption that an existing primitive proves presence on that page

## 12. Step 2 — Analyze Visual Input

Выделить:
- section title
- section shell / container
- column count
- grid system
- row structure
- card type
- text hierarchy
- controls
- icons
- dividers
- arrows
- badges
- border / radius
- background
- hover / focus / active states
- motion / JS behavior if visible or implied

Отметить visually critical things:
- geometry
- icon family
- spacing rhythm
- typography scale
- container behavior
- state behavior

Нельзя угадывать, что optional.

## 13. Step 2A — Block Passport

Перед кодом сделать короткий block passport.

Обязательные поля:
- task name
- source page
- mode
- outer container
- grid system
- inner row
- column structure
- primitive list
- icon source
- text source
- visible states
- hover/focus/active behavior
- key dimensions to match
- existing primitive candidates
- decision per primitive: `reuse / refine / create`

Hard rule:
- no passport = no implementation
- incomplete passport = incomplete verification

Для custom block passport дополнительно указать:
- какие элементы будут copied 1:1 from source
- с какой exact source page будет copied каждый такой element
- какие элементы не копируются 1:1, а only reuse/refine existing kit primitives
- для каждого text element указать exact typography source:
  - selector
  - font family
  - size
  - line-height
  - letter-spacing

Для copy block passport дополнительно указать:
- какую exact grid / column system использует source block
- как эта grid читается на desktop
- какие container / columns / gaps / alignment rules будут copied

Hard rule:
- если блок создается как copy existing block, grid must be identified during decomposition before coding
- нельзя сначала верстать block, а потом “подгонять” grid визуально

## 14. Step 3 — Decompose Into Primitives

Разлагать по ролям, а не по page names.

Примеры:
- section-head
- card-grid
- article-card
- product-card
- promo-banner
- process-step
- process-arrow
- icon-box
- logo-item
- footer-column
- legal-row

Каждый item должен быть reusable и atomic enough.

### Mandatory Source Search After Decomposition

Сразу после декомпозиции блока нужно пройти по каждому primitive candidate и определить:
1. будет ли он copied 1:1 from source
2. если да — на какой exact source page он найден
3. если нет — это `reuse` или `refine` уже существующего primitive

Для задач этого проекта source-copy поиск по умолчанию делать сначала на:
- [Demo19 Home](https://ohio.clbthemes.com/demo19/)
- [Demo19 Shop](https://ohio.clbthemes.com/demo19/shop/)
- [Demo19 Product Layout Chair A132](https://ohio.clbthemes.com/demo19/product/layout-chair-a132/)

Hard rule:
- нельзя придумывать новый primitive, если было заявлено copy 1:1, но source element не найден
- нельзя молча подменять “не найденный source element” похожим придуманным primitive
- если exact source for copying not found, нужно остановиться по этому element и явно сообщить пользователю:
  - какой element не найден
  - на каких source pages искали
  - что без source-copy этот primitive не будет создан

Короткое правило:
- found source -> can copy
- not found source -> report to user, do not invent
- copy block without verified grid -> forbidden

## 15. Primitive Boundary

`primitives.html` хранит только atomic или near-atomic reusable parts.

Примеры допустимых primitive types:
- shell
- media
- heading group
- input field
- select field
- checkbox row
- button
- badge
- icon
- card body

Нельзя держать в `primitives.html`:
- full newsletter banner form
- full contact hero with copy + form + media
- any complete CTA / footer / hero / header / product card / newsletter composition
- source clusters типа:
  - `header actions`
  - `header left`
  - `subheader strip`
  - `newsletter fields group`
  - `product thumbnail card`
  - `footer legal row`

Правила:
- one preview = one primitive
- if preview already shows a full assembled section, it is a block and belongs in `blocks.html`
- if preview mixes multiple independent source parts, split them first
- if a primitive cannot be named as one stable source element, it is not a primitive

Build order:
1. add or refine atomic primitives in `primitives.html`
2. assemble final section only in `blocks.html`

## 16. Primitive Registry Page Rules

`primitives.html` должен быть strict registry page, not a storage of ready sections.

Required behavior:
- group by element type, not by finished use case
- keep one canonical sample per primitive
- remove duplicates instead of restyling duplicates
- remove any primitive that cannot be confirmed against source

Recommended sections:
- Typography
- Branding
- Navigation
- Buttons
- Icon Buttons
- Icons
- Forms
- Commerce
- Card Atoms
- Media
- Footer Atoms
- Utilities

Registry hard rules:
- one preview = one primitive
- context background allowed only when needed to read the source primitive correctly
- do not keep a questionable primitive “temporarily”; verify it or remove it

## 17. Step 4 — Source-Page Verification

Для exact page extraction tasks это mandatory.

Для каждого candidate element присвоить один status:
- `verified on source page`
- `not on source page`
- `unclear, needs confirmation`

Правила:
- if not verified on named page, exclude it
- if unclear, do not silently include it
- do not replace a missing source element with a similar one from another page
- do not substitute with an existing primitive before verification

Для каждого verified element зафиксировать:
- where it exists on source page
- what role it serves
- whether it has visible states or interaction

### Special Rule For Exact Page Extraction

Если user asks:
- extract all elements from page X

сначала сделать verified inventory only from that page.

Required inventory format:
- element name
- verified on source page: yes/no
- existing primitive match:
  - reuse
  - refine
  - create

If an item cannot be tied to the page, exclude it.

Only after verified inventory is locked may coding begin.

## 18. Step 5 — Audit Existing Primitives

Проверять:
- `primitives.html`
- `elements.css`
- approved existing block usage when needed

Для каждого verified primitive выбрать ровно одно:
1. `reuse as-is`
2. `refine existing primitive`
3. `create new primitive`

Правила:
- do not create if exact or near-exact primitive already exists
- do not duplicate near-identical primitives
- prefer refinement over duplication
- visually similar but structurally or behaviorally different is not `reuse as-is`
- primitive audit always happens after source verification

Decision rule:
- `reuse` only if structure, icon, behavior and geometry already match
- otherwise `refine` or `create`

Typography rule:
- `reuse` forbidden if font family, size, line-height, letter-spacing, or text rhythm differ materially from source
- if typography differs, decision can only be `refine` or `create`

## 19. Step 6 — Hard Prohibitions

Never:
- invent icons when source defines them
- invent backgrounds, labels, captions, badges, frames, hovers, or states
- copy text from unrelated Ohio pages
- mix elements from multiple Ohio pages during single-page extraction
- treat a similar existing primitive as proof the source contains that element
- create new primitives without auditing existing ones
- style visual components in `blocks.css`
- silently “improve” the design against source
- code before verification is complete
- mark task complete while known mismatches still exist
- treat a matched layout as proof that typography also matches
- leave a local modifier in place if it overrides source typography, icon size, or spacing
- answer with explanations instead of fixing the verified mismatch first when the mismatch is actionable in the same turn

If icons matter:
- copy from screenshot
- or copy from exact source page
- or reuse already approved matching icon primitive
- never improvise

Icon hard rule:
- similar icon is not a valid substitute
- if icon source is not proven, treat icon as unresolved and report it

## 20. Plan Format Before Coding

Перед кодом всегда output short plan in this order:
1. Understanding of the task
2. Task type
3. Mode
4. Locked source scope
5. Primitive decomposition
6. Source verification:
   - verified
   - excluded
   - unclear
7. Existing primitive audit:
   - reuse
   - refine
   - create
8. What will be copied exactly
9. What will not be copied
10. Files to change
11. Expected result

Если no material ambiguity remains, implement immediately.
Если material ambiguity remains and cannot be verified safely, ask once before coding.

Passport completion gate:
- if typography source is missing for any visible heading/body text, the passport is incomplete
- incomplete passport means code forbidden

## 21. Implementation Order

После verification:
1. add or refine primitives in `primitives.html`
2. add component styles in `elements.css`
3. assemble blocks in `blocks.html` if needed
4. use `blocks.css` only for layout wrappers and responsive section composition
5. record exact values in `specs.html` when needed

Rule:
- component visuals belong in `elements.css`
- section assembly / layout wrappers belong in `blocks.css`

В бывшей терминологии `верстальщик`:
- Step 1 — decomposition
- Step 2 — kit audit
- Step 3 — create missing primitives from source
- Step 4 — assemble block only from existing or newly added primitives

Жесткий запрет:
- нельзя писать CSS в `blocks.css` для component visuals
- only grid/layout wrappers belong there

## 22. Exact Extraction First

Это primary rule для `strict-1to1`.

Wrong behavior:
- rebuild manually from memory or visual impression
- choose a similar primitive before proving source identity
- simplify DOM before exact local source copy exists
- copy pattern from another Ohio page because it feels equivalent

Correct behavior:
1. identify real source DOM fragment
2. identify real source assets
3. identify real source composition and wrappers
4. identify real source typography chain
5. reproduce locally with minimal interpretation
6. verify against source
7. only then remove non-essential source noise if output stays identical

Invalid order:
1. invent local analogue
2. style until it looks similar
3. patch mismatches on top

If first implementation pass still deviates:
- do not keep adjusting by intuition
- stop and re-measure
- stop and re-check source
- stop and identify exact mismatch class

Mismatch classes:
- `source mismatch`
- `primitive mismatch`
- `grid mismatch`
- `icon mismatch`
- `typography mismatch`
- `state mismatch`
- `asset mismatch`

## 23. Stateful Element Rule

Для любого element with interaction or visible state task is incomplete until geometry and behavior both match source.

Примеры:
- color swatches
- theme switcher
- tabs
- dropdowns
- checkboxes / radios
- quantity steppers
- icon buttons with active state
- hover reveals

Mandatory workflow:
1. extract exact source HTML
2. extract exact source CSS for:
   - default
   - hover
   - focus
   - active
   - selected
   - dark/light if present
3. preserve DOM layers that create geometry or motion
4. recreate real local state logic
5. verify by interaction, not by static screenshot

Never:
- replace working source logic with static imitation
- remove hidden select from swatches if source logic depends on it
- remove `toddler` / `thumb` / `track` layer if it forms original geometry
- declare completion if default visual looks similar but state logic is broken

Verification for such elements must include:
- computed style default
- computed style active/selected
- local click/toggle/select
- screenshot before reply

Short reporting formula:
1. source html found
2. source css found
3. states transferred
4. local interaction verified
5. screenshot captured

## 24. Verification

После implementation verification is mandatory.

Check:
- no extra elements
- no wrong-source elements
- no extra labels
- no extra backgrounds
- no extra borders
- correct icon family
- correct icon scale
- correct spacing rhythm
- correct number of items
- correct states
- correct hover / focus / active logic
- correct source grid and container bounds
- approved primitive reuse happened where possible
- result matches approved plan

If result differs from plan:
- say exactly where
- do not pretend it matches

For exact page extraction and primitive-registry tasks verification is incomplete until:
- local result is checked against live original page
- each extracted primitive is compared to source counterpart
- mismatches are written explicitly

Mandatory compare-to-original checklist:
- correct source page
- correct primitive type
- correct text
- correct asset
- correct icon family
- correct spacing rhythm
- correct typography:
  - font family
  - font size
  - line height
  - letter spacing
  - text weight
- correct background and border logic
- correct hover / focus / active behavior when applicable
- correct outer container width
- correct inner row width
- no element exceeds source grid bounds
- no wrong-source elements
- no missing source elements that were claimed as extracted

Mandatory reporting rule:
- never say “done” without compare-to-original verification
- if comparison is partial, say it is partial
- if differences remain, list them briefly and concretely
- if a mismatch is actionable in the same turn, fix first and report after the recheck

Production gate:
- implementation is not accepted until it passes source comparison
- visual approval without source comparison is not enough
- local success without geometry match is not enough for `strict-1to1`

## 25. Playwright Verification Protocol

For frontend block/page extraction work Playwright verification is mandatory.

Required actions:
1. open local result page
2. open live original source page
3. check console
4. check `pageerror`
5. check failed requests
6. check HTTP `4xx/5xx`
7. check broken images
8. capture screenshots
9. compare local primitives/blocks against original
10. record mismatches explicitly
11. capture at least one local screenshot of the changed block before reply

For exact page extraction additionally:
1. confirm local page renders
2. confirm source page renders
3. confirm no console errors
4. confirm no broken assets
5. confirm wrong-source blocks are absent
6. confirm expected source sections are present
7. compare text
8. compare assets
9. compare background logic
10. compare icon family
11. compare spacing rhythm
12. compare typography
13. compare visible state logic
14. compare computed styles where needed
15. measure source and local container/row widths
16. detect wrappers that break grid:
    - `100vw`
    - negative margins
    - oversized fixed widths
17. measure key bounding boxes
18. compare local vs source `width/height`
19. write mismatch list
20. only after that report status

Grid hard rules:
- every primitive preview on kit page must remain inside original source grid
- if source uses a contained layout, do not stretch preview with viewport-width tricks
- check primitive and wrapper both
- if row must follow original container, compare row width, not only child card sizes

Hard rule:
- visual similarity alone is not enough for `strict-1to1`
- compare `getBoundingClientRect()` values for key primitives
- if width or height still differs materially, continue before reporting completion

Repeat-error prevention:
- after failed comparison, do not broad-tweak by intuition
- identify exact failed check
- classify mismatch
- fix against measured mismatch
- rerun the same check

Rebuild rule after strategy failure:
- if section was built from manual approximation instead of exact extraction, stop polishing it
- replace it with a new build from the real source fragment
- verify rebuilt version from zero

Mandatory final wording:
- state verification level:
  - `functional only`
  - `functional + visual`
  - `functional + visual + computed-style`
- if not full, say what was not checked

Mandatory mismatch reporting format:
- primitive name
- what differs
- source value or behavior
- local value or behavior
- source size when relevant
- local size when relevant
- whether fixed or still open

Mandatory acceptance checklist per block:
- source locked
- screenshot locked
- mode locked
- block passport written
- source presence verified
- existing primitives audited
- implementation completed
- Playwright checks completed
- grid checks completed
- geometry checks completed
- icon checks completed
- state checks completed
- mismatch log completed
- all open mismatches fixed or explicitly approved

Completion rule:
- if any item above is missing, the block is not complete

## 25A. Stop Conditions

If any of these conditions is true, stop implementation and do not improvise:
- exact source fragment not found
- typography source chain not found
- icon source unresolved
- source page inaccessible
- local verification tooling failed and no fallback verification was completed

Then report only:
- which exact element is blocked
- which source pages/selectors were checked
- what exact proof is still missing

Hard rule:
- blocked means blocked
- blocked does not permit approximation

## 25B. Existing Primitive Shortcut Ban

Existing primitive may speed up implementation only after proof, never before proof.

Forbidden shortcut:
1. see a similar primitive locally
2. refine it by intuition
3. later try to align it to source

Required order:
1. prove exact source structure
2. prove exact source typography
3. prove exact source icon/state/spacing
4. only then decide whether local primitive can be reused or refined

If this order is broken:
- discard the shortcut path
- rebuild from source

## 26. Design Fidelity

Blocks must match Ohio Demo19 100% unless the user explicitly approved a deviation.

Типичные mismatch patterns:
- confusing `-with-overlay` with `-with-overlay-image`
- missing modifiers like `-top`, `-metro`, `-stretch`, `-contained`, `-left`
- missing `data-*` attributes like `data-cursor-class`, `data-js`, `data-tilt`

## 27. Source Lookup and Component Build Template

Source lookup sequence:
1. `grep` `/tmp/ohio.css`
2. if needed, check `/tmp/ohio-parent.css`
3. `curl` exact Ohio page for markup
4. use `getComputedStyle` only for post-build verification

If `/tmp/ohio.css` is missing:
- `curl -s https://wpsandbox.spaces.community/wp-content/themes/ohio/style.css > /tmp/ohio.css`

Build template:
```bash
# 1. Find CSS
cat /tmp/ohio.css | grep -n "\.component-name"

# 2. Read the full ruleset

# 3. Find source HTML
curl -s "https://ohio.clbthemes.com/PAGE/" | grep -A 20 "component-name"

# 4. Find variables if needed
cat /tmp/ohio.css | grep -n "clb-variable-name"
```

Post-build verification example:
```javascript
getComputedStyle(document.querySelector('.component')).PROPERTY
```

## 28. Ohio → Kit Variable Mapping

| Ohio var | Kit var | Значение |
|----------|---------|----------|
| `--clb-circle` | `--icon-circle-md` | `3.5rem` / `56px` |
| `--clb-color-fill` | `--color-surface-soft` | `rgba(136,136,137,0.05)` |
| `--clb-border-radius` | `--radius-sm` | `0.5rem` |
| `--clb-border-radius-large` | `--radius-lg` | `1rem` |
| `--clb-spacer-3` | `--space-7` | `1rem` |
| `--clb-spacer-05` | — | `0.25rem` |
| `--clb-spacer-1` | — | `0.5rem` |
| `--clb-transition-function` | built into `--transition-base` | `cubic-bezier(.645,.045,.355,1)` |
| `--clb-transition-duration` | built into `--transition-base` | `.35s` |
| `--clb-transform-scale` | — | `scale3d(1.06,1.06,1.06)` |
| `--clb-color-black-light` | `--color-dark` | `#282828` |
| `--clb-grid-gutter` | `28px` | `14px × 2` |

## 29. Working File Map

Main files:
- `primitives.html`
- `elements.css`
- `blocks.html`
- `blocks.css`
- `specs.html`

## 30. Quick Decision Table

If user says:
- “сделай как на скрине” -> screenshot leads
- “возьми разметку из Ohio” -> Ohio gives structure, screenshot still controls visible target unless user said exact
- “сделай 1:1” -> `strict-1to1`
- “адаптируй под магазин” -> `store-adapt`
- “не переносить фон/рамку/label/hover” -> explicitly exclude them in the plan
- “если примитив уже есть — используй его” -> mandatory `reuse / refine / create` audit
- “возьми все элементы с конкретной страницы” -> exact page extraction with verified inventory before code
- “сначала моки, потом wpgraphql” -> use the standard delivery pipeline

## 31. Response Style

Final response must stay short.

Say only:
- what was done
- which files changed
- what was verified
- what remains, if anything

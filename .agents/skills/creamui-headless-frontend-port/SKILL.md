---
name: creamui-headless-frontend-port
description: >
  Протокол миграции HTML/CSS блоков CreamUI в headless frontend на Next.js 16
  + App Router + TypeScript + CSS Modules + WPGraphQL/Apollo. Использовать,
  когда пользователь просит делать отдельные frontend-страницы, переносить
  блоки по одному, собирать их из существующих примитивов, добавлять mock data,
  делать адаптивность, готовить код к последующему подключению WordPress API
  без лишних правок и без выдумывания.
---

# CreamUI Headless Frontend Port

Это отдельный протокол для переноса approved kit/block в новый headless stack.

Использовать только когда задача уже не про `html demo page`, а про:
- Next.js frontend page
- React components
- mock data
- API-ready integration layer
- frontend port блока или страницы

Если задача still про visual extraction / 1:1 repair / Ohio source compare:
- сначала использовать `creamui-block-flow-strict`
- только после visual approval переходить в этот skill

## 0. Main Goal

Цель:
- перенести блок в production-like frontend код
- сохранить визуальное и semantic поведение
- использовать уже существующие стили и tokens
- собрать block на mock data так, чтобы позже осталось только подключить WPGraphQL backend

Короткое правило:
- approved visuals first
- frontend port second
- live API last

## 1. Hard Rules

Нельзя:
- выдумывать UI, состояния, поля данных, которых нет в approved block
- делать новый block без decomposition на primitives
- смешивать несколько blocks в одной задаче, если user не просил
- писать лапшу в компоненте
- тащить inline styles, если это не требуется exact behavior
- использовать Tailwind в компонентах
- писать непроверенный HTML/CSS/JS
- подключать real backend раньше, чем block работает на mocks

Обязательно:
- один block за раз
- сначала plan
- сначала primitives, потом block
- новые primitives выносить на отдельную primitives page
- 1:1 совпадение primitive preview с block fragment
- semantic markup
- accessible interactive elements
- responsive behavior
- valid TypeScript
- валидный JSX/HTML/CSS

## 2. Stack Contract

Целевой стек:
- Next.js 16
- App Router
- TypeScript
- SSR + ISR where needed
- CSS Modules
- CSS Variables from existing Ohio/CreamUI tokens
- WPGraphQL
- WPGraphQL for WooCommerce
- Apollo Client v4

Запрещено отклоняться от этого без explicit user approval.

## 3. Delivery Order

Всегда работать в таком порядке:
1. lock target page/block
2. confirm approved visual source
3. decompose block into primitives
4. audit existing primitives
5. create missing primitives first
6. assemble block component
7. add mock data contract
8. add responsive behavior
9. verify locally
10. report briefly

Если block не approved visually:
- frontend port forbidden

## 4. Task Types

### A. Primitive Port

Использовать, когда нужен только primitive:
- button
- tag
- breadcrumb
- swatch
- menu item
- input
- badge

Результат:
- reusable React primitive
- CSS Module
- preview on primitives page

### B. Block Port

Использовать, когда user дает один block:
- header
- mega menu
- product grid
- article grid
- contact form block

Результат:
- block component
- uses approved primitives
- mock data
- responsive behavior

### C. Page Port

Использовать, когда user просит отдельную страницу:
- home
- shop
- product
- category

Результат:
- route-level page
- SSR / ISR / client split
- block composition from approved components

## 5. Mandatory Plan Before Coding

Перед кодом всегда писать короткий plan в таком порядке:
1. Understanding
2. Task type
3. Route or page target
4. Rendering mode:
   - SSR
   - ISR
   - client
5. Locked source block
6. Primitive decomposition
7. Existing primitive audit:
   - reuse
   - refine
   - create
8. Mock data shape
9. Future API contract
10. Files to change
11. Expected result

Если route / folder target materially unclear:
- ask once before coding

## 6. Primitive Audit

Для каждого блока сначала определить:
- какие primitives уже существуют
- какие из них exact enough
- какие нужно refine
- каких нет вообще

Правило:
- block cannot be built before missing primitives are added

Новые primitives:
- выносить на отдельную primitives page in frontend stack
- preview must show only what is actually used in the block
- no extra showcase junk

## 7. Component Structure Rules

Каждый frontend block делать как чистую структуру:
- route/page layer
- block layer
- primitive layer
- data layer

Минимальные правила:
- component file contains structure and light view logic only
- no mixed data fetching + visual helpers + utility soup in one file
- repeated mapping / normalization goes to helper functions
- constants and mock data separate from JSX if they grow

Предпочтительная логика:
- `components/primitives/*`
- `components/blocks/*`
- `lib/mocks/*`
- `lib/graphql/*`
- `app/*`

Если в проекте уже есть другая agreed structure:
- follow project structure, not this default

## 8. Styling Rules

Использовать:
- CSS Modules for component styles
- existing CSS variables / design tokens

Нельзя:
- Tailwind in components
- random hardcoded colors if token exists
- global overrides unless truly shared infra style

Правило переноса:
- сначала reuse existing visual tokens
- потом переносить only block-local rules into module
- keep selectors flat and readable

## 9. Semantic and Accessibility Rules

Обязательно:
- buttons are buttons
- links are links
- nav uses `nav`
- lists use `ul/ol` when list semantics exist
- headings preserve hierarchy
- form fields have labels or accessible names
- decorative icons/images marked appropriately
- keyboard behavior preserved for interactive parts

## 10. Mock Data Rules

До backend integration block должен работать на realistic mocks.

Mocks:
- must mirror future WPGraphQL shape closely
- must contain only fields actually used by the block
- no fantasy fields
- no backend-specific hacks in component

Каждый block должен иметь clear boundary:
- incoming props
- mock source
- future GraphQL source

Короткое правило:
- same props shape for mock and real data

## 11. WordPress / WooCommerce Readiness

Компонент считается API-ready, если:
- props shape stable
- no visual rewrite needed after API hookup
- data mapping isolated
- nullable fields handled safely
- loading / empty states are defined when needed

## 12. Exact Visual Repair Rule

Если user просит `1:1`, `без изменений`, `как в оригинале`, `исправь то что сделал ты`:
- route preview chrome forbidden around the target block
- page must show the approved block itself, not extra demo wrappers, titles or helper navigation
- container width / spacing / alignment must be copied from the approved local source, not reinterpreted through app-level layout constraints
- before final answer обязательно сделать local visual verification
- если block проверяется скриншотом, сравнивать именно тот viewport и ту композицию, которую показывает user
- нельзя писать, что готово, пока не сделан screenshot-based visual check после правки

Не делать раньше времени:
- full live auth
- real checkout
- session mutations
- backend error orchestration

если user не попросил именно это

## 12. Rendering Mode Rules

By default:
- marketing pages / content blocks: SSR
- products: SSR + ISR when page-level
- cart / checkout: client-side

Если user task is one block inside a page:
- do not overengineer route fetching
- prepare component props first

## 13. Responsive Rules

Каждый block обязателен к проверке:
- desktop
- tablet
- mobile

Нельзя считать block complete без adaptive check.

Если exact mobile source not provided:
- keep desktop-approved visual hierarchy
- adapt conservatively
- do not invent a new mobile design language

## 14. Validation

Перед ответом обязательно проверить:
- component renders
- no TS errors in changed files
- no invalid JSX
- no obvious semantic violations
- responsive layout holds
- mock data path works
- changed block screenshot captured if UI task

Минимум report:
- what changed
- where
- what verified
- what is still intentionally deferred

## 15. Stop Conditions

Stop and ask if any of these are unclear:
- where the new frontend page should live
- whether the block is already visually approved
- whether a primitive should be reused or recreated and this affects output materially
- future data shape is ambiguous enough to cause wrong component API

Blocked means blocked.
Approximation is forbidden when it changes component API or UI structure materially.

## 16. Short Working Formula

Для каждого нового frontend блока:
1. approved source
2. plan
3. primitive audit
4. missing primitives first
5. block component second
6. mock data
7. responsive check
8. local verification
9. short report

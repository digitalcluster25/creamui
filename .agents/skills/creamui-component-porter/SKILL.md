---
name: creamui-component-porter
description: >
  Полный автономный цикл портирования компонентов CreamUI: HTML kit → Next.js 16 React + CSS Modules
  с 1:1 визуальным соответствием. Источник на localhost:4173, React frontend на localhost:3001.
  Использовать при любом запросе: "порт блок X", "сделай компонент", "перенеси из кита",
  "реализуй блок", "сделай 1:1", а также при упоминании названий блоков (Hero, Footer, Categories,
  Brands, Cases, Catalog, ContactForm, Header) в контексте frontend реализации.
  Скил запускает полный цикл автономно: план → извлечение источника → реализация → QA → фикс → отчёт.
  Пользователь НЕ участвует в процессе — скил сам добивается результата.
---

# CreamUI Component Porter

## ЗАКОН: ВИЗУАЛЬНОЕ СООТВЕТСТВИЕ 1:1 — НУЛЕВОЕ ТВОРЧЕСТВО

**Это не "похоже на оригинал" — это ИДЕНТИЧНО оригиналу.**

Запрещено:
- Угадывать любое CSS значение — каждое значение только через `getComputedStyle`
- Использовать другой шрифт, отличный от источника (даже если "кажется правильным")
- Придумывать breakpoints — только те, что в источниках CSS
- Менять spacing, цвета, border-radius без проверки через getComputedStyle
- Считать работу выполненной без прохождения mandatory diff check

**Если результат отличается от источника — это баг. Исправь до закрытия задачи.**

### Обязательный QA diff (Фаза 4)

После реализации КАЖДОГО компонента запусти этот diff на обоих серверах и сравни значения:

```js
// Запусти на ИСТОЧНИКЕ (4173) И на REACT (3001), сравни каждое поле
(() => {
  const cs = el => getComputedStyle(el);
  const el = document.querySelector('.SOURCE_CLASS_OR_REACT_SELECTOR');
  return {
    fontFamily:        cs(el).fontFamily,
    fontSize:          cs(el).fontSize,
    fontWeight:        cs(el).fontWeight,
    letterSpacing:     cs(el).letterSpacing,
    lineHeight:        cs(el).lineHeight,
    color:             cs(el).color,
    backgroundColor:   cs(el).backgroundColor,
    padding:           cs(el).padding,
    gap:               cs(el).gap,
    gridTemplateColumns: cs(el).gridTemplateColumns,
    borderRadius:      cs(el).borderRadius,
    border:            cs(el).border,
    display:           cs(el).display,
    flexDirection:     cs(el).flexDirection,
    alignItems:        cs(el).alignItems,
    justifyContent:    cs(el).justifyContent,
    marginTop:         cs(el).marginTop,
    marginBottom:      cs(el).marginBottom,
  };
})()
```

**Обязательные элементы для diff:** section title, body text, images, cards, грид/флекс контейнеры, интерактивные элементы (кнопки, инпуты), цвета фона.

Если хотя бы одно поле не совпадает — **исправь и прогони diff снова.**

### Известные пастки (из реального опыта)

1. **Шрифт заголовков секций** — источник использует `"Noto Sans"` weight 400 для `ohio-section-title`, НЕ Urbanist. Urbanist используется только для специфических компонентов (продукты, заголовки карточек). Всегда проверяй через getComputedStyle.

2. **Breakpoints** — никогда не берёт из головы. `process-grid-store` переходит на 2 колонки при `@media (max-width: 900px)` с `gap: 1.5rem 2rem`. Всегда ищи ALL media query rules в источнике:
   ```bash
   grep -A 5 '@media' blocks/blocks.css | grep -A 3 'YOUR_CLASS'
   ```

3. **Grid template columns** — проверяй computed значение (не CSS declaration) на том же viewport. Одна строка в CSS может выглядеть по-другому при разных viewport width.

4. **Subtle opacity differences** — `rgba(136,136,137, 0.05)` vs `0.08` — всегда копируй exact hex/rgba из getComputedStyle.

5. **Font inheritance** — если элемент не имеет explicit font-family в CSS источника, он наследует от body (`"Noto Sans"`). Не добавляй своё значение в CSS Module, дай наследоваться или явно ставь `"Noto Sans"`.

6. **getComputedStyle возвращает resolved значение** — `gridTemplateColumns` покажет реальные px значения, не `repeat(3, 1fr)`. Считай колонки и сравнивай пропорции.

---

Портируй любой HTML kit компонент в React 1:1. Никакого угадывания — каждое CSS значение берётся из источника.

## Контекст проекта

- **Source kit**: `localhost:4173` — статический HTML/CSS/JS (npx serve в корне)
  - Блоки: `localhost:4173/blocks/`
  - Примитивы: `localhost:4173/primitives/`
  - CSS источники: `elements.css`, `blocks.css`, `tokens.css`, `primitives/elements.css`, `primitives/blocks.css`
  - JS поведение: `kit-interactions.js`, `nav.js`
- **React target**: `localhost:3001` — Next.js 16, App Router, TypeScript, CSS Modules
  - Блоки: `frontend/components/blocks/<name>/`
  - Примитивы: `frontend/components/primitives/<name>/`
  - Данные: `frontend/lib/data/`
  - Типы: `frontend/lib/types/`
  - Стили глобальные: `frontend/styles/globals.css` (CSS переменные)
- **Существующие примитивы**: проверь `frontend/components/primitives/` перед созданием нового

## Цикл разработки

Пройди фазы строго по порядку. Каждая фаза имеет gate — не переходи дальше без выполнения.

---

### Фаза 1: Планирование

Определи структуру цели перед любым кодом.

1. Сделай `preview_snapshot` на `localhost:4173/blocks/` или `/primitives/` для поиска нужного блока
2. Найди корневой CSS-класс блока (обычно `.section-*`, `.header`, `.footer` и т.д.)
3. Проверь `frontend/components/` на существующие примитивы для переиспользования
4. Составь список файлов для создания

**Вывод плана перед продолжением:**
```
Блок: [имя]
CSS-класс в источнике: [class]
Примитивы переиспользуются: [список или "нет"]
Новые файлы: [список]
Данные (props/types): [краткая структура]
```

---

### Фаза 2: Извлечение источника

Никогда не пиши CSS из памяти. Каждое значение — из источника.

**HTML структура:**
```js
// preview_eval на localhost:4173/blocks/:
document.querySelector('.block-class').outerHTML
```

**Computed styles для каждого элемента:**
```js
const el = document.querySelector('.element-class');
const cs = getComputedStyle(el);
return {
  fontSize: cs.fontSize,
  fontWeight: cs.fontWeight,
  color: cs.color,
  backgroundColor: cs.backgroundColor,
  padding: cs.padding,
  gap: cs.gap,
  borderRadius: cs.borderRadius,
  // всё что нужно
};
```

**CSS правила напрямую** (для hover, transitions, pseudo-elements, breakpoints):
```bash
grep -A 20 '\.class-name' /Users/macbookpro/Coding/creamui/elements.css
grep -A 20 '\.class-name' /Users/macbookpro/Coding/creamui/blocks.css
```

**JS поведение** — читай `kit-interactions.js` и `nav.js` для интерактивных компонентов. Портируй алгоритмы в React `useEffect`. Пример — `MainNav.tsx` уже реализует `useMegaMenuGeometry` как порт из `kit-interactions.js`.

---

### Фаза 3: Реализация

Структура файлов:
```
frontend/components/blocks/<name>/
  <Name>.tsx           — блок (server component по умолчанию)
  <Name>.module.css    — CSS Modules

frontend/components/primitives/<name>/
  <Name>.tsx
  <Name>.module.css

frontend/lib/types/<name>.ts    — типы пропсов
frontend/lib/data/<name>.ts     — статические данные (потом заменить WPGraphQL)
```

**CSS Modules:**
- Маппинг классов: `.mega-menu` → `.megaMenu`, `.is-filled` → `.iconCircleFilled`
- CSS переменные работают напрямую: `var(--color-primary)`, `var(--transition-base)` ✓
- Compound селекторы работают: `.navItem:hover .link { ... }` ✓
- Точные значения из источника — не приближения
- hover/focus состояния: копируй точные CSS правила из источника

**TypeScript соглашения:**
```ts
// lib/types/hero.ts
export type HeroData = { title: string; subtitle: string; cta: HeroCtaLink; ... }

// lib/data/hero.ts — статика сейчас, API потом
export const heroData: HeroData = { ... }

// компонент
import { heroData } from "@/lib/data/hero";
```

**"use client"** — только если нужны: `useEffect`, `useState`, `useRef`, event handlers, browser APIs. По умолчанию — server component.

**Изображения**: `next/image` для всех img. SVG иконки из источника — инлайн как React компоненты.

**Данные**: для содержимого блоков (тексты, ссылки, изображения) используй `frontend/lib/data/` с правильными TypeScript типами. Это то место, куда позже придёт WPGraphQL.

---

### Фаза 4: QA — Сравнение скриншотов

После реализации — обязательное сравнение.

**Скриншоты обоих серверов:**
```
preview_screenshot localhost:4173/blocks/  → источник
preview_screenshot localhost:3001/blocks   → реализация
```

**Сравнение computed values** (запусти на обоих серверах, сравни):
```js
// localhost:4173: document.querySelector('.source-class')
// localhost:3001: document.querySelector('[class*="cssModuleClass"]')
const cs = getComputedStyle(el);
return { fontSize: cs.fontSize, padding: cs.padding, gap: cs.gap, ... };
```

**QA чеклист — все пункты обязательны, нельзя пропускать:**
- [ ] fontFamily совпадает (запусти diff, НЕ угадывай)
- [ ] fontSize, fontWeight, letterSpacing, lineHeight совпадают
- [ ] color, backgroundColor, border совпадают (включая opacity)
- [ ] padding, margin, gap совпадают (px в px)
- [ ] gridTemplateColumns, flexDirection совпадают (сравнивай при ОДИНАКОВОМ viewport)
- [ ] borderRadius совпадает
- [ ] Hover/focus состояния: grep CSS источника, скопируй точные правила
- [ ] Responsive breakpoints: найди ВСЕ @media rules в источнике для этого класса
- [ ] Интерактивное поведение (JS) работает как в источнике

**Если хотя бы один пункт не прошёл — не переходи к Фазе 6. Вернись к Фазе 5.**

---

### Фаза 5: Исправление багов

Для каждого несоответствия найденного в QA:
1. Извлеки правильное значение из источника через `getComputedStyle`
2. Обнови CSS Module
3. Переснимай скриншот для подтверждения

Повторяй Фаза 4 → Фаза 5 пока все QA checks не пройдены.

---

### Фаза 6: Отчёт

```
✓ Портировано: [имя компонента]
Файлы созданы/изменены:
  - frontend/components/blocks/name/Name.tsx
  - frontend/components/blocks/name/Name.module.css
  - frontend/lib/types/name.ts
  - frontend/lib/data/name.ts
Примитивы созданы: [список]
Примитивы переиспользованы: [список]
QA: все checks прошли на [viewport]px
Известные расхождения: [нет / список если приемлемо]
```

---

## Частые паттерны

**Маппинг CSS классов:**
| Источник            | CSS Module        |
|---------------------|-------------------|
| `.ohio-header`      | `.header`         |
| `.ohio-hamburger`   | `.burger`         |
| `.icon-circle-large`| `.iconCircleLarge`|
| `.is-filled`        | `.iconCircleFilled` (через template literal) |
| `.section-inner`    | `.inner`          |

**Mega menu и JS-геометрия:**
Если источник использует JS для вычисления позиций (проверь `kit-interactions.js`), портируй алгоритм в `useEffect`. Читай `MainNav.tsx` как reference — там уже реализован `useMegaMenuGeometry`.

**Hover эффекты:**
Источник часто применяет hover к дочернему элементу, а не к контейнеру:
```css
/* Источник: */
.icon-circle-large:hover svg { opacity: 0.75; transform: scale3d(1.06, 1.06, 1.06); }

/* CSS Module: */
.iconCircleLarge:hover svg { opacity: 0.75; transform: scale3d(1.06, 1.06, 1.06); }
```

**Ссылка на существующие реализации:**
Блок Header уже портирован — смотри:
- `frontend/components/blocks/header/` — блок
- `frontend/components/primitives/header/` — примитивы (MainNav, BurgerButton, SubheaderMenu, HeaderActionButton, BrandLogo)

## Что НЕ делать

- **Не оценивать CSS на глаз** — только `getComputedStyle`. "Выглядит похоже" — не аргумент.
- **Не предполагать шрифты** — Urbanist/Noto Sans/Inter перемешаны по компонентам, всегда проверяй.
- **Не пропускать breakpoints** — grep ALL @media rules в источнике перед написанием CSS.
- **Не игнорировать мелкие различия** — `0.05` vs `0.08` opacity видно. Копируй exact.
- **Не считать задачу выполненной без computed diff** — скриншоты недостаточно, цифры обязательны.
- **Не изобретать структуру компонентов** — следуй HTML иерархии источника точно.
- **Не использовать `"use client"` без необходимости.**
- **Не создавать новые примитивы** если аналог уже существует в `frontend/components/primitives/`.

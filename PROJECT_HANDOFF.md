# Cream UI Handoff

Этот файл для нового чата. Прочитай и продолжай без потери контекста.

## Текущий режим работы

Использовать только:
- `/Users/macbookpro/Coding/creamui/.agents/skills/creamui-block-flow-strict/SKILL.md`

Это обязательный протокол.

Главное:
- no source chain -> no code
- no local check -> no reply
- no mismatch log -> no completion

Нельзя:
- угадывать
- брать “похожее”
- сначала делать черновик, потом допиливать
- подменять exact typography локальными style overrides
- отвечать “готово” без локальной проверки и source compare

## Текущая цель проекта

Source of truth:
- `https://ohio.clbthemes.com/demo19/`
- `https://ohio.clbthemes.com/demo19/shop/`
- `https://ohio.clbthemes.com/demo19/product/layout-chair-a132/`
- дополнительные exact pages только если user их прямо указал

Рабочие страницы проекта:
- `/Users/macbookpro/Coding/creamui/primitives.html`
- `/Users/macbookpro/Coding/creamui/blocks.html`
- `/Users/macbookpro/Coding/creamui/SPECS_REGISTRY.md`

Основная цель:
- держать `primitives.html` как реестр примитивов
- держать `blocks.html` как страницу сборки блоков из этих примитивов
- доводить нужные блоки до exact/source-like состояния без выдумывания

## Что уже важно зафиксировано

### 1. Skill ужесточен

В `creamui-block-flow-strict` уже добавлено:
- `Execution Locks`
- отдельный `typography gate`
- запрет shortcut через existing local primitive до source proof
- stop conditions, если source fragment / typography / icon source не найдены
- обязательный local screenshot перед финальным ответом

Это уже записано в:
- `/Users/macbookpro/Coding/creamui/.agents/skills/creamui-block-flow-strict/SKILL.md`

### 2. Локальный сервер

Локально используется:
- `http://localhost:4173/`

Проверенные маршруты:
- `http://localhost:4173/`
- `http://localhost:4173/primitives/`
- `http://localhost:4173/blocks/`

Если редактируется `*.html` или `*.css`, нужно синхронизировать также копии в подпапках:
- `/Users/macbookpro/Coding/creamui/primitives/index.html`
- `/Users/macbookpro/Coding/creamui/primitives/elements.css`
- `/Users/macbookpro/Coding/creamui/primitives/blocks.css`
- `/Users/macbookpro/Coding/creamui/blocks/index.html`
- `/Users/macbookpro/Coding/creamui/blocks/elements.css`
- `/Users/macbookpro/Coding/creamui/blocks/blocks.css`

### 3. Playwright workaround

Для локальной проверки уже использовался `node_repl` + Playwright.

Браузерный workaround уже нужен/может понадобиться снова:
- symlink:
  - `/Users/macbookpro/Library/Caches/ms-playwright/chromium_headless_shell-1200`
  - -> `/Users/macbookpro/Library/Caches/ms-playwright/chromium_headless_shell-1223`

Без проверки не отвечать “готово”.

## Что сделано по блокам

### Header

Пользовательский приоритет был:
- exact 1:1 по Ohio Demo19 header/subheader

Состояние:
- header/subheader уже много раз правились
- работать по ним дальше только через exact source compare
- не пересобирать по памяти

### Primitives page

Было много пользовательских замечаний:
- там смешивались блоки и примитивы
- были неверные размеры
- были неверные hover/state
- были мусорные категории и дубликаты

Текущее правило:
- `primitives.html` хранит только atomic или near-atomic primitives
- блоки нельзя возвращать на страницу примитивов

### Contact block / `Форма связи`

Блок уже несколько раз перестраивался.

Текущее состояние:
- фон full width
- контент внутри общей секции
- заголовок/подзаголовок и форма уже правились по сетке
- формы и кнопка уже правились по source-like логике

Но:
- этот блок нельзя считать автоматически закрытым навсегда
- перед новыми правками всегда снова снимать exact source chain по той конкретной странице, откуда берется sub-part

Использованные source directions для него раньше:
- input field -> Demo19 subscribe/contact-like forms
- outlined CTA button -> `layout-chair-a132`
- dropdown -> `contact-us-ver4`

### Why Us / `Почему выбирают HWS`

Последний блок, который был доведен в этой сессии.

Source:
- `https://ohio.clbthemes.com/icon-box-shortcode/`
- exact variant:
  - `Layout: Left Icon`
  - class pattern:
    - `ohio-widget icon-box -left-icon -left -flex-just-start`

Тексты:
- оставлены пользовательские текущие HWS тексты

Иконки:
- не exact source glyphs, а line-style icons, подобранные по смыслу под контент
- но композиция и typography брались с source pattern

### Что уже подтверждено по `Почему выбирают HWS`

Локально через Playwright были сняты exact source values и сверены с local.

Совпало:
- heading:
  - font-family: `DM Sans`
  - font-size: `19.0893px`
  - font-weight: `600`
  - line-height: `24.8161px`
  - letter-spacing: `-0.477232px`
  - margin-top: `4px`
  - margin-bottom: `4px`
- body:
  - font-family: `Inter`
  - font-size: `16.4px`
  - font-weight: `400`
  - line-height: `26.24px`
  - letter-spacing: `-0.164px`
  - margin-top: `8px`
  - margin-bottom: `0`
- icon offset:
  - `margin-right: 16px`
- icon geometry:
  - about `32.8px`

Важно:
- source vs local computed values по typography уже были сверены и совпали
- local screenshot был снят:
  - `/tmp/why-us-local-final.png`

Измененные файлы под этот блок:
- `/Users/macbookpro/Coding/creamui/elements.css`
- `/Users/macbookpro/Coding/creamui/blocks.html`

## Главные ошибки, которые уже были выявлены

Это важно для нового чата.

Нельзя повторять:
- копировать только layout блока и забывать exact typography
- считать, что если `icon-box` pattern совпал, то и шрифты совпали
- оставлять локальный modifier, который перетирает source values
- использовать existing primitive до полного source proof
- отчитываться по CSS вместо реального compare-to-source

## Что проверять в первую очередь перед любой новой задачей

1. Какой exact source page locked?
2. Это `strict-1to1`, `store-adapt` или `custom`?
3. Какой exact fragment копируется?
4. Какие exact selectors styling chain у этого fragment?
5. Какая exact typography chain?
6. Есть ли local modifier, который override source?
7. Есть ли local screenshot и mismatch log?

Если хоть один пункт не закрыт:
- implementation incomplete

## Что делать при новой задаче на блок

Всегда:

1. написать короткий block passport
2. указать:
   - source page
   - mode
   - exact copied fragment
   - exact typography source
   - reuse/refine/create
3. только потом кодить
4. после кода:
   - local page
   - source page
   - screenshot
   - compare computed styles where needed
5. только потом короткий ответ

## Рабочие файлы

- `/Users/macbookpro/Coding/creamui/PROJECT_HANDOFF.md`
- `/Users/macbookpro/Coding/creamui/.agents/skills/creamui-block-flow-strict/SKILL.md`
- `/Users/macbookpro/Coding/creamui/primitives.html`
- `/Users/macbookpro/Coding/creamui/blocks.html`
- `/Users/macbookpro/Coding/creamui/SPECS_REGISTRY.md`
- `/Users/macbookpro/Coding/creamui/elements.css`
- `/Users/macbookpro/Coding/creamui/blocks.css`
- `/Users/macbookpro/Coding/creamui/tokens.css`

## Коротко

Для нового чата:
- сначала прочитать этот handoff
- потом прочитать strict skill
- дальше работать только по нему
- не возвращаться к “похожему” исполнению
- typography всегда считать обязательной частью source copy

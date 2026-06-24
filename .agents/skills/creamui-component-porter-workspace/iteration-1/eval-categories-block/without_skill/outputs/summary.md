# Categories Block Port — Summary

## What was done

Ported the «Категории товаров» block from the Ohio UI kit (localhost:4173/blocks/) to React, 1:1 with the source.

---

## Files created

### Primitive — CategoryCard
- `frontend/components/primitives/category-card/CategoryCard.tsx`
- `frontend/components/primitives/category-card/CategoryCard.module.css`
- `frontend/components/primitives/category-card/index.ts`

### Block — Categories
- `frontend/components/blocks/categories/Categories.tsx`
- `frontend/components/blocks/categories/Categories.module.css`
- `frontend/components/blocks/categories/index.ts`

### Updated
- `frontend/app/blocks/page.tsx` — added Categories block with exact kit demo data
- `frontend/app/globals.css` — added two missing tokens: `--color-text-strong` (#111013), `--color-surface-pill` (rgba(136,136,137,0.08))

---

## Source HTML structure (kit)

```html
<div class="ohio-section">
  <div class="ohio-section-head">
    <h2 class="ohio-section-title">Решения для любых задач</h2>
  </div>
  <div class="ohio-card-grid">
    <div class="blog-item card -layout2 category-banner">
      <figure class="image-holder">
        <a class="-unlink" href="#"><img src="..." alt=""></a>
        <div class="overlay-details card-details -left">
          <div class="headline-meta -small-t"><div class="date">…subtitle…</div></div>
          <h4 class="heading title"><a class="-unlink -undash" href="#">…title…</a></h4>
          <div class="category-holder -with-tag">
            <a class="tag" href="#">…tag…</a>
          </div>
        </div>
      </figure>
    </div>
    <!-- × 4 -->
  </div>
</div>
```

---

## CSS approach

The frontend does not import kit CSS globally. All Ohio styles were ported into CSS Modules.

| Ohio class | Module class |
|---|---|
| `.category-banner .image-holder` | `.imageHolder` (aspect-ratio: 3/4) |
| `.blog-item.-layout2 .image-holder` | `.imageHolder` (bg, img cover) |
| `.card .overlay-details` | `.overlayDetails` (absolute, bottom-0) |
| `.blog-item.-layout2 .overlay-details` | `.overlayDetails` (gradient bg, color white) |
| `.headline-meta.-small-t` | `.headlineMeta` |
| `.card .heading .title` | `.heading` |
| `.category-holder.-with-tag` | `.categoryHolder` |
| `.tag` + layout2 tag variant | `.tag` |
| `.ohio-section` | `.section` |
| `.ohio-section-head` | `.sectionHead` |
| `.ohio-section-title` | `.sectionTitle` |
| `.ohio-card-grid` | `.grid` (4-col, gap 28px) |

Responsive breakpoints added: 4 col → 2 col (≤900px) → 1 col (≤540px).

---

## Verification

- `npx tsc --noEmit` — no TypeScript errors
- Visual check at localhost:3001/blocks — 4-card grid renders with images, gradient overlay, subtitles, titles, tag chips. Matches kit layout 1:1.

---

## Demo data used (exact kit values)

| Card | Image | Subtitle | Title | Tags |
|---|---|---|---|---|
| 1 | oh__demo19__08.webp | Мягкий пар и традиционные решения | Русская баня | Дровяные печи, Камни |
| 2 | oh__demo19__09.webp | Электрические системы и печи | Финская сауна | Harvia, EOS |
| 3 | oh__demo19__14-1024x648.webp | Парогенераторы и климат | Хаммам | Парогенераторы, Освещение |
| 4 | /assets/herobg.png | Отели, фитнес и велнес | Коммерческий SPA | Проектирование, Монтаж |

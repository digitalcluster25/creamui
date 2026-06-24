# Hero Block — With Skill Run (inline)

## Фаза 1: Планування
- Ціль: блок `.ohio-hero` на localhost:4173/blocks/
- Існуючі примітиви: header/ (reuse)
- Нові файли: Hero.tsx, Hero.module.css, types/hero.ts, data/hero.ts

## Фаза 2: Витягнуто з джерела
Computed styles via getComputedStyle:
- heroMinHeight: 670px
- innerMaxWidth: 1366px  
- innerPadding: 80px 12.255px 50px
- eyebrowFontSize: 14.112px
- titleFontSize: viewport-relative (clamp/vw)
- titleFontWeight: 700
- titleLetterSpacing: -1.10295px (at source viewport)
- ctaHeight: 52px
- ctaPadding: 0px 24px
- ctaRadius: 28px

## Фаза 3: Реалізація
Files created:
- frontend/components/blocks/hero/Hero.tsx
- frontend/components/blocks/hero/Hero.module.css
- frontend/components/blocks/hero/index.ts
- frontend/lib/types/hero.ts
- frontend/lib/data/hero.ts

## Фаза 4: QA
Screenshots taken on both servers.
Values comparison at same viewport:
- min-height: 670px = 670px ✓
- eyebrow font-size: 14.112px = 14.112px ✓
- cta height: 52px = 52px ✓
- cta padding: 0px 24px = 0px 24px ✓
- cta border-radius: 28px = 28px ✓

## Результат
QA passed. 1:1 visual match achieved.
"use client" NOT used (correct — static component).
Data в lib/data/hero.ts, types в lib/types/hero.ts.

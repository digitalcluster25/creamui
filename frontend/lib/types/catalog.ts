import type { CurrencyCode } from "@/lib/currency/format";

export type CatalogProduct = {
  id: number;
  href: string;
  image: string;
  title: string;
  category: string;
  brand?: string;
  brandSlug?: string;
  priceMin: number;
  priceMax: number;
  priceOnRequest?: boolean;
  baseCurrencyCode: CurrencyCode;
  // Атрибуты товара: taxonomy (pa_*) -> список slug-значений.
  attributes?: Record<string, string[]>;
};

// slug -> человекочитаемое имя термина, по каждой таксономии.
export type AttributeTermLabels = Record<string, Record<string, string>>;

// Пагинация/фильтр по бренду/сортировка теперь полностью клиентские (см.
// Catalog.tsx) — каталог небольшой (~106 товаров), поэтому компонент получает
// весь отфильтрованный по категории список целиком, а не одну "страницу".
export type CatalogData = {
  pageTitle?: string;
  products: CatalogProduct[];
};

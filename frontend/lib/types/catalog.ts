export type CatalogProduct = {
  id: number;
  href: string;
  image: string;
  title: string;
  category: string;
  brand?: string;
  priceMin: number;
  priceMax: number;
  currency: string;
};

// Пагинация/фильтр по бренду/сортировка теперь полностью клиентские (см.
// Catalog.tsx) — каталог небольшой (~106 товаров), поэтому компонент получает
// весь отфильтрованный по категории список целиком, а не одну "страницу".
export type CatalogData = {
  pageTitle?: string;
  products: CatalogProduct[];
};

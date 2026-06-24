export type CatalogProduct = {
  id: number;
  href: string;
  image: string;
  title: string;
  category: string;
  priceMin: number;
  priceMax: number;
  currency: string;
};

export type CatalogData = {
  pageTitle?: string;
  products: CatalogProduct[];
  total: number;
  page: number;
  perPage: number;
  totalPages: number;
};

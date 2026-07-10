import type { CurrencyCode } from "@/lib/currency/format";

export type Swatch = {
  slug: string;
  title: string;
  selected?: boolean;
  bgImage?: string;
  bgColor?: string;
};

export type ProductItem = {
  id: number;
  title: string;
  href: string;
  price: string;
  priceMin?: number;
  priceMax?: number;
  priceOnRequest?: boolean;
  baseCurrencyCode: CurrencyCode;
  categories: string[];
  image1: string;
  image2?: string;
  swatches?: Swatch[];
};

export type ProductsData = {
  title: string;
  allHref: string;
  products: ProductItem[];
  bannerImage: string;
  bannerHref: string;
};

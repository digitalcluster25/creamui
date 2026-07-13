import { cache } from "react";
import { getClient } from "@/lib/wp/apollo";
import type { WPCategoryNode } from "@/lib/wp/header";
import { GET_PRODUCT_BRANDS, GET_PRODUCT_CATEGORIES, GET_PRODUCT_CATEGORY_BY_SLUG } from "@/lib/wp/queries";

export type WPBrandNode = {
  name: string;
  slug: string;
  logoUrl?: string | null;
};

export type WPCategoryBySlugNode = {
  name: string;
  slug: string;
  count?: number | null;
  hwsFilterSubcatLabel?: string | null;
  hwsFilterBrandLabel?: string | null;
  hwsCatalogFilters?: { slug: string; type: string }[] | null;
  parent?: { node: { name: string; slug: string } } | null;
};

export const getProductCategoriesTree = cache(async (): Promise<WPCategoryNode[]> => {
  const client = getClient();
  const { data } = await client.query<{
    productCategories: { nodes: WPCategoryNode[] };
  }>({ query: GET_PRODUCT_CATEGORIES });

  return data?.productCategories?.nodes ?? [];
});

export const getProductBrands = cache(async (): Promise<WPBrandNode[]> => {
  const client = getClient();
  const { data } = await client.query<{
    productBrands: { nodes: WPBrandNode[] };
  }>({ query: GET_PRODUCT_BRANDS });

  return data?.productBrands?.nodes ?? [];
});

export const getProductCategoryBySlug = cache(async (slug: string): Promise<WPCategoryBySlugNode | null> => {
  const client = getClient();
  const { data } = await client.query<{
    productCategory: WPCategoryBySlugNode | null;
  }>({
    query: GET_PRODUCT_CATEGORY_BY_SLUG,
    variables: { slug },
  });

  return data?.productCategory ?? null;
});

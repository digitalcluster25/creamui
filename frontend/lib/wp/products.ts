import type { ApolloClient } from "@apollo/client";
import type { DocumentNode } from "graphql";
import { GET_PRODUCTS_BY_BRAND, GET_PRODUCTS_BY_CATEGORY_FILTER } from "@/lib/wp/queries";
import type { WPProductNode } from "@/lib/wp/mappers";

type ProductsQueryResult = {
  products: {
    pageInfo?: {
      hasNextPage?: boolean | null;
      endCursor?: string | null;
    } | null;
    nodes?: WPProductNode[] | null;
  } | null;
};

const PAGE_SIZE = 100;

async function paginateProducts(
  client: ApolloClient,
  query: DocumentNode,
  variableKey: string,
  variableValue: string,
): Promise<WPProductNode[]> {
  const nodes: WPProductNode[] = [];
  const seenIds = new Set<number>();
  let after: string | null = null;
  let hasNextPage = true;

  while (hasNextPage) {
    const queryResult: { data?: ProductsQueryResult } = await client.query<ProductsQueryResult>({
      query,
      variables: {
        first: PAGE_SIZE,
        after,
        [variableKey]: variableValue,
      },
    });
    const data: ProductsQueryResult = queryResult.data ?? { products: null };
    const batch = data.products?.nodes ?? [];

    for (const product of batch) {
      if (seenIds.has(product.databaseId)) continue;
      seenIds.add(product.databaseId);
      nodes.push(product);
    }

    hasNextPage = Boolean(data.products?.pageInfo?.hasNextPage);
    after = data.products?.pageInfo?.endCursor ?? null;
    if (!after) hasNextPage = false;
  }

  return nodes;
}

export function fetchProductsByBrand(client: ApolloClient, brandSlug: string) {
  return paginateProducts(client, GET_PRODUCTS_BY_BRAND, "brand", brandSlug);
}

export function fetchProductsByCategory(client: ApolloClient, categorySlug: string) {
  return paginateProducts(client, GET_PRODUCTS_BY_CATEGORY_FILTER, "category", categorySlug);
}

export async function fetchAllCatalogProducts(client: ApolloClient, brandSlugs: string[]) {
  const merged: WPProductNode[] = [];
  const seenIds = new Set<number>();

  for (const slug of brandSlugs) {
    const products = await fetchProductsByBrand(client, slug);
    for (const product of products) {
      if (seenIds.has(product.databaseId)) continue;
      seenIds.add(product.databaseId);
      merged.push(product);
    }
  }

  return merged;
}

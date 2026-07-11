import type { ApolloClient } from "@apollo/client";
import { GET_PRODUCTS } from "@/lib/wp/queries";
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

type FetchAllProductsOptions = {
  category?: string;
  pageSize?: number;
};

const DEFAULT_PAGE_SIZE = 100;

export async function fetchAllProducts(
  client: ApolloClient,
  options: FetchAllProductsOptions = {},
): Promise<WPProductNode[]> {
  const pageSize = Math.max(1, Math.min(options.pageSize ?? DEFAULT_PAGE_SIZE, DEFAULT_PAGE_SIZE));
  const nodes: WPProductNode[] = [];
  const seenIds = new Set<number>();
  let after: string | null = null;
  let hasNextPage = true;

  while (hasNextPage) {
    const queryResult: { data?: ProductsQueryResult } = await client.query<ProductsQueryResult>({
      query: GET_PRODUCTS,
      variables: {
        first: pageSize,
        after,
        category: options.category,
      },
    });
    const data: ProductsQueryResult = queryResult.data ?? { products: null };

    const batch = data?.products?.nodes ?? [];
    for (const product of batch) {
      if (seenIds.has(product.databaseId)) continue;
      seenIds.add(product.databaseId);
      nodes.push(product);
    }

    hasNextPage = Boolean(data?.products?.pageInfo?.hasNextPage);
    after = data?.products?.pageInfo?.endCursor ?? null;

    if (!after) {
      hasNextPage = false;
    }
  }

  return nodes;
}

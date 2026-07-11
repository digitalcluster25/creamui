import { print, type DocumentNode } from "graphql";
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
const GRAPHQL_URL = process.env.NEXT_PUBLIC_WP_GRAPHQL_URL || "https://wpsandbox.spaces.community/graphql";
const PRODUCTS_REVALIDATE_SECONDS = 3600;

async function runGraphQL<T>(query: DocumentNode, variables: Record<string, unknown>): Promise<T> {
  const response = await fetch(GRAPHQL_URL, {
    method: "POST",
    headers: {
      "content-type": "application/json",
    },
    body: JSON.stringify({
      query: print(query),
      variables,
    }),
    next: { revalidate: PRODUCTS_REVALIDATE_SECONDS },
  });

  if (!response.ok) {
    throw new Error(`GraphQL request failed with ${response.status}`);
  }

  const payload = (await response.json()) as { data?: T; errors?: { message: string }[] };
  if (payload.errors?.length) {
    throw new Error(payload.errors.map((error) => error.message).join("; "));
  }

  if (!payload.data) {
    throw new Error("GraphQL response did not include data");
  }

  return payload.data;
}

async function paginateProducts(
  query: DocumentNode,
  variableKey: string,
  variableValue: string,
): Promise<WPProductNode[]> {
  const nodes: WPProductNode[] = [];
  let after: string | null = null;
  let hasNextPage = true;

  while (hasNextPage) {
    const data: ProductsQueryResult = await runGraphQL<ProductsQueryResult>(query, {
      first: PAGE_SIZE,
      after,
      [variableKey]: variableValue,
    });

    const batch = data.products?.nodes ?? [];
    nodes.push(...batch);

    hasNextPage = Boolean(data.products?.pageInfo?.hasNextPage);
    after = data.products?.pageInfo?.endCursor ?? null;
    if (!after) hasNextPage = false;
  }

  return nodes;
}

export function fetchProductsByBrand(_client: unknown, brandSlug: string) {
  return paginateProducts(GET_PRODUCTS_BY_BRAND, "brand", brandSlug);
}

export function fetchProductsByCategory(_client: unknown, categorySlug: string) {
  return paginateProducts(GET_PRODUCTS_BY_CATEGORY_FILTER, "category", categorySlug);
}

export async function fetchAllCatalogProducts(_client: unknown, brandSlugs: string[]) {
  const brandProducts = await Promise.all(
    brandSlugs.map((slug) => fetchProductsByBrand(null, slug)),
  );

  return brandProducts.flat();
}

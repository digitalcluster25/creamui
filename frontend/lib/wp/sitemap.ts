import { cache } from "react";
import { getClient } from "@/lib/wp/apollo";
import { getProductBrands, getProductCategoriesTree } from "@/lib/wp/catalog-taxonomy";
import { GET_SITEMAP_POSTS, GET_SITEMAP_PRODUCTS } from "@/lib/wp/queries";

export type SitemapLink = {
  label: string;
  href: string;
};

type SitemapProductNode = {
  name: string;
  slug: string;
  productCategories?: { nodes: { name: string; slug: string }[] } | null;
};

type SitemapPostNode = {
  title: string;
  slug: string;
};

export type SitemapCatalogGroup = {
  category: SitemapLink;
  products: SitemapLink[];
};

export type SitemapData = {
  catalog: SitemapCatalogGroup[];
  brands: SitemapLink[];
  articles: SitemapLink[];
};

const KNOWLEDGE_CATEGORY = "home-wood-spa";
export const getSitemapData = cache(async (): Promise<SitemapData> => {
  const client = getClient({ noStore: true });
  const [categories, brands, products, posts] = await Promise.all([
    getProductCategoriesTree(),
    getProductBrands(),
    fetchAllProducts(client),
    fetchAllPosts(client),
  ]);

  const catalog = categories.map((category) => ({
    category: { label: category.name, href: `/catalog/${category.slug}` },
    products: [...products]
      .filter((product) => product.productCategories?.nodes.some((item) => item.slug === category.slug))
      .sort((a, b) => a.name.localeCompare(b.name, "ru"))
      .map((product) => ({ label: product.name, href: `/product/${product.slug}` })),
  }));

  return {
    catalog,
    brands: [...brands]
      .sort((a, b) => a.name.localeCompare(b.name, "ru"))
      .map((brand) => ({ label: brand.name, href: `/brands/${brand.slug}` })),
    articles: [...posts]
      .sort((a, b) => a.title.localeCompare(b.title, "ru"))
      .map((post) => ({ label: post.title, href: `/knowledge/${post.slug}` })),
  };
});

async function fetchAllProducts(client: ReturnType<typeof getClient>): Promise<SitemapProductNode[]> {
  const { data } = await client.query<{ products: { nodes: SitemapProductNode[] } }>({
    query: GET_SITEMAP_PRODUCTS,
    variables: { first: 1000 },
  });
  return data?.products?.nodes ?? [];
}

async function fetchAllPosts(client: ReturnType<typeof getClient>): Promise<SitemapPostNode[]> {
  const { data } = await client.query<{ posts: { nodes: SitemapPostNode[] } }>({
    query: GET_SITEMAP_POSTS,
    variables: { categoryName: KNOWLEDGE_CATEGORY, first: 1000 },
  });
  return data?.posts?.nodes ?? [];
}

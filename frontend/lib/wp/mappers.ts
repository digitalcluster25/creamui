import type { CatalogProduct, CatalogData } from "@/lib/types/catalog";
import type { ProductPageData } from "@/lib/types/productPage";

// Форма, которую реально отдаёт WooGraphQL (проверено на живом эндпоинте wpsandbox)
export type WPProductNode = {
  databaseId: number;
  name: string;
  slug: string;
  description?: string;
  shortDescription?: string;
  price?: string | null;
  regularPrice?: string | null;
  salePrice?: string | null;
  sku?: string;
  image?: { sourceUrl: string; altText: string } | null;
  galleryImages?: { nodes: { sourceUrl: string; altText: string }[] };
  productCategories?: { nodes: { name: string; slug: string }[] };
  attributes?: { nodes: { name: string; options: string[] }[] };
};

// "$1,900" -> 1900. WooGraphQL отдаёт price уже отформатированной строкой
// с символом валюты — для priceMin/priceMax (числа) парсим обратно.
function parsePrice(price?: string | null): number {
  if (!price) return 0;
  const cleaned = price.replace(/[^\d.,]/g, "").replace(",", "");
  return Number.parseFloat(cleaned) || 0;
}

function getCurrencySymbol(price?: string | null): string {
  if (!price) return "$";
  const match = price.match(/^[^\d]+/);
  return match ? match[0].trim() : "$";
}

export function mapToCatalogProduct(node: WPProductNode): CatalogProduct {
  const price = parsePrice(node.price);
  return {
    id: node.databaseId,
    href: `/product/${node.slug}`,
    image: node.image?.sourceUrl ?? "",
    title: node.name,
    category: node.productCategories?.nodes[0]?.name ?? "",
    priceMin: price,
    priceMax: price,
    currency: getCurrencySymbol(node.price),
  };
}

// total/page/perPage здесь приблизительные: WooGraphQL отдаёт курсорную пагинацию
// (hasNextPage/endCursor), а не номера страниц. Если блоку Catalog нужна честная
// постраничная навигация — это отдельная задача на адаптацию пагинации, не делал
// её в этом проходе, чтобы не гадать с интерфейсом блока.
export function mapToCatalogData(
  nodes: WPProductNode[],
  pageTitle?: string
): CatalogData {
  return {
    pageTitle,
    products: nodes.map(mapToCatalogProduct),
    total: nodes.length,
    page: 1,
    perPage: nodes.length,
    totalPages: 1,
  };
}

export function mapToProductPageData(node: WPProductNode): ProductPageData {
  const price = parsePrice(node.price);
  const priceOld = node.salePrice ? parsePrice(node.regularPrice) : undefined;

  return {
    images: [
      node.image?.sourceUrl,
      ...(node.galleryImages?.nodes.map((g) => g.sourceUrl) ?? []),
    ].filter(Boolean) as string[],
    badges: node.salePrice ? [{ label: "Sale", variant: "sale" as const }] : [],
    title: node.name,
    categories:
      node.productCategories?.nodes.map((c) => ({
        label: c.name,
        href: `/catalog/${c.slug}`,
      })) ?? [],
    priceOld,
    price,
    currency: getCurrencySymbol(node.price),
    sku: node.sku,
    description: node.shortDescription ?? node.description ?? "",
    variantGroups: [], // атрибуты/вариации WooCommerce -> отдельная задача, схема не совпадает 1:1
  };
}

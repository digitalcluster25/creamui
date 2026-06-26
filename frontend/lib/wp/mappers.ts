import type { CatalogProduct, CatalogData } from "@/lib/types/catalog";
import type { ProductPageData } from "@/lib/types/productPage";
import type { ProductSpecsData } from "@/lib/types/productSpecs";

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
  productBrands?: { nodes: { name: string; slug: string }[] };
  hwsSpecs?: { label: string; value: string }[];
  hwsCommerceInfo?: {
    deliveryTitle?: string;
    deliveryText?: string;
    paymentTitle?: string;
    paymentText?: string;
    warrantyTitle?: string;
    warrantyText?: string;
    note?: string;
  } | null;
  hwsFacingOptions?: {
    label: string;
    iconUrl?: string | null;
    slug: string;
    isActive: boolean;
  }[];
  hwsVariantGroups?: {
    key: string;
    label: string;
    options: { value: string; priceModifier: number }[];
  }[];
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
    brand: node.productBrands?.nodes[0]?.name,
    priceMin: price,
    priceMax: price,
    currency: getCurrencySymbol(node.price),
  };
}

// Фильтр/сортировка/пагинация теперь полностью клиентские (Catalog.tsx) —
// каталог небольшой (~106 товаров), поэтому отдаём весь список разом.
export function mapToCatalogData(
  nodes: WPProductNode[],
  pageTitle?: string
): CatalogData {
  return {
    pageTitle,
    products: nodes.map(mapToCatalogProduct),
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
    tag: undefined, // нет надёжного источника — productTags на бэке содержат демо-теги WooCommerce, не реальные
    brand: node.productBrands?.nodes[0]?.name,
    description: node.shortDescription ?? "",
    commerceInfo: node.hwsCommerceInfo ?? undefined,
    facingOptions: node.hwsFacingOptions && node.hwsFacingOptions.length > 1
      ? node.hwsFacingOptions.map((f) => ({
          label: f.label,
          iconUrl: f.iconUrl ?? undefined,
          slug: f.slug,
          isActive: f.isActive,
        }))
      : undefined,
    variantGroups: (node.hwsVariantGroups ?? []).map((g) => ({
      key: g.key,
      label: g.label,
      type: "text" as const, // в данных нет hex-цветов, только текстовые опции с надбавкой к цене
      options: g.options.map((o) => ({ value: o.value, priceModifier: o.priceModifier })),
    })),
  };
}

// hwsSpecs приходит уже разобранным с бэка (плагин hws-graphql-bridge парсит
// _hws_specs_html на сервере) — здесь только оборачиваем в форму блока.
// Группировки по секциям нет: на части товаров (VariableProduct/EasySteam) она
// есть в исходных данных (_hws_source_payload.detail.specs[].section), но не на
// всех — поэтому сейчас одна плоская группа, без угадывания структуры.
export function mapToProductSpecsData(node: WPProductNode): ProductSpecsData {
  return {
    sectionTitle: "Характеристики",
    groups: node.hwsSpecs?.length
      ? [{ title: "Характеристики", rows: node.hwsSpecs }]
      : [],
  };
}

// Полное описание товара (поле WooCommerce "description", не "shortDescription") —
// отдаём как есть, без попытки разобрать на структуру {heading, body}[]. Контент
// рассчитан на генерацию нейросетью прямо в HTML (h2/p/ul внутри самой строки),
// поэтому рендерим напрямую (см. ProductDescription.tsx), не подгоняем под мок.
export function mapToProductDescriptionHtml(node: WPProductNode): string | undefined {
  return node.description?.trim() || undefined;
}

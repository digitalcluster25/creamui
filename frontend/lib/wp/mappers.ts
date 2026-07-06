import type { CatalogProduct, CatalogData } from "@/lib/types/catalog";
import type { ProductPageData } from "@/lib/types/productPage";
import type { ProductSpecsData } from "@/lib/types/productSpecs";
import type { BlogPost } from "@/lib/types/blogPosts";
import type { CategoriesData } from "@/lib/types/categories";
import type { ProductsData } from "@/lib/types/products";
import type { WPCategoryChildNode, WPCategoryNode } from "@/lib/wp/header";

// Форма, которую реально отдаёт WooGraphQL (проверено на живом эндпоинте wpsandbox)
export type WPProductNode = {
  databaseId: number;
  name: string;
  slug: string;
  date?: string;
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
    brandSlug: node.productBrands?.nodes[0]?.slug,
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

export function mapToHomeProductsData(nodes: WPProductNode[]): ProductsData {
  const products = [...nodes]
    .sort((a, b) => new Date(b.date ?? 0).getTime() - new Date(a.date ?? 0).getTime())
    .slice(0, 4)
    .map((node) => ({
      id: node.databaseId,
      title: node.name,
      href: `/product/${node.slug}`,
      price: node.price ?? "",
      priceMin: parsePrice(node.price),
      priceMax: parsePrice(node.price),
      currency: getCurrencySymbol(node.price),
      categories: (node.productCategories?.nodes ?? []).map((category) => category.name),
      image1: node.image?.sourceUrl ?? "",
      image2: node.galleryImages?.nodes?.[0]?.sourceUrl,
      swatches: node.hwsFacingOptions?.map((option) => ({
        slug: option.slug,
        title: option.label,
        selected: option.isActive,
        bgImage: option.iconUrl ?? undefined,
      })),
    }));

  return {
    title: "Подобранная коллекция",
    allHref: "/catalog",
    products,
    bannerImage: "https://colabrio.ams3.cdn.digitaloceanspaces.com/ohio-stage-demo-19/oh__demo19__17.webp",
    bannerHref: "#",
  };
}

const HOME_CATEGORY_ORDER = [
  "russian-bath-stoves",
  "sauna-stoves",
  "hammam-stoves",
  "commercial-bath-stoves",
] as const;

export function mapToHomeCategoriesData(nodes: WPCategoryNode[]): CategoriesData {
  const children = nodes.flatMap((node) => node.children?.nodes ?? []);
  const bySlug = new Map(children.map((node) => [node.slug, node]));

  const items = HOME_CATEGORY_ORDER
    .map((slug) => bySlug.get(slug))
    .filter((node): node is WPCategoryChildNode => Boolean(node))
    .map((node) => ({
      id: String(node.databaseId),
      imageSrc: node.image?.sourceUrl ?? "",
      imageAlt: node.image?.altText?.trim() || node.name,
      href: `/catalog/${node.slug}`,
      subtitle: node.hwsSubtitle?.trim() ?? "",
      title: node.name,
      tags: [],
    }));

  return {
    sectionTitle: "Решения для любых задач",
    items,
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

// ---------------------------------------------------------------------------
// Статьи ("База знаний") — форма, которую отдаёт core WPGraphQL для постов.
// ---------------------------------------------------------------------------
export type WPPostNode = {
  databaseId: number;
  title: string;
  slug: string;
  date: string;
  excerpt?: string;
  content?: string;
  author?: { node: { name: string; avatar?: { url: string } | null } } | null;
  tags?: { nodes: { name: string; slug: string }[] } | null;
  categories?: { nodes: { name: string; slug: string }[] } | null;
  featuredImage?: { node: { sourceUrl: string; altText?: string } } | null;
};

function stripHtml(html?: string): string {
  if (!html) return "";
  return html
    .replace(/<[^>]*>/g, " ")
    .replace(/&hellip;/g, "…")
    .replace(/&#8217;/g, "’")
    .replace(/&#171;/g, "«")
    .replace(/&#187;/g, "»")
    .replace(/&amp;/g, "&")
    .replace(/\s+/g, " ")
    .trim();
}

// В WP нет готового поля "время чтения" — считаем по количеству слов
// в полном тексте (в excerpt слишком мало слов для честной оценки),
// ~200 слов/мин — стандартная оценка для среднего читателя.
function estimateReadTime(node: WPPostNode): string {
  const words = stripHtml(node.content || node.excerpt).split(" ").filter(Boolean).length;
  const minutes = Math.max(1, Math.round(words / 200));
  return `${minutes} мин чтения`;
}

function formatRuDate(iso: string): string {
  return new Date(iso).toLocaleDateString("ru-RU", {
    day: "numeric",
    month: "long",
    year: "numeric",
  });
}

export function mapToBlogPost(node: WPPostNode): BlogPost {
  return {
    id: node.databaseId,
    image: node.featuredImage?.node?.sourceUrl ?? "",
    title: node.title,
    href: `/knowledge/${node.slug}`,
    readTime: estimateReadTime(node),
    author: node.author?.node?.name ?? "HWS",
    excerpt: stripHtml(node.excerpt),
    tags: (node.tags?.nodes ?? []).map((t) => t.name),
    date: formatRuDate(node.date),
  };
}

export type ArticlePageData = {
  title: string;
  image?: string;
  author: string;
  authorAvatar?: string;
  date: string;
  readTime: string;
  category?: { name: string; href: string };
  tags: string[];
  contentHtml: string;
};

export function mapToArticlePageData(node: WPPostNode): ArticlePageData {
  const category = node.categories?.nodes?.[0];
  return {
    title: node.title,
    image: node.featuredImage?.node?.sourceUrl ?? undefined,
    author: node.author?.node?.name ?? "HWS",
    authorAvatar: node.author?.node?.avatar?.url ?? undefined,
    date: formatRuDate(node.date),
    readTime: estimateReadTime(node),
    category: category ? { name: category.name, href: `/knowledge` } : undefined,
    tags: (node.tags?.nodes ?? []).map((t) => t.name),
    contentHtml: node.content?.trim() ?? "",
  };
}

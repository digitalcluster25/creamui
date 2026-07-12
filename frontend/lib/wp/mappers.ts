import type { CatalogProduct, CatalogData } from "@/lib/types/catalog";
import type { ProductPageData } from "@/lib/types/productPage";
import type { ProductSpecsData } from "@/lib/types/productSpecs";
import type { BlogPost } from "@/lib/types/blogPosts";
import type { CategoriesData } from "@/lib/types/categories";
import type { ProductsData } from "@/lib/types/products";
import type { CurrencyCode } from "@/lib/currency/format";
import { htmlToPlainText } from "@/lib/content/plainText";
import type { WPCategoryNode } from "@/lib/wp/header";

// Форма, которую реально отдаёт WooGraphQL (проверено на живом эндпоинте wpsandbox)
export type WPProductNode = {
  databaseId: number;
  name: string;
  slug: string;
  date?: string;
  description?: string;
  shortDescription?: string;
  hwsPriceOnRequest?: boolean;
  price?: string | null;
  regularPrice?: string | null;
  salePrice?: string | null;
  sku?: string;
  image?: { sourceUrl: string; altText: string; hwsOptimizedUrl?: string | null } | null;
  galleryImages?: { nodes: { sourceUrl: string; altText: string; hwsOptimizedUrl?: string | null }[] };
  productCategories?: { nodes: { name: string; slug: string; parent?: { node: { name: string; slug: string } } | null }[] };
  productBrands?: { nodes: { name: string; slug: string }[] };
  hwsSpecs?: { label: string; value: string }[];
  hwsSpecGroups?: { title: string; rows: { label: string; value: string }[] }[];
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
    options: { value: string; slug?: string; priceModifier: number }[];
  }[];
  attributes?: { nodes: { name: string; options: string[] }[] };
  variations?: {
    nodes: {
      databaseId: number;
      name?: string;
      sku?: string;
      price?: string | null;
      image?: { sourceUrl: string; hwsOptimizedUrl?: string | null } | null;
      attributes?: { nodes: { name: string; value: string }[] };
    }[];
  };
};

function pickDisplayCategory(
  categories: NonNullable<WPProductNode["productCategories"]>["nodes"] | undefined
): string {
  if (!categories?.length) return "";
  const leaf = categories.find((category) => !category.parent?.node);
  const child = categories.find((category) => Boolean(category.parent?.node));
  return (child ?? leaf ?? categories[0])?.name ?? "";
}

// "$1,900" -> 1900.  "$6.563 - $8.174" -> 6563 (first value).
// WooGraphQL отдаёт price уже отформатированной строкой с символом валюты.
// Для VariableProduct это может быть диапазон "$min - $max" —
// берём первый элемент (min), для min/max отдельно используем parsePriceRange.
function parsePrice(price?: string | null): number {
  if (!price) return 0;
  // Диапазон "$6.563 - $8.174" → берём первую часть
  const part = price.includes(" - ") ? price.split(" - ")[0] : price;
  return parseSinglePrice(part);
}

function parseSinglePrice(raw: string): number {
  const cleaned = raw.replace(/[^\d.,]/g, "");
  const separators = [...cleaned].filter((char) => char === "." || char === ",");

  if (separators.length === 0) return Number.parseFloat(cleaned) || 0;

  if (separators.length === 1) {
    const separator = separators[0];
    const parts = cleaned.split(separator);
    // "6.563" — точка как разделитель тысяч (3 цифры после), не десятичная
    if (parts[1]?.length === 3) {
      return Number.parseFloat(parts.join("")) || 0;
    }
    return Number.parseFloat(parts.join(".")) || 0;
  }

  const normalized = cleaned.replace(/,/g, ".");
  const lastDotIndex = normalized.lastIndexOf(".");
  const integerPart = normalized.slice(0, lastDotIndex).replace(/\./g, "");
  const decimalPart = normalized.slice(lastDotIndex + 1);
  return Number.parseFloat(`${integerPart}.${decimalPart}`) || 0;
}

// "$6.563 - $8.174" -> { min: 6563, max: 8174 }
function parsePriceRange(price?: string | null): { min: number; max: number } {
  if (!price) return { min: 0, max: 0 };
  if (price.includes(" - ")) {
    const [lo, hi] = price.split(" - ");
    return { min: parseSinglePrice(lo), max: parseSinglePrice(hi) };
  }
  const single = parseSinglePrice(price);
  return { min: single, max: single };
}

function getCurrencySymbol(price?: string | null): string {
  if (!price) return "$";
  const match = price.match(/^[^\d]+/);
  return match ? match[0].trim() : "$";
}

function getCurrencyCode(price?: string | null): CurrencyCode {
  const symbol = getCurrencySymbol(price);
  if (symbol === "₽") return "RUB";
  if (symbol === "₼") return "AZN";
  return "USD";
}

export function mapToCatalogProduct(node: WPProductNode): CatalogProduct {
  const { min, max } = parsePriceRange(node.price);
  return {
    id: node.databaseId,
    href: `/product/${node.slug}`,
    image: resolveMediaUrl(node.image) ?? "",
    title: node.name,
    category: pickDisplayCategory(node.productCategories?.nodes),
    brand: node.productBrands?.nodes[0]?.name,
    brandSlug: node.productBrands?.nodes[0]?.slug,
    priceMin: min,
    priceMax: max,
    priceOnRequest: Boolean(node.hwsPriceOnRequest),
    baseCurrencyCode: getCurrencyCode(node.price),
    attributes: mapProductAttributes(node.attributes?.nodes),
    categorySlugs: node.productCategories?.nodes.map((c) => c.slug) ?? [],
  };
}

function mapProductAttributes(
  nodes: { name: string; options: string[] }[] | undefined
): Record<string, string[]> {
  const result: Record<string, string[]> = {};
  for (const attr of nodes ?? []) {
    if (!attr?.name) continue;
    const options = (attr.options ?? []).filter(Boolean);
    if (options.length > 0) result[attr.name] = options;
  }
  return result;
}

function resolveMediaUrl(
  media?: { sourceUrl?: string | null; hwsOptimizedUrl?: string | null } | null,
): string | undefined {
  return media?.hwsOptimizedUrl ?? media?.sourceUrl ?? undefined;
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
      priceOnRequest: Boolean(node.hwsPriceOnRequest),
      baseCurrencyCode: getCurrencyCode(node.price),
      categories: (node.productCategories?.nodes ?? []).map((category) => category.name),
      image1: resolveMediaUrl(node.image) ?? "",
      image2: resolveMediaUrl(node.galleryImages?.nodes?.[0]),
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
  "steam-generators-and-hammam",
  "commercial",
] as const;

export function mapToHomeCategoriesData(nodes: WPCategoryNode[]): CategoriesData {
  const bySlug = new Map(nodes.map((node) => [node.slug, node]));

  const resolveCategoryImageSrc = (node: WPCategoryNode): string => {
    if (node.hwsImageUrl) return node.hwsImageUrl;
    if (node.image?.sourceUrl) return node.image.sourceUrl;

    const childWithImage = (node.children?.nodes ?? []).find(
      (child) => Boolean(child.hwsImageUrl ?? child.image?.sourceUrl),
    );

    return childWithImage?.hwsImageUrl ?? childWithImage?.image?.sourceUrl ?? "";
  };

  const items = HOME_CATEGORY_ORDER
    .map((slug) => bySlug.get(slug))
    .filter((node): node is WPCategoryNode => Boolean(node))
    .map((node) => ({
      id: String(node.databaseId),
      imageSrc: resolveCategoryImageSrc(node),
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

const CATEGORY_FALLBACK_IMAGES: Record<string, string> = {
  "russian-bath-stoves": "/assets/sauna.png",
  "sauna-stoves": "/assets/sauna.png",
  "steam-generators-and-hammam": "/assets/steam.png",
  commercial: "/assets/heater.png",
  "control-units": "/assets/heater.png",
  "chimneys-and-installation": "/assets/heater.png",
  "water-tanks-and-heat-exchangers": "/assets/heater.png",
  "stones-and-cladding": "/assets/sauna.png",
  accessories: "/assets/acs.png",
};

type CategoryLikeNode = {
  databaseId: number;
  name: string;
  slug: string;
  hwsSubtitle?: string | null;
  hwsImageUrl?: string | null;
  image?: { sourceUrl: string; altText?: string | null } | null;
  count: number | null;
  children?: {
    nodes: {
      hwsImageUrl?: string | null;
      image?: { sourceUrl: string; altText?: string | null } | null;
    }[];
  };
};

export function mapToCategoryCardsData(
  nodes: CategoryLikeNode[],
  sectionTitle: string,
): CategoriesData {
  const resolveCategoryImageSrc = (node: CategoryLikeNode): string => {
    if (node.hwsImageUrl) return node.hwsImageUrl;
    if (node.image?.sourceUrl) return node.image.sourceUrl;

    const childWithImage = (node.children?.nodes ?? []).find(
      (child) => Boolean(child.hwsImageUrl ?? child.image?.sourceUrl),
    );

    return childWithImage?.hwsImageUrl ?? childWithImage?.image?.sourceUrl ?? "";
  };

  return {
    sectionTitle,
    items: nodes.map((node) => ({
      id: String(node.databaseId),
      imageSrc: resolveCategoryImageSrc(node),
      imageAlt: node.image?.altText?.trim() || node.name,
      href: `/catalog/${node.slug}`,
      subtitle: node.hwsSubtitle?.trim() || (typeof node.count === "number" ? `${node.count} товаров` : ""),
      title: node.name,
      tags:
        typeof node.count === "number" && node.count > 0
          ? [{ id: `${node.slug}-count`, label: `${node.count} товаров`, href: `/catalog/${node.slug}` }]
          : [],
    })),
  };
}

// Нормализация строки в slug-формат для сопоставления:
// "Серпентинит Бархат" → "серпентинит-бархат"
// "10 кВт" → "10-квт"
function toSlug(s: string): string {
  return s.toLowerCase().replace(/\s+/g, "-").replace(/ё/g, "е");
}

// Строим variantEntries из нативных WooCommerce variations.
// Slug-значения атрибутов вариаций ("10-квт") маппим обратно к
// человекочитаемым лейблам из hwsVariantGroups ("10 кВт").
function buildVariantEntries(
  node: WPProductNode,
): ProductPageData["variantEntries"] {
  const variations = node.variations?.nodes;
  if (!variations?.length) return undefined;

  const groups = node.hwsVariantGroups ?? [];
  if (groups.length === 0) return undefined;

  // Строим slug→label карту из hwsVariantGroups.options[].slug
  // Если slug доступен — прямой маппинг. Если нет — fallback на нормализацию лейбла.
  const keyByAttr = new Map<string, string>(); // "pa_power" → "power"
  const slugToLabel = new Map<string, Map<string, string>>(); // key → (slug → label)

  for (const g of groups) {
    const labelMap = new Map<string, string>();
    for (const o of g.options) {
      if (o.slug) {
        // Прямой маппинг: slug терма → лейбл (надёжный)
        labelMap.set(toSlug(o.slug), o.value);
      } else {
        // Fallback: нормализация лейбла (для EasySteam без slug-ов)
        labelMap.set(toSlug(o.value), o.value);
      }
    }
    slugToLabel.set(g.key, labelMap);
  }

  // Определяем маппинг pa_атрибут → key через совпадение slug-значений
  if (variations[0]?.attributes?.nodes) {
    for (const attr of variations[0].attributes.nodes) {
      const decodedAttrName = decodeURIComponent(attr.name);
      // Попробуем: pa_power → power, pa_facing → facing
      const stripped = decodedAttrName.replace(/^pa_/, "");
      if (slugToLabel.has(stripped)) {
        keyByAttr.set(decodedAttrName, stripped);
        continue;
      }
      // Или атрибут может совпадать с key напрямую (не pa_)
      if (slugToLabel.has(decodedAttrName)) {
        keyByAttr.set(decodedAttrName, decodedAttrName);
        continue;
      }
      // Полный перебор: ищем group, у которого slug-значения пересекаются
      const decodedVal = toSlug(decodeURIComponent(attr.value));
      for (const g of groups) {
        const labelMap = slugToLabel.get(g.key)!;
        if (labelMap.has(decodedVal) && !keyByAttr.has(decodedAttrName)) {
          keyByAttr.set(decodedAttrName, g.key);
        }
      }
    }
  }

  // Если не удалось замаппить хотя бы один атрибут — не строим entries
  if (keyByAttr.size === 0) return undefined;

  // Проверяем полноту покрытия: entries имеют смысл только если вариации
  // покрывают всю матрицу (VVD: 6×4=24 вариаций = 24 variations ✓).
  // Если покрытие частичное (EasySteam: 10 из сотен) — не используем,
  // иначе цена будет прыгать между exact и additive при переключении.
  const expectedCount = groups.reduce((acc, g) => acc * g.options.length, 1);
  if (variations.length < expectedCount) return undefined;

  const entries: NonNullable<ProductPageData["variantEntries"]> = [];

  for (const v of variations) {
    const selection: Record<string, string> = {};
    let valid = true;

    for (const attr of v.attributes?.nodes ?? []) {
      const decodedName = decodeURIComponent(attr.name);
      const groupKey = keyByAttr.get(decodedName);
      if (!groupKey) { valid = false; break; }

      const decodedVal = toSlug(decodeURIComponent(attr.value));
      const labelMap = slugToLabel.get(groupKey);
      const label = labelMap?.get(decodedVal);
      if (!label) { valid = false; break; }

      selection[groupKey] = label;
    }

    if (!valid || Object.keys(selection).length === 0) continue;

    entries.push({
      selection,
      price: parsePrice(v.price),
      sku: v.sku,
      image: resolveMediaUrl(v.image),
    });
  }

  return entries.length > 0 ? entries : undefined;
}

export function mapToProductPageData(node: WPProductNode): ProductPageData {
  const price = parsePrice(node.price);
  const priceOld = node.salePrice ? parsePrice(node.regularPrice) : undefined;
  const rawCategories = node.productCategories?.nodes ?? [];
  const categories = rawCategories.map((c) => ({
    label: c.name,
    href: `/catalog/${c.slug}`,
    parentSlug: c.parent?.node?.slug ?? null,
  }));

  // Для хлебных крошек: находим самую глубокую категорию (у которой есть parent)
  // и строим цепочку parent → child
  const childCat = categories.find((c) => c.parentSlug !== null);
  const parentCat = childCat
    ? categories.find((c) => c.href === `/catalog/${childCat.parentSlug}`) ?? null
    : null;

  const breadcrumbCategories = parentCat && childCat
    ? [parentCat, childCat]
    : categories.length > 0
      ? [categories[0]]
      : [];

  const variantEntries = buildVariantEntries(node);

  return {
    images: [
      resolveMediaUrl(node.image),
      ...(node.galleryImages?.nodes.map((g) => resolveMediaUrl(g)) ?? []),
    ].filter(Boolean) as string[],
    badges: node.salePrice ? [{ label: "Sale", variant: "sale" as const }] : [],
    breadcrumbs: [
      { label: "Главная", href: "/" },
      { label: "Каталог", href: "/catalog" },
      ...breadcrumbCategories.map((c) => ({ label: c.label, href: c.href })),
      { label: node.name },
    ],
    title: node.name,
    categories: categories.map((c) => ({ label: c.label, href: c.href })),
    priceOld,
    price,
    priceOnRequest: Boolean(node.hwsPriceOnRequest),
    baseCurrencyCode: getCurrencyCode(node.price),
    sku: node.sku,
    tag: undefined, // нет надёжного источника — productTags на бэке содержат демо-теги WooCommerce, не реальные
    brand: node.productBrands?.nodes[0]?.name,
    brandHref: node.productBrands?.nodes[0]?.slug ? `/brands/${node.productBrands.nodes[0].slug}` : undefined,
    description: htmlToPlainText(node.shortDescription),
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
      type: "text" as const,
      options: g.options.map((o) => ({ value: o.value, priceModifier: o.priceModifier })),
    })),
    variantEntries,
  };
}

// Характеристики товара: приоритет hwsSpecGroups (с группировкой),
// fallback на hwsSpecs (плоский список → одна группа).
export function mapToProductSpecsData(node: WPProductNode): ProductSpecsData {
  // Новый формат с группами (плагин hws-specs-groups + graphql bridge)
  if (node.hwsSpecGroups?.length) {
    return {
      sectionTitle: "Характеристики",
      groups: node.hwsSpecGroups.map((g) => ({
        title: g.title,
        rows: g.rows,
      })),
    };
  }

  // Fallback: старый плоский hwsSpecs
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
  const html = node.description?.trim();
  return html || undefined;
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

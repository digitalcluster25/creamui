import type { Metadata } from "next";
import { notFound } from "next/navigation";
import { Header } from "@/components/sections/header";
import { Catalog } from "@/components/sections/catalog";
import { CatalogOverview } from "@/components/sections/catalog-overview/CatalogOverview";
import { CatalogSeo, type CatalogSeoData } from "@/components/sections/catalog-seo/CatalogSeo";
import { Footer } from "@/components/sections/footer";
import { Breadcrumbs } from "@/components/primitives/breadcrumbs/Breadcrumbs";
import { footerData } from "@/lib/data/footer";
import { flattenCategories, getHeaderData, type WPCategoryNode, type WPCategoryChildNode } from "@/lib/wp/header";
import { getClient } from "@/lib/wp/apollo";
import { GET_PRODUCTS, GET_PRODUCT_BRANDS, GET_PRODUCT_CATEGORIES, GET_ATTRIBUTE_TERMS } from "@/lib/wp/queries";
import { filtersForBranch, attributeParamKey } from "@/lib/data/catalogFilters";
import { mapToCatalogData, type WPProductNode } from "@/lib/wp/mappers";
import type { AttributeTermLabels } from "@/lib/types/catalog";
import type { CategoriesData } from "@/lib/types/categories";
import styles from "../../page.module.css";

export const revalidate = 3600;

type Params = { slug: string };

type BrandNode = {
  name: string;
  slug: string;
  logoUrl?: string | null;
};

type FlatCategoryNode = WPCategoryNode | WPCategoryChildNode;

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

const ATTRIBUTE_TERM_FIELDS: Record<string, string> = {
  allPaFuelType: "pa_fuel-type",
  allPaEquipmentType: "pa_equipment-type",
  allPaSteamRoomVolume: "pa_steam-room-volume",
  allPaPower: "pa_power",
  allPaVoltage: "pa_voltage",
  allPaCladdingMaterial: "pa_cladding-material",
  allPaUsageClass: "pa_usage-class",
  allPaRoomType: "pa_room-type",
  allPaSeries: "pa_series",
};

async function getAttributeTermLabels(
  client: ReturnType<typeof getClient>
): Promise<AttributeTermLabels> {
  const labels: AttributeTermLabels = {};
  try {
    const { data } = await client.query<Record<string, { nodes: { name: string; slug: string }[] }>>({
      query: GET_ATTRIBUTE_TERMS,
    });
    for (const [field, taxonomy] of Object.entries(ATTRIBUTE_TERM_FIELDS)) {
      const nodes = data?.[field]?.nodes ?? [];
      labels[taxonomy] = Object.fromEntries(nodes.map((n) => [n.slug, n.name]));
    }
  } catch (e) {
    console.error("WP GraphQL error (brand attribute terms):", e);
  }
  return labels;
}

function pickProductCategorySlugs(product: WPProductNode): string[] {
  const categories = product.productCategories?.nodes ?? [];
  const childSlugs = categories
    .filter((category) => Boolean(category.parent?.node?.slug))
    .map((category) => category.slug);

  if (childSlugs.length > 0) return childSlugs;
  return categories.map((category) => category.slug);
}

function buildBrandLead(brandName: string, productsCount: number, categoryNames: string[]): string {
  const names = categoryNames.slice(0, 3);
  const catalogFocus = names.length > 0 ? names.join(", ") : "основных направлениях каталога";

  return `${brandName} представлен в ${productsCount} товарах HWS. На этой странице собраны все позиции бренда, а ниже можно быстро перейти к ключевым направлениям: ${catalogFocus}.`;
}

function buildBrandSeo(brandName: string, categoryNames: string[]): CatalogSeoData {
  const names = categoryNames.slice(0, 4);
  const focus = names.length > 0 ? names.join(", ") : "релевантным разделам каталога";

  return {
    eyebrow: "Брендовая посадочная страница",
    title: `${brandName} в каталоге HWS`,
    paragraphs: [
      `Страница бренда ${brandName} собирает ассортимент в одном месте и убирает необходимость начинать поиск с фильтра по бренду внутри общего каталога.`,
      `Для UX это короткий путь к нужным товарам, а для SEO это отдельная индексируемая посадочная страница с понятной тематикой и выходами в ${focus}.`,
    ],
    bullets: [
      "Все товары бренда собраны на одной странице.",
      "Переходы в категории идут из брендовой посадочной страницы, а не только через query-фильтры.",
      "Дальше пользователь уточняет выбор branch-aware фильтрами внутри каталога.",
    ],
  };
}

function collectBrandFilterKeys(products: WPProductNode[]): string[] {
  const keys = new Set<string>();

  for (const product of products) {
    const branchSlugs = new Set(
      (product.productCategories?.nodes ?? [])
        .map((category) => category.parent?.node?.slug ?? category.slug)
        .filter(Boolean),
    );

    for (const branchSlug of branchSlugs) {
      for (const key of filtersForBranch(branchSlug)) {
        keys.add(key);
      }
    }
  }

  return Array.from(keys);
}

function buildBrandCategoryCards(
  products: WPProductNode[],
  allCategories: FlatCategoryNode[],
): CategoriesData | null {
  const counts = new Map<string, number>();

  for (const product of products) {
    for (const slug of pickProductCategorySlugs(product)) {
      counts.set(slug, (counts.get(slug) ?? 0) + 1);
    }
  }

  const categoryMap = new Map(allCategories.map((category) => [category.slug, category]));
  const items = Array.from(counts.entries())
    .map(([slug, count]) => {
      const category = categoryMap.get(slug);
      if (!category) return null;

      return {
        id: String(category.databaseId),
        imageSrc: category.image?.sourceUrl ?? CATEGORY_FALLBACK_IMAGES[slug] ?? "/assets/sauna.png",
        imageAlt: category.image?.altText?.trim() || category.name,
        href: `/catalog/${slug}`,
        subtitle: `${count} товаров ${count === 1 ? "бренда" : "бренда"}`,
        title: category.name,
        tags: [{ id: `${slug}-brand-count`, label: `${count} товаров`, href: `/catalog/${slug}` }],
        count,
      };
    })
    .filter((item): item is NonNullable<typeof item> => Boolean(item))
    .sort((a, b) => b.count - a.count || a.title.localeCompare(b.title, "ru"))
    .slice(0, 6)
    .map(({ count: _count, ...item }) => item);

  if (items.length === 0) return null;

  return {
    sectionTitle: "Ключевые разделы бренда",
    items,
  };
}

export async function generateStaticParams(): Promise<Params[]> {
  try {
    const client = getClient();
    const { data } = await client.query<{ productBrands: { nodes: BrandNode[] } }>({
      query: GET_PRODUCT_BRANDS,
    });

    return (data?.productBrands?.nodes ?? []).map((brand) => ({ slug: brand.slug }));
  } catch (e) {
    console.error("WP GraphQL error (generateStaticParams brands):", e);
    return [];
  }
}

export async function generateMetadata({
  params,
}: {
  params: Promise<Params>;
}): Promise<Metadata> {
  const { slug } = await params;

  try {
    const client = getClient();
    const { data } = await client.query<{ productBrands: { nodes: BrandNode[] } }>({
      query: GET_PRODUCT_BRANDS,
    });

    const brand = (data?.productBrands?.nodes ?? []).find((entry) => entry.slug === slug);
    if (!brand) {
      return {
        title: "Бренды HWS",
        description: "Брендовые страницы каталога HWS.",
      };
    }

    return {
      title: `${brand.name} | Бренд в каталоге HWS`,
      description: `Все товары бренда ${brand.name} в каталоге HWS: быстрый переход в релевантные категории и подбор по branch-aware фильтрам.`,
      alternates: {
        canonical: `/brands/${brand.slug}`,
      },
    };
  } catch (e) {
    console.error("WP GraphQL error (brand metadata):", e);
    return {
      title: "Бренды HWS",
      description: "Брендовые страницы каталога HWS.",
    };
  }
}

export default async function BrandPage({
  params,
  searchParams,
}: {
  params: Promise<Params>;
  searchParams?: Promise<Record<string, string | string[] | undefined>>;
}) {
  const { slug } = await params;
  const resolvedSearchParams = (await searchParams) ?? {};

  const client = getClient();
  const [{ data: brandsData }, { data: productsData }, { data: categoriesData }] = await Promise.all([
    client.query<{ productBrands: { nodes: BrandNode[] } }>({
      query: GET_PRODUCT_BRANDS,
    }),
    client.query<{ products: { nodes: WPProductNode[] } }>({
      query: GET_PRODUCTS,
      variables: { first: 1000 },
    }),
    client.query<{ productCategories: { nodes: WPCategoryNode[] } }>({
      query: GET_PRODUCT_CATEGORIES,
    }),
  ]);

  const brand = (brandsData?.productBrands?.nodes ?? []).find((entry) => entry.slug === slug);
  if (!brand) notFound();

  const brandProducts = (productsData?.products?.nodes ?? []).filter(
    (product) => product.productBrands?.nodes?.some((entry) => entry.slug === slug),
  );
  if (brandProducts.length === 0) notFound();

  const categoryTree = categoriesData?.productCategories?.nodes ?? [];
  const flatCategories = flattenCategories(categoryTree);
  const overviewCategories = buildBrandCategoryCards(brandProducts, flatCategories);
  const uniqueCategoryNames = overviewCategories?.items.map((item) => item.title) ?? [];

  const filterKeys = collectBrandFilterKeys(brandProducts);
  const initialFilters: Record<string, string> = {};
  for (const key of filterKeys) {
    const value = resolvedSearchParams[attributeParamKey(key)];
    if (typeof value === "string" && value) initialFilters[key] = value;
  }

  const catalogData = mapToCatalogData(brandProducts, undefined);
  const termLabels = await getAttributeTermLabels(client);
  const headerData = await getHeaderData();
  const lead = buildBrandLead(brand.name, brandProducts.length, uniqueCategoryNames);
  const seoData = buildBrandSeo(brand.name, uniqueCategoryNames);

  return (
    <main>
      <Header data={headerData} hideBurgerOnDesktop hideActionsOnDesktop />
      <Breadcrumbs
        items={[
          { label: "Главная", href: "/" },
          { label: "Бренды", href: "/brands" },
          { label: brand.name },
        ]}
      />
      <div className={styles.section}>
        <CatalogOverview title={brand.name} lead={lead} categories={overviewCategories} />
        <Catalog data={catalogData} filterKeys={filterKeys} termLabels={termLabels} initialFilters={initialFilters} />
        <CatalogSeo data={seoData} />
      </div>
      <div className={styles.sectionFooter}>
        <Footer data={footerData} />
      </div>
    </main>
  );
}

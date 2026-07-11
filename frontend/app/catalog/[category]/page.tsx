import type { Metadata } from "next";
import { notFound } from "next/navigation";
import { Header } from "@/components/sections/header";
import { Catalog } from "@/components/sections/catalog";
import { CatalogOverview } from "@/components/sections/catalog-overview/CatalogOverview";
import { CatalogSeo } from "@/components/sections/catalog-seo/CatalogSeo";
import { Categories } from "@/components/sections/categories";
import { Footer } from "@/components/sections/footer";
import { Breadcrumbs } from "@/components/primitives/breadcrumbs/Breadcrumbs";
import { getHeaderData, flattenCategories, type WPCategoryNode } from "@/lib/wp/header";
import { footerData } from "@/lib/data/footer";
import { getClient } from "@/lib/wp/apollo";
import { GET_PRODUCT_BRANDS, GET_PRODUCT_CATEGORIES, GET_PRODUCT_CATEGORY_BY_SLUG, GET_ATTRIBUTE_TERMS } from "@/lib/wp/queries";
import { mapToCatalogData, mapToCategoryCardsData, type WPProductNode } from "@/lib/wp/mappers";
import { filtersForBranch, attributeParamKey } from "@/lib/data/catalogFilters";
import { CATALOG_BRANCH_INTROS, buildCatalogCategoryContent } from "@/lib/data/catalogBranches";
import { buildCatalogRobots } from "@/lib/seo/catalog";
import type { AttributeTermLabels } from "@/lib/types/catalog";
import type { CategoriesData } from "@/lib/types/categories";
import { fetchProductsByCategory } from "@/lib/wp/products";
import styles from "../page.module.css";

// Корневое поле WPGraphQL (allPa*) -> имя таксономии (pa_*).
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
    console.error("WP GraphQL error (attribute terms):", e);
  }
  return labels;
}

export const revalidate = 3600;

type Params = { category: string };
type BrandNode = { name: string; slug: string; logoUrl?: string | null };

const BRAND_FALLBACK_IMAGE = "/assets/hws-dark-logo-short.png";

export async function generateMetadata({
  params,
  searchParams,
}: {
  params: Promise<Params>;
  searchParams?: Promise<Record<string, string | string[] | undefined>>;
}): Promise<Metadata> {
  const { category } = await params;
  const resolvedSearchParams = (await searchParams) ?? {};
  const branchIntro = CATALOG_BRANCH_INTROS[category];

  if (branchIntro) {
    return {
      title: `${branchIntro.title} | Каталог HWS`,
      description: branchIntro.lead,
      alternates: {
        canonical: `/catalog/${category}`,
      },
      robots: buildCatalogRobots(resolvedSearchParams),
    };
  }

  try {
    const client = getClient();
    const { data } = await client.query<{
      productCategory: { name: string; slug: string; count?: number | null; parent?: { node: { name: string; slug: string } } | null } | null;
    }>({
      query: GET_PRODUCT_CATEGORY_BY_SLUG,
      variables: { slug: category },
    });
    const found = data?.productCategory;
    if (!found?.name) {
      return {
        title: "Каталог HWS",
        description: "Каталог печей, парогенераторов, автоматики и инженерных решений HWS.",
      };
    }

    const parentName = found.parent?.node?.name;
    const parentSlug = found.parent?.node?.slug;
    const metadataContent = parentName && parentSlug
      ? buildCatalogCategoryContent({
          categoryName: found.name,
          categorySlug: found.slug,
          categoryCount: found.count,
          branchName: parentName,
          branchSlug: parentSlug,
        })
      : null;

    return {
      title: `${found.name}${parentName ? ` | ${parentName}` : ""} | Каталог HWS`,
      description: metadataContent?.lead ?? (parentName
        ? `Раздел «${found.name}» внутри ветки «${parentName}» в каталоге HWS. Подбор по брендам, сериям и параметрам текущей категории.`
        : `Раздел «${found.name}» в каталоге HWS.`),
      alternates: {
        canonical: `/catalog/${found.slug}`,
      },
      robots: buildCatalogRobots(resolvedSearchParams),
    };
  } catch (e) {
    console.error("WP GraphQL error (category metadata):", e);
    return {
      title: "Каталог HWS",
      description: "Каталог печей, парогенераторов, автоматики и инженерных решений HWS.",
    };
  }
}

// Категории — дерево (parent + children), а реальные разделы каталога живут
// на уровне детей ("russian-bath-stoves" и т.п.). Раньше тут искали совпадение
// только среди верхнего уровня, поэтому все подкатегории отдавали 404 —
// разворачиваем дерево в плоский список перед поиском/генерацией путей.
export async function generateStaticParams(): Promise<Params[]> {
  try {
    const client = getClient();
    const { data } = await client.query<{
      productCategories: { nodes: WPCategoryNode[] };
    }>({ query: GET_PRODUCT_CATEGORIES });
    const all = flattenCategories(data?.productCategories?.nodes ?? []);
    return all.map((c) => ({ category: c.slug }));
  } catch (e) {
    console.error("WP GraphQL error (generateStaticParams category):", e);
    return [];
  }
}

export default async function CatalogCategoryPage({
  params,
  searchParams,
}: {
  params: Promise<Params>;
  searchParams?: Promise<Record<string, string | string[] | undefined>>;
}) {
  const { category } = await params;
  const resolvedSearchParams = (await searchParams) ?? {};
  const initialBrandSlug = typeof resolvedSearchParams.brand === "string" ? resolvedSearchParams.brand : "";

  const client = getClient();
  const [{ data: categoryTreeData }, { data: categoryData }, { data: brandsData }] = await Promise.all([
    client.query<{
      productCategories: { nodes: WPCategoryNode[] };
    }>({
      query: GET_PRODUCT_CATEGORIES,
    }),
    client.query<{
      productCategory: { name: string; slug: string; count?: number | null; parent?: { node: { name: string; slug: string } } | null } | null;
    }>({
      query: GET_PRODUCT_CATEGORY_BY_SLUG,
      variables: { slug: category },
    }),
    client.query<{ productBrands: { nodes: BrandNode[] } }>({
      query: GET_PRODUCT_BRANDS,
    }),
  ]);

  const found = categoryData?.productCategory;
  if (!found?.slug) {
    notFound();
  }

  const topCategories = categoryTreeData?.productCategories?.nodes ?? [];
  const currentParentNode =
    topCategories.find((node) => node.slug === found.slug) ??
    topCategories.find((node) => node.slug === found.parent?.node?.slug) ??
    null;
  const currentChildNodes =
    currentParentNode?.children?.nodes?.map((node) => ({
      ...node,
      hwsSubtitle: node.hwsSubtitle || currentParentNode.hwsSubtitle || null,
    })) ?? [];

  let catalogData;
  let productsNodes: WPProductNode[] = [];
  try {
    productsNodes = await fetchProductsByCategory(client, category);
    catalogData = mapToCatalogData(productsNodes, undefined);
  } catch (e) {
    console.error("WP GraphQL error (catalog category):", e);
    catalogData = mapToCatalogData([], undefined);
  }

  // Ветка = верхний раздел. Для подкатегории берём родителя, иначе саму себя.
  const branchSlug = found.parent?.node?.slug ?? found.slug;
  const filterKeys = filtersForBranch(branchSlug);
  const branchIntro = CATALOG_BRANCH_INTROS[branchSlug];
  const categoryContent =
    found.slug === branchSlug
      ? null
      : buildCatalogCategoryContent({
          categoryName: found.name,
          categorySlug: found.slug,
          categoryCount: found.count,
          branchName: currentParentNode?.name ?? found.parent?.node?.name ?? found.name,
          branchSlug,
        });
  const initialFilters: Record<string, string> = {};
  for (const key of filterKeys) {
    const value = resolvedSearchParams[attributeParamKey(key)];
    if (typeof value === "string" && value) initialFilters[key] = value;
  }

  const termLabels = await getAttributeTermLabels(client);
  const headerData = await getHeaderData();
  const brandCards = buildCategoryBrandCards(productsNodes, brandsData?.productBrands?.nodes ?? []);

  return (
    <main>
      <Header data={headerData} hideBurgerOnDesktop hideActionsOnDesktop />
      <Breadcrumbs
        items={[
          { label: "Главная", href: "/" },
          { label: "Каталог", href: "/catalog" },
          ...(found.parent?.node ? [{ label: found.parent.node.name, href: `/catalog/${found.parent.node.slug}` }] : []),
          { label: found.name },
        ]}
      />
      <div className={styles.section}>
        <CatalogOverview
          title={found.name}
          lead={found.slug === branchSlug ? branchIntro?.lead : (categoryContent?.lead ?? `Раздел внутри ветки «${currentParentNode?.name ?? branchIntro?.title ?? found.name}». Сначала выберите подходящий подтип, затем сузьте выдачу фильтрами только этой ветки.`)}
          categories={
            currentChildNodes.length > 0
              ? mapToCategoryCardsData(
                  currentChildNodes,
                  found.slug === branchSlug ? "Подразделы" : "Соседние подразделы",
                )
              : null
          }
        />
        {brandCards ? <Categories data={brandCards} /> : null}
        <Catalog
          data={catalogData}
          initialBrandSlug={initialBrandSlug}
          filterKeys={filterKeys}
          termLabels={termLabels}
          initialFilters={initialFilters}
        />
        <CatalogSeo data={found.slug === branchSlug ? branchIntro?.seo ?? null : categoryContent?.seo ?? null} />
      </div>
      <div className={styles.sectionFooter}>
        <Footer data={footerData} />
      </div>
    </main>
  );
}

function buildCategoryBrandCards(products: WPProductNode[], allBrands: BrandNode[]): CategoriesData | null {
  const brandCounts = new Map<string, { name: string; count: number }>();

  for (const product of products) {
    for (const brand of product.productBrands?.nodes ?? []) {
      if (!brand.slug || !brand.name) continue;
      const current = brandCounts.get(brand.slug);
      brandCounts.set(brand.slug, {
        name: brand.name,
        count: (current?.count ?? 0) + 1,
      });
    }
  }

  if (brandCounts.size < 2) {
    return null;
  }

  const brandMeta = new Map(allBrands.map((brand) => [brand.slug, brand]));
  const items = Array.from(brandCounts.entries())
    .map(([slug, data]) => {
      const meta = brandMeta.get(slug);
      return {
        id: `brand-${slug}`,
        imageSrc: meta?.logoUrl || BRAND_FALLBACK_IMAGE,
        imageAlt: data.name,
        href: `/brands/${slug}`,
        subtitle: `${data.count} товаров`,
        title: data.name,
        tags: [{ id: `brand-${slug}-link`, label: "Страница бренда", href: `/brands/${slug}` }],
        count: data.count,
      };
    })
    .sort((a, b) => b.count - a.count || a.title.localeCompare(b.title, "ru"))
    .slice(0, 6)
    .map(({ count: _count, ...item }) => item);

  return items.length > 1
    ? {
        sectionTitle: "Бренды в разделе",
        items,
      }
    : null;
}

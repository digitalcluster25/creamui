import type { Metadata } from "next";
import { notFound } from "next/navigation";
import { Header } from "@/components/sections/header";
import { CatalogPreview } from "@/components/sections/catalog-preview";
import { CatalogOverview } from "@/components/sections/catalog-overview/CatalogOverview";
import { CatalogSeo } from "@/components/sections/catalog-seo/CatalogSeo";
import { Footer } from "@/components/sections/footer";
import { Breadcrumbs } from "@/components/primitives/breadcrumbs/Breadcrumbs";
import { getHeaderData, flattenCategories, type WPCategoryNode } from "@/lib/wp/header";
import { getFooterData } from "@/lib/wp/footer";
import { getClient } from "@/lib/wp/apollo";
import { mapToCatalogProduct, type WPProductNode } from "@/lib/wp/mappers";
import { CATALOG_BRANCH_INTROS, buildCatalogCategoryContent } from "@/lib/data/catalogBranches";
import { ATTRIBUTE_LABELS } from "@/lib/data/catalogFilters";
import { GET_ATTRIBUTE_TERMS } from "@/lib/wp/queries";
import { fetchProductsByCategory } from "@/lib/wp/products";
import { getProductBrands, getProductCategoriesTree, getProductCategoryBySlug, type WPBrandNode } from "@/lib/wp/catalog-taxonomy";
import styles from "../page.module.css";

export const revalidate = 3600;

type Params = { category: string };

const BRAND_FALLBACK_IMAGE = "/assets/hws-dark-logo-short.png";

export async function generateMetadata({
  params,
}: {
  params: Promise<Params>;
}): Promise<Metadata> {
  const { category } = await params;
  const branchIntro = CATALOG_BRANCH_INTROS[category];

  if (branchIntro) {
    return {
      title: `${branchIntro.title} | Каталог HWS`,
      description: branchIntro.lead,
      alternates: {
        canonical: `/catalog/${category}`,
      },
    };
  }

  try {
    const found = await getProductCategoryBySlug(category);
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
    const all = flattenCategories(await getProductCategoriesTree());
    return all.map((c) => ({ category: c.slug }));
  } catch (e) {
    console.error("WP GraphQL error (generateStaticParams category):", e);
    return [];
  }
}

export default async function CatalogCategoryPage({
  params,
}: {
  params: Promise<Params>;
}) {
  const footerData = await getFooterData();
  const { category } = await params;

  const client = getClient();
  const [topCategories, found, brands, attrTermsResult] = await Promise.all([
    getProductCategoriesTree(),
    getProductCategoryBySlug(category),
    getProductBrands(),
    client.query<Record<string, { nodes: { name: string; slug: string }[] }>>({ query: GET_ATTRIBUTE_TERMS }).catch(() => null),
  ]);
  if (!found?.slug) {
    notFound();
  }

  const currentParentNode =
    topCategories.find((node) => node.slug === found.slug) ??
    topCategories.find((node) => node.slug === found.parent?.node?.slug) ??
    null;
  const currentChildNodes =
    currentParentNode?.children?.nodes?.map((node) => ({
      ...node,
      hwsSubtitle: node.hwsSubtitle || currentParentNode.hwsSubtitle || null,
    })) ?? [];

  let productsNodes: WPProductNode[] = [];
  try {
    productsNodes = await fetchProductsByCategory(client, category);
  } catch (e) {
    console.error("WP GraphQL error (catalog category):", e);
  }

  // Ветка = верхний раздел. Для подкатегории берём родителя, иначе саму себя.
  const branchSlug = found.parent?.node?.slug ?? found.slug;
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

  const headerData = await getHeaderData();
  const previewProducts = productsNodes.map(mapToCatalogProduct);
  const brandFilters = buildBrandFilters(productsNodes, brands);
  const categoryFilters =
    found.slug === branchSlug && currentChildNodes.length > 1
      ? currentChildNodes.map((n) => ({ slug: n.slug, name: n.name, type: "category" as const }))
      : [];
  const allFilters = [...categoryFilters, ...brandFilters];
  const attrTermMap = buildAttrTermMap(attrTermsResult?.data ?? null);
  const attributeFilters = buildAttributeFilters(productsNodes, found.hwsCatalogFilters ?? [], attrTermMap);

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
          lead={found.slug === branchSlug ? (branchIntro?.lead ?? undefined) : (categoryContent?.lead ?? undefined)}
          categories={null}
        />
        <CatalogPreview
          total={productsNodes.length}
          products={previewProducts}
          filters={allFilters.length > 1 ? allFilters : undefined}
          subcategoryLabel={found.hwsFilterSubcatLabel}
          brandLabel={found.hwsFilterBrandLabel}
          attributeFilters={attributeFilters.length > 0 ? attributeFilters : undefined}
        />
        <CatalogSeo data={found.slug === branchSlug ? branchIntro?.seo ?? null : categoryContent?.seo ?? null} />
      </div>
      <div className={styles.sectionFooter}>
        <Footer data={footerData} />
      </div>
    </main>
  );
}


// Maps WooGraphQL allPa* field names to pa_* taxonomy slugs
const GQL_FIELD_TO_TAX: Record<string, string> = {
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

function buildAttrTermMap(
  data: Record<string, { nodes: { name: string; slug: string }[] }> | null,
): Record<string, Record<string, string>> {
  const map: Record<string, Record<string, string>> = {};
  if (!data) return map;
  for (const [field, tax] of Object.entries(GQL_FIELD_TO_TAX)) {
    const nodes = data[field]?.nodes ?? [];
    if (nodes.length) {
      map[tax] = Object.fromEntries(nodes.map((n) => [n.slug, n.name]));
    }
  }
  return map;
}

function buildAttributeFilters(
  products: WPProductNode[],
  filterConfigs: { slug: string; type: string }[],
  termMap: Record<string, Record<string, string>>,
): { slug: string; type: string; label: string; options: { value: string; name: string }[] }[] {
  if (!filterConfigs.length) return [];
  const result: { slug: string; type: string; label: string; options: { value: string; name: string }[] }[] = [];
  for (const { slug, type } of filterConfigs) {
    const seen = new Map<string, string>();
    const terms = termMap[slug] ?? {};
    for (const p of products) {
      for (const attr of p.attributes?.nodes ?? []) {
        if (attr.name === slug) {
          for (const opt of attr.options ?? []) {
            if (!seen.has(opt)) seen.set(opt, terms[opt] ?? opt);
          }
        }
      }
    }
    if (seen.size > 0) {
      const options = [...seen.entries()]
        .map(([value, name]) => ({ value, name }))
        .sort((a, b) => a.name.localeCompare(b.name, "ru"));
      result.push({ slug, type, label: ATTRIBUTE_LABELS[slug] ?? slug.replace(/^pa_/, ""), options });
    }
  }
  return result;
}

function buildBrandFilters(
  products: WPProductNode[],
  _allBrands: WPBrandNode[],
): { slug: string; name: string; type: "brand" }[] {
  const seen = new Map<string, string>();
  for (const product of products) {
    for (const brand of product.productBrands?.nodes ?? []) {
      if (brand.slug && brand.name && !seen.has(brand.slug)) {
        seen.set(brand.slug, brand.name);
      }
    }
  }
  if (seen.size < 2) return [];
  return Array.from(seen.entries()).map(([slug, name]) => ({ slug, name, type: "brand" as const }));
}

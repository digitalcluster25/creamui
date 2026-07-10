import { notFound } from "next/navigation";
import { Header } from "@/components/sections/header";
import { Catalog } from "@/components/sections/catalog";
import { Footer } from "@/components/sections/footer";
import { Breadcrumbs } from "@/components/primitives/breadcrumbs/Breadcrumbs";
import { getHeaderData, flattenCategories, type WPCategoryNode } from "@/lib/wp/header";
import { footerData } from "@/lib/data/footer";
import { getClient } from "@/lib/wp/apollo";
import { GET_PRODUCTS, GET_PRODUCT_CATEGORIES, GET_PRODUCT_CATEGORY_BY_SLUG, GET_ATTRIBUTE_TERMS } from "@/lib/wp/queries";
import { mapToCatalogData, type WPProductNode } from "@/lib/wp/mappers";
import { filtersForBranch, attributeParamKey } from "@/lib/data/catalogFilters";
import type { AttributeTermLabels } from "@/lib/types/catalog";
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

  // Список категорий в навигации приходит с hideEmpty:true, поэтому после
  // перевода всех товаров в draft пустые категории исчезают из дерева.
  // Для страницы категории проверяем slug напрямую через productCategory,
  // чтобы валидная, но пустая категория не отдавала 404.
  const { data: categoryData } = await client.query<{
    productCategory: { name: string; slug: string; parent?: { node: { name: string; slug: string } } | null } | null;
  }>({
    query: GET_PRODUCT_CATEGORY_BY_SLUG,
    variables: { slug: category },
  });

  const found = categoryData?.productCategory;
  if (!found?.slug) {
    notFound();
  }

  let catalogData;
  try {
    const { data } = await client.query<{ products: { nodes: WPProductNode[] } }>({
      query: GET_PRODUCTS,
      variables: { first: 200, category },
    });
    catalogData = mapToCatalogData(data?.products?.nodes ?? [], found.name);
  } catch (e) {
    console.error("WP GraphQL error (catalog category):", e);
    catalogData = mapToCatalogData([], found.name);
  }

  // Ветка = верхний раздел. Для подкатегории берём родителя, иначе саму себя.
  const branchSlug = found.parent?.node?.slug ?? found.slug;
  const filterKeys = filtersForBranch(branchSlug);
  const initialFilters: Record<string, string> = {};
  for (const key of filterKeys) {
    const value = resolvedSearchParams[attributeParamKey(key)];
    if (typeof value === "string" && value) initialFilters[key] = value;
  }

  const termLabels = await getAttributeTermLabels(client);
  const headerData = await getHeaderData();

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
        <Catalog
          data={catalogData}
          initialBrandSlug={initialBrandSlug}
          filterKeys={filterKeys}
          termLabels={termLabels}
          initialFilters={initialFilters}
        />
      </div>
      <div className={styles.sectionFooter}>
        <Footer data={footerData} />
      </div>
    </main>
  );
}

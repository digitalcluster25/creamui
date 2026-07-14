import type { Metadata } from "next";
import { Header } from "@/components/sections/header";
import { CatalogCollections, type CatalogCollection } from "@/components/sections/catalog-collections";
import { CatalogOverview } from "@/components/sections/catalog-overview/CatalogOverview";
import { CatalogSeo } from "@/components/sections/catalog-seo/CatalogSeo";
import { Footer } from "@/components/sections/footer";
import { Breadcrumbs } from "@/components/primitives/breadcrumbs/Breadcrumbs";
import { CATALOG_BRANCH_INTROS } from "@/lib/data/catalogBranches";
import { getHeaderData, HEADER_CATEGORY_ORDER, type WPCategoryNode } from "@/lib/wp/header";
import { getFooterData } from "@/lib/wp/footer";
import { getClient } from "@/lib/wp/apollo";
import { mapToCatalogProduct, mapToCategoryCardsData, type WPProductNode } from "@/lib/wp/mappers";
import { CATALOG_ROOT_SEO } from "@/lib/data/catalogBranches";
import { GET_SITE_TEXTS } from "@/lib/wp/queries";
import { fetchProductsByCategory } from "@/lib/wp/products";
import { getProductCategoriesTree } from "@/lib/wp/catalog-taxonomy";
import styles from "./page.module.css";

export const dynamic = "force-dynamic";

const ROOT_COLLECTION_LIMIT = 5;
const PRODUCTS_PER_COLLECTION = 4;

export async function generateMetadata(): Promise<Metadata> {
  return {
    title: "Каталог HWS | Печи, парогенераторы, автоматика и инженерия",
    description:
      "Каталог HWS с навигацией по сценариям выбора: печи для русской бани, саунные печи, парогенераторы и хаммам, автоматика, дымоходы, баки, облицовка и аксессуары.",
    alternates: {
      canonical: "/catalog",
    },
  };
}

export default async function CatalogPage() {
  const footerData = await getFooterData();
  let hubData = null;
  let collections: CatalogCollection[] = [];
  let siteTexts: { catalogCollectionsTitle?: string | null; catalogOverviewTitle?: string | null; catalogOverviewLead?: string | null } = {};
  try {
    const client = getClient();
    const [categoryNodes, textsResult] = await Promise.all([
      getProductCategoriesTree(),
      client.query<{ hwsSiteTexts: typeof siteTexts }>({ query: GET_SITE_TEXTS }).catch(() => null),
    ]);
    hubData = mapToCategoryCardsData(categoryNodes, "Основные направления каталога");
    collections = await buildRootCollections(client, categoryNodes);
    siteTexts = textsResult?.data?.hwsSiteTexts ?? {};
  } catch (e) {
    console.error("WP GraphQL error (catalog):", e);
  }

  const headerData = await getHeaderData();

  return (
    <main>
      <Header data={headerData} hideBurgerOnDesktop hideActionsOnDesktop />
      <Breadcrumbs items={[{ label: "Главная", href: "/" }, { label: "Каталог" }]} />
      <div className={styles.section}>
        <CatalogOverview
          title={siteTexts.catalogOverviewTitle ?? "Каталог HWS"}
          lead={siteTexts.catalogOverviewLead ?? "Каталог организован по реальным сценариям выбора: сначала тип решения, затем подкатегория, и только после этого фильтры по мощности, объёму, серии и бренду."}
          categories={hubData}
        />
        <CatalogCollections
          title={siteTexts.catalogCollectionsTitle ?? "Популярные подборки по ключевым разделам"}
          collections={collections}
        />
        <CatalogSeo data={CATALOG_ROOT_SEO} />
      </div>
      <div className={styles.sectionFooter}>
        <Footer data={footerData} />
      </div>
    </main>
  );
}

async function buildRootCollections(
  client: ReturnType<typeof getClient>,
  categories: WPCategoryNode[],
): Promise<CatalogCollection[]> {
  const bySlug = new Map(categories.map((category) => [category.slug, category]));
  const topCategories = HEADER_CATEGORY_ORDER
    .map((slug) => bySlug.get(slug))
    .filter((category): category is WPCategoryNode => Boolean(category))
    .slice(0, ROOT_COLLECTION_LIMIT);

  const productGroups = await Promise.all(
    topCategories.map((category) => fetchProductsByCategory(client, category.slug)),
  );

  const collections: Array<CatalogCollection | null> = topCategories
    .map((category, index) => {
      const products = (productGroups[index] ?? [])
        .slice(0, PRODUCTS_PER_COLLECTION)
        .map((product: WPProductNode) => mapToCatalogProduct(product));

      if (products.length === 0) {
        return null;
      }

      return {
        id: category.slug,
        title: category.name,
        href: `/catalog/${category.slug}`,
        description:
          category.hwsSubtitle?.trim() ??
          CATALOG_BRANCH_INTROS[category.slug]?.lead ??
          null,
        products,
      };
    });

  return collections.filter((collection): collection is CatalogCollection => collection !== null);
}

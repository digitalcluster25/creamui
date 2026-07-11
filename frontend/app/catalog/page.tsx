import type { Metadata } from "next";
import { Header } from "@/components/sections/header";
import { Catalog } from "@/components/sections/catalog";
import { CatalogOverview } from "@/components/sections/catalog-overview/CatalogOverview";
import { CatalogSeo } from "@/components/sections/catalog-seo/CatalogSeo";
import { Footer } from "@/components/sections/footer";
import { Breadcrumbs } from "@/components/primitives/breadcrumbs/Breadcrumbs";
import { getHeaderData, type WPCategoryNode } from "@/lib/wp/header";
import { footerData } from "@/lib/data/footer";
import { getClient } from "@/lib/wp/apollo";
import { GET_PRODUCT_BRANDS, GET_PRODUCT_CATEGORIES } from "@/lib/wp/queries";
import { mapToCatalogData, mapToCategoryCardsData } from "@/lib/wp/mappers";
import { CATALOG_ROOT_SEO } from "@/lib/data/catalogBranches";
import { buildCatalogRobots } from "@/lib/seo/catalog";
import { fetchAllCatalogProducts } from "@/lib/wp/products";
import styles from "./page.module.css";

export const revalidate = 3600;

type BrandNode = {
  name: string;
  slug: string;
};

export async function generateMetadata({
  searchParams,
}: {
  searchParams?: Promise<Record<string, string | string[] | undefined>>;
}): Promise<Metadata> {
  const resolvedSearchParams = (await searchParams) ?? {};

  return {
    title: "Каталог HWS | Печи, парогенераторы, автоматика и инженерия",
    description:
      "Каталог HWS с навигацией по сценариям выбора: печи для русской бани, саунные печи, парогенераторы и хаммам, автоматика, дымоходы, баки, облицовка и аксессуары.",
    alternates: {
      canonical: "/catalog",
    },
    robots: buildCatalogRobots(resolvedSearchParams),
  };
}

export default async function CatalogPage({
  searchParams,
}: {
  searchParams?: Promise<{ brand?: string }>;
}) {
  const resolvedSearchParams = await searchParams;
  const initialBrandSlug = typeof resolvedSearchParams?.brand === "string" ? resolvedSearchParams.brand : "";
  let catalogData;
  let hubData = null;
  try {
    const client = getClient();
    const [{ data: brandsData }, { data: categoriesData }] = await Promise.all([
      client.query<{ productBrands: { nodes: BrandNode[] } }>({
        query: GET_PRODUCT_BRANDS,
      }),
      client.query<{ productCategories: { nodes: WPCategoryNode[] } }>({
        query: GET_PRODUCT_CATEGORIES,
      }),
    ]);
    const productsData = await fetchAllCatalogProducts(
      client,
      (brandsData?.productBrands?.nodes ?? []).map((brand) => brand.slug).filter(Boolean),
    );
    catalogData = mapToCatalogData(productsData, undefined);
    hubData = mapToCategoryCardsData(categoriesData?.productCategories?.nodes ?? [], "Основные направления каталога");
  } catch (e) {
    console.error("WP GraphQL error (catalog):", e);
    catalogData = mapToCatalogData([], undefined);
  }

  const headerData = await getHeaderData();

  return (
    <main>
      <Header data={headerData} hideBurgerOnDesktop hideActionsOnDesktop />
      <Breadcrumbs items={[{ label: "Главная", href: "/" }, { label: "Каталог" }]} />
      <div className={styles.section}>
        <CatalogOverview
          title="Каталог HWS"
          lead="Каталог организован по реальным сценариям выбора: сначала тип решения, затем подкатегория, и только после этого фильтры по мощности, объёму, серии и бренду."
          categories={hubData}
        />
        <Catalog data={catalogData} initialBrandSlug={initialBrandSlug} />
        <CatalogSeo data={CATALOG_ROOT_SEO} />
      </div>
      <div className={styles.sectionFooter}>
        <Footer data={footerData} />
      </div>
    </main>
  );
}

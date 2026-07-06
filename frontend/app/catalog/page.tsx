import { Header } from "@/components/sections/header";
import { Catalog } from "@/components/sections/catalog";
import { Footer } from "@/components/sections/footer";
import { Breadcrumbs } from "@/components/primitives/breadcrumbs/Breadcrumbs";
import { getHeaderData } from "@/lib/wp/header";
import { footerData } from "@/lib/data/footer";
import { getClient } from "@/lib/wp/apollo";
import { GET_PRODUCTS } from "@/lib/wp/queries";
import { mapToCatalogData, type WPProductNode } from "@/lib/wp/mappers";
import styles from "./page.module.css";

export const revalidate = 3600;

export default async function CatalogPage({
  searchParams,
}: {
  searchParams?: Promise<{ brand?: string }>;
}) {
  const resolvedSearchParams = await searchParams;
  const initialBrandSlug = typeof resolvedSearchParams?.brand === "string" ? resolvedSearchParams.brand : "";
  let catalogData;
  try {
    const client = getClient();
    const { data } = await client.query<{ products: { nodes: WPProductNode[] } }>({
      query: GET_PRODUCTS,
      variables: { first: 200 },
    });
    catalogData = mapToCatalogData(data?.products?.nodes ?? [], "Каталог");
  } catch (e) {
    console.error("WP GraphQL error (catalog):", e);
    catalogData = mapToCatalogData([], "Каталог");
  }

  const headerData = await getHeaderData();

  return (
    <main>
      <Header data={headerData} hideBurgerOnDesktop hideActionsOnDesktop />
      <Breadcrumbs items={[{ label: "Главная", href: "/" }, { label: "Каталог" }]} />
      <div className={styles.section}>
        <Catalog data={catalogData} initialBrandSlug={initialBrandSlug} />
      </div>
      <div className={styles.sectionFooter}>
        <Footer data={footerData} />
      </div>
    </main>
  );
}

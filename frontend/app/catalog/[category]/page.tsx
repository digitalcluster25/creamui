import { notFound } from "next/navigation";
import { Header } from "@/components/sections/header";
import { Catalog } from "@/components/sections/catalog";
import { Footer } from "@/components/sections/footer";
import { headerMock } from "@/lib/mocks/header";
import { footerData } from "@/lib/data/footer";
import { getClient } from "@/lib/wp/apollo";
import { GET_PRODUCTS, GET_PRODUCT_CATEGORIES } from "@/lib/wp/queries";
import { mapToCatalogData, type WPProductNode } from "@/lib/wp/mappers";
import styles from "../page.module.css";

export const revalidate = 3600;

type Params = { category: string };
type WPCategoryNode = { slug: string; name: string };

export async function generateStaticParams(): Promise<Params[]> {
  try {
    const client = getClient();
    const { data } = await client.query<{
      productCategories: { nodes: WPCategoryNode[] };
    }>({ query: GET_PRODUCT_CATEGORIES });
    return (data?.productCategories?.nodes ?? []).map((c) => ({ category: c.slug }));
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
  const { category } = await params;

  const client = getClient();

  // Проверяем существование категории отдельно — иначе несуществующий slug
  // в `where.category` просто вернёт пустой список товаров, а не ошибку,
  // и страница молча показала бы "каталог пуст" вместо 404.
  const { data: catData } = await client.query<{
    productCategories: { nodes: WPCategoryNode[] };
  }>({ query: GET_PRODUCT_CATEGORIES });

  const found = catData?.productCategories?.nodes?.find((c) => c.slug === category);
  if (!found) {
    notFound();
  }

  let catalogData;
  try {
    const { data } = await client.query<{ products: { nodes: WPProductNode[] } }>({
      query: GET_PRODUCTS,
      variables: { first: 24, category },
    });
    catalogData = mapToCatalogData(data?.products?.nodes ?? [], found.name);
  } catch (e) {
    console.error("WP GraphQL error (catalog category):", e);
    catalogData = mapToCatalogData([], found.name);
  }

  return (
    <main>
      <Header data={headerMock} hideBurgerOnDesktop hideActionsOnDesktop />
      <div className={styles.section}>
        <Catalog data={catalogData} />
      </div>
      <div className={styles.sectionFooter}>
        <Footer data={footerData} />
      </div>
    </main>
  );
}

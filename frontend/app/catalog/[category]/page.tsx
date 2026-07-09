import { notFound } from "next/navigation";
import { Header } from "@/components/sections/header";
import { Catalog } from "@/components/sections/catalog";
import { Footer } from "@/components/sections/footer";
import { Breadcrumbs } from "@/components/primitives/breadcrumbs/Breadcrumbs";
import { getHeaderData, flattenCategories, type WPCategoryNode } from "@/lib/wp/header";
import { footerData } from "@/lib/data/footer";
import { getClient } from "@/lib/wp/apollo";
import { GET_PRODUCTS, GET_PRODUCT_CATEGORIES, GET_PRODUCT_CATEGORY_BY_SLUG } from "@/lib/wp/queries";
import { mapToCatalogData, type WPProductNode } from "@/lib/wp/mappers";
import styles from "../page.module.css";

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
  searchParams?: Promise<{ brand?: string }>;
}) {
  const { category } = await params;
  const resolvedSearchParams = await searchParams;
  const initialBrandSlug = typeof resolvedSearchParams?.brand === "string" ? resolvedSearchParams.brand : "";

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
        <Catalog data={catalogData} initialBrandSlug={initialBrandSlug} />
      </div>
      <div className={styles.sectionFooter}>
        <Footer data={footerData} />
      </div>
    </main>
  );
}

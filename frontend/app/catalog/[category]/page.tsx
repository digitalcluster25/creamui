import type { Metadata } from "next";
import { notFound } from "next/navigation";
import { Header } from "@/components/sections/header";
import { CatalogCollections, type CatalogCollection } from "@/components/sections/catalog-collections";
import { CatalogPreview } from "@/components/sections/catalog-preview";
import { CatalogOverview } from "@/components/sections/catalog-overview/CatalogOverview";
import { CatalogSeo } from "@/components/sections/catalog-seo/CatalogSeo";
import { Categories } from "@/components/sections/categories";
import { Footer } from "@/components/sections/footer";
import { Breadcrumbs } from "@/components/primitives/breadcrumbs/Breadcrumbs";
import { getHeaderData, flattenCategories, type WPCategoryNode } from "@/lib/wp/header";
import { footerData } from "@/lib/data/footer";
import { getClient } from "@/lib/wp/apollo";
import { GET_PRODUCT_BRANDS, GET_PRODUCT_CATEGORIES, GET_PRODUCT_CATEGORY_BY_SLUG } from "@/lib/wp/queries";
import { mapToCategoryCardsData, mapToCatalogProduct, type WPProductNode } from "@/lib/wp/mappers";
import { CATALOG_BRANCH_INTROS, buildCatalogCategoryContent } from "@/lib/data/catalogBranches";
import type { CategoriesData } from "@/lib/types/categories";
import { fetchProductsByCategory } from "@/lib/wp/products";
import styles from "../page.module.css";

export const revalidate = 3600;
const CATEGORY_PREVIEW_LIMIT = 12;

type Params = { category: string };
type BrandNode = { name: string; slug: string; logoUrl?: string | null };

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
}: {
  params: Promise<Params>;
}) {
  const { category } = await params;

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
  const brandCards = buildCategoryBrandCards(productsNodes, brandsData?.productBrands?.nodes ?? []);
  const childCollections =
    found.slug === branchSlug
      ? buildChildCollections(currentChildNodes, productsNodes)
      : [];
  const previewProducts = productsNodes.slice(0, CATEGORY_PREVIEW_LIMIT).map(mapToCatalogProduct);
  const previewDescription =
    found.slug === branchSlug
      ? "Верхний уровень раздела отдает легкую обзорную выдачу по ключевым подкатегориям без перегрузки страницы."
      : "На странице оставлена быстрая серверная выдача карточек, чтобы каталог открывался заметно быстрее и не раздувался клиентскими данными.";

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
        {found.slug === branchSlug ? (
          <CatalogCollections
            title="Популярные товары по подкатегориям"
            collections={childCollections}
          />
        ) : (
          <>
            {brandCards ? <Categories data={brandCards} /> : null}
            <CatalogPreview
              title="Товары в категории"
              description={previewDescription}
              total={productsNodes.length}
              products={previewProducts}
            />
          </>
        )}
        <CatalogSeo data={found.slug === branchSlug ? branchIntro?.seo ?? null : categoryContent?.seo ?? null} />
      </div>
      <div className={styles.sectionFooter}>
        <Footer data={footerData} />
      </div>
    </main>
  );
}

function buildChildCollections(
  childNodes: Array<{
    databaseId: number;
    name: string;
    slug: string;
    hwsSubtitle?: string | null;
  }>,
  products: WPProductNode[],
): CatalogCollection[] {
  const collections: Array<CatalogCollection | null> = childNodes
    .map((child) => {
      const collectionProducts = products
        .filter((product) =>
          (product.productCategories?.nodes ?? []).some((category) => category.slug === child.slug),
        )
        .slice(0, 8)
        .map(mapToCatalogProduct);

      if (collectionProducts.length === 0) {
        return null;
      }

      return {
        id: child.slug,
        title: child.name,
        href: `/catalog/${child.slug}`,
        description: child.hwsSubtitle?.trim() || null,
        products: collectionProducts,
      };
    });

  return collections.filter((collection): collection is CatalogCollection => collection !== null);
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

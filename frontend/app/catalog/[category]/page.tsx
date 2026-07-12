import type { Metadata } from "next";
import { notFound } from "next/navigation";
import { Header } from "@/components/sections/header";
import { CatalogPreview } from "@/components/sections/catalog-preview";
import { CatalogOverview } from "@/components/sections/catalog-overview/CatalogOverview";
import { CatalogSeo } from "@/components/sections/catalog-seo/CatalogSeo";
import { Categories } from "@/components/sections/categories";
import { Footer } from "@/components/sections/footer";
import { Breadcrumbs } from "@/components/primitives/breadcrumbs/Breadcrumbs";
import { getHeaderData, flattenCategories, type WPCategoryNode } from "@/lib/wp/header";
import { footerData } from "@/lib/data/footer";
import { getClient } from "@/lib/wp/apollo";
import { mapToCatalogProduct, type WPProductNode } from "@/lib/wp/mappers";
import { CATALOG_BRANCH_INTROS, buildCatalogCategoryContent } from "@/lib/data/catalogBranches";
import type { CategoriesData } from "@/lib/types/categories";
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
  const { category } = await params;

  const client = getClient();
  const [topCategories, found, brands] = await Promise.all([
    getProductCategoriesTree(),
    getProductCategoryBySlug(category),
    getProductBrands(),
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
  const brandCards = buildCategoryBrandCards(productsNodes, brands);
  const previewProducts = productsNodes.map(mapToCatalogProduct);

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
        {brandCards ? <Categories data={brandCards} /> : null}
        <CatalogPreview
          title="Товары в разделе"
          total={productsNodes.length}
          products={previewProducts}
          filterCategories={
            found.slug === branchSlug && currentChildNodes.length > 1
              ? currentChildNodes.map((n) => ({ slug: n.slug, name: n.name }))
              : undefined
          }
        />
        <CatalogSeo data={found.slug === branchSlug ? branchIntro?.seo ?? null : categoryContent?.seo ?? null} />
      </div>
      <div className={styles.sectionFooter}>
        <Footer data={footerData} />
      </div>
    </main>
  );
}


function buildCategoryBrandCards(products: WPProductNode[], allBrands: WPBrandNode[]): CategoriesData | null {
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

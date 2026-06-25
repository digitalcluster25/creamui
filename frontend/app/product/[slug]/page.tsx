import { notFound } from "next/navigation";
import { Header } from "@/components/sections/header";
import { ProductPage } from "@/components/sections/product-page";
import { ProductDescription } from "@/components/sections/product-description/ProductDescription";
import { ProductSpecs } from "@/components/sections/product-specs";
import { Footer } from "@/components/sections/footer";
import { headerMock } from "@/lib/mocks/header";
import { footerData } from "@/lib/data/footer";
import { productDescriptionData } from "@/lib/data/productDescription";
import { getClient } from "@/lib/wp/apollo";
import { GET_PRODUCT_BY_SLUG, GET_PRODUCT_SLUGS } from "@/lib/wp/queries";
import {
  mapToProductPageData,
  mapToProductSpecsData,
  type WPProductNode,
} from "@/lib/wp/mappers";
import { productSpecsData as mockProductSpecsData } from "@/lib/data/productSpecs";
import styles from "./page.module.css";

export const revalidate = 3600;

type Params = { slug: string };

// Предсобираем страницы под все известные на момент сборки товары (ISR).
// Товары, добавленные позже, всё равно откроются — Next.js дособерёт страницу
// по требованию благодаря `revalidate` (ISR fallback), просто не в первой сборке.
export async function generateStaticParams(): Promise<Params[]> {
  try {
    const client = getClient();
    const { data } = await client.query<{ products: { nodes: { slug: string }[] } }>({
      query: GET_PRODUCT_SLUGS,
      variables: { first: 200 },
    });
    return (data?.products?.nodes ?? []).map((n) => ({ slug: n.slug }));
  } catch (e) {
    console.error("WP GraphQL error (generateStaticParams product):", e);
    return [];
  }
}

export default async function ProductPageRoute({
  params,
}: {
  params: Promise<Params>;
}) {
  const { slug } = await params;

  const client = getClient();
  const { data } = await client.query<{ product: WPProductNode | null }>({
    query: GET_PRODUCT_BY_SLUG,
    variables: { slug },
    // WooGraphQL возвращает GraphQL-ошибку (не просто null) для несуществующего
    // slug — без errorPolicy: 'all' Apollo бросает исключение вместо того,
    // чтобы дать нам спокойно проверить data.product === null и отдать 404.
    errorPolicy: "all",
  });

  if (!data?.product) {
    notFound();
  }

  const productPageData = mapToProductPageData(data.product);
  const specs = mapToProductSpecsData(data.product);
  const productSpecsData = specs.groups.length ? specs : mockProductSpecsData;

  return (
    <main>
      <Header data={headerMock} hideBurgerOnDesktop hideActionsOnDesktop />
      <div className={styles.productPageWrap}>
        <ProductPage data={productPageData} />
      </div>
      <div className={styles.section}>
        <ProductDescription {...productDescriptionData} />
      </div>
      <div className={styles.section}>
        <ProductSpecs data={productSpecsData} />
      </div>
      <div className={styles.section}>
        <Footer data={footerData} />
      </div>
    </main>
  );
}

import { notFound } from "next/navigation";
import { Header } from "@/components/sections/header";
import { ProductPage } from "@/components/sections/product-page";
import { ProductDescription } from "@/components/sections/product-description/ProductDescription";
import { ProductSpecs } from "@/components/sections/product-specs";
import { Footer } from "@/components/sections/footer";
import { getHeaderData } from "@/lib/wp/header";
import { footerData } from "@/lib/data/footer";
import { getClient } from "@/lib/wp/apollo";
import { GET_PRODUCT_BY_SLUG, GET_PRODUCT_SLUGS, GET_CONTACT_CHANNELS } from "@/lib/wp/queries";
import {
  mapToProductPageData,
  mapToProductSpecsData,
  mapToProductDescriptionHtml,
  type WPProductNode,
} from "@/lib/wp/mappers";
import { productSpecsData as mockProductSpecsData } from "@/lib/data/productSpecs";
import { getProductOverride } from "@/lib/data/productOverrides";
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

  // Три запроса параллельно: товар + контакты + хедер
  const [productResult, channelsResult, headerData] = await Promise.all([
    client.query<{ product: WPProductNode | null }>({
      query: GET_PRODUCT_BY_SLUG,
      variables: { slug },
      errorPolicy: "all",
    }),
    client.query<{
      hwsContactChannels: { whatsappNumber: string; telegramUsername: string } | null;
    }>({ query: GET_CONTACT_CHANNELS }).catch(() => ({ data: null })),
    getHeaderData(),
  ]);

  const data = productResult.data;

  if (!data?.product) {
    notFound();
  }

  const override = getProductOverride(slug);
  const productPageDataBase = mapToProductPageData(data.product);
  const productPageData = override
    ? { ...productPageDataBase, ...override.page }
    : productPageDataBase;
  const specs = override?.specs ?? mapToProductSpecsData(data.product);
  const productSpecsData = specs.groups.length ? specs : mockProductSpecsData;
  const descriptionHtml = override?.descriptionHtml ?? mapToProductDescriptionHtml(data.product);

  // Извлекаем highlights из характеристик для инфографики под галереей
  const highlights: { value: string; label: string }[] = [];
  let volumeMin = "";
  let volumeMax = "";
  let volumeSingle = "";
  let power = "";

  for (const group of productSpecsData.groups) {
    for (const row of group.rows) {
      const l = row.label.toLowerCase();
      if (l.includes("минимальный объем") || l.includes("минимальный объём")) {
        volumeMin = row.value;
      } else if (l.includes("максимальный объем") || l.includes("максимальный объём")) {
        volumeMax = row.value;
      } else if ((l.includes("объём парн") || l.includes("объем парн") || l.includes("объём парного")) && !volumeSingle) {
        volumeSingle = row.value;
      }
      if ((l.includes("мощность") && !l.includes("макс") && !l.includes("температур")) || (l.includes("номинальная") && l.includes("мощность"))) {
        if (!power) power = row.value;
      }
    }
  }

  // Объём парной
  if (volumeMin && volumeMax) {
    highlights.push({ value: `${volumeMin} – ${volumeMax}`, label: "Объём парной" });
  } else if (volumeSingle) {
    highlights.push({ value: volumeSingle, label: "Объём парной" });
  } else if (volumeMax) {
    highlights.push({ value: volumeMax, label: "Объём парной" });
  }

  // Мощность или топливо
  if (power) {
    highlights.push({ value: power, label: "Мощность" });
  } else {
    const cats = productPageData.categories.map((c) => c.label.toLowerCase());
    if (cats.some((c) => c.includes("дров"))) {
      highlights.push({ value: "Дрова / Газ", label: "Топливо" });
    }
  }

  const c = channelsResult.data?.hwsContactChannels;
  const contactChannels = {
    whatsappNumber: c?.whatsappNumber || undefined,
    telegramUsername: c?.telegramUsername || undefined,
  };

  return (
    <main>
      <Header data={headerData} hideBurgerOnDesktop hideActionsOnDesktop />
      <div className={styles.productPageWrap}>
        <ProductPage data={productPageData} contactChannels={contactChannels} highlights={highlights} />
      </div>
      <div className={styles.section}>
        <ProductDescription sectionTitle="Описание товара" html={descriptionHtml} />
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

import type { Metadata } from "next";
import { notFound } from "next/navigation";
import { cache } from "react";
import { Header } from "@/components/sections/header";
import { ProductPage } from "@/components/sections/product-page";
import { ProductDescription } from "@/components/sections/product-description/ProductDescription";
import { ProductSpecs } from "@/components/sections/product-specs";
import { Footer } from "@/components/sections/footer";
import { getHeaderData } from "@/lib/wp/header";
import { getFooterData } from "@/lib/wp/footer";
import { getClient } from "@/lib/wp/apollo";
import { GET_PRODUCT_BY_SLUG, GET_PRODUCT_SLUGS, GET_CONTACT_CHANNELS, GET_SITE_TEXTS } from "@/lib/wp/queries";
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

const getProductBySlugCached = cache(async (slug: string) => {
  const client = getClient();
  const { data } = await client.query<{ product: WPProductNode | null }>({
    query: GET_PRODUCT_BY_SLUG,
    variables: { slug },
    errorPolicy: "all",
  });

  return data?.product ?? null;
});

const getProductSlugsCached = cache(async () => {
  const client = getClient();
  const { data } = await client.query<{ products: { nodes: { slug: string }[] } }>({
    query: GET_PRODUCT_SLUGS,
    variables: { first: 1000 },
  });

  return data?.products?.nodes ?? [];
});

const getContactChannelsCached = cache(async () => {
  const client = getClient();
  const { data } = await client.query<{
    hwsContactChannels: { whatsappNumber: string; telegramUsername: string } | null;
  }>({ query: GET_CONTACT_CHANNELS });

  return data?.hwsContactChannels ?? null;
});

export async function generateMetadata({
  params,
}: {
  params: Promise<Params>;
}): Promise<Metadata> {
  const { slug } = await params;

  try {
    const product = await getProductBySlugCached(slug);
    if (!product) {
      return {
        title: "Товар не найден | HWS",
        description: "Карточка товара в каталоге HWS.",
      };
    }

    const mapped = mapToProductPageData(product);
    const descriptionHtml = mapToProductDescriptionHtml(product) ?? "";
    const descriptionText = descriptionHtml
      .replace(/<[^>]+>/g, " ")
      .replace(/\s+/g, " ")
      .trim()
      .slice(0, 220);

    return {
      title: `${mapped.title} | HWS`,
      description: descriptionText || `${mapped.title} в каталоге HWS.`,
    };
  } catch (e) {
    console.error("WP GraphQL error (product metadata):", e);
    return {
      title: "Каталог HWS",
      description: "Печи, парогенераторы, автоматика и инженерные решения HWS.",
    };
  }
}

// Предсобираем страницы под все известные на момент сборки товары (ISR).
// Товары, добавленные позже, всё равно откроются — Next.js дособерёт страницу
// по требованию благодаря `revalidate` (ISR fallback), просто не в первой сборке.
export async function generateStaticParams(): Promise<Params[]> {
  try {
    return (await getProductSlugsCached()).map((n) => ({ slug: n.slug }));
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
  const footerData = await getFooterData();
  const { slug } = await params;

  const client = getClient();
  const [product, channelsData, headerData, textsResult] = await Promise.all([
    getProductBySlugCached(slug),
    getContactChannelsCached().catch(() => null),
    getHeaderData(),
    client.query<{ hwsSiteTexts: { productDescriptionTitle?: string | null } }>({ query: GET_SITE_TEXTS }).catch(() => null),
  ]);
  const productDescriptionTitle = textsResult?.data?.hwsSiteTexts?.productDescriptionTitle ?? "Описание товара";
  if (!product) {
    notFound();
  }

  const override = getProductOverride(slug);
  const productPageDataBase = mapToProductPageData(product);
  const productPageData = override
    ? { ...productPageDataBase, ...override.page }
    : productPageDataBase;
  const specs = override?.specs ?? mapToProductSpecsData(product);
  const productSpecsData = specs.groups.length ? specs : mockProductSpecsData;
  const descriptionHtml = override?.descriptionHtml ?? mapToProductDescriptionHtml(product);

  // Highlights из GraphQL (плагин Инфографика) или fallback из характеристик
  let highlights: { value: string; label: string }[] = productPageData.highlights ?? [];

  if (!highlights.length) {
    let volumeMin = "", volumeMax = "", volumeSingle = "", power = "";
    for (const group of productSpecsData.groups) {
      for (const row of group.rows) {
        const l = row.label.toLowerCase();
        if (l.includes("минимальный объем") || l.includes("минимальный объём")) volumeMin = row.value;
        else if (l.includes("максимальный объем") || l.includes("максимальный объём")) volumeMax = row.value;
        else if ((l.includes("объём парн") || l.includes("объем парн") || l.includes("объём парного")) && !volumeSingle) volumeSingle = row.value;
        if ((l.includes("мощность") && !l.includes("макс") && !l.includes("температур")) || (l.includes("номинальная") && l.includes("мощность"))) {
          if (!power) power = row.value;
        }
      }
    }
    if (volumeMin && volumeMax) highlights.push({ value: `${volumeMin} – ${volumeMax}`, label: "Объём парной" });
    else if (volumeSingle) highlights.push({ value: volumeSingle, label: "Объём парной" });
    else if (volumeMax) highlights.push({ value: volumeMax, label: "Объём парной" });
    if (power) highlights.push({ value: power, label: "Мощность" });
    else if (productPageData.categories.some((c) => c.label.toLowerCase().includes("дров")))
      highlights.push({ value: "Дрова / Газ", label: "Топливо" });
  }

  const contactChannels = {
    whatsappNumber: channelsData?.whatsappNumber || undefined,
    telegramUsername: channelsData?.telegramUsername || undefined,
  };

  return (
    <main>
      <Header data={headerData} hideBurgerOnDesktop hideActionsOnDesktop />
      <div className={styles.productPageWrap}>
        <ProductPage data={productPageData} contactChannels={contactChannels} highlights={highlights} />
      </div>
      <div className={styles.section}>
        <ProductDescription sectionTitle={productDescriptionTitle} html={descriptionHtml} />
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

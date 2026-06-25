import { Header } from "@/components/sections/header";
import { ProductPage } from "@/components/sections/product-page";
import { ProductDescription } from "@/components/sections/product-description/ProductDescription";
import { ProductSpecs } from "@/components/sections/product-specs";
import { Footer } from "@/components/sections/footer";
import { headerMock } from "@/lib/mocks/header";
import { footerData } from "@/lib/data/footer";
import { productDescriptionData } from "@/lib/data/productDescription";
import { getClient } from "@/lib/wp/apollo";
import { GET_PRODUCT_BY_SLUG } from "@/lib/wp/queries";
import {
  mapToProductPageData,
  mapToProductSpecsData,
  type WPProductNode,
} from "@/lib/wp/mappers";
import { productPageData as mockProductPageData } from "@/lib/data/productPage";
import { productSpecsData as mockProductSpecsData } from "@/lib/data/productSpecs";
import styles from "./page.module.css";

export const revalidate = 3600;

// Временно — пока нет динамического роута /product/[slug] (это шаг 2).
// Сочи М2 (EasySteam) выбран как тестовый slug специально: у него 31 строка
// характеристик (богатая схема specs), в отличие от 2-строчных у ВВД —
// показательнее проверяет hwsSpecs.
const TEST_SLUG = "easysteam-1003523";

export default async function ProductPageRoute() {
  let productPageData;
  let productSpecsData;
  try {
    const client = getClient();
    const { data } = await client.query<{ product: WPProductNode | null }>({
      query: GET_PRODUCT_BY_SLUG,
      variables: { slug: TEST_SLUG },
    });
    if (data?.product) {
      productPageData = mapToProductPageData(data.product);
      const specs = mapToProductSpecsData(data.product);
      productSpecsData = specs.groups.length ? specs : mockProductSpecsData;
    } else {
      productPageData = mockProductPageData;
      productSpecsData = mockProductSpecsData;
    }
  } catch (e) {
    console.error("WP GraphQL error (product):", e);
    productPageData = mockProductPageData;
    productSpecsData = mockProductSpecsData;
  }

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

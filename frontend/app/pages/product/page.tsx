import { Header } from "@/components/sections/header";
import { ProductPage } from "@/components/sections/product-page";
import { ProductDescription } from "@/components/sections/product-description/ProductDescription";
import { ProductSpecs } from "@/components/sections/product-specs";
import { Footer } from "@/components/sections/footer";
import { headerMock } from "@/lib/mocks/header";
import { footerData } from "@/lib/data/footer";
import { productDescriptionData } from "@/lib/data/productDescription";
import { productSpecsData } from "@/lib/data/productSpecs";
import { getClient } from "@/lib/wp/apollo";
import { GET_PRODUCT_BY_SLUG } from "@/lib/wp/queries";
import { mapToProductPageData, type WPProductNode } from "@/lib/wp/mappers";
import { productPageData as mockProductPageData } from "@/lib/data/productPage";
import styles from "./page.module.css";

export const revalidate = 3600;

// Временно — пока нет динамического роута /product/[slug] (это шаг 2).
// Реальный slug взят с живого wpsandbox для проверки, что данные реально идут с бэка.
const TEST_SLUG = "vvd-pech-premera-rusa";

export default async function ProductPageRoute() {
  let productPageData;
  try {
    const client = getClient();
    const { data } = await client.query<{ product: WPProductNode | null }>({
      query: GET_PRODUCT_BY_SLUG,
      variables: { slug: TEST_SLUG },
    });
    productPageData = data?.product ? mapToProductPageData(data.product) : mockProductPageData;
  } catch (e) {
    console.error("WP GraphQL error (product):", e);
    productPageData = mockProductPageData;
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

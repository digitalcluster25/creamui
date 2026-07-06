import { Header } from "@/components/sections/header";
import { Footer } from "@/components/sections/footer";
import { BrandsDirectory } from "@/components/sections/brands-directory/BrandsDirectory";
import { Breadcrumbs } from "@/components/primitives/breadcrumbs/Breadcrumbs";
import { footerData } from "@/lib/data/footer";
import { getHeaderData } from "@/lib/wp/header";
import { getClient } from "@/lib/wp/apollo";
import { GET_PRODUCT_BRANDS } from "@/lib/wp/queries";
import styles from "../page.module.css";

export const revalidate = 3600;

type BrandNode = {
  name: string;
  slug: string;
  logoUrl?: string | null;
};

export default async function BrandsPage() {
  let brands: BrandNode[] = [];

  try {
    const client = getClient();
    const { data } = await client.query<{ productBrands: { nodes: BrandNode[] } }>({
      query: GET_PRODUCT_BRANDS,
    });

    brands = [...(data?.productBrands?.nodes ?? [])].sort((a, b) =>
      a.name.localeCompare(b.name, "ru"),
    );
  } catch (e) {
    console.error("WP GraphQL error (brands page):", e);
  }

  const headerData = await getHeaderData();

  return (
    <main>
      <Header data={headerData} hideBurgerOnDesktop hideActionsOnDesktop />
      <Breadcrumbs items={[{ label: "Главная", href: "/" }, { label: "Бренды" }]} />
      <div className={styles.section}>
        <BrandsDirectory brands={brands} />
      </div>
      <div className={styles.sectionFooter}>
        <Footer data={footerData} />
      </div>
    </main>
  );
}

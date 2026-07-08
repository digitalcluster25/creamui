import { Header } from "@/components/sections/header";
import { Hero } from "@/components/sections/hero";
import { Categories } from "@/components/sections/categories";
import { Cases } from "@/components/sections/cases";
import { Products } from "@/components/sections/products";
import { Brands, type BrandLogo } from "@/components/sections/brands/Brands";
import { HowWeWork } from "@/components/sections/how-we-work";
import { BlogPosts } from "@/components/sections/blog-posts";
import { ContactForm } from "@/components/sections/contact-form";
import { Footer } from "@/components/sections/footer";
import { getHeaderData } from "@/lib/wp/header";
import { heroData } from "@/lib/data/hero";
import { footerData } from "@/lib/data/footer";
import { casesData } from "@/lib/data/cases";
import { howWeWorkData } from "@/lib/data/howWeWork";
import { contactFormData } from "@/lib/data/contactForm";
import type { CategoriesData } from "@/lib/types/categories";
import type { BlogPostsData } from "@/lib/types/blogPosts";
import type { ProductsData } from "@/lib/types/products";
import { getClient } from "@/lib/wp/apollo";
import { GET_FEATURED_PRODUCTS, GET_POSTS, GET_PRODUCT_BRANDS, GET_PRODUCT_CATEGORIES } from "@/lib/wp/queries";
import { mapToBlogPost, mapToHomeCategoriesData, mapToHomeProductsData, type WPPostNode, type WPProductNode } from "@/lib/wp/mappers";
import type { WPCategoryNode } from "@/lib/wp/header";
import styles from "./page.module.css";

export const revalidate = 3600;
const KNOWLEDGE_CATEGORY = "home-wood-spa";

export default async function HomePage() {
  let brandLogos: BrandLogo[] = [];
  let categoriesData: CategoriesData = { sectionTitle: "Решения для любых задач", items: [] };
  let blogPostsData: BlogPostsData = { title: "База знаний", allHref: "/knowledge", posts: [] };
  let productsData: ProductsData = {
    title: "Подобранная коллекция",
    allHref: "/catalog",
    products: [],
    bannerImage: "https://colabrio.ams3.cdn.digitaloceanspaces.com/ohio-stage-demo-19/oh__demo19__17.webp",
    bannerHref: "#",
  };
  try {
    const client = getClient();

    // Все 4 GraphQL-запроса параллельно — вместо ~4с последовательно получаем ~1с
    const [brandsResult, categoriesResult, featuredResult, postsResult] = await Promise.all([
      client.query<{
        productBrands: { nodes: { name: string; slug: string; logoUrl: string | null }[] };
      }>({ query: GET_PRODUCT_BRANDS }),
      client.query<{
        productCategories: { nodes: WPCategoryNode[] };
      }>({ query: GET_PRODUCT_CATEGORIES }),
      client.query<{
        products: { nodes: WPProductNode[] };
      }>({
        query: GET_FEATURED_PRODUCTS,
        variables: { first: 50 },
      }),
      client.query<{
        posts: { nodes: WPPostNode[] };
      }>({
        query: GET_POSTS,
        variables: { categoryName: KNOWLEDGE_CATEGORY, first: 4 },
      }),
    ]);

    brandLogos = (brandsResult.data?.productBrands?.nodes ?? [])
      .filter((b) => !!b.logoUrl)
      .map((b) => ({ src: b.logoUrl as string, alt: b.name }));

    categoriesData = mapToHomeCategoriesData(categoriesResult.data?.productCategories?.nodes ?? []);
    productsData = mapToHomeProductsData(featuredResult.data?.products?.nodes ?? []);

    blogPostsData = {
      title: "База знаний",
      allHref: "/knowledge",
      posts: (postsResult.data?.posts?.nodes ?? []).map(mapToBlogPost),
    };
  } catch (e) {
    console.error("WP GraphQL error (home page categories/brands/posts):", e);
  }

  const headerData = await getHeaderData();

  return (
    <main>
      <Header data={headerData} hideBurgerOnDesktop hideActionsOnDesktop />
      <Hero data={heroData} />
      <div className={styles.section}>
        <Categories data={categoriesData} />
      </div>
      <div className={styles.section}>
        <Cases data={casesData} />
      </div>
      <div className={styles.section}>
        <Products data={productsData} />
      </div>
      <div className={styles.sectionFlush}>
        <Brands brands={brandLogos} />
      </div>
      <div className={styles.section}>
        <HowWeWork data={howWeWorkData} />
      </div>
      <div className={styles.section}>
        <BlogPosts data={blogPostsData} />
      </div>
      <ContactForm data={contactFormData} />
      <div className={styles.sectionFooter}>
        <Footer data={footerData} />
      </div>
    </main>
  );
}

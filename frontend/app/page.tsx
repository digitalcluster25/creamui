import type { Metadata } from "next";
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
import { getFooterData } from "@/lib/wp/footer";
import { casesData } from "@/lib/data/cases";
import { howWeWorkData } from "@/lib/data/howWeWork";
import { contactFormData } from "@/lib/data/contactForm";
import type { CategoriesData } from "@/lib/types/categories";
import type { BlogPostsData } from "@/lib/types/blogPosts";
import type { ProductsData } from "@/lib/types/products";
import type { CasesData } from "@/lib/types/cases";
import { getClient } from "@/lib/wp/apollo";
import { GET_FEATURED_PRODUCTS, GET_HOME_PRODUCTS, GET_POSTS, GET_PRODUCT_BRANDS, GET_PRODUCT_CATEGORIES, GET_SITE_TEXTS } from "@/lib/wp/queries";
import { mapToBlogPost, mapToHomeCategoriesData, mapToHomeProductsData, type WPPostNode, type WPProductNode } from "@/lib/wp/mappers";
import type { WPCategoryNode } from "@/lib/wp/header";
import { normalizeWpMediaUrl } from "@/lib/wp/media";
import styles from "./page.module.css";

export const dynamic = "force-dynamic";
const KNOWLEDGE_CATEGORY = "home-wood-spa";

export const metadata: Metadata = {
  title: "HWS | Печи, сауны, хаммамы и SPA-оборудование под ключ",
  description:
    "HWS подбирает и поставляет оборудование для бань, саун, хаммамов и SPA: печи, парогенераторы, автоматику, аксессуары, монтаж и запуск проектов.",
  alternates: {
    canonical: "/",
  },
  openGraph: {
    title: "HWS | Премиальные бани, сауны и хаммамы под ключ",
    description:
      "Каталог оборудования и проектные решения для бань, саун, хаммамов и SPA: подбор, поставка, монтаж и сервис.",
    url: "/",
    siteName: "HWS",
    type: "website",
  },
};

export default async function HomePage() {
  const footerData = await getFooterData();
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
  let homeCasesData: CasesData | null = casesData;
  try {
    const client = getClient();

    const [brandsResult, categoriesResult, featuredResult, homeProductsResult, postsResult, textsResult] = await Promise.all([
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
        products: { nodes: WPProductNode[] };
      }>({
        query: GET_HOME_PRODUCTS,
        variables: { first: 50 },
      }),
      client.query<{
        posts: { nodes: WPPostNode[] };
      }>({
        query: GET_POSTS,
        variables: { categoryName: KNOWLEDGE_CATEGORY, first: 4 },
      }),
      client.query<{ hwsSiteTexts: {
        homeCategoriesTitle?: string | null;
        homeProductsTitle?: string | null;
        homeBlogTitle?: string | null;
        homeCasesEnabled?: boolean | null;
        homeCasesTitle?: string | null;
        homeCasesSlides?: CasesData["projects"] | null;
      } }>({ query: GET_SITE_TEXTS }).catch(() => null),
    ]);

    const siteTexts = textsResult?.data?.hwsSiteTexts ?? {};

    if (textsResult?.data?.hwsSiteTexts?.homeCasesEnabled !== undefined) {
      const configuredSlides = siteTexts.homeCasesSlides ?? [];
      homeCasesData = siteTexts.homeCasesEnabled === false || configuredSlides.length === 0
        ? null
        : {
            title: siteTexts.homeCasesTitle ?? casesData.title,
            projects: configuredSlides,
          };
    }

    brandLogos = (brandsResult.data?.productBrands?.nodes ?? [])
      .filter((b) => !!b.logoUrl)
      .map((b) => ({ src: normalizeWpMediaUrl(b.logoUrl) ?? (b.logoUrl as string), alt: b.name }));

    categoriesData = {
      ...mapToHomeCategoriesData(categoriesResult.data?.productCategories?.nodes ?? []),
      sectionTitle: siteTexts.homeCategoriesTitle ?? "Решения для любых задач",
    };
    const featuredNodes = featuredResult.data?.products?.nodes ?? [];
    const fallbackNodes = homeProductsResult.data?.products?.nodes ?? [];
    productsData = {
      ...mapToHomeProductsData(featuredNodes.length > 0 ? featuredNodes : fallbackNodes),
      title: siteTexts.homeProductsTitle ?? "Подобранная коллекция",
    };

    blogPostsData = {
      title: siteTexts.homeBlogTitle ?? "База знаний",
      allHref: "/knowledge",
      posts: (postsResult.data?.posts?.nodes ?? []).map(mapToBlogPost),
    };
  } catch (e) {
    console.error("WP GraphQL error (home page categories/brands/posts):", e);
  }

  const headerData = await getHeaderData();

  return (
    <main className={styles.home} data-home-page>
      <Header data={headerData} hideBurgerOnDesktop hideActionsOnDesktop />
      <div className={styles.heroBlock} data-home-block="hero">
        <Hero data={heroData} />
      </div>
      <div className={styles.section} data-home-block="categories">
        <Categories data={categoriesData} />
      </div>
      {homeCasesData ? (
        <div className={styles.section} data-home-block="cases">
          <Cases data={homeCasesData} />
        </div>
      ) : null}
      <div className={styles.section} data-home-block="products">
        <Products data={productsData} />
      </div>
      <div className={styles.sectionFlush} data-home-block="brands">
        <Brands brands={brandLogos} />
      </div>
      <div className={styles.section} data-home-block="how-we-work">
        <HowWeWork data={howWeWorkData} />
      </div>
      <div className={styles.section} data-home-block="knowledge">
        <BlogPosts data={blogPostsData} />
      </div>
      <div className={styles.contactBlock} data-home-block="contact">
        <ContactForm data={contactFormData} />
      </div>
      <div className={styles.sectionFooter} data-home-block="footer">
        <Footer data={footerData} />
      </div>
    </main>
  );
}

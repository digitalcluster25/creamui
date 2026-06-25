import { Header } from "@/components/sections/header";
import { Hero } from "@/components/sections/hero";
import { heroData } from "@/lib/data/hero";
import { Categories } from "@/components/sections/categories";
import { Cases } from "@/components/sections/cases";
import { Products } from "@/components/sections/products";
import { HowWeWork } from "@/components/sections/how-we-work";
import { BlogPosts } from "@/components/sections/blog-posts";
import { ContactForm } from "@/components/sections/contact-form";
import { Brands } from "@/components/sections/brands/Brands";
import { Footer } from "@/components/sections/footer";
import { Catalog } from "@/components/sections/catalog";
import { ProductPage } from "@/components/sections/product-page";
import { ProductSpecs } from "@/components/sections/product-specs";
import { ProductDescription } from "@/components/sections/product-description/ProductDescription";
import { headerMock } from "@/lib/mocks/header";
import { footerData } from "@/lib/data/footer";
import { casesData } from "@/lib/data/cases";
import { productsData } from "@/lib/data/products";
import { howWeWorkData } from "@/lib/data/howWeWork";
import { blogPostsData } from "@/lib/data/blogPosts";
import { contactFormData } from "@/lib/data/contactForm";
import { catalogData } from "@/lib/data/catalog";
import { productPageData } from "@/lib/data/productPage";
import { productSpecsData } from "@/lib/data/productSpecs";
import { productDescriptionData } from "@/lib/data/productDescription";
import type { CategoriesData } from "@/lib/types/categories";

const categoriesData: CategoriesData = {
  sectionTitle: "Решения для любых задач",
  items: [
    {
      id: "1",
      imageSrc: "https://colabrio.ams3.cdn.digitaloceanspaces.com/ohio-stage-demo-19/oh__demo19__08.webp",
      imageAlt: "",
      href: "#",
      subtitle: "Мягкий пар и традиционные решения",
      title: "Русская баня",
      tags: [
        { id: "1", label: "Дровяные печи", href: "#" },
        { id: "2", label: "Камни", href: "#" },
      ],
    },
    {
      id: "2",
      imageSrc: "https://colabrio.ams3.cdn.digitaloceanspaces.com/ohio-stage-demo-19/oh__demo19__09.webp",
      imageAlt: "",
      href: "#",
      subtitle: "Электрические системы и печи",
      title: "Финская сауна",
      tags: [
        { id: "1", label: "Harvia", href: "#" },
        { id: "2", label: "EOS", href: "#" },
      ],
    },
    {
      id: "3",
      imageSrc: "https://colabrio.ams3.cdn.digitaloceanspaces.com/ohio-stage-demo-19/oh__demo19__14.webp",
      imageAlt: "",
      href: "#",
      subtitle: "Парогенераторы и климат",
      title: "Хаммам",
      tags: [
        { id: "1", label: "Парогенераторы", href: "#" },
        { id: "2", label: "Освещение", href: "#" },
      ],
    },
    {
      id: "4",
      imageSrc: "/assets/herobg.png",
      imageAlt: "",
      href: "#",
      subtitle: "Отели, фитнес и велнес",
      title: "Коммерческий SPA",
      tags: [
        { id: "1", label: "Проектирование", href: "#" },
        { id: "2", label: "Монтаж", href: "#" },
      ],
    },
  ],
};

const section = { padding: "64px clamp(16px, 1.5vw, 28px)" };

const headerMock2 = {
  ...headerMock,
  topLinks: headerMock.topLinks.map(l => ({ ...l })),
  primaryNav: headerMock.primaryNav.map(n => ({ ...n, megaMenu: n.megaMenu ? n.megaMenu.map(m => ({ ...m })) : undefined })),
  currencies: headerMock.currencies.map(c => ({ ...c })),
  actions: headerMock.actions.map(a => ({ ...a })),
  hamburgerContacts: headerMock.hamburgerContacts.map(h => ({ ...h, lines: [...h.lines] })),
  hamburgerSocials: headerMock.hamburgerSocials.map(s => ({ ...s })),
};

export default function SectionsPage() {
  return (
    <>
      <div className="app-shell">
        <header className="app-header">
          <div>
            <h1 className="app-title">Секции</h1>
            <p className="app-subtitle">Полные секции страницы — шапка, герой, каталог, кейсы, блог и др.</p>
          </div>
          <nav className="app-nav" aria-label="Навигация по frontend">
            <a href="/">Главная</a>
            <a href="/primitives">Примитивы</a>
            <a href="/blocks">Блоки</a>
            <a href="/sections" aria-current="page">Секции</a>
            <a href="/pages">Страницы</a>
          </nav>
        </header>
      </div>
      <main>
      <Header data={headerMock} hideBurgerOnDesktop hideActionsOnDesktop />
      <Hero data={heroData} />
      <div style={section}>
        <Categories data={categoriesData} />
      </div>
      <div style={section}>
        <Cases data={casesData} />
      </div>
      <div style={section}>
        <Products data={productsData} />
      </div>
      <div style={section}>
        <Brands
          brands={[
            { src: "/assets/vvd.png", alt: "VVD" },
            { src: "/assets/easysteam.svg", alt: "EasySteam" },
            { src: "/assets/sangens.png", alt: "Sangens" },
          ]}
        />
      </div>
      <div style={section}>
        <HowWeWork data={howWeWorkData} />
      </div>
      <div style={section}>
        <BlogPosts data={blogPostsData} />
      </div>
      <ContactForm data={contactFormData} />
      <div style={section}>
        <Catalog data={catalogData} />
      </div>
      <div style={section}>
        <ProductPage data={productPageData} />
      </div>
      <div style={section}>
        <ProductDescription {...productDescriptionData} />
      </div>
      <div style={section}>
        <ProductSpecs data={productSpecsData} />
      </div>
      <div style={section}>
        <Footer data={footerData} />
      </div>
      <Header data={headerMock2} />
    </main>
    </>
  );
}

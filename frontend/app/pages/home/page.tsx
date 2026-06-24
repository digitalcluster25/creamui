import { Header } from "@/components/sections/header";
import { Hero } from "@/components/sections/hero";
import { Categories } from "@/components/sections/categories";
import { Cases } from "@/components/sections/cases";
import { Products } from "@/components/sections/products";
import { Brands } from "@/components/sections/brands/Brands";
import { HowWeWork } from "@/components/sections/how-we-work";
import { BlogPosts } from "@/components/sections/blog-posts";
import { ContactForm } from "@/components/sections/contact-form";
import { Footer } from "@/components/sections/footer";
import { headerMock } from "@/lib/mocks/header";
import { heroData } from "@/lib/data/hero";
import { footerData } from "@/lib/data/footer";
import { casesData } from "@/lib/data/cases";
import { productsData } from "@/lib/data/products";
import { howWeWorkData } from "@/lib/data/howWeWork";
import { blogPostsData } from "@/lib/data/blogPosts";
import { contactFormData } from "@/lib/data/contactForm";
import type { CategoriesData } from "@/lib/types/categories";
import styles from "./page.module.css";

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

export default function HomePage() {
  return (
    <main>
      <Header data={headerMock} hideBurgerOnDesktop hideActionsOnDesktop />
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
        <Brands />
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

import type { Metadata } from "next";
import Link from "next/link";
import { Breadcrumbs } from "@/components/primitives/breadcrumbs/Breadcrumbs";
import { Footer } from "@/components/sections/footer";
import { Header } from "@/components/sections/header";
import { getFooterData } from "@/lib/wp/footer";
import { getHeaderData } from "@/lib/wp/header";
import { getSitemapData } from "@/lib/wp/sitemap";
import styles from "./page.module.css";

export const dynamic = "force-dynamic";

export async function generateMetadata(): Promise<Metadata> {
  return {
    title: "Карта сайта | HWS",
    description:
      "Карта сайта HWS: каталог банных печей, саунных печей, парогенераторов, автоматики, сервисные разделы и информация о компании.",
    alternates: {
      canonical: "/sitemap",
    },
  };
}

const coreLinks = [
  { label: "Главная", href: "/" },
  { label: "Каталог", href: "/catalog" },
  { label: "Бренды", href: "/brands" },
  { label: "Контакты", href: "/contacts" },
  { label: "База знаний", href: "/knowledge" },
];

export default async function SitemapPage() {
  const [footerData, headerData, sitemapData] = await Promise.all([
    getFooterData(),
    getHeaderData(),
    getSitemapData(),
  ]);

  return (
    <main>
      <Header data={headerData} hideBurgerOnDesktop hideActionsOnDesktop />
      <Breadcrumbs items={[{ label: "Главная", href: "/" }, { label: "Карта сайта" }]} />
      <section className={styles.section} aria-labelledby="sitemap-title">
        <div className={styles.hero}>
          <h1 id="sitemap-title" className={styles.title}>
            Карта сайта
          </h1>
          <p className={styles.lead}>
            Полный список основных разделов HWS для быстрого перехода к товарам, сервисной информации и материалам компании.
          </p>
        </div>

        <nav className={styles.navigation} aria-label="Разделы сайта">
          <section className={styles.group}>
            <h2 className={styles.groupTitle}>Основное</h2>
            <ul className={styles.links}>
              {coreLinks.map((link) => (
                <li key={link.href}>
                  <Link href={link.href}>{link.label}</Link>
                </li>
              ))}
            </ul>
          </section>

          {footerData.columns.map((column) => (
            <section key={column.id} className={styles.group}>
              <h2 className={styles.groupTitle}>{column.title}</h2>
              <ul className={styles.links}>
                {column.links.map((link) => (
                  <li key={link.id}>
                    {link.href ? <Link href={link.href}>{link.label}</Link> : <span>{link.label}</span>}
                  </li>
                ))}
              </ul>
            </section>
          ))}
        </nav>

        <section className={styles.directory} aria-labelledby="catalog-directory-title">
          <h2 id="catalog-directory-title" className={styles.directoryTitle}>
            Каталог
          </h2>
          <div className={styles.catalogGroups}>
            {sitemapData.catalog.map((group) => (
              <section key={group.category.href} className={styles.catalogGroup}>
                <h3 className={styles.catalogTitle}>
                  <Link href={group.category.href}>{group.category.label}</Link>
                </h3>
                <ul className={styles.deepLinks}>
                  {group.products.map((product) => (
                    <li key={product.href}>
                      <Link href={product.href}>{product.label}</Link>
                    </li>
                  ))}
                </ul>
              </section>
            ))}
          </div>
        </section>

        <div className={styles.directoryGrid}>
          <section className={styles.directory} aria-labelledby="brands-directory-title">
            <h2 id="brands-directory-title" className={styles.directoryTitle}>
              Бренды
            </h2>
            <ul className={styles.deepLinks}>
              {sitemapData.brands.map((brand) => (
                <li key={brand.href}>
                  <Link href={brand.href}>{brand.label}</Link>
                </li>
              ))}
            </ul>
          </section>

          <section className={styles.directory} aria-labelledby="articles-directory-title">
            <h2 id="articles-directory-title" className={styles.directoryTitle}>
              Статьи
            </h2>
            <ul className={styles.deepLinks}>
              {sitemapData.articles.map((article) => (
                <li key={article.href}>
                  <Link href={article.href}>{article.label}</Link>
                </li>
              ))}
            </ul>
          </section>
        </div>
      </section>
      <div className={styles.footer}>
        <Footer data={footerData} />
      </div>
    </main>
  );
}

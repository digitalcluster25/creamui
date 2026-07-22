import type { Metadata } from "next";
import Link from "next/link";
import { Breadcrumbs } from "@/components/primitives/breadcrumbs/Breadcrumbs";
import { Footer } from "@/components/sections/footer";
import { Header } from "@/components/sections/header";
import { getFooterData } from "@/lib/wp/footer";
import { getHeaderData } from "@/lib/wp/header";
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
  const [footerData, headerData] = await Promise.all([getFooterData(), getHeaderData()]);

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
      </section>
      <div className={styles.footer}>
        <Footer data={footerData} />
      </div>
    </main>
  );
}

import Link from "next/link";
import styles from "./page.module.css";

const pages = [
  {
    id: "home",
    href: "/pages/home",
    title: "Главная",
    text: "Хедер, категории, кейсы, продукты, бренды, как работаем, блог, форма, футер.",
  },
  {
    id: "catalog",
    href: "/pages/catalog",
    title: "Каталог",
    text: "Хедер, каталог товаров, футер.",
  },
  {
    id: "product",
    href: "/pages/product",
    title: "Товар",
    text: "Хедер, карточка товара, описание, характеристики, футер.",
  },
];

export default function PagesPreviewPage() {
  return (
    <main className="app-shell">
      <header className="app-header">
        <div>
          <h1 className="app-title">Страницы</h1>
          <p className="app-subtitle">Preview-слой для полноценных страниц нового стека.</p>
        </div>
        <nav className="app-nav" aria-label="Навигация по frontend">
          <a href="/">Главная</a>
          <a href="/primitives">Примитивы</a>
          <a href="/blocks">Блоки</a>
          <a href="/pages" aria-current="page">
            Страницы
          </a>
        </nav>
      </header>

      <section className={styles.grid}>
        {pages.map((page) => (
          <Link key={page.id} href={page.href} className={styles.card}>
            <span className={styles.eyebrow}>Page</span>
            <h2 className={styles.title}>{page.title}</h2>
            <p className={styles.text}>{page.text}</p>
          </Link>
        ))}
      </section>
    </main>
  );
}

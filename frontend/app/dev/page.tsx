import Link from "next/link";

import styles from "./page.module.css";

const sections = [
  {
    href: "/primitives",
    title: "Примитивы",
    text: "Локальные примитивы нового стека, только то, что реально нужно блоку Header.",
  },
  {
    href: "/blocks",
    title: "Блоки",
    text: "Портированные блоки на React + CSS Modules с моками и готовой точкой подключения API.",
  },
  {
    href: "/sections",
    title: "Секции",
    text: "Полные секции страницы — шапка, герой, каталог, кейсы, блог и др.",
  },
];

export default function HomePage() {
  return (
    <main className="app-shell">
      <header className="app-header">
        <div>
          <h1 className="app-title">Frontend</h1>
          <p className="app-subtitle">Новый headless-слой для CreamUI.</p>
        </div>
      </header>

      <section className={styles.grid} aria-label="Разделы фронтенда">
        {sections.map((section) => (
          <Link key={section.href} href={section.href} className={styles.card}>
            <span className={styles.eyebrow}>Preview</span>
            <h2 className={styles.cardTitle}>{section.title}</h2>
            <p className={styles.cardText}>{section.text}</p>
          </Link>
        ))}
      </section>
    </main>
  );
}

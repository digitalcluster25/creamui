"use client";

import styles from "./page.module.css";
import { BlogPostCard } from "@/components/blocks/blog-post-card";
import { CaseCard } from "@/components/blocks/case-card";
import { ProductCard } from "@/components/blocks/product-card";
import { CategoryCard } from "@/components/blocks/category-card";
import { PromoBanner } from "@/components/blocks/promo-banner";
import { HowWeWorkStep } from "@/components/blocks/how-we-work-step";
import { FooterColumn } from "@/components/blocks/footer-column";
import { blogPostsData } from "@/lib/data/blogPosts";
import { casesData } from "@/lib/data/cases";
import { productsData } from "@/lib/data/products";
import { howWeWorkData } from "@/lib/data/howWeWork";
import { footerData } from "@/lib/data/footer";

export default function BlocksPage() {
  return (
    <main className="app-shell">
      <header className="app-header">
        <div>
          <h1 className="app-title">Блоки</h1>
          <p className="app-subtitle">Переиспользуемые UI-блоки — карточки, баннеры, составные элементы.</p>
        </div>
        <nav className="app-nav" aria-label="Навигация по frontend">
          <a href="/">Главная</a>
          <a href="/primitives">Примитивы</a>
          <a href="/blocks" aria-current="page">Блоки</a>
          <a href="/sections">Секции</a>
          <a href="/pages">Страницы</a>
        </nav>
      </header>

      <div className={styles.stack}>

        <section className={styles.card}>
          <h2 className={styles.title}>BlogPostCard</h2>
          <div className={styles.gridFour}>
            {blogPostsData.posts.map((post) => (
              <BlogPostCard key={post.id} post={post} />
            ))}
          </div>
        </section>

        <section className={styles.card}>
          <h2 className={styles.title}>CaseCard</h2>
          <div className={styles.gridThree}>
            {casesData.projects.slice(0, 3).map((project) => (
              <CaseCard key={project.id} slide={project} />
            ))}
          </div>
        </section>

        <section className={styles.card}>
          <h2 className={styles.title}>ProductCard</h2>
          <ul className={styles.gridThree} style={{ listStyle: "none", margin: 0, padding: 0 }}>
            {productsData.products.map((product) => (
              <ProductCard key={product.id} product={product} />
            ))}
          </ul>
        </section>

        <section className={styles.card}>
          <h2 className={styles.title}>CategoryCard</h2>
          <div className={styles.gridFour}>
            <CategoryCard
              image="https://colabrio.ams3.cdn.digitaloceanspaces.com/ohio-stage-demo-19/oh__demo19__08.webp"
              href="#"
              subtitle="Мягкий пар и традиционные решения"
              title="Русская баня"
              tags={[{ label: "Дровяные печи" }, { label: "Камни" }]}
            />
          </div>
        </section>

        <section className={styles.card}>
          <h2 className={styles.title}>PromoBanner</h2>
          <div style={{ maxWidth: 400 }}>
            <PromoBanner
              href="#"
              imageSrc="https://colabrio.ams3.cdn.digitaloceanspaces.com/ohio-stage-demo-19/oh__demo19__04.webp"
              title={"Получите бесплатный\nдизайн-план."}
              subtitle="Начните свой путь с бесплатного дизайн-плана."
              buttonLabel="Войти и скачать"
            />
          </div>
        </section>

        <section className={styles.card}>
          <h2 className={styles.title}>HowWeWorkStep</h2>
          <div className={styles.gridThree}>
            {howWeWorkData.steps.map((step) => (
              <HowWeWorkStep
                key={step.id}
                number={step.number}
                title={step.title}
                description={step.description}
              />
            ))}
          </div>
        </section>

        <section className={styles.card}>
          <h2 className={styles.title}>FooterColumn</h2>
          <div className={styles.gridFour}>
            {footerData.columns.map((col) => (
              <FooterColumn
                key={col.id}
                title={col.title}
                links={col.links}
                paymentsLabel={col.paymentsLabel}
              />
            ))}
          </div>
        </section>

      </div>
    </main>
  );
}

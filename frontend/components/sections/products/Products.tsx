import type { ProductsData } from "@/lib/types/products";
import { ProductCard } from "@/components/blocks/product-card";
import styles from "./Products.module.css";

const ARROW_SVG = (
  <svg width="16" height="16" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
    <path d="M8 0L6.59 1.41L12.17 7H0V9H12.17L6.59 14.59L8 16L16 8L8 0Z" />
  </svg>
);

export function Products({ data }: { data: ProductsData }) {
  return (
    <section className={styles.section}>
      <div className={styles.head}>
        <h2 className={styles.title}>{data.title}</h2>
        <a href={data.allHref} className={`${styles.allLink} ${styles.allLinkHead}`}>
          Смотреть все
          {ARROW_SVG}
        </a>
      </div>
      <ul className={styles.productGrid}>
        {data.products.map((product) => (
          <ProductCard key={product.id} product={product} />
        ))}
      </ul>
      <a href={data.allHref} className={`${styles.allLink} ${styles.allLinkBottom}`}>
        Смотреть все
        {ARROW_SVG}
      </a>
    </section>
  );
}

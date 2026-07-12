import { CatalogProductCard } from "@/components/blocks/catalog-product-card";
import type { CatalogProduct } from "@/lib/types/catalog";
import styles from "./CatalogPreview.module.css";

type Props = {
  title?: string;
  description?: string | null;
  total: number;
  products: CatalogProduct[];
};

export function CatalogPreview({
  title = "Товары в разделе",
  description,
  total,
  products,
}: Props) {
  if (products.length === 0) return null;

  return (
    <section className={styles.section}>
      <div className={styles.head}>
        <div className={styles.copy}>
          <h2 className={styles.title}>{title}</h2>
          {description ? <p className={styles.description}>{description}</p> : null}
        </div>
        <p className={styles.count}>
          {total > products.length ? `Показаны ${products.length} из ${total} товаров` : `${total} товаров`}
        </p>
      </div>

      <div className={styles.grid}>
        {products.map((product) => (
          <CatalogProductCard
            key={product.id}
            href={product.href}
            image={product.image}
            title={product.title}
            category={product.category}
            priceMin={product.priceMin}
            priceMax={product.priceMax}
            priceOnRequest={product.priceOnRequest}
            baseCurrency={product.baseCurrencyCode}
          />
        ))}
      </div>
    </section>
  );
}

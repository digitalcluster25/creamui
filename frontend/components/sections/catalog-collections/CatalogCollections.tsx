import { CatalogProductCard } from "@/components/blocks/catalog-product-card";
import type { CatalogProduct } from "@/lib/types/catalog";
import styles from "./CatalogCollections.module.css";

export type CatalogCollection = {
  id: string;
  title: string;
  href: string;
  description?: string | null;
  products: CatalogProduct[];
};

type Props = {
  title?: string;
  collections: CatalogCollection[];
};

export function CatalogCollections({ title = "Подборки по подкатегориям", collections }: Props) {
  if (collections.length === 0) return null;

  return (
    <section className={styles.section}>
      <div className={styles.head}>
        <h2 className={styles.title}>{title}</h2>
      </div>

      <div className={styles.stack}>
        {collections.map((collection) => (
          <section key={collection.id} className={styles.collection}>
            <div className={styles.collectionHead}>
              <div className={styles.collectionCopy}>
                <h3 className={styles.collectionTitle}>{collection.title}</h3>
                {collection.description ? (
                  <p className={styles.collectionDescription}>{collection.description}</p>
                ) : null}
              </div>
              <a href={collection.href} className={styles.collectionLink}>
                Смотреть раздел
              </a>
            </div>

            <div className={styles.grid}>
              {collection.products.map((product) => (
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
        ))}
      </div>
    </section>
  );
}

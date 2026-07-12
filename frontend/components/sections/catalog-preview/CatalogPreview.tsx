"use client";

import { useState } from "react";
import { CatalogProductCard } from "@/components/blocks/catalog-product-card";
import type { CatalogProduct } from "@/lib/types/catalog";
import styles from "./CatalogPreview.module.css";

type FilterCategory = {
  slug: string;
  name: string;
};

type Props = {
  title?: string;
  description?: string | null;
  total: number;
  products: CatalogProduct[];
  filterCategories?: FilterCategory[];
};

export function CatalogPreview({
  title = "Товары в разделе",
  description,
  total,
  products,
  filterCategories,
}: Props) {
  const [activeFilter, setActiveFilter] = useState<string | null>(null);

  if (products.length === 0) return null;

  const visible = activeFilter
    ? products.filter((p) => p.categorySlugs?.includes(activeFilter))
    : products;

  const hasFilters = filterCategories && filterCategories.length > 1;

  return (
    <section className={styles.section}>
      <div className={styles.head}>
        <div className={styles.copy}>
          <h2 className={styles.title}>{title}</h2>
          {description ? <p className={styles.description}>{description}</p> : null}
        </div>
        <p className={styles.count}>
          {visible.length < total
            ? `${visible.length} из ${total} товаров`
            : `${total} товаров`}
        </p>
      </div>

      {hasFilters && (
        <div className={styles.filters}>
          <button
            className={`${styles.filterBtn}${activeFilter === null ? ` ${styles.filterBtnActive}` : ""}`}
            onClick={() => setActiveFilter(null)}
          >
            Все
          </button>
          {filterCategories.map((cat) => (
            <button
              key={cat.slug}
              className={`${styles.filterBtn}${activeFilter === cat.slug ? ` ${styles.filterBtnActive}` : ""}`}
              onClick={() => setActiveFilter(activeFilter === cat.slug ? null : cat.slug)}
            >
              {cat.name}
            </button>
          ))}
        </div>
      )}

      <div className={styles.grid}>
        {visible.map((product) => (
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

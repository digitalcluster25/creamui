"use client";

import { useState } from "react";
import { CatalogProductCard } from "@/components/blocks/catalog-product-card";
import type { CatalogProduct } from "@/lib/types/catalog";
import styles from "./CatalogPreview.module.css";

type FilterItem = {
  slug: string;
  name: string;
  type: "category" | "brand";
};

type Props = {
  total: number;
  products: CatalogProduct[];
  filters?: FilterItem[];
};

export function CatalogPreview({ total, products, filters }: Props) {
  const [active, setActive] = useState<string | null>(null);

  if (products.length === 0) return null;

  const visible = active
    ? products.filter((p) => {
        const f = filters?.find((x) => x.slug === active);
        if (!f) return true;
        return f.type === "brand"
          ? p.brandSlug === active
          : (p.categorySlugs?.includes(active) ?? false);
      })
    : products;

  const hasFilters = filters && filters.length > 1;

  return (
    <section className={styles.section}>
      <div className={styles.head}>
        <p className={styles.count}>
          {visible.length < total
            ? `${visible.length} из ${total} товаров`
            : `${total} товаров`}
        </p>
      </div>

      {hasFilters && (
        <div className={styles.filters}>
          <button
            className={`${styles.filterBtn}${active === null ? ` ${styles.filterBtnActive}` : ""}`}
            onClick={() => setActive(null)}
          >
            Все
          </button>
          {filters.map((f) => (
            <button
              key={f.slug}
              className={`${styles.filterBtn}${active === f.slug ? ` ${styles.filterBtnActive}` : ""}`}
              onClick={() => setActive(active === f.slug ? null : f.slug)}
            >
              {f.name}
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

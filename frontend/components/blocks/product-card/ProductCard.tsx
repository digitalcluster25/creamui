"use client";

import { useState } from "react";
import type { ProductItem } from "@/lib/types/products";
import styles from "./ProductCard.module.css";

export function ProductCard({ product }: { product: ProductItem }) {
  const initialSlug = product.swatches?.find((s) => s.selected)?.slug ?? product.swatches?.[0]?.slug ?? null;
  const [selectedSlug, setSelectedSlug] = useState<string | null>(initialSlug);

  return (
    <li className={styles.productCard}>
      <div className={styles.productThumb}>
        {/* eslint-disable-next-line @next/next/no-img-element */}
        <img className={styles.productImg1} src={product.image1} alt={product.title} loading="lazy" />
        {product.image2 && (
          // eslint-disable-next-line @next/next/no-img-element
          <img className={styles.productImg2} src={product.image2} alt="" aria-hidden="true" loading="lazy" />
        )}
      </div>
      <div className={styles.productDetails}>
        <h3 className={styles.productName}>
          <a href={product.href} target="_blank" rel="noopener noreferrer">
            {product.title}
          </a>
        </h3>
        {product.categories.length > 0 && (
          <p className={styles.productCategory}>
            {product.categories.map((cat, i) => (
              <a key={i} href="#">{cat}</a>
            ))}
          </p>
        )}
        <p className={styles.productPrice}>{product.price}</p>
        {product.swatches && product.swatches.length > 0 && (
          <div className={styles.swatchContainer}>
            {product.swatches.map((sw) => (
              <button
                key={sw.slug}
                type="button"
                className={`${styles.swatchOption}${sw.slug === selectedSlug ? ` ${styles.swatchSelected}` : ""}`}
                title={sw.title}
                aria-label={sw.title}
                aria-pressed={sw.slug === selectedSlug}
                onClick={() => setSelectedSlug(sw.slug)}
              >
                <span
                  className={styles.swatchInner}
                  style={
                    sw.bgImage
                      ? { backgroundImage: `url('${sw.bgImage}')` }
                      : sw.bgColor
                      ? { backgroundColor: sw.bgColor }
                      : undefined
                  }
                />
              </button>
            ))}
          </div>
        )}
      </div>
    </li>
  );
}

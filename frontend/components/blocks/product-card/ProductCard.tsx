"use client";

import { useState } from "react";
import type { ProductItem } from "@/lib/types/products";
import { useCurrency } from "@/components/providers/CurrencyProvider";
import {
  convertPrice,
  formatMoney,
  getCurrencySymbol,
} from "@/lib/currency/format";
import styles from "./ProductCard.module.css";

export function ProductCard({ product }: { product: ProductItem }) {
  const initialSlug = product.swatches?.find((s) => s.selected)?.slug ?? product.swatches?.[0]?.slug ?? null;
  const [selectedSlug] = useState<string | null>(initialSlug);
  const { activeCurrency, rates } = useCurrency();
  const symbol = getCurrencySymbol(activeCurrency);
  const convertedMin = product.priceMin != null
    ? convertPrice(product.priceMin, product.baseCurrencyCode, activeCurrency, rates)
    : null;
  const convertedMax = product.priceMax != null
    ? convertPrice(product.priceMax, product.baseCurrencyCode, activeCurrency, rates)
    : null;

  return (
    <li className={styles.productCard}>
      <div className={styles.productThumb}>
        <a href={product.href}>
          {/* eslint-disable-next-line @next/next/no-img-element */}
          <img className={styles.productImg1} src={product.image1} alt={product.title} loading="lazy" />
          {product.image2 && (
            // eslint-disable-next-line @next/next/no-img-element
            <img className={styles.productImg2} src={product.image2} alt="" aria-hidden="true" loading="lazy" />
          )}
        </a>
      </div>
      <div className={styles.productDetails}>
        <h3 className={styles.productName}>
          <a href={product.href}>{product.title}</a>
        </h3>
        {product.categories.length > 0 && (
          <p className={styles.productCategory}>
            {product.categories.map((cat, i) => (
              <a key={i} href="#">{cat}</a>
            ))}
          </p>
        )}
        <p className={styles.productPrice}>
          {product.priceOnRequest
            ? "Цена по запросу"
            : convertedMin != null && convertedMax != null
            ? `${symbol}${formatMoney(convertedMin, activeCurrency)} – ${symbol}${formatMoney(convertedMax, activeCurrency)}`
            : product.price}
        </p>
      </div>
    </li>
  );
}

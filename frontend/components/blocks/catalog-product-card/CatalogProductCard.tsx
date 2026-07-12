"use client";

import Image from "next/image";
import { useCurrency } from "@/components/providers/CurrencyProvider";
import {
  convertPrice,
  formatMoney,
  getCurrencySymbol,
  type CurrencyCode,
} from "@/lib/currency/format";
import styles from "./CatalogProductCard.module.css";

type Props = {
  href: string;
  image: string;
  title: string;
  category: string;
  priceMin: number;
  priceMax: number;
  priceOnRequest?: boolean;
  baseCurrency: CurrencyCode;
};

export function CatalogProductCard({ href, image, title, category, priceMin, priceMax, priceOnRequest = false, baseCurrency }: Props) {
  const { activeCurrency, rates } = useCurrency();
  const convertedMin = convertPrice(priceMin, baseCurrency, activeCurrency, rates);
  const convertedMax = convertPrice(priceMax, baseCurrency, activeCurrency, rates);
  const symbol = getCurrencySymbol(activeCurrency);

  return (
    <div className={styles.card}>
      <a href={href} className={styles.imageLink}>
        <div className={styles.imageWrap}>
          <Image
            src={image}
            alt={title}
            fill
            sizes="(max-width: 768px) 50vw, (max-width: 1200px) 33vw, 25vw"
            quality={60}
            className={styles.image}
          />
        </div>
      </a>
      <div className={styles.info}>
        <a href={href} className={styles.titleLink}>
          <span className={styles.title}>{title}</span>
        </a>
        <p className={styles.category}>{category}</p>
        <p className={styles.price}>
          {priceOnRequest
            ? "Цена по запросу"
            : `${symbol}${formatMoney(convertedMin, activeCurrency)} – ${symbol}${formatMoney(convertedMax, activeCurrency)}`}
        </p>
      </div>
    </div>
  );
}

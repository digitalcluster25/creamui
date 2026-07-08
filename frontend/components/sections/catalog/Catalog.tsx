"use client";

import { useEffect, useMemo, useState } from "react";
import { usePathname, useRouter } from "next/navigation";
import type { CatalogData } from "@/lib/types/catalog";
import { CatalogProductCard } from "@/components/blocks/catalog-product-card";
import styles from "./Catalog.module.css";

const ARROW_RIGHT = (
  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" aria-hidden="true">
    <polyline points="9 18 15 12 9 6" />
  </svg>
);

const CLOSE_ICON = (
  <svg width="10" height="10" viewBox="0 0 10 10" fill="none" aria-hidden="true">
    <path d="M1 1L9 9M9 1L1 9" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" />
  </svg>
);

const PER_PAGE = 24;
type SortKey = "default" | "price-asc" | "price-desc" | "name";

type Props = {
  data: CatalogData;
  initialBrandSlug?: string;
};

export function Catalog({ data, initialBrandSlug = "" }: Props) {
  const router = useRouter();
  const pathname = usePathname();
  const [sort, setSort] = useState<SortKey>("default");
  const [page, setPage] = useState(1);

  const { products } = data;

  const brandOptions = useMemo(
    () =>
      Array.from(
        new Map(
          products
            .filter((product) => product.brand && product.brandSlug)
            .map((product) => [product.brandSlug as string, { slug: product.brandSlug as string, name: product.brand as string }])
        ).values()
      ).sort((a, b) => a.name.localeCompare(b.name, "ru")),
    [products]
  );

  const normalizedInitialBrand = useMemo(
    () => (brandOptions.some((option) => option.slug === initialBrandSlug) ? initialBrandSlug : ""),
    [brandOptions, initialBrandSlug]
  );

  const [brand, setBrand] = useState(normalizedInitialBrand);

  useEffect(() => {
    setBrand(normalizedInitialBrand);
    setPage(1);
  }, [normalizedInitialBrand]);

  const filtered = useMemo(
    () => (brand ? products.filter((p) => p.brandSlug === brand) : products),
    [products, brand]
  );

  const sorted = useMemo(() => {
    const arr = [...filtered];
    if (sort === "price-asc") arr.sort((a, b) => a.priceMin - b.priceMin);
    else if (sort === "price-desc") arr.sort((a, b) => b.priceMin - a.priceMin);
    else if (sort === "name") arr.sort((a, b) => a.title.localeCompare(b.title, "ru"));
    return arr;
  }, [filtered, sort]);

  const total = sorted.length;
  const totalPages = Math.max(1, Math.ceil(total / PER_PAGE));
  const currentPage = Math.min(page, totalPages);
  const from = total === 0 ? 0 : (currentPage - 1) * PER_PAGE + 1;
  const to = Math.min(currentPage * PER_PAGE, total);
  const visible = sorted.slice((currentPage - 1) * PER_PAGE, currentPage * PER_PAGE);
  const selectedBrand = brandOptions.find((option) => option.slug === brand);

  function changeBrand(value: string) {
    setBrand(value);
    setPage(1);

    const params = new URLSearchParams(typeof window !== "undefined" ? window.location.search : "");
    if (value) {
      params.set("brand", value);
    } else {
      params.delete("brand");
    }
    const query = params.toString();
    router.replace(query ? `${pathname}?${query}` : pathname, { scroll: false });
  }

  function changeSort(value: SortKey) {
    setSort(value);
    setPage(1);
  }

  return (
    <section className={styles.section}>
      {data.pageTitle && <h1 className={styles.pageTitle}>{data.pageTitle}</h1>}
      <div className={styles.toolbar}>
        <span className={styles.filtersLabel}>Фильтры</span>
        <div className={styles.filterSelects}>
          <div className={styles.filterSelectWrap}>
            <select
              className={[styles.filterSelect, brand ? styles.filterSelectActive : ""].filter(Boolean).join(" ")}
              value={brand}
              onChange={(e) => changeBrand(e.target.value)}
              aria-label="Бренд"
            >
              <option value="">Бренд</option>
              {brandOptions.map((option) => (
                <option key={option.slug} value={option.slug}>{option.name}</option>
              ))}
            </select>
          </div>
        </div>

        <div className={styles.toolbarRight}>
          <span className={styles.count}>
            {total === 0 ? "Ничего не найдено" : `Отображение ${from}–${to} из ${total}`}
          </span>
          <select
            className={styles.sort}
            value={sort}
            onChange={(e) => changeSort(e.target.value as SortKey)}
            aria-label="Сортировка"
          >
            <option value="default">Исходная сортировка</option>
            <option value="price-asc">Цена: по возрастанию</option>
            <option value="price-desc">Цена: по убыванию</option>
            <option value="name">По названию</option>
          </select>
        </div>
      </div>

      {brand && (
        <div className={styles.chips}>
          <button type="button" className={styles.chip} onClick={() => changeBrand("")}>
            {selectedBrand?.name ?? brand}
            {CLOSE_ICON}
          </button>
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
            baseCurrency={product.baseCurrencyCode}
          />
        ))}
      </div>

      {totalPages > 1 && (
        <nav className={styles.pagination} aria-label="Страницы каталога">
          {Array.from({ length: totalPages }, (_, i) => i + 1).map((p) => (
            <button
              key={p}
              type="button"
              onClick={() => setPage(p)}
              className={[styles.pageLink, p === currentPage ? styles.pageLinkActive : ""].filter(Boolean).join(" ")}
              aria-current={p === currentPage ? "page" : undefined}
            >
              {p}
            </button>
          ))}
          <button
            type="button"
            onClick={() => setPage((p) => Math.min(p + 1, totalPages))}
            className={styles.pageArrow}
            aria-label="Следующая страница"
            disabled={currentPage === totalPages}
          >
            {ARROW_RIGHT}
          </button>
        </nav>
      )}
    </section>
  );
}

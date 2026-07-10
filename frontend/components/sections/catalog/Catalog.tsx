"use client";

import { useEffect, useMemo, useState } from "react";
import { usePathname, useRouter } from "next/navigation";
import type { CatalogData, AttributeTermLabels } from "@/lib/types/catalog";
import { CatalogProductCard } from "@/components/blocks/catalog-product-card";
import { ATTRIBUTE_LABELS, attributeParamKey } from "@/lib/data/catalogFilters";
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
  // Порядок таксономий (pa_*) для текущей ветки каталога.
  filterKeys?: string[];
  // slug -> имя термина, по каждой таксономии (для подписи опций).
  termLabels?: AttributeTermLabels;
  // Начальные значения фильтров из URL: taxonomy -> slug.
  initialFilters?: Record<string, string>;
};

export function Catalog({
  data,
  initialBrandSlug = "",
  filterKeys = [],
  termLabels = {},
  initialFilters = {},
}: Props) {
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

  // Для каждой таксономии ветки собираем реально встречающиеся значения.
  // Фильтр показываем только если различных значений >= 2 (иначе бесполезен).
  const attributeFilters = useMemo(() => {
    return filterKeys
      .map((key) => {
        const slugs = new Set<string>();
        for (const product of products) {
          for (const slug of product.attributes?.[key] ?? []) slugs.add(slug);
        }
        const options = Array.from(slugs)
          .map((slug) => ({ slug, name: termLabels[key]?.[slug] ?? slug }))
          .sort((a, b) => a.name.localeCompare(b.name, "ru"));
        return { key, label: ATTRIBUTE_LABELS[key] ?? key, options };
      })
      .filter((f) => f.options.length >= 2);
  }, [filterKeys, products, termLabels]);

  const normalizedInitialBrand = useMemo(
    () => (brandOptions.some((option) => option.slug === initialBrandSlug) ? initialBrandSlug : ""),
    [brandOptions, initialBrandSlug]
  );

  const [brand, setBrand] = useState(normalizedInitialBrand);

  // Начальные значения атрибутных фильтров, отфильтрованные до валидных.
  const normalizedInitialFilters = useMemo(() => {
    const result: Record<string, string> = {};
    for (const filter of attributeFilters) {
      const value = initialFilters[filter.key];
      if (value && filter.options.some((o) => o.slug === value)) result[filter.key] = value;
    }
    return result;
  }, [attributeFilters, initialFilters]);

  const [filters, setFilters] = useState<Record<string, string>>(normalizedInitialFilters);

  useEffect(() => {
    setBrand(normalizedInitialBrand);
    setPage(1);
  }, [normalizedInitialBrand]);

  useEffect(() => {
    setFilters(normalizedInitialFilters);
    setPage(1);
  }, [normalizedInitialFilters]);

  const filtered = useMemo(
    () =>
      products.filter((product) => {
        if (brand && product.brandSlug !== brand) return false;
        for (const [key, value] of Object.entries(filters)) {
          if (!value) continue;
          if (!(product.attributes?.[key] ?? []).includes(value)) return false;
        }
        return true;
      }),
    [products, brand, filters]
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

  // Единая точка синхронизации URL: собираем актуальные бренд + фильтры.
  function syncUrl(nextBrand: string, nextFilters: Record<string, string>) {
    const params = new URLSearchParams();
    if (nextBrand) params.set("brand", nextBrand);
    for (const [key, value] of Object.entries(nextFilters)) {
      if (value) params.set(attributeParamKey(key), value);
    }
    const query = params.toString();
    router.replace(query ? `${pathname}?${query}` : pathname, { scroll: false });
  }

  function changeBrand(value: string) {
    setBrand(value);
    setPage(1);
    syncUrl(value, filters);
  }

  function changeFilter(key: string, value: string) {
    const next = { ...filters };
    if (value) next[key] = value;
    else delete next[key];
    setFilters(next);
    setPage(1);
    syncUrl(brand, next);
  }

  function changeSort(value: SortKey) {
    setSort(value);
    setPage(1);
  }

  const hasActiveChips = Boolean(brand) || Object.values(filters).some(Boolean);

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

          {attributeFilters.map((filter) => (
            <div key={filter.key} className={styles.filterSelectWrap}>
              <select
                className={[styles.filterSelect, filters[filter.key] ? styles.filterSelectActive : ""].filter(Boolean).join(" ")}
                value={filters[filter.key] ?? ""}
                onChange={(e) => changeFilter(filter.key, e.target.value)}
                aria-label={filter.label}
              >
                <option value="">{filter.label}</option>
                {filter.options.map((option) => (
                  <option key={option.slug} value={option.slug}>{option.name}</option>
                ))}
              </select>
            </div>
          ))}
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

      {hasActiveChips && (
        <div className={styles.chips}>
          {brand && (
            <button type="button" className={styles.chip} onClick={() => changeBrand("")}>
              {selectedBrand?.name ?? brand}
              {CLOSE_ICON}
            </button>
          )}
          {attributeFilters
            .filter((filter) => filters[filter.key])
            .map((filter) => {
              const value = filters[filter.key];
              const option = filter.options.find((o) => o.slug === value);
              return (
                <button
                  key={filter.key}
                  type="button"
                  className={styles.chip}
                  onClick={() => changeFilter(filter.key, "")}
                >
                  {option?.name ?? value}
                  {CLOSE_ICON}
                </button>
              );
            })}
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

"use client";

import { useState } from "react";
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

const FILTERS = [
  {
    key: "volume" as const,
    label: "Объем парной",
    options: ["до 8 м³", "до 12 м³", "до 16 м³", "до 20 м³", "до 24 м³", "более 24 м³"],
  },
  {
    key: "power" as const,
    label: "Мощность",
    options: ["до 10 кВт", "10–15 кВт", "15–20 кВт", "более 20 кВт"],
  },
  {
    key: "material" as const,
    label: "Материал облицовки",
    options: ["Нефрит", "Талькохлорит", "Жадеит", "Порфирит", "Без облицовки"],
  },
];

type FilterKey = "volume" | "power" | "material";
type FilterState = Record<FilterKey, string>;

type Props = {
  data: CatalogData;
};

export function Catalog({ data }: Props) {
  const [sort, setSort] = useState("default");
  const [filters, setFilters] = useState<FilterState>({ volume: "", power: "", material: "" });
  const { products, total, page, perPage, totalPages } = data;
  const from = (page - 1) * perPage + 1;
  const to = Math.min(page * perPage, total);

  const activeChips = FILTERS.filter((f) => filters[f.key] !== "");

  function setFilter(key: FilterKey, value: string) {
    setFilters((prev) => ({ ...prev, [key]: value }));
  }

  function removeFilter(key: FilterKey) {
    setFilters((prev) => ({ ...prev, [key]: "" }));
  }

  return (
    <section className={styles.section}>
      {data.pageTitle && <h1 className={styles.pageTitle}>{data.pageTitle}</h1>}
      <div className={styles.toolbar}>
        <span className={styles.filtersLabel}>Фильтры</span>
        <div className={styles.filterSelects}>
          {FILTERS.map((f) => (
            <div key={f.key} className={styles.filterSelectWrap}>
              <select
                className={[styles.filterSelect, filters[f.key] ? styles.filterSelectActive : ""].filter(Boolean).join(" ")}
                value={filters[f.key]}
                onChange={(e) => setFilter(f.key, e.target.value)}
                aria-label={f.label}
              >
                <option value="">{f.label}</option>
                {f.options.map((o) => (
                  <option key={o} value={o}>{o}</option>
                ))}
              </select>
            </div>
          ))}
        </div>

        <div className={styles.toolbarRight}>
          <span className={styles.count}>Отображение {from}–{to} из {total}</span>
          <select
            className={styles.sort}
            value={sort}
            onChange={(e) => setSort(e.target.value)}
            aria-label="Сортировка"
          >
            <option value="default">Исходная сортировка</option>
            <option value="price-asc">Цена: по возрастанию</option>
            <option value="price-desc">Цена: по убыванию</option>
            <option value="name">По названию</option>
          </select>
        </div>
      </div>

      {activeChips.length > 0 && (
        <div className={styles.chips}>
          {activeChips.map((f) => (
            <button
              key={f.key}
              type="button"
              className={styles.chip}
              onClick={() => removeFilter(f.key)}
            >
              {filters[f.key]}
              {CLOSE_ICON}
            </button>
          ))}
        </div>
      )}

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
            currency={product.currency}
          />
        ))}
      </div>

      <nav className={styles.pagination} aria-label="Страницы каталога">
        {Array.from({ length: totalPages }, (_, i) => i + 1).map((p) => (
          <a
            key={p}
            href="#"
            className={[styles.pageLink, p === page ? styles.pageLinkActive : ""].filter(Boolean).join(" ")}
            aria-current={p === page ? "page" : undefined}
          >
            {p}
          </a>
        ))}
        <a href="#" className={styles.pageArrow} aria-label="Следующая страница">
          {ARROW_RIGHT}
        </a>
      </nav>
    </section>
  );
}

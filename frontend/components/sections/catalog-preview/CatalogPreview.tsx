"use client";

import { useState, useRef, useEffect } from "react";
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

const SORT_OPTIONS = [
  { value: "default", label: "По умолчанию" },
  { value: "price_asc", label: "Цена: по возрастанию" },
  { value: "price_desc", label: "Цена: по убыванию" },
  { value: "name_asc", label: "Название: А–Я" },
];

const PER_PAGE_OPTIONS = [12, 24, 48];

function MultiDropdown({
  label,
  options,
  selected,
  onToggle,
}: {
  label: string;
  options: FilterItem[];
  selected: Set<string>;
  onToggle: (slug: string) => void;
}) {
  const [open, setOpen] = useState(false);
  const ref = useRef<HTMLDivElement>(null);

  useEffect(() => {
    function close(e: MouseEvent) {
      if (ref.current && !ref.current.contains(e.target as Node)) setOpen(false);
    }
    if (open) document.addEventListener("mousedown", close);
    return () => document.removeEventListener("mousedown", close);
  }, [open]);

  const activeCount = options.filter((o) => selected.has(o.slug)).length;

  return (
    <div className={styles.dropdown} ref={ref}>
      <button
        className={`${styles.dropdownTrigger}${activeCount > 0 ? ` ${styles.dropdownTriggerActive}` : ""}`}
        onClick={() => setOpen((v) => !v)}
        type="button"
      >
        {label}
        {activeCount > 0 && <span className={styles.dropdownBadge}>{activeCount}</span>}
        <svg className={`${styles.dropdownChevron}${open ? ` ${styles.dropdownChevronOpen}` : ""}`} width="10" height="6" viewBox="0 0 10 6" fill="none">
          <path d="M1 1L5 5L9 1" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
        </svg>
      </button>
      {open && (
        <div className={styles.dropdownMenu}>
          {options.map((opt) => (
            <label key={opt.slug} className={styles.dropdownOption}>
              <input
                type="checkbox"
                checked={selected.has(opt.slug)}
                onChange={() => onToggle(opt.slug)}
                className={styles.dropdownCheckbox}
              />
              <span className={styles.dropdownCheckmark} />
              <span className={styles.dropdownOptionLabel}>{opt.name}</span>
            </label>
          ))}
        </div>
      )}
    </div>
  );
}

function SingleDropdown({
  label,
  value,
  options,
  onChange,
  alignRight,
}: {
  label: string;
  value: string;
  options: { value: string; label: string }[];
  onChange: (v: string) => void;
  alignRight?: boolean;
}) {
  const [open, setOpen] = useState(false);
  const ref = useRef<HTMLDivElement>(null);

  useEffect(() => {
    function close(e: MouseEvent) {
      if (ref.current && !ref.current.contains(e.target as Node)) setOpen(false);
    }
    if (open) document.addEventListener("mousedown", close);
    return () => document.removeEventListener("mousedown", close);
  }, [open]);

  const current = options.find((o) => o.value === value);

  return (
    <div className={styles.dropdown} ref={ref}>
      <button
        className={styles.dropdownTrigger}
        onClick={() => setOpen((v) => !v)}
        type="button"
      >
        <span className={styles.dropdownTriggerMeta}>{label}:</span>
        {current?.label}
        <svg className={`${styles.dropdownChevron}${open ? ` ${styles.dropdownChevronOpen}` : ""}`} width="10" height="6" viewBox="0 0 10 6" fill="none">
          <path d="M1 1L5 5L9 1" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
        </svg>
      </button>
      {open && (
        <div className={`${styles.dropdownMenu}${alignRight ? ` ${styles.dropdownMenuRight}` : ""}`}>
          {options.map((opt) => (
            <button
              key={opt.value}
              className={`${styles.dropdownOption} ${styles.dropdownOptionBtn}${opt.value === value ? ` ${styles.dropdownOptionSelected}` : ""}`}
              onClick={() => { onChange(opt.value); setOpen(false); }}
              type="button"
            >
              {opt.label}
            </button>
          ))}
        </div>
      )}
    </div>
  );
}

export function CatalogPreview({ total, products, filters }: Props) {
  const [selectedCategories, setSelectedCategories] = useState<Set<string>>(new Set());
  const [selectedBrands, setSelectedBrands] = useState<Set<string>>(new Set());
  const [sort, setSort] = useState("default");
  const [perPage, setPerPage] = useState(12);

  if (products.length === 0) return null;

  const categoryOptions = filters?.filter((f) => f.type === "category") ?? [];
  const brandOptions = filters?.filter((f) => f.type === "brand") ?? [];

  function toggleCategory(slug: string) {
    setSelectedCategories((prev) => {
      const next = new Set(prev);
      next.has(slug) ? next.delete(slug) : next.add(slug);
      return next;
    });
  }

  function toggleBrand(slug: string) {
    setSelectedBrands((prev) => {
      const next = new Set(prev);
      next.has(slug) ? next.delete(slug) : next.add(slug);
      return next;
    });
  }

  const filtered = products.filter((p) => {
    const catOk = selectedCategories.size === 0 || (p.categorySlugs?.some((s) => selectedCategories.has(s)) ?? false);
    const brandOk = selectedBrands.size === 0 || (p.brandSlug ? selectedBrands.has(p.brandSlug) : false);
    return catOk && brandOk;
  });

  const sorted = [...filtered].sort((a, b) => {
    if (sort === "price_asc") return a.priceMin - b.priceMin;
    if (sort === "price_desc") return b.priceMin - a.priceMin;
    if (sort === "name_asc") return a.title.localeCompare(b.title, "ru");
    return 0;
  });

  const paged = sorted.slice(0, perPage);

  const hasFilters = categoryOptions.length > 1 || brandOptions.length > 1;
  const allSelected: FilterItem[] = [
    ...categoryOptions.filter((o) => selectedCategories.has(o.slug)),
    ...brandOptions.filter((o) => selectedBrands.has(o.slug)),
  ];
  const hasActive = allSelected.length > 0;

  return (
    <section className={styles.section}>
      <div className={styles.filterBar}>
        <div className={styles.filterBarLeft}>
          {hasFilters && categoryOptions.length > 1 && (
            <MultiDropdown
              label="Подкатегория"
              options={categoryOptions}
              selected={selectedCategories}
              onToggle={toggleCategory}
            />
          )}
          {hasFilters && brandOptions.length > 1 && (
            <MultiDropdown
              label="Бренд"
              options={brandOptions}
              selected={selectedBrands}
              onToggle={toggleBrand}
            />
          )}
        </div>
        <div className={styles.filterBarRight}>
          <SingleDropdown
            label="Сортировка"
            value={sort}
            options={SORT_OPTIONS}
            onChange={setSort}
            alignRight
          />
          <SingleDropdown
            label="На странице"
            value={String(perPage)}
            options={PER_PAGE_OPTIONS.map((n) => ({ value: String(n), label: String(n) }))}
            onChange={(v) => setPerPage(Number(v))}
            alignRight
          />
        </div>
      </div>

      {hasActive && (
        <div className={styles.activeFilters}>
          <span className={styles.activeCount}>Подобрано {filtered.length} из {total} товаров</span>
          <div className={styles.activeChips}>
            {allSelected.map((f) => (
              <button
                key={f.slug}
                className={styles.activeChip}
                onClick={() => f.type === "category" ? toggleCategory(f.slug) : toggleBrand(f.slug)}
                type="button"
              >
                {f.name}
                <svg width="10" height="10" viewBox="0 0 10 10" fill="none" aria-hidden="true">
                  <path d="M1 1L9 9M9 1L1 9" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round"/>
                </svg>
              </button>
            ))}
          </div>
        </div>
      )}

      <div className={styles.grid}>
        {paged.map((product) => (
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

      {sorted.length > paged.length && (
        <div className={styles.showMore}>
          <button
            className={styles.showMoreBtn}
            onClick={() => setPerPage((v) => v + perPage)}
            type="button"
          >
            Показать ещё ({sorted.length - paged.length})
          </button>
        </div>
      )}
    </section>
  );
}

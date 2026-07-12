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

function Dropdown({
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

export function CatalogPreview({ total, products, filters }: Props) {
  const [selectedCategories, setSelectedCategories] = useState<Set<string>>(new Set());
  const [selectedBrands, setSelectedBrands] = useState<Set<string>>(new Set());

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

  const visible = products.filter((p) => {
    const catOk = selectedCategories.size === 0 || (p.categorySlugs?.some((s) => selectedCategories.has(s)) ?? false);
    const brandOk = selectedBrands.size === 0 || (p.brandSlug ? selectedBrands.has(p.brandSlug) : false);
    return catOk && brandOk;
  });

  const hasFilters = categoryOptions.length > 1 || brandOptions.length > 1;
  const allSelected: FilterItem[] = [
    ...categoryOptions.filter((o) => selectedCategories.has(o.slug)),
    ...brandOptions.filter((o) => selectedBrands.has(o.slug)),
  ];
  const hasActive = allSelected.length > 0;

  return (
    <section className={styles.section}>
      {hasFilters && (
        <div className={styles.filterBar}>
          {categoryOptions.length > 1 && (
            <Dropdown
              label="Подкатегория"
              options={categoryOptions}
              selected={selectedCategories}
              onToggle={toggleCategory}
            />
          )}
          {brandOptions.length > 1 && (
            <Dropdown
              label="Бренд"
              options={brandOptions}
              selected={selectedBrands}
              onToggle={toggleBrand}
            />
          )}
        </div>
      )}

      {hasActive && (
        <div className={styles.activeFilters}>
          <span className={styles.activeCount}>Подобрано {visible.length} из {total} товаров</span>
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

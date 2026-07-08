"use client";

import styles from "./HeaderPrimitives.module.css";

import type { HeaderCurrency } from "@/lib/types/header";
import { useCurrency } from "@/components/providers/CurrencyProvider";
import type { CurrencyCode } from "@/lib/currency/format";

type CurrencySwitcherProps = {
  currencies: HeaderCurrency[];
  defaultCurrency: string;
};

export function CurrencySwitcher({
  currencies,
}: CurrencySwitcherProps) {
  const { activeCurrency, setActiveCurrency } = useCurrency();

  return (
    <div className={styles.currencySwitcher} role="tablist" aria-label="Валюта">
      {currencies.map((currency) => {
        const isActive = currency.code === activeCurrency;

        return (
          <button
            key={currency.code}
            type="button"
            role="tab"
            aria-selected={isActive}
            className={`${styles.currencyItem} ${isActive ? styles.currencyItemActive : ""}`}
            onClick={() => setActiveCurrency(currency.code as CurrencyCode)}
          >
            {currency.label}
          </button>
        );
      })}
    </div>
  );
}

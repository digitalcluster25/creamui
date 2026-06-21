"use client";

import { useState } from "react";

import styles from "./HeaderPrimitives.module.css";

import type { HeaderCurrency } from "@/lib/types/header";

type CurrencySwitcherProps = {
  currencies: HeaderCurrency[];
  defaultCurrency: string;
};

export function CurrencySwitcher({
  currencies,
  defaultCurrency,
}: CurrencySwitcherProps) {
  const [activeCurrency, setActiveCurrency] = useState(defaultCurrency);

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
            onClick={() => setActiveCurrency(currency.code)}
          >
            {currency.label}
          </button>
        );
      })}
    </div>
  );
}

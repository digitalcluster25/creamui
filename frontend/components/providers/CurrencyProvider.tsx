"use client";

import { createContext, useContext, useEffect, useState } from "react";
import {
  DEFAULT_CURRENCY_RATES,
  type CurrencyCode,
  type CurrencyRates,
} from "@/lib/currency/format";

type CurrencyContextValue = {
  activeCurrency: CurrencyCode;
  setActiveCurrency: (currency: CurrencyCode) => void;
  rates: CurrencyRates;
};

const STORAGE_KEY = "hws-active-currency";

const CurrencyContext = createContext<CurrencyContextValue | null>(null);

export function CurrencyProvider({
  children,
}: {
  children: React.ReactNode;
}) {
  const [activeCurrency, setActiveCurrency] = useState<CurrencyCode>("USD");
  const [rates, setRates] = useState<CurrencyRates>(DEFAULT_CURRENCY_RATES);

  useEffect(() => {
    const saved = window.localStorage.getItem(STORAGE_KEY);
    if (saved === "USD" || saved === "AZN" || saved === "UZS") {
      setActiveCurrency(saved);
    }
  }, []);

  useEffect(() => {
    window.localStorage.setItem(STORAGE_KEY, activeCurrency);
  }, [activeCurrency]);

  useEffect(() => {
    let isCancelled = false;

    fetch("/api/exchange-rates", { cache: "no-store" })
      .then((response) => {
        if (!response.ok) {
          throw new Error(`Exchange rates request failed: ${response.status}`);
        }
        return response.json() as Promise<CurrencyRates>;
      })
      .then((data) => {
        if (!isCancelled) {
          setRates({
            USD: data.USD || DEFAULT_CURRENCY_RATES.USD,
            AZN: data.AZN || DEFAULT_CURRENCY_RATES.AZN,
            UZS: data.UZS || DEFAULT_CURRENCY_RATES.UZS,
            RUB: data.RUB,
            updatedAt: data.updatedAt,
          });
        }
      })
      .catch(() => {
        if (!isCancelled) {
          setRates(DEFAULT_CURRENCY_RATES);
        }
      });

    return () => {
      isCancelled = true;
    };
  }, []);

  return (
    <CurrencyContext.Provider value={{ activeCurrency, setActiveCurrency, rates }}>
      {children}
    </CurrencyContext.Provider>
  );
}

export function useCurrency() {
  const context = useContext(CurrencyContext);

  if (!context) {
    throw new Error("useCurrency must be used within CurrencyProvider");
  }

  return context;
}

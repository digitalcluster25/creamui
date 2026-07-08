export type CurrencyCode = "USD" | "AZN" | "UZS" | "RUB";

export type CurrencyRates = {
  USD: number;
  AZN: number;
  UZS: number;
  RUB?: number;
  updatedAt?: string;
};

export const DEFAULT_CURRENCY_RATES: CurrencyRates = {
  USD: 1,
  AZN: 1.7,
  UZS: 12600,
};

function getRate(currency: CurrencyCode, rates: CurrencyRates): number {
  if (currency === "RUB") {
    return rates.RUB ?? 1;
  }

  return rates[currency];
}

export function getCurrencySymbol(currency: CurrencyCode): string {
  switch (currency) {
    case "AZN":
      return "₼";
    case "UZS":
      return "сум";
    case "RUB":
      return "₽";
    case "USD":
    default:
      return "$";
  }
}

export function getCurrencyLocale(currency: CurrencyCode): string {
  switch (currency) {
    case "AZN":
      return "az-AZ";
    case "UZS":
      return "uz-UZ";
    case "RUB":
      return "ru-RU";
    case "USD":
    default:
      return "en-US";
  }
}

export function convertPrice(
  amount: number,
  from: CurrencyCode,
  to: CurrencyCode,
  rates: CurrencyRates,
): number {
  if (!Number.isFinite(amount) || amount === 0 || from === to) {
    return amount;
  }

  if (from === "USD") {
    return amount * getRate(to, rates);
  }

  if (to === "USD") {
    return amount / getRate(from, rates);
  }

  const amountInUsd = amount / getRate(from, rates);
  return amountInUsd * getRate(to, rates);
}

export function formatMoney(
  amount: number,
  currency: CurrencyCode,
): string {
  // Центы убираем — цены на печи для бани всегда целые числа,
  // дробная часть от конвертации RUB→USD не нужна покупателю.
  return amount.toLocaleString(getCurrencyLocale(currency), {
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  });
}

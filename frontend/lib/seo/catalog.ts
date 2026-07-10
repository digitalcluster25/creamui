import type { Metadata } from "next";

export type CatalogSearchParams = Record<string, string | string[] | undefined>;

function hasMeaningfulValue(value: string | string[] | undefined) {
  if (Array.isArray(value)) return value.some((item) => Boolean(item));
  return Boolean(value);
}

export function hasCatalogSeoNoise(searchParams: CatalogSearchParams = {}) {
  return Object.values(searchParams).some(hasMeaningfulValue);
}

export function buildCatalogRobots(searchParams: CatalogSearchParams = {}): Metadata["robots"] {
  if (!hasCatalogSeoNoise(searchParams)) {
    return {
      index: true,
      follow: true,
    };
  }

  return {
    index: false,
    follow: true,
    googleBot: {
      index: false,
      follow: true,
    },
  };
}

import type { ProductPageData } from "@/lib/types/productPage";
import type { ProductSpecsData } from "@/lib/types/productSpecs";

export type ProductOverride = {
  page: Partial<ProductPageData>;
  specs?: ProductSpecsData;
  descriptionHtml?: string;
};

const PRODUCT_OVERRIDES: Record<string, ProductOverride> = {};

export function getProductOverride(slug: string): ProductOverride | undefined {
  return PRODUCT_OVERRIDES[slug];
}

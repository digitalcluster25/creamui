export type ProductSwatch = {
  value: string;
  color?: string;
  priceModifier?: number;
};

export type ProductVariantGroup = {
  key: string;
  label: string;
  type: "color" | "text";
  options: ProductSwatch[];
  fullWidth?: boolean;
};

export type ProductVariantEntry = {
  selection: Record<string, string>;
  price: number;
  sku?: string;
  image?: string;
};

export type Breadcrumb = { label: string; href?: string };

export type CommerceInfo = {
  deliveryTitle?: string;
  deliveryText?: string;
  paymentTitle?: string;
  paymentText?: string;
  warrantyTitle?: string;
  warrantyText?: string;
  note?: string;
};

export type FacingOption = {
  label: string;
  iconUrl?: string;
  slug: string;
  isActive: boolean;
};

export type ProductPageData = {
  breadcrumbs?: Breadcrumb[];
  images: string[];
  badges: { label: string; variant: "default" | "sale" | "new" }[];
  title: string;
  categories: { label: string; href: string }[];
  priceOld?: number;
  price: number;
  currency: string;
  sku?: string;
  tag?: string;
  brand?: string;
  description: string;
  commerceInfo?: CommerceInfo;
  facingOptions?: FacingOption[];
  variantGroups: ProductVariantGroup[];
  variantEntries?: ProductVariantEntry[];
};

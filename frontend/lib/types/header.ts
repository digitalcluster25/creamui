export type HeaderTopLink = {
  id: string;
  label: string;
  href?: string;
  isActive?: boolean;
};

export type HeaderCatalogItem = {
  id: string;
  label: string;
  href: string;
  iconSrc: string;
};

export type HeaderNavItem = {
  id: string;
  label: string;
  href: string;
  megaMenu?: HeaderCatalogItem[];
};

export type HeaderCurrency = {
  code: string;
  label: string;
};

export type HeaderIconAction = {
  id: string;
  href?: string;
  label: string;
  kind: "wishlist" | "cart";
  badge?: number;
};

export type HeaderHamburgerContact = {
  id: string;
  title?: string;
  lines: string[];
};

export type HeaderHamburgerLink = {
  id: string;
  label: string;
  href: string;
};

export type HeaderData = {
  topLinks: HeaderTopLink[];
  brandHref: string;
  brandSrc: string;
  brandAlt: string;
  primaryNav: HeaderNavItem[];
  currencies: HeaderCurrency[];
  defaultCurrency: string;
  cartTotal: string;
  actions: HeaderIconAction[];
  hamburgerContacts: HeaderHamburgerContact[];
  hamburgerSocials: HeaderHamburgerLink[];
};

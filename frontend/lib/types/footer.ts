export type FooterLink = {
  id: string;
  label: string;
  href: string;
};

export type FooterColumn = {
  id: string;
  title: string;
  links: FooterLink[];
  /** Optional label for payments section (shown in products column) */
  paymentsLabel?: string;
};

export type FooterContactColumn = {
  id: string;
  title: string;
  phone: string;
  schedule: string[];
  email: string;
};

export type FooterLegalLink = {
  id: string;
  label: string;
  href: string;
};

export type FooterData = {
  columns: FooterColumn[];
  contactColumn: FooterContactColumn;
  copyright: string;
  legalLinks: FooterLegalLink[];
};

export type HeroFeatureBadge = {
  id: string;
  text: string;
  /** "svg" for inline SVG icon, "img" for image path */
  iconType: "svg" | "img";
  /** SVG markup string (when iconType === "svg") */
  iconSvg?: string;
  /** Image src path (when iconType === "img") */
  iconSrc?: string;
};

export type HeroCtaLink = {
  label: string;
  href: string;
};

export type HeroData = {
  eyebrow: string;
  title: string;
  lead: string;
  cta: HeroCtaLink;
  /** CSS value for background image, e.g. "url('/assets/herobg.png')" */
  backgroundImage: string;
  badges: HeroFeatureBadge[];
};

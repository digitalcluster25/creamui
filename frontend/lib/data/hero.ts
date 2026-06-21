import type { HeroData } from "@/lib/types/hero";

export const heroData: HeroData = {
  eyebrow: "Премиальные решения для бань, саун и SPA —",
  title: "Премиальные бани, сауны и хаммамы под ключ",
  lead: "Подбор оборудования, поставка, монтаж\nи запуск в Узбекистане и Азербайджане",
  cta: {
    label: "Получить расчёт мощности",
    href: "#",
  },
  backgroundImage: "url('/assets/herobg.png')",
  badges: [
    {
      id: "official-supply",
      text: "Официальные поставки",
      iconType: "svg",
      iconSvg: `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" aria-hidden="true"><path d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path></svg>`,
    },
    {
      id: "premium-brands",
      text: "Премиальные бренды",
      iconType: "img",
      iconSrc: "/assets/brand.png",
    },
    {
      id: "design-selection",
      text: "Проектирование и подбор",
      iconType: "img",
      iconSrc: "/assets/decision-making.png",
    },
    {
      id: "install-launch",
      text: "Монтаж и запуск",
      iconType: "img",
      iconSrc: "/assets/unboxing.png",
    },
    {
      id: "warranty-service",
      text: "Гарантия и сервис",
      iconType: "img",
      iconSrc: "/assets/configuration.png",
    },
  ],
};

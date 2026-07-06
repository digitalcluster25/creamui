import type { HeaderData } from "@/lib/types/header";

export const headerMock: HeaderData = {
  topLinks: [
    {
      id: "shop",
      label: "Интернет магазин",
      isActive: false,
    },
    {
      id: "construction",
      label: "Строительная компания",
      href: "#",
      isActive: true,
    },
  ],
  brandHref: "#",
  brandSrc: "/assets/hws-dark-logo-short.png",
  brandAlt: "HWS",
  primaryNav: [
    {
      id: "catalog",
      label: "Каталог",
      href: "#",
      megaMenu: [
        {
          id: "russian-bath",
          label: "Печи для русской бани",
          href: "#",
          iconSrc: "/assets/sauna.png",
        },
        {
          id: "sauna",
          label: "Печи для сауны",
          href: "#",
          iconSrc: "/assets/sauna.png",
        },
        {
          id: "hammam",
          label: "Печи для хаммама",
          href: "#",
          iconSrc: "/assets/sauna.png",
        },
        {
          id: "commercial",
          label: "Коммерческие печи",
          href: "#",
          iconSrc: "/assets/heater.png",
        },
        {
          id: "steam",
          label: "Парогенераторы",
          href: "#",
          iconSrc: "/assets/steam.png",
        },
        {
          id: "accessories",
          label: "Аксессуары для бани",
          href: "#",
          iconSrc: "/assets/acs.png",
        },
      ],
    },
    {
      id: "brands",
      label: "Бренды",
      href: "/brands",
    },
    {
      id: "knowledge",
      label: "База знаний",
      href: "/knowledge",
    },
    {
      id: "contacts",
      label: "Контакты",
      href: "/contacts",
    },
  ],
  currencies: [
    { code: "USD", label: "USD" },
    { code: "UZS", label: "UZS" },
    { code: "AZN", label: "AZN" },
  ],
  defaultCurrency: "USD",
  cartTotal: "$0.00",
  actions: [
    {
      id: "wishlist",
      href: "#",
      label: "Избранное",
      kind: "wishlist",
    },
    {
      id: "cart",
      href: "#",
      label: "Корзина",
      kind: "cart",
      badge: 0,
    },
  ],
  hamburgerContacts: [
    {
      id: "stores-tashkent",
      title: "Магазины",
      lines: ["Ташкент, ул. Шота Руставели, 12", "+998 71 207 44 10"],
    },
    {
      id: "stores-baku",
      lines: ["Баку, пр. Нефтяников, 88", "+994 12 404 77 21"],
    },
    {
      id: "partners",
      title: "Сотрудничество",
      lines: ["partners@hws-store.com", "+998 90 555 12 40"],
    },
  ],
  hamburgerSocials: [
    {
      id: "instagram",
      label: "Instagram",
      href: "https://www.instagram.com/",
    },
  ],
};

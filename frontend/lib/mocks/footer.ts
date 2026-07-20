import type { FooterData } from "@/lib/types/footer";

export const footerMock: FooterData = {
  columns: [
    {
      id: "products",
      title: "Товары",
      links: [
        { id: "russian-bath", label: "Печи для русской бани", href: "#" },
        { id: "sauna", label: "Печи для сауны", href: "#" },
        { id: "hammam", label: "Печи для хаммама", href: "#" },
        { id: "commercial", label: "Коммерческие печи", href: "#" },
        { id: "steam", label: "Парогенераторы", href: "#" },
        { id: "accessories", label: "Аксессуары для бани", href: "#" },
      ],
      paymentsLabel: "Безопасная оплата",
    },
    {
      id: "company",
      title: "Компания",
      links: [
        { id: "about", label: "О нас", href: "#" },
        { id: "responsibility", label: "Ответственность", href: "#" },
        { id: "consulting", label: "Консультации по подбору", href: "#" },
        { id: "care", label: "Уход и обслуживание", href: "#" },
        { id: "materials", label: "Обзор материалов", href: "#" },
        { id: "docs", label: "Инструкции и документы", href: "#" },
        { id: "specs", label: "Характеристики товаров", href: "#" },
      ],
    },
    {
      id: "help",
      title: "Помощь",
      links: [
        { id: "help-center", label: "Центр помощи", href: "#" },
        { id: "cancel", label: "Отмена заказа", href: "#" },
        { id: "returns", label: "Возврат и гарантия", href: "#" },
        { id: "delivery", label: "Доставка", href: "#" },
        { id: "accessibility", label: "Доступность сайта", href: "#" },
      ],
    },
  ],
  contactColumn: {
    id: "contacts",
    title: "Контакты",
    phone: "+994 50 859 98 67",
    schedule: [
      "Понедельник - пятница",
      "10:00 - 19:00 (Узбекистан, UTC+5)",
      "10:00 - 19:00 (Азербайджан, UTC+4)",
    ],
    email: "office@hws.shopping",
  },
  copyright: "© 2016-2026 HWS. Все права защищены",
  legalLinks: [
    { id: "policy", label: "Полиси", href: "#" },
    { id: "terms", label: "Условия использования", href: "#" },
    { id: "ui-kit", label: "UI Kit", href: "/dev" },
  ],
};

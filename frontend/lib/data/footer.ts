import type { FooterData } from "@/lib/types/footer";

export const footerData: FooterData = {
  columns: [
    {
      id: "products",
      title: "Товары",
      links: [
        { id: "1", label: "Печи для русской бани", href: "#" },
        { id: "2", label: "Печи для сауны", href: "#" },
        { id: "3", label: "Печи для хаммама", href: "#" },
        { id: "4", label: "Коммерческие печи", href: "#" },
        { id: "5", label: "Парогенераторы", href: "#" },
        { id: "6", label: "Аксессуары для бани", href: "#" },
      ],
      paymentsLabel: "Безопасная оплата",
    },
    {
      id: "company",
      title: "Компания",
      links: [
        { id: "1", label: "О нас", href: "#" },
        { id: "2", label: "Ответственность", href: "#" },
        { id: "3", label: "Консультации по подбору", href: "#" },
        { id: "4", label: "Уход и обслуживание", href: "#" },
        { id: "5", label: "Обзор материалов", href: "#" },
        { id: "6", label: "Инструкции и документы", href: "#" },
        { id: "7", label: "Характеристики товаров", href: "#" },
      ],
    },
    {
      id: "help",
      title: "Помощь",
      links: [
        { id: "1", label: "Центр помощи", href: "#" },
        { id: "2", label: "Отмена заказа", href: "#" },
        { id: "3", label: "Возврат и гарантия", href: "#" },
        { id: "4", label: "Доставка", href: "#" },
        { id: "5", label: "Доступность сайта", href: "#" },
      ],
    },
  ],
  contactColumn: {
    id: "contacts",
    title: "Контакты",
    phone: "8 800 200-00-00",
    schedule: ["Понедельник - пятница", "10:00 - 19:00"],
    email: "sales@wpsandbox.spaces.community",
  },
  copyright: "© 2016-2026 HWS. Все права защищены",
  legalLinks: [
    { id: "policy", label: "Полиси", href: "#" },
    { id: "terms", label: "Условия использования", href: "#" },
  ],
};

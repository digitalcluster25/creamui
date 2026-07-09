import type { FooterData } from "@/lib/types/footer";

export const footerData: FooterData = {
  columns: [
    {
      id: "products",
      title: "Товары",
      links: [
        { id: "1", label: "Печи для русской бани", href: "/catalog/russian-bath-stoves" },
        { id: "2", label: "Печи для сауны", href: "/catalog/sauna-stoves" },
        { id: "3", label: "Парогенераторы и хаммам", href: "/catalog/steam-generators-and-hammam" },
        { id: "4", label: "Коммерческие решения", href: "/catalog/commercial" },
        { id: "5", label: "Пульты и автоматика", href: "/catalog/control-units" },
        { id: "6", label: "Аксессуары", href: "/catalog/accessories" },
      ],
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
    { id: "ui-kit", label: "UI Kit", href: "/dev" },
  ],
};

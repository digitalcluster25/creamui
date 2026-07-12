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
        { id: "1", label: "О нас", href: "/o-nas" },
        { id: "2", label: "Ответственность", href: "/otvetstvennost" },
        { id: "3", label: "Консультации по подбору", href: "/konsultatsii-po-podboru" },
        { id: "4", label: "Уход и обслуживание", href: "/uhod-i-obsluzhivanie" },
        { id: "5", label: "Обзор материалов", href: "/obzor-materialov" },
        { id: "6", label: "Инструкции и документы", href: "/instruktsii-i-dokumenty" },
        { id: "7", label: "Характеристики товаров", href: "/harakteristiki-tovarov" },
      ],
    },
    {
      id: "help",
      title: "Помощь",
      links: [
        { id: "1", label: "Центр помощи", href: "/tsentr-pomoshchi" },
        { id: "2", label: "Отмена заказа", href: "/otmena-zakaza" },
        { id: "3", label: "Возврат и гарантия", href: "/vozvrat-i-garantiya" },
        { id: "4", label: "Доставка", href: "/dostavka" },
        { id: "5", label: "Доступность сайта", href: "/dostupnost-sayta" },
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
    { id: "policy", label: "Полиси" },
    { id: "terms", label: "Условия использования" },
    { id: "ui-kit", label: "UI Kit", href: "/dev" },
  ],
};

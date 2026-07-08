import type { ProductPageData } from "@/lib/types/productPage";

export const productPageData: ProductPageData = {
  breadcrumbs: [
    { label: "Магазин", href: "#" },
    { label: "Печи для бани", href: "#" },
    { label: "Санgens W12S Stone" },
  ],
  images: [
    "https://wpsandbox.spaces.community/wp-content/uploads/2026/05/l-prod-image-1-600x600.png",
    "https://wpsandbox.spaces.community/wp-content/uploads/2026/05/w-12-20-b-prod-image-1-600x600.png",
    "https://wpsandbox.spaces.community/wp-content/uploads/2026/05/l-prod-image-1-600x600.png",
    "https://wpsandbox.spaces.community/wp-content/uploads/2026/05/w-12-20-b-prod-image-1-600x600.png",
  ],
  badges: [
    { label: "В наличии", variant: "default" },
    { label: "Скидка", variant: "sale" },
  ],
  title: "Сочи М2, без облицовки, до 22 м³",
  categories: [
    { label: "Печи для русской бани", href: "#" },
    { label: "Дровяные печи", href: "#" },
  ],
  priceOld: 6810,
  price: 5500,
  baseCurrencyCode: "USD",
  sku: "SOCHI-M2-22",
  tag: "Сочи",
  brand: "EasySteam",
  description:
    "Печь для русской бани с закрытой каменкой до 22 м³. Мягкий пар, встроенный парогенератор, защитные экраны из нержавеющей стали. Топка из AISI 430.",
  commerceInfo: {
    deliveryTitle: "Доставка",
    deliveryText: "Срок поставки: от 1 до 12 недель.",
    paymentTitle: "Оплата",
    paymentText: "Наличными, картой или на расчетный счет.",
    warrantyTitle: "Гарантия до 3-х лет",
    warrantyText: "Зависит от режима использования: бытовое или коммерческое.",
    note: "Точные сроки и условия подтвердит менеджер перед оплатой.",
  },
  variantGroups: [
    {
      key: "color",
      label: "Цвет обшивки",
      type: "color",
      options: [
        { value: "Терракота", color: "#aa6c6d" },
        { value: "Серый", color: "#b7b7b7" },
        { value: "Антрацит", color: "#3a3a3c" },
        { value: "Слоновая кость", color: "#e8e0d0" },
        { value: "Тёмно-зелёный", color: "#3a5c4e" },
      ],
    },
    {
      key: "door",
      label: "Варианты дверки",
      type: "text",
      options: [
        { value: "Слева" },
        { value: "Сзади" },
        { value: "Справа" },
      ],
    },
    {
      key: "protection",
      label: "Защита топки",
      type: "text",
      options: [
        { value: "Защитные экраны" },
        { value: "Футеровка", priceModifier: 200 },
      ],
    },
    {
      key: "fuel",
      label: "Вид топлива",
      type: "text",
      fullWidth: true,
      options: [
        { value: "Дрова" },
        { value: "Под газовую горелку", priceModifier: 220 },
        { value: "Газ САБК-40", priceModifier: 510 },
        { value: "Газ + дрова ГГУ-40", priceModifier: 890 },
      ],
    },
    {
      key: "steel",
      label: "Сталь",
      type: "text",
      options: [
        { value: "AISI 430" },
        { value: "AISI 321", priceModifier: 1860 },
      ],
    },
  ],
};

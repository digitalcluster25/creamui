export type ContactOffice = {
  city: string;
  address: string[];
  phone: string;
  hoursTitle: string;
  hours: string[];
};

export const contactsPageData = {
  eyebrow: "Связаться с HWS",
  title: "Контакты",
  lead: "Запланируйте визит, консультацию или выезд специалиста в удобный для вас офис.",
  officesTitle: "Наши офисы",
  officesLead: "Обсудим проект, подберем оборудование и покажем материалы вживую.",
  offices: [
    {
      city: "Ташкент",
      address: ["ул. Шота Руставели, 12", "Ташкент, Узбекистан"],
      phone: "+998 71 207 44 10",
      hoursTitle: "Часы консультаций:",
      hours: ["Понедельник – Пятница", "10:00 – 19:00"],
    },
    {
      city: "Баку",
      address: ["пр. Нефтяников, 88", "Баку, Азербайджан"],
      phone: "+994 12 404 77 21",
      hoursTitle: "Часы консультаций:",
      hours: ["Понедельник – Пятница", "10:00 – 19:00"],
    },
    {
      city: "Дубай",
      address: ["Al Wasl Road, Jumeirah", "Dubai, UAE"],
      phone: "+971 4 556 18 40",
      hoursTitle: "Часы консультаций:",
      hours: ["Понедельник – Суббота", "11:00 – 20:00"],
    },
  ] satisfies ContactOffice[],
};

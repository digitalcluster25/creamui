import type { CategoriesData } from "@/lib/types/categories";

export const categoriesData: CategoriesData = {
  sectionTitle: "Решения для любых задач",
  items: [
    {
      id: "russian-bath",
      imageSrc: "https://wpsandbox.spaces.community/wp-content/uploads/2026/05/oh__demo19__08.webp",
      imageAlt: "Русская баня",
      href: "#",
      subtitle: "Мягкий пар и традиционные решения",
      title: "Русская баня",
      tags: [
        { id: "wood-stoves", label: "Дровяные печи", href: "#" },
        { id: "stones", label: "Камни", href: "#" },
      ],
    },
    {
      id: "finnish-sauna",
      imageSrc: "https://wpsandbox.spaces.community/wp-content/uploads/2026/05/oh__demo19__09.webp",
      imageAlt: "Финская сауна",
      href: "#",
      subtitle: "Электрические системы и печи",
      title: "Финская сауна",
      tags: [
        { id: "harvia", label: "Harvia", href: "#" },
        { id: "eos", label: "EOS", href: "#" },
      ],
    },
    {
      id: "hammam",
      imageSrc: "https://wpsandbox.spaces.community/wp-content/uploads/2026/05/oh__demo19__14-1024x648.webp",
      imageAlt: "Хаммам",
      href: "#",
      subtitle: "Парогенераторы и климат",
      title: "Хаммам",
      tags: [
        { id: "steam-generators", label: "Парогенераторы", href: "#" },
        { id: "lighting", label: "Освещение", href: "#" },
      ],
    },
    {
      id: "commercial-spa",
      imageSrc: "/assets/herobg.png",
      imageAlt: "Коммерческий SPA",
      href: "#",
      subtitle: "Отели, фитнес и велнес",
      title: "Коммерческий SPA",
      tags: [
        { id: "design", label: "Проектирование", href: "#" },
        { id: "installation", label: "Монтаж", href: "#" },
      ],
    },
  ],
};

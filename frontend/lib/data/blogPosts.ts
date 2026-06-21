import type { BlogPostsData } from "@/lib/types/blogPosts";

export const blogPostsData: BlogPostsData = {
  title: "База знаний",
  allHref: "#",
  posts: [
    {
      id: 1,
      image: "https://colabrio.ams3.cdn.digitaloceanspaces.com/ohio-stage-demo-19/oh__demo19__09.webp",
      title: "Как выбрать печь для бани: полное руководство",
      href: "#",
      readTime: "4 мин чтения",
      author: "Игорь Костин",
      excerpt:
        "Разбираем, как подобрать печь по объему парной, типу топлива, режиму парения и сценарию эксплуатации без переплаты за лишнюю мощность.",
      tags: ["Подбор", "Русская баня"],
    },
    {
      id: 2,
      image: "https://colabrio.ams3.cdn.digitaloceanspaces.com/ohio-stage-demo-19/oh__demo19__08.webp",
      title: "Ошибки при строительстве хаммама",
      href: "#",
      readTime: "4 мин чтения",
      author: "Игорь Костин",
      excerpt:
        "Собрали типовые просчеты по пароизоляции, уклонам, вентиляции и подбору парогенератора, которые чаще всего приводят к дорогостоящим переделкам.",
      tags: ["Хаммам", "Монтаж"],
    },
    {
      id: 3,
      image: "https://colabrio.ams3.cdn.digitaloceanspaces.com/ohio-stage-demo-19/oh__demo19__18.webp",
      title: "Расчёт мощности печи: пошаговый подход",
      href: "#",
      readTime: "4 мин чтения",
      author: "Игорь Костин",
      excerpt:
        "Показываем, как учитывать объем, материалы отделки, стеклянные поверхности и режим использования, чтобы печь стабильно держала температуру.",
      tags: ["Сауна", "Расчёт"],
    },
    {
      id: 4,
      image: "https://colabrio.ams3.cdn.digitaloceanspaces.com/ohio-stage-demo-19/oh__demo19__07.webp",
      title: "Русская баня и сауна: в чём разница",
      href: "#",
      readTime: "4 мин чтения",
      author: "Игорь Костин",
      excerpt:
        "Сравниваем влажность, температурные режимы, типы печей и ощущения от парения, чтобы было проще выбрать формат под ваш проект.",
      tags: ["Сравнение", "Экспертиза"],
    },
  ],
};

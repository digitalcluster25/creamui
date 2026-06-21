import type { ProductsData } from "@/lib/types/products";

export const productsData: ProductsData = {
  title: "Подобранная коллекция",
  allHref: "#",
  bannerImage: "https://colabrio.ams3.cdn.digitaloceanspaces.com/ohio-stage-demo-19/oh__demo19__17.webp",
  bannerHref: "#",
  products: [
    {
      id: 1,
      title: "Электрическая печь для бани и сауны Sangens W12S Stone",
      href: "https://sangens.com/ru/catalog/furnaces/series-w/sangens_w12s/",
      price: "$9381.86",
      categories: ["Электрические печи", "Натуральный камень"],
      image1: "https://sangens.com/wp-content/uploads/2026/05/w-12-20-s-prod-image-1.png",
      image2: "https://sangens.com/wp-content/uploads/2024/04/w_s12_1.png",
      swatches: [
        { slug: "glass-black", title: "Стекло чёрное", selected: true, bgImage: "https://sangens.com/wp-content/uploads/2025/11/facing_icon_glass_black.webp" },
        { slug: "glass-white", title: "Стекло белое", bgImage: "https://sangens.com/wp-content/uploads/2025/11/facing_icon_glass_white.webp" },
        { slug: "brick", title: "Ригельный кирпич", bgImage: "https://sangens.com/wp-content/uploads/2025/11/facing_icon_brick.webp" },
        { slug: "stone", title: "Натуральный камень", bgImage: "https://sangens.com/wp-content/uploads/2025/11/facing_icon_stone.webp" },
      ],
    },
    {
      id: 2,
      title: "Печь Домна 80 К в полноценном кожухе",
      href: "https://easysteam.ru/products/product/1015861",
      price: "$23357.05",
      categories: ["Печи для бани", "Варианты кожуха"],
      image1: "https://easysteam.ru/images/offers/1015861.jpg",
      image2: "https://easysteam.ru/photos/shares/Products/Pechi/Pechi_K/2024/D80_K/New_kozhuch/1/Domna80_K_piroksenit_new_1.png",
      swatches: [
        { slug: "talk", title: "Талькохлорит", selected: true, bgImage: "https://easysteam.ru/photos/shares/ikomki_v_nabory/talk.jpg" },
        { slug: "zmeevik", title: "Змеевик", bgImage: "https://easysteam.ru/photos/shares/ikomki_v_nabory/zm.jpg" },
        { slug: "piroksenit", title: "Пироксенит", bgImage: "https://easysteam.ru/photos/shares/ikomki_v_nabory/piro.jpg" },
        { slug: "zhadeit", title: "Жадеит", bgImage: "https://easysteam.ru/photos/shares/ikomki_v_nabory/zh_ikon.jpg" },
      ],
    },
    {
      id: 3,
      title: "Электрическая паротермальная печь ФутуРус 2.0 10 кВт Cерпентинит Бархат",
      href: "https://vvd.su/product/elektricheskie-pechi-dlya-bani/elektricheskaya-parotermalnaya-pech-parizhar-futurus-v2-0/?oid=1383",
      price: "$8105.77",
      categories: ["Электрические печи", "Облицовочный материал"],
      image1: "https://vvd.su/upload/iblock/69b/6mr1rha0mkezcvh0a0dhfp0mcv561q1m.jpg",
      image2: "https://vvd.su/upload/iblock/b00/g31octd86vfd3f8ath130j4glt6zcqd8.jpg",
      swatches: [
        { slug: "talkohlorit", title: "Талькохлорит", bgColor: "#b7b3a8" },
        { slug: "serpentinite-velvet", title: "Серпентинит Бархат", selected: true, bgColor: "#5d5a55" },
        { slug: "serpentinite-premium", title: "Серпентинит Премиум", bgColor: "#7d7a73" },
        { slug: "pyroxenite-black", title: "Пироксенит чёрный", bgColor: "#222226" },
      ],
    },
  ],
};

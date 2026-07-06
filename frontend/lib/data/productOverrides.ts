import type { ProductPageData } from "@/lib/types/productPage";
import type { ProductSpecsData } from "@/lib/types/productSpecs";

export type ProductOverride = {
  page: Partial<ProductPageData>;
  specs?: ProductSpecsData;
  descriptionHtml?: string;
};

const VDD_SHARED_IMAGES = [
  "https://vvd.su/upload/iblock/69b/6mr1rha0mkezcvh0a0dhfp0mcv561q1m.jpg",
  "https://vvd.su/upload/iblock/b00/g31octd86vfd3f8ath130j4glt6zcqd8.jpg",
  "https://vvd.su/upload/iblock/faa/in0a4lftcvu3ea2ml0oo250ul10gtqdz.jpg",
  "https://vvd.su/upload/iblock/527/leen1wawkxhwj2jc71a8xkzlpp2igblr.jpg",
  "https://vvd.su/upload/iblock/4dc/jp7v725jonmda8zwb5vtg64q6ar2xck0.jpg",
  "https://vvd.su/upload/iblock/c99/125ovu311e6j8yke5dct343i02vzkxpl.jpg",
  "https://vvd.su/upload/iblock/93c/z3693utbbdx0kwf6wj5ygw16e5ma52o8.jpg",
  "https://vvd.su/upload/iblock/37c/50dugjh37e9ir0n4hw7i4ddbuqkrshv2.jpg",
] as const;

const VDD_DOCS = [
  {
    title: "Руководство ФутуРус 2.0",
    url: "https://vvd.su/upload/iblock/701/ikz9hv5w3bwdel0i7rc1171vose63gsa.pdf",
  },
  {
    title: "ТЭНы рекомендации",
    url: "https://vvd.su/upload/iblock/563/gf6r3250285sc1vro5uaz0cy17jt3db3.pdf",
  },
] as const;

const VDD_VIDEOS = [
  "https://vkvideo.ru/video_ext.php?oid=-32855794&id=456240469&hash=",
  "https://vkvideo.ru/video_ext.php?oid=-32855794&id=456240383&hash=",
  "https://vkvideo.ru/video_ext.php?oid=-32855794&id=456240359&hash=",
  "https://vkvideo.ru/video_ext.php?oid=-32855794&id=456240251&hash=03665c169eba7bf0",
  "https://vkvideo.ru/video_ext.php?oid=-32855794&id=456240139&hash=a22d910f43ac89f5",
  "https://vkvideo.ru/video_ext.php?oid=-32855794&id=456239735&hash=",
  "https://vkvideo.ru/video_ext.php?oid=-32855794&id=456239739&hash=",
  "https://vkvideo.ru/video_ext.php?oid=-32855794&id=456240117&hash=",
  "https://vkvideo.ru/video_ext.php?oid=-32855794&id=456240293&hash=9be0ef8d60f7ece9",
] as const;

const VDD_DESCRIPTION_HTML = `
<p><strong>ФутуРус 2.0</strong> — обновленная версия мультирежимной электрической банной печи с интеллектуальной системой управления. ФутуРус 2.0 подстроится под те режимы, с которыми любите париться именно вы.</p>
<p>Оптимизированная функция конвектора и <strong>увеличенная каменка с закладом камней 90-130 кг</strong> позволяет получить еще большее количество качественного управляемого пара.</p>
<p><strong>Обновленный ФутуРус 2.0 имеет возможность подключения автоподачи воды</strong>, что делает поход в баню еще более комфортным и технологичным.</p>
<p>Внешний корпус электрической банной печи ФутуРус V2.0 — облицовка из натурального камня, который всегда сохраняет температуру окружающей среды независимо от того, до какой температуры нагрелись камни в каменке. <strong>Это гарантирует безопасность при случайных прикосновениях к работающей печи.</strong> Инфракрасное излучение от корпуса печи отсутствует, что ценится профессиональными пармастерами, поскольку нет вероятности перегрева тела при длительном нахождении в парной. В частных и домашних банях это преимущество также гарантирует безопасность взрослых и детей.</p>
<p><strong>Встроенный парогенератор</strong> печи оснащён защитой от перегрева: при отсутствии воды парогенератор не включится.</p>
<p>В модификации ФутуРус 2.0 сохранена функция <strong>«чистый воздух»</strong>, принудительная система подачи обеспечивает регулируемое и постоянное присутствие свежего воздуха в парной.</p>
<p>Для подключения печи требуется электропитание и подвод магистральной воды либо наличие бака-накопителя для обеспечения необходимого для работы объёма воды.</p>
<p><strong>Интеллектуальный пульт управления печью имеет 5 заводских настроек:</strong> хаммам, паровая баня, баня, сауна, экстремальная сауна, а также индивидуальные настройки в режиме «ФутуРус». Пульт работает как независимая система управления, монтируется в смежное с парной помещение, <strong>возможно подключение удаленного доступа</strong>, в том числе синхронизация с голосовым помощником «Алиса» и внедрение в эко-систему «Умный дом» (дополнительная опция).</p>
<h3>Документы</h3>
<ul>
  ${VDD_DOCS.map((doc) => `<li><a href="${doc.url}" target="_blank" rel="noopener noreferrer">${doc.title}</a></li>`).join("\n  ")}
</ul>
<h3>Видео</h3>
<ul>
  ${VDD_VIDEOS.map((url, index) => `<li><a href="${url}" target="_blank" rel="noopener noreferrer">Видео ${index + 1}</a></li>`).join("\n  ")}
</ul>
`;

const VDD_OVERRIDE: ProductOverride = {
  page: {
    breadcrumbs: [
      { label: "Главная", href: "/" },
      { label: "Каталог", href: "/catalog" },
      { label: "Электрические печи", href: "/catalog" },
      { label: "Паротермальные электрические печи ФутуРус", href: "/catalog" },
      { label: "Электрическая паротермальная печь ФутуРус 2.0 10 кВт Cерпентинит Бархат" },
    ],
    images: [...VDD_SHARED_IMAGES],
    badges: [{ label: "Лучшая электрическая печь для бани", variant: "default" }],
    title: "Электрическая паротермальная печь ФутуРус 2.0 10 кВт Cерпентинит Бархат",
    categories: [
      { label: "Электрические печи", href: "/catalog" },
      { label: "Паротермальные электрические печи ФутуРус", href: "/catalog" },
    ],
    priceOld: undefined,
    price: 587500,
    currency: "₽",
    sku: "1377",
    brand: "ВВД",
    description: `
      <ul class="hws-product-highlights">
        <li><strong>Мощность:</strong> 10 кВт</li>
        <li><strong>Объём парного помещения:</strong> 10-15 м³</li>
        <li><strong>Объём ёмкости для воды:</strong> 15 л</li>
        <li><strong>Производительность:</strong> 3,9 л/час</li>
        <li><strong>Облицовка:</strong> Серпентинит Бархат</li>
      </ul>
    `,
    variantGroups: [
      {
        key: "power",
        label: "Номинальная потребляемая мощность",
        type: "text",
        options: [
          { value: "10 кВт" },
          { value: "12 кВт" },
          { value: "14 кВт" },
          { value: "16 кВт" },
          { value: "18 кВт" },
          { value: "20 кВт" },
        ],
      },
      {
        key: "facing",
        label: "Облицовочный материал",
        type: "text",
        options: [
          { value: "Серпентинит Бархат" },
          { value: "Серпентинит Премиум" },
          { value: "Пироксенит чёрный" },
          { value: "Талькохлорит" },
        ],
      },
    ],
    variantEntries: [
      { selection: { power: "10 кВт", facing: "Серпентинит Бархат" }, price: 587500, sku: "1383", image: "https://vvd.su/upload/iblock/69b/6mr1rha0mkezcvh0a0dhfp0mcv561q1m.jpg" },
      { selection: { power: "10 кВт", facing: "Серпентинит Премиум" }, price: 597100, sku: "1388", image: "https://vvd.su/upload/iblock/79b/45nc1ub923znhbgv5z30upr7bywpvxp1.jpg" },
      { selection: { power: "10 кВт", facing: "Пироксенит чёрный" }, price: 544800, sku: "1393", image: "https://vvd.su/upload/iblock/33f/4awrona3ysaoe40i12gjjvmtx2a909pk.jpg" },
      { selection: { power: "10 кВт", facing: "Талькохлорит" }, price: 511700, sku: "1378", image: "https://vvd.su/upload/iblock/ea5/vnn0588lki2jc57rapy6nhdn0gxrn2az.jpg" },
      { selection: { power: "12 кВт", facing: "Серпентинит Бархат" }, price: 595100, sku: "1384", image: "https://vvd.su/upload/iblock/69b/6mr1rha0mkezcvh0a0dhfp0mcv561q1m.jpg" },
      { selection: { power: "12 кВт", facing: "Серпентинит Премиум" }, price: 605110, sku: "1389", image: "https://vvd.su/upload/iblock/79b/45nc1ub923znhbgv5z30upr7bywpvxp1.jpg" },
      { selection: { power: "12 кВт", facing: "Пироксенит чёрный" }, price: 553200, sku: "1394", image: "https://vvd.su/upload/iblock/33f/4awrona3ysaoe40i12gjjvmtx2a909pk.jpg" },
      { selection: { power: "12 кВт", facing: "Талькохлорит" }, price: 520000, sku: "1379", image: "https://vvd.su/upload/iblock/ea5/vnn0588lki2jc57rapy6nhdn0gxrn2az.jpg" },
      { selection: { power: "14 кВт", facing: "Серпентинит Бархат" }, price: 603600, sku: "1385", image: "https://vvd.su/upload/iblock/69b/6mr1rha0mkezcvh0a0dhfp0mcv561q1m.jpg" },
      { selection: { power: "14 кВт", facing: "Серпентинит Премиум" }, price: 613300, sku: "1390", image: "https://vvd.su/upload/iblock/79b/45nc1ub923znhbgv5z30upr7bywpvxp1.jpg" },
      { selection: { power: "14 кВт", facing: "Пироксенит чёрный" }, price: 561700, sku: "1395", image: "https://vvd.su/upload/iblock/33f/4awrona3ysaoe40i12gjjvmtx2a909pk.jpg" },
      { selection: { power: "14 кВт", facing: "Талькохлорит" }, price: 528600, sku: "1380", image: "https://vvd.su/upload/iblock/ea5/vnn0588lki2jc57rapy6nhdn0gxrn2az.jpg" },
      { selection: { power: "16 кВт", facing: "Серпентинит Бархат" }, price: 611700, sku: "1386", image: "https://vvd.su/upload/iblock/69b/6mr1rha0mkezcvh0a0dhfp0mcv561q1m.jpg" },
      { selection: { power: "16 кВт", facing: "Серпентинит Премиум" }, price: 621300, sku: "1391", image: "https://vvd.su/upload/iblock/79b/45nc1ub923znhbgv5z30upr7bywpvxp1.jpg" },
      { selection: { power: "16 кВт", facing: "Пироксенит чёрный" }, price: 563300, sku: "1396", image: "https://vvd.su/upload/iblock/33f/4awrona3ysaoe40i12gjjvmtx2a909pk.jpg" },
      { selection: { power: "16 кВт", facing: "Талькохлорит" }, price: 536700, sku: "1381", image: "https://vvd.su/upload/iblock/ea5/vnn0588lki2jc57rapy6nhdn0gxrn2az.jpg" },
      { selection: { power: "18 кВт", facing: "Серпентинит Бархат" }, price: 619700, sku: "1387", image: "https://vvd.su/upload/iblock/69b/6mr1rha0mkezcvh0a0dhfp0mcv561q1m.jpg" },
      { selection: { power: "18 кВт", facing: "Серпентинит Премиум" }, price: 629400, sku: "1392", image: "https://vvd.su/upload/iblock/79b/45nc1ub923znhbgv5z30upr7bywpvxp1.jpg" },
      { selection: { power: "18 кВт", facing: "Пироксенит чёрный" }, price: 578400, sku: "1397", image: "https://vvd.su/upload/iblock/33f/4awrona3ysaoe40i12gjjvmtx2a909pk.jpg" },
      { selection: { power: "18 кВт", facing: "Талькохлорит" }, price: 545300, sku: "1382", image: "https://vvd.su/upload/iblock/ea5/vnn0588lki2jc57rapy6nhdn0gxrn2az.jpg" },
      { selection: { power: "20 кВт", facing: "Серпентинит Бархат" }, price: 627800, sku: "1400", image: "https://vvd.su/upload/iblock/69b/6mr1rha0mkezcvh0a0dhfp0mcv561q1m.jpg" },
      { selection: { power: "20 кВт", facing: "Серпентинит Премиум" }, price: 637300, sku: "1401", image: "https://vvd.su/upload/iblock/79b/45nc1ub923znhbgv5z30upr7bywpvxp1.jpg" },
      { selection: { power: "20 кВт", facing: "Пироксенит чёрный" }, price: 586900, sku: "1398", image: "https://vvd.su/upload/iblock/33f/4awrona3ysaoe40i12gjjvmtx2a909pk.jpg" },
      { selection: { power: "20 кВт", facing: "Талькохлорит" }, price: 553600, sku: "1399", image: "https://vvd.su/upload/iblock/ea5/vnn0588lki2jc57rapy6nhdn0gxrn2az.jpg" },
    ],
  },
  specs: {
    sectionTitle: "Характеристики",
    groups: [
      {
        title: "Характеристики",
        rows: [
          { label: "Номинальная потребляемая мощность", value: "10 кВт" },
          { label: "Облицовочный материал", value: "Серпентинит Бархат" },
          { label: "Габариты ШхГхВ, мм", value: "683х590х1052" },
          { label: "Вес печи, кг", value: "90" },
          { label: "Вес облицовки, кг", value: "256" },
          { label: "Вес печи в облицовке, кг", value: "346" },
          { label: "Вес заклада камней, кг", value: "80-90" },
          { label: "Объём ёмкости для воды, л", value: "15" },
          { label: "Производительность (по расходу воды), л/час", value: "3,9" },
          { label: "Объём парного помещения, м³", value: "10-15" },
        ],
      },
    ],
  },
  descriptionHtml: VDD_DESCRIPTION_HTML,
};

const PRODUCT_OVERRIDES: Record<string, ProductOverride> = {
  "vvd-elektricheskaya-parotermalnaya-pech-parizhar-futurus-v2-0": VDD_OVERRIDE,
};

export function getProductOverride(slug: string): ProductOverride | undefined {
  return PRODUCT_OVERRIDES[slug];
}

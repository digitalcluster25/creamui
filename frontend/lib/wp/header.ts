import { getClient } from "@/lib/wp/apollo";
import { GET_PRODUCT_CATEGORIES } from "@/lib/wp/queries";
import { headerMock } from "@/lib/mocks/header";
import type { HeaderData, HeaderCatalogItem } from "@/lib/types/header";

export type WPCategoryNode = {
  databaseId: number;
  name: string;
  slug: string;
  hwsSubtitle?: string | null;
  image?: { sourceUrl: string; altText?: string | null } | null;
  count: number | null;
  children?: { nodes: WPCategoryChildNode[] };
};

export type WPCategoryChildNode = {
  databaseId: number;
  name: string;
  slug: string;
  hwsSubtitle?: string | null;
  image?: { sourceUrl: string; altText?: string | null } | null;
  count: number | null;
};

// Статичная карта иконок по slug категории — миниатюры категорий в WP не
// заполнены (image всегда null), поэтому иконки мега-меню держим на фронте.
const CATEGORY_ICONS: Record<string, string> = {
  "russian-bath-stoves": "/assets/sauna.png",
  "sauna-stoves": "/assets/sauna.png",
  "steam-generators-and-hammam": "/assets/steam.png",
  commercial: "/assets/heater.png",
  "control-units": "/assets/heater.png",
  "chimneys-and-installation": "/assets/heater.png",
  "water-tanks-and-heat-exchangers": "/assets/heater.png",
  "stones-and-cladding": "/assets/sauna.png",
  accessories: "/assets/acs.png",
};
const DEFAULT_ICON = "/assets/sauna.png";

const HEADER_CATEGORY_ORDER = [
  "russian-bath-stoves",
  "sauna-stoves",
  "steam-generators-and-hammam",
  "commercial",
  "control-units",
  "chimneys-and-installation",
  "water-tanks-and-heat-exchangers",
  "stones-and-cladding",
  "accessories",
] as const;

// Разворачивает дерево категорий (parent + children) в плоский список —
// используется и тут для меню, и в /catalog/[category] для поиска категории.
export function flattenCategories(
  nodes: WPCategoryNode[],
): (WPCategoryNode | WPCategoryChildNode)[] {
  return nodes.flatMap((node) => [node, ...(node.children?.nodes ?? [])]);
}

export async function getHeaderData(): Promise<HeaderData> {
  try {
    const client = getClient();
    const { data } = await client.query<{ productCategories: { nodes: WPCategoryNode[] } }>({
      query: GET_PRODUCT_CATEGORIES,
    });

    const parents = data?.productCategories?.nodes ?? [];
    const bySlug = new Map(parents.map((node) => [node.slug, node]));

    // Каноническое меню каталога строим по верхнему уровню taxonomy, а не по
    // произвольному раскрытию children. Это синхронизирует storefront с новой
    // архитектурой каталога и не дублирует intent-ветки.
    const megaMenu: HeaderCatalogItem[] = HEADER_CATEGORY_ORDER
      .map((slug) => bySlug.get(slug))
      .filter((node): node is WPCategoryNode => Boolean(node))
      .map((node) => ({
        id: node.slug,
        label: node.name,
        href: `/catalog/${node.slug}`,
        iconSrc: CATEGORY_ICONS[node.slug] ?? DEFAULT_ICON,
      }));

    if (!megaMenu.length) {
      return { ...headerMock, brandHref: "/" };
    }

    return {
      ...headerMock,
      brandHref: "/",
      primaryNav: headerMock.primaryNav.map((item) =>
        item.id === "catalog"
          ? { ...item, href: "/catalog", megaMenu }
          : item.id === "brands"
            ? { ...item, href: "/brands" }
            : item.id === "contacts"
              ? { ...item, href: "/contacts" }
            : item,
      ),
    };
  } catch (e) {
    console.error("WP GraphQL error (header categories):", e);
    return { ...headerMock, brandHref: "/" };
  }
}

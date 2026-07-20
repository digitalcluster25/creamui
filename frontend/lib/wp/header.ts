import { cache } from "react";
import { gql } from "@apollo/client";
import { headerMock } from "@/lib/mocks/header";
import type { HeaderData, HeaderCatalogItem, HeaderNavItem } from "@/lib/types/header";
import { getProductCategoriesTree } from "@/lib/wp/catalog-taxonomy";
import { getClient } from "@/lib/wp/apollo";

export type WPCategoryNode = {
  databaseId: number;
  name: string;
  slug: string;
  hwsSubtitle?: string | null;
  hwsImageUrl?: string | null;
  image?: { sourceUrl: string; altText?: string | null } | null;
  count: number | null;
  children?: { nodes: WPCategoryChildNode[] };
};

export type WPCategoryChildNode = {
  databaseId: number;
  name: string;
  slug: string;
  hwsSubtitle?: string | null;
  hwsImageUrl?: string | null;
  image?: { sourceUrl: string; altText?: string | null } | null;
  count: number | null;
};

// Статичная карта иконок по slug категории — миниатюры категорий в WP не
// заполнены (image всегда null), поэтому иконки мега-меню держим на фронте.
const CATEGORY_ICONS: Record<string, string> = {
  "russian-bath-stoves": "/assets/menu-russian-bath.png",
  "sauna-stoves": "/assets/menu-sauna.png",
  "steam-generators-and-hammam": "/assets/menu-hammam.png",
  commercial: "/assets/menu-commercial.png",
  "control-units": "/assets/heater.png",
  "chimneys-and-installation": "/assets/heater.png",
  "water-tanks-and-heat-exchangers": "/assets/heater.png",
  "stones-and-cladding": "/assets/sauna.png",
  accessories: "/assets/acs.png",
};
const DEFAULT_ICON = "/assets/sauna.png";

const GET_HEADER_MENU = gql`
  query GetHeaderMenu {
    menus(first: 50) {
      nodes {
        locations
        menuItems(first: 100) {
          nodes { id label url parentId }
        }
      }
    }
  }
`;

type WPMenuItem = { id: string; label: string; url: string; parentId?: string | null };
type WPMenuNode = { locations: string[]; menuItems: { nodes: WPMenuItem[] } };

function normalizeHref(url: string): string {
  let path = url.replace("https://wpsandbox.spaces.community", "") || "/";
  path = path.replace(/^\/product-category\/([^/]+)\/?$/, "/catalog/$1");
  path = path.replace(/\/$/, "") || "/";
  return path;
}

function toNavItems(items: WPMenuItem[], parentId: string | null = null): HeaderNavItem[] {
  return items
    .filter((item) => (item.parentId ?? null) === parentId)
    .map((item) => {
      const children = toNavItems(items, item.id);
      return {
        id: item.id,
        label: item.label,
        href: normalizeHref(item.url),
        ...(children.length ? { children } : {}),
      };
    });
}

export const HEADER_CATEGORY_ORDER = [
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

const getHeaderDataCached = cache(async (): Promise<HeaderData> => {
  try {
    const menuResult = await getClient({ noStore: true }).query<{ menus: { nodes: WPMenuNode[] } }>({
      query: GET_HEADER_MENU,
    });
    const mainMenu = menuResult.data?.menus.nodes.find((menu) =>
      menu.locations.some((location) => ["MENU_1", "PRIMARY", "PRIMARY_MENU"].includes(location)),
    );

    const parents = await getProductCategoriesTree();
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

    if (!megaMenu.length && !mainMenu) {
      return { ...headerMock, brandHref: "/" };
    }

    const primaryNav = mainMenu
      ? toNavItems(mainMenu.menuItems.nodes).map((item) =>
          item.href === "/catalog"
            ? { ...item, megaMenu }
            : item,
        )
      : headerMock.primaryNav;

    return {
      ...headerMock,
      brandHref: "/",
      primaryNav,
    };
  } catch (e) {
    console.error("WP GraphQL error (header categories):", e);
    return { ...headerMock, brandHref: "/" };
  }
});

export function getHeaderData(): Promise<HeaderData> {
  return getHeaderDataCached();
}

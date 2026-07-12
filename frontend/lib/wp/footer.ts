import { cache } from "react";
import { getClient } from "@/lib/wp/apollo";
import { gql } from "@apollo/client";
import type { FooterData } from "@/lib/types/footer";
import { footerData as staticFooterData } from "@/lib/data/footer";

const WP_ORIGIN = "https://wpsandbox.spaces.community";

const GET_FOOTER_MENUS = gql`
  query GetFooterMenus {
    products: menu(id: "FOOTER_PRODUCTS", idType: LOCATION) {
      menuItems(first: 50) {
        nodes { label url }
      }
    }
    company: menu(id: "FOOTER_COMPANY", idType: LOCATION) {
      menuItems(first: 50) {
        nodes { label url }
      }
    }
    help: menu(id: "FOOTER_HELP", idType: LOCATION) {
      menuItems(first: 50) {
        nodes { label url }
      }
    }
  }
`;

type MenuItem = { label: string; url: string };
type MenuResult = { menuItems: { nodes: MenuItem[] } } | null;

function normalizeHref(url: string): string {
  let path = url.replace(WP_ORIGIN, "") || "/";
  // WP product category URLs → Next.js catalog routes
  path = path.replace(/^\/product-category\/([^/]+)\/?.*$/, "/catalog/$1");
  // strip trailing slash
  path = path.replace(/\/$/, "") || "/";
  return path;
}

function toLinks(menu: MenuResult, idPrefix: string) {
  if (!menu) return [];
  return menu.menuItems.nodes.map((item, i) => ({
    id: `${idPrefix}-${i}`,
    label: item.label,
    href: normalizeHref(item.url),
  }));
}

export const getFooterData = cache(async (): Promise<FooterData> => {
  try {
    const client = getClient();
    type FooterMenusQuery = {
      products: MenuResult;
      company: MenuResult;
      help: MenuResult;
    };
    const result = await client.query<FooterMenusQuery>({
      query: GET_FOOTER_MENUS,
      context: { fetchOptions: { next: { revalidate: 3600 } } },
    });
    const data = result.data;

    return {
      ...staticFooterData,
      columns: [
        {
          id: "products",
          title: staticFooterData.columns[0].title,
          links: toLinks(data?.products ?? null, "p"),
        },
        {
          id: "company",
          title: staticFooterData.columns[1].title,
          links: toLinks(data?.company ?? null, "c"),
        },
        {
          id: "help",
          title: staticFooterData.columns[2].title,
          links: toLinks(data?.help ?? null, "h"),
        },
      ],
    };
  } catch {
    return staticFooterData;
  }
});

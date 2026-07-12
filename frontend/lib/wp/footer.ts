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

function toLinks(menu: MenuResult, idPrefix: string) {
  if (!menu) return [];
  return menu.menuItems.nodes.map((item, i) => ({
    id: `${idPrefix}-${i}`,
    label: item.label,
    href: item.url.replace(WP_ORIGIN, "") || "/",
  }));
}

export const getFooterData = cache(async (): Promise<FooterData> => {
  try {
    const client = getClient();
    const { data } = await client.query<{
      products: MenuResult;
      company: MenuResult;
      help: MenuResult;
    }>({
      query: GET_FOOTER_MENUS,
      context: { fetchOptions: { next: { revalidate: 3600 } } },
    });

    return {
      ...staticFooterData,
      columns: [
        {
          id: "products",
          title: staticFooterData.columns[0].title,
          links: toLinks(data.products, "p"),
        },
        {
          id: "company",
          title: staticFooterData.columns[1].title,
          links: toLinks(data.company, "c"),
        },
        {
          id: "help",
          title: staticFooterData.columns[2].title,
          links: toLinks(data.help, "h"),
        },
      ],
    };
  } catch {
    return staticFooterData;
  }
});

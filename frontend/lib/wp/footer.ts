import { cache } from "react";
import { getClient } from "@/lib/wp/apollo";
import { gql } from "@apollo/client";
import type { FooterData } from "@/lib/types/footer";
import { footerData as staticFooterData } from "@/lib/data/footer";
import { ACTIVE_CATALOG_CATEGORY_SLUGS } from "@/lib/wp/catalog-taxonomy";

const WP_ORIGIN = "https://wpsandbox.spaces.community";
const SITE_ORIGIN = "https://hws.shopping";

const GET_FOOTER_MENUS = gql`
  query GetFooterMenus {
    menus(first: 50) {
      nodes {
        locations
        menuItems(first: 50) {
          nodes { label url }
        }
      }
    }
  }
`;

type MenuItem = { label: string; url: string };
type MenuResult = { menuItems: { nodes: MenuItem[] } } | null;
type MenuNode = { locations: string[]; menuItems: { nodes: MenuItem[] } };

function normalizeHref(url: string): string {
  let path = url;
  try {
    const parsed = new URL(url);
    if (parsed.origin === WP_ORIGIN || parsed.origin === SITE_ORIGIN) {
      path = `${parsed.pathname}${parsed.search}${parsed.hash}`;
    }
  } catch {
    // Keep relative or otherwise non-URL menu values unchanged.
  }
  path = path || "/";
  // WP product category URLs → Next.js catalog routes
  path = path.replace(/^\/product-category\/([^/]+)\/?.*$/, "/catalog/$1");
  // strip trailing slash
  path = path.replace(/\/$/, "") || "/";
  return path;
}

function toLinks(menu: MenuResult, idPrefix: string) {
  if (!menu) return [];
  return menu.menuItems.nodes
    .filter((item) => {
      const match = item.url.match(/\/product-category\/([^/?#]+)/);
      return !match || ACTIVE_CATALOG_CATEGORY_SLUGS.includes(match[1] as (typeof ACTIVE_CATALOG_CATEGORY_SLUGS)[number]);
    })
    .map((item, i) => ({
    id: `${idPrefix}-${i}`,
    label: item.label,
    href: normalizeHref(item.url),
    }));
}

export const getFooterData = cache(async (): Promise<FooterData> => {
  try {
    // Menu edits must be visible immediately; WordPress menu changes are not
    // covered by the catalog revalidation webhook.
    const client = getClient({ noStore: true });
    type FooterMenusQuery = {
      menus: { nodes: MenuNode[] };
    };
    const result = await client.query<FooterMenusQuery>({
      query: GET_FOOTER_MENUS,
    });
    const menus = result.data?.menus?.nodes ?? [];
    const byLocation = (location: string): MenuResult =>
      menus.find((menu) => menu.locations.includes(location)) ?? null;

    return {
      ...staticFooterData,
      columns: [
        {
          id: "products",
          title: staticFooterData.columns[0].title,
          links: toLinks(byLocation("FOOTER_PRODUCTS"), "p"),
        },
        {
          id: "company",
          title: staticFooterData.columns[1].title,
          links: toLinks(byLocation("FOOTER_COMPANY"), "c"),
        },
        {
          id: "help",
          title: staticFooterData.columns[2].title,
          links: toLinks(byLocation("FOOTER_HELP"), "h"),
        },
      ],
    };
  } catch {
    return staticFooterData;
  }
});

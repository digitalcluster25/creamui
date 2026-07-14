import { Header } from "@/components/sections/header";
import { KnowledgeBase } from "@/components/sections/knowledge-base";
import { Footer } from "@/components/sections/footer";
import { Breadcrumbs } from "@/components/primitives/breadcrumbs/Breadcrumbs";
import { getHeaderData } from "@/lib/wp/header";
import { getFooterData } from "@/lib/wp/footer";
import { getClient } from "@/lib/wp/apollo";
import { GET_POSTS, GET_SITE_TEXTS } from "@/lib/wp/queries";
import { mapToBlogPost, type WPPostNode } from "@/lib/wp/mappers";
import styles from "./page.module.css";

export const dynamic = "force-dynamic";

// Раздел "База знаний" привязан к категории WP "home-wood-spa"
// (https://wpsandbox.spaces.community/category/home-wood-spa/) — там сейчас
// все материалы, которые есть в админке.
const KNOWLEDGE_CATEGORY = "home-wood-spa";

export default async function KnowledgePage() {
  const footerData = await getFooterData();
  let posts: ReturnType<typeof mapToBlogPost>[] = [];
  let knowledgeTitle = "База знаний";
  try {
    const client = getClient();
    const [postsResult, textsResult] = await Promise.all([
      client.query<{ posts: { nodes: WPPostNode[] } }>({
        query: GET_POSTS,
        variables: { categoryName: KNOWLEDGE_CATEGORY, first: 100 },
      }),
      client.query<{ hwsSiteTexts: { knowledgePageTitle?: string | null } }>({ query: GET_SITE_TEXTS }).catch(() => null),
    ]);
    posts = (postsResult.data?.posts?.nodes ?? []).map(mapToBlogPost);
    knowledgeTitle = textsResult?.data?.hwsSiteTexts?.knowledgePageTitle ?? "База знаний";
  } catch (e) {
    console.error("WP GraphQL error (knowledge base):", e);
  }

  const headerData = await getHeaderData();

  return (
    <main>
      <Header data={headerData} hideBurgerOnDesktop hideActionsOnDesktop />
      <Breadcrumbs items={[{ label: "Главная", href: "/" }, { label: "База знаний" }]} />
      <div className={styles.section}>
        <KnowledgeBase data={{ title: knowledgeTitle, allHref: "/knowledge", posts }} />
      </div>
      <div className={styles.sectionFooter}>
        <Footer data={footerData} />
      </div>
    </main>
  );
}

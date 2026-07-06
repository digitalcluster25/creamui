import { Header } from "@/components/sections/header";
import { KnowledgeBase } from "@/components/sections/knowledge-base";
import { Footer } from "@/components/sections/footer";
import { getHeaderData } from "@/lib/wp/header";
import { footerData } from "@/lib/data/footer";
import { getClient } from "@/lib/wp/apollo";
import { GET_POSTS } from "@/lib/wp/queries";
import { mapToBlogPost, type WPPostNode } from "@/lib/wp/mappers";
import styles from "./page.module.css";

export const revalidate = 3600;

// Раздел "База знаний" привязан к категории WP "home-wood-spa"
// (https://wpsandbox.spaces.community/category/home-wood-spa/) — там сейчас
// все материалы, которые есть в админке.
const KNOWLEDGE_CATEGORY = "home-wood-spa";

export default async function KnowledgePage() {
  let posts: ReturnType<typeof mapToBlogPost>[] = [];
  try {
    const client = getClient();
    const { data } = await client.query<{ posts: { nodes: WPPostNode[] } }>({
      query: GET_POSTS,
      variables: { categoryName: KNOWLEDGE_CATEGORY, first: 100 },
    });
    posts = (data?.posts?.nodes ?? []).map(mapToBlogPost);
  } catch (e) {
    console.error("WP GraphQL error (knowledge base):", e);
  }

  const headerData = await getHeaderData();

  return (
    <main>
      <Header data={headerData} hideBurgerOnDesktop hideActionsOnDesktop />
      <div className={styles.section}>
        <KnowledgeBase data={{ title: "База знаний", allHref: "/knowledge", posts }} />
      </div>
      <div className={styles.sectionFooter}>
        <Footer data={footerData} />
      </div>
    </main>
  );
}

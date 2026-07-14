import { notFound, permanentRedirect } from "next/navigation";
import { Header } from "@/components/sections/header";
import { ArticlePage } from "@/components/sections/article-page";
import { Footer } from "@/components/sections/footer";
import { getHeaderData } from "@/lib/wp/header";
import { getFooterData } from "@/lib/wp/footer";
import { getClient } from "@/lib/wp/apollo";
import { GET_POST_BY_SLUG, GET_POST_SLUGS, GET_POSTS } from "@/lib/wp/queries";
import { mapToArticlePageData, mapToBlogPost, type WPPostNode } from "@/lib/wp/mappers";
import styles from "./page.module.css";

export const dynamic = "force-dynamic";

const KNOWLEDGE_CATEGORY = "home-wood-spa";

type Params = { slug: string };

export async function generateStaticParams(): Promise<Params[]> {
  return [];
}

export default async function KnowledgeArticlePage({
  params,
}: {
  params: Promise<Params>;
}) {
  const footerData = await getFooterData();
  const { slug: rawSlug } = await params;
  const slug = decodeURIComponent(rawSlug);

  const client = getClient();
  const { data } = await client.query<{ post: WPPostNode | null }>({
    query: GET_POST_BY_SLUG,
    variables: { slug },
    errorPolicy: "all",
  });

  if (!data?.post) {
    try {
      const { data: fallbackData } = await client.query<{ posts: { nodes: WPPostNode[] } }>({
        query: GET_POSTS,
        variables: { categoryName: KNOWLEDGE_CATEGORY, first: 100 },
      });
      const canonicalPost = (fallbackData?.posts?.nodes ?? []).find((post) => slug.startsWith(post.slug));
      if (canonicalPost) {
        permanentRedirect(`/knowledge/${canonicalPost.slug}`);
      }
    } catch (e) {
      console.error("WP GraphQL error (knowledge fallback by slug):", e);
    }

    notFound();
  }

  const headerData = await getHeaderData();
  const articleData = mapToArticlePageData(data.post);

  let relatedPosts: ReturnType<typeof mapToBlogPost>[] = [];
  try {
    const { data: relatedData } = await client.query<{ posts: { nodes: WPPostNode[] } }>({
      query: GET_POSTS,
      variables: { categoryName: KNOWLEDGE_CATEGORY, first: 4 },
    });
    relatedPosts = (relatedData?.posts?.nodes ?? [])
      .filter((n) => n.slug !== slug)
      .slice(0, 3)
      .map(mapToBlogPost);
  } catch (e) {
    console.error("WP GraphQL error (related knowledge posts):", e);
  }

  return (
    <main>
      <Header data={headerData} hideBurgerOnDesktop hideActionsOnDesktop />
      <div className={styles.section}>
        <ArticlePage data={articleData} relatedPosts={relatedPosts} />
      </div>
      <div className={styles.sectionFooter}>
        <Footer data={footerData} />
      </div>
    </main>
  );
}

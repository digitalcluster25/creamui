import type { BlogPostsData } from "@/lib/types/blogPosts";
import { BlogPostCard } from "@/components/blocks/blog-post-card";
import styles from "./KnowledgeBase.module.css";

// Простая сетка статей + заголовок раздела, без сайдбара с фильтрами —
// на сайте пока одна категория статей ("Дом. Дерево. SPA"), фильтровать
// пока не по чему, поэтому сайдбар из Ohio Editorial здесь избыточен.
export function KnowledgeBase({ data }: { data: BlogPostsData }) {
  return (
    <section className={styles.section}>
      <h1 className={styles.pageTitle}>{data.title}</h1>
      {data.posts.length === 0 ? (
        <p className={styles.empty}>Материалы скоро появятся.</p>
      ) : (
        <div className={styles.grid}>
          {data.posts.map((post) => (
            <BlogPostCard key={post.id} post={post} />
          ))}
        </div>
      )}
    </section>
  );
}

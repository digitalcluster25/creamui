import type { BlogPostsData } from "@/lib/types/blogPosts";
import { BlogPostCard } from "@/components/blocks/blog-post-card";
import styles from "./BlogPosts.module.css";

export function BlogPosts({ data }: { data: BlogPostsData }) {
  return (
    <section className={styles.section}>
      <div className={styles.head}>
        <h2 className={styles.title}>{data.title}</h2>
        <a href={data.allHref} className={`${styles.allLink} ${styles.allLinkHead}`}>
          Смотреть все статьи
          <svg viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" />
          </svg>
        </a>
      </div>
      <div className={styles.col}>
        <div className={styles.grid}>
          {data.posts.map((post) => (
            <BlogPostCard key={post.id} post={post} />
          ))}
        </div>
        <a href={data.allHref} className={`${styles.allLink} ${styles.allLinkBottom}`}>
          Смотреть все статьи
          <svg viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" />
          </svg>
        </a>
      </div>
    </section>
  );
}

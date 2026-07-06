import type { BlogPost } from "@/lib/types/blogPosts";
import styles from "./BlogPostCard.module.css";

export function BlogPostCard({ post }: { post: BlogPost }) {
  return (
    <article className={styles.card}>
      <div className={styles.imageHolder}>
        {/* eslint-disable-next-line @next/next/no-img-element */}
        <img src={post.image} alt={post.title} loading="lazy" />
        <div className={styles.overlayDetails}>
          <ul className={styles.metaHolder}>
            <li className={styles.metaItem}>
              <span className={styles.metaPrefix}>Опубликовано</span>
              <span className={styles.metaAuthor}>{post.author}</span>
            </li>
          </ul>
        </div>
      </div>
      <div className={styles.cardDetails}>
        <div className={styles.headlineMeta}>
          {post.date && <span className={styles.date}>{post.date}</span>}
          <span className={styles.readTime}>{post.readTime}</span>
        </div>
        <h3 className={styles.cardTitle}>
          <a href={post.href}>{post.title}</a>
        </h3>
        <p className={styles.cardExcerpt}>{post.excerpt}</p>
        <ul className={styles.tags}>
          {post.tags.map((tag) => (
            <li key={tag} className={styles.tag}>{tag}</li>
          ))}
        </ul>
      </div>
    </article>
  );
}

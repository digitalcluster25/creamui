import type { ArticlePageData } from "@/lib/wp/mappers";
import type { BlogPost } from "@/lib/types/blogPosts";
import { BlogPostCard } from "@/components/blocks/blog-post-card";
import styles from "./ArticlePage.module.css";

type Props = {
  data: ArticlePageData;
  relatedPosts?: BlogPost[];
};

// Структура повторяет реальный шаблон Ohio (single.php + parts/elements/page_headline.php,
// сверено напрямую с файлами темы на wpsandbox.spaces.community, не с догадок по
// скриншоту): плоский текстовый headline БЕЗ фонового/hero-изображения — фичевая
// картинка в оригинале нигде не выводится на странице статьи, только текст +
// инлайн-картинки внутри contentHtml. Мета: категория + время чтения одной строкой
// с "•"-разделителем (var(--clb-color-primary) в оригинале — #D91842), затем
// h1, затем блок автор/дата (аватар 48px = --clb-circle-small: 3rem).
export function ArticlePage({ data, relatedPosts = [] }: Props) {
  return (
    <article>
      <div className={styles.headline}>
        <div className={styles.headlineMeta}>
          {data.category && (
            <a href={data.category.href} className={styles.category}>
              {data.category.name}
            </a>
          )}
          <span className={styles.readEstimate}>{data.readTime}</span>
        </div>

        <h1 className={styles.title}>{data.title}</h1>

        <ul className={styles.metaHolder}>
          <li className={styles.metaItemFlex}>
            {data.authorAvatar && (
              // eslint-disable-next-line @next/next/no-img-element
              <img className={styles.avatar} src={data.authorAvatar} alt={data.author} />
            )}
            <div className={styles.authorDetails}>
              <span className={styles.prefix}>Автор</span>
              <span className={styles.metaValue}>{data.author}</span>
            </div>
          </li>
          <li className={styles.metaItem}>
            <span className={styles.prefix}>Опубликовано</span>
            <span className={styles.metaValue}>{data.date}</span>
          </li>
        </ul>
      </div>

      <nav className={styles.breadcrumbs} aria-label="Хлебные крошки">
        <a href="/">Главная</a>
        <span className={styles.crumbSep}>/</span>
        <a href="/knowledge">База знаний</a>
        <span className={styles.crumbSep}>/</span>
        <span>{data.title}</span>
      </nav>

      <div className={styles.wrapper}>
        {data.contentHtml && (
          <div className={styles.content} dangerouslySetInnerHTML={{ __html: data.contentHtml }} />
        )}

        {data.tags.length > 0 && (
          <div className={styles.tagsRow}>
            <span className={styles.tagsCaption}>Отмечено:</span>
            <ul className={styles.tags}>
              {data.tags.map((tag) => (
                <li key={tag} className={styles.tag}>{tag}</li>
              ))}
            </ul>
          </div>
        )}
      </div>

      {relatedPosts.length > 0 && (
        <div className={styles.related}>
          <h4 className={styles.relatedTitle}>Похожие материалы</h4>
          <div className={styles.relatedGrid}>
            {relatedPosts.map((post) => (
              <BlogPostCard key={post.id} post={post} />
            ))}
          </div>
        </div>
      )}
    </article>
  );
}

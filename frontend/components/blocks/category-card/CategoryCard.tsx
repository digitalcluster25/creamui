import Image from "next/image";
import styles from "./CategoryCard.module.css";

export type CategoryTag = {
  label: string;
  href?: string;
};

export type CategoryCardProps = {
  /** Image src URL */
  image: string;
  /** Image alt text */
  imageAlt?: string;
  /** Link URL for image and title */
  href?: string;
  /** Small subtitle above the title (headline-meta / .date) */
  subtitle?: string;
  /** Main heading / category name */
  title: string;
  /** List of tag chips rendered below the title */
  tags?: CategoryTag[];
};

/**
 * CategoryCard — Ohio demo19 category-banner card.
 *
 * Replicates:
 *   .blog-item.card.-layout2.category-banner
 *     figure.image-holder
 *       a > img
 *       .overlay-details
 *         .headline-meta.-small-t > .date
 *         h4.heading.title > a
 *         .category-holder.-with-tag > a.tag
 */
export function CategoryCard({
  image,
  imageAlt = "",
  href = "#",
  subtitle,
  title,
  tags = [],
}: CategoryCardProps) {
  return (
    <div className={styles.card}>
      <figure className={styles.imageHolder}>
        <a href={href} style={{ display: "contents" }}>
          <Image
            src={image}
            alt={imageAlt}
            fill
            sizes="(max-width: 860px) 100vw, (max-width: 1200px) 50vw, 33vw"
          />
        </a>
        <div className={styles.overlayDetails}>
          {subtitle && (
            <div className={styles.headlineMeta}>
              <div className={styles.date}>{subtitle}</div>
            </div>
          )}
          <h4 className={styles.heading}>
            <a href={href}>{title}</a>
          </h4>
          {tags.length > 0 && (
            <div className={styles.categoryHolder}>
              {tags.map((tag, i) => (
                <a key={i} className={styles.tag} href={tag.href ?? "#"}>
                  {tag.label}
                </a>
              ))}
            </div>
          )}
        </div>
      </figure>
    </div>
  );
}

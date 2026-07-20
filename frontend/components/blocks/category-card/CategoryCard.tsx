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
  /** Browser loading hint for above-the-fold cards */
  imageLoading?: "eager" | "lazy";
  /** Browser fetch priority hint for LCP-sensitive cards */
  imageFetchPriority?: "high" | "low" | "auto";
  /** Next image priority for the very first cards */
  imagePriority?: boolean;
  /** Visual treatment for catalog navigation cards */
  variant?: "image" | "menu";
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
  imageLoading = "lazy",
  imageFetchPriority = "auto",
  imagePriority = false,
  variant = "image",
}: CategoryCardProps) {
  const normalizedImage = normalizeImageSrc(image);

  if (variant === "menu") {
    return (
      <div className={`${styles.card} ${styles.cardMenu}`}>
        <div className={styles.menuContent}>
          <h4 className={`${styles.heading} ${styles.menuHeading}`}>
            <a href={href}>{title}</a>
          </h4>
          {tags.length > 0 && (
            <div className={styles.categoryHolder}>
              {tags.map((tag, i) => (
                <a key={i} className={`${styles.tag} ${styles.menuTag}`} href={tag.href ?? "#"}>
                  {tag.label}
                </a>
              ))}
            </div>
          )}
        </div>
      </div>
    );
  }

  return (
    <div className={styles.card}>
      <figure className={styles.imageHolder}>
        <a href={href} style={{ display: "contents" }}>
          <Image
            src={normalizedImage}
            alt={imageAlt}
            fill
            sizes="(max-width: 860px) 100vw, (max-width: 1200px) 50vw, 33vw"
            quality={60}
            className={styles.image}
            loading={imagePriority ? undefined : imageLoading}
            fetchPriority={imageFetchPriority}
            priority={imagePriority}
          />
        </a>
        <div className={styles.overlayDetails}>
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

function normalizeImageSrc(value: string): string {
  if (!value) return "";
  try {
    return value.startsWith("http://") || value.startsWith("https://")
      ? encodeURI(value)
      : value;
  } catch {
    return value;
  }
}

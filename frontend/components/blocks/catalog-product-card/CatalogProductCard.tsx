import styles from "./CatalogProductCard.module.css";

type Props = {
  href: string;
  image: string;
  title: string;
  category: string;
  priceMin: number;
  priceMax: number;
  currency: string;
};

function formatPrice(n: number) {
  return n.toLocaleString("en-US");
}

export function CatalogProductCard({ href, image, title, category, priceMin, priceMax, currency }: Props) {
  return (
    <div className={styles.card}>
      <a href={href} className={styles.imageLink}>
        <div className={styles.imageWrap}>
          {/* eslint-disable-next-line @next/next/no-img-element */}
          <img src={image} alt={title} className={styles.image} />
        </div>
      </a>
      <div className={styles.info}>
        <a href={href} className={styles.titleLink}>
          <h2 className={styles.title}>{title}</h2>
        </a>
        <p className={styles.category}>{category}</p>
        <p className={styles.price}>
          {currency}{formatPrice(priceMin)} – {currency}{formatPrice(priceMax)}
        </p>
      </div>
    </div>
  );
}

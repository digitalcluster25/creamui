import type { Breadcrumb } from "@/lib/types/productPage";
import styles from "./Breadcrumbs.module.css";

type Props = {
  items: Breadcrumb[];
};

export function Breadcrumbs({ items }: Props) {
  if (!items.length) return null;

  return (
    <nav className={styles.holder} aria-label="Хлебные крошки">
      <div className={styles.inner}>
        <ol className={styles.list}>
          {items.map((item, i) => {
            const isLast = i === items.length - 1;

            return (
              <li key={`${item.label}-${i}`} className={styles.item}>
                {i > 0 && (
                  <svg className={styles.sep} width="5" height="9" viewBox="0 0 9 16" fill="none" aria-hidden="true">
                    <path d="M0 14.5697L1.36504 16L9 8L1.36504 0L0 1.4303L6.26992 8L0 14.5697Z" fill="currentColor" />
                  </svg>
                )}
                {item.href && !isLast ? (
                  <a href={item.href} className={styles.link}>{item.label}</a>
                ) : (
                  <span className={isLast ? styles.current : styles.link}>{item.label}</span>
                )}
              </li>
            );
          })}
        </ol>
      </div>
    </nav>
  );
}

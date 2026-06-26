import styles from "./ProductDescription.module.css";

type Props = { sectionTitle?: string; html?: string };

// Контент приходит как одна HTML-строка из поля WooCommerce "Полное описание"
// (со временем будет генерироваться нейросетью — заголовки/абзацы внутри самой
// строки, не отдельными полями) — рендерим как есть, не пытаясь разбить на
// блоки с фиксированной структурой. Пусто -> секции вообще нет, не показываем
// пустой заголовок без содержимого.
export function ProductDescription({ sectionTitle, html }: Props) {
  if (!html || !html.trim()) return null;

  return (
    <section className={styles.wrapper}>
      {sectionTitle && <h2 className={styles.sectionTitle}>{sectionTitle}</h2>}
      <div className={styles.blocks} dangerouslySetInnerHTML={{ __html: html }} />
    </section>
  );
}

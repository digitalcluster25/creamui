import styles from "./ProductDescription.module.css";

type DescBlock = { heading: string; body: string };
type Props = { sectionTitle?: string; blocks: DescBlock[] };

export function ProductDescription({ sectionTitle, blocks }: Props) {
  return (
    <section className={styles.wrapper}>
      {sectionTitle && <h2 className={styles.sectionTitle}>{sectionTitle}</h2>}
      <div className={styles.blocks}>
        {blocks.map((b, i) => (
          <div key={i} className={styles.block}>
            <h3 className={styles.heading}>{b.heading}</h3>
            <p className={styles.body}>{b.body}</p>
          </div>
        ))}
      </div>
    </section>
  );
}

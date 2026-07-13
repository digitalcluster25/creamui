import styles from "./ProductHighlights.module.css";

type Highlight = { value: string; label: string };
type Props = { highlights: Highlight[] };

export function ProductHighlights({ highlights }: Props) {
  if (!highlights.length) return null;
  const cols = Math.min(highlights.length, 3);
  return (
    <div className={styles.grid} data-cols={cols}>
      {highlights.map((h, i) => (
        <div key={i} className={styles.card}>
          <span className={styles.value}>{h.value}</span>
          <span className={styles.label}>{h.label}</span>
        </div>
      ))}
    </div>
  );
}

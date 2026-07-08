import styles from "./ProductHighlights.module.css";

type Highlight = {
  value: string;
  label: string;
};

type Props = {
  highlights: Highlight[];
};

export function ProductHighlights({ highlights }: Props) {
  if (!highlights.length) return null;

  return (
    <div className={styles.grid}>
      {highlights.map((h) => (
        <div key={h.label} className={styles.card}>
          <span className={styles.value}>{h.value}</span>
          <span className={styles.label}>{h.label}</span>
        </div>
      ))}
    </div>
  );
}

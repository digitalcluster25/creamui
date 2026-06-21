import type { HowWeWorkData } from "@/lib/types/howWeWork";
import styles from "./HowWeWork.module.css";

export function HowWeWork({ data }: { data: HowWeWorkData }) {
  return (
    <section className={styles.section}>
      <div className={styles.head}>
        <h2 className={styles.title}>{data.title}</h2>
      </div>
      <div className={styles.grid}>
        {data.steps.map((step) => (
          <div key={step.id} className={styles.step}>
            <p className={styles.number}>{step.number}</p>
            <h3 className={styles.headline}>{step.title}</h3>
            <p className={styles.description}>{step.description}</p>
          </div>
        ))}
      </div>
    </section>
  );
}

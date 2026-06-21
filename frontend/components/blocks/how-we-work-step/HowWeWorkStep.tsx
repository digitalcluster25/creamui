import styles from "./HowWeWorkStep.module.css";

type Props = {
  number: string;
  title: string;
  description: string;
};

export function HowWeWorkStep({ number, title, description }: Props) {
  return (
    <div className={styles.step}>
      <p className={styles.number}>{number}</p>
      <h3 className={styles.headline}>{title}</h3>
      <p className={styles.description}>{description}</p>
    </div>
  );
}

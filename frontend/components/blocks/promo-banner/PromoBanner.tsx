import styles from "./PromoBanner.module.css";

const ARROW_SVG = (
  <svg width="16" height="16" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
    <path d="M8 0L6.59 1.41L12.17 7H0V9H12.17L6.59 14.59L8 16L16 8L8 0Z" />
  </svg>
);

type Props = {
  href: string;
  imageSrc: string;
  title: string;
  subtitle: string;
  buttonLabel: string;
  onButtonClick?: () => void;
};

export function PromoBanner({ href, imageSrc, title, subtitle, buttonLabel, onButtonClick }: Props) {
  return (
    <div className={styles.banner}>
      <a className={styles.link} href={href} aria-label={title} />
      {/* eslint-disable-next-line @next/next/no-img-element */}
      <img className={styles.img} src={imageSrc} alt={title} />
      <div className={styles.overlay}>
        <div>
          <h3 className={styles.title}>{title}</h3>
          <p className={styles.subtitle}>{subtitle}</p>
        </div>
        <button className={styles.btn} type="button" onClick={onButtonClick}>
          {buttonLabel}
          {ARROW_SVG}
        </button>
      </div>
    </div>
  );
}

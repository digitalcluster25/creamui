import styles from "./CtaButton.module.css";

const ARROW_SVG = (
  <svg width="18" height="18" viewBox="0 0 512 512" fill="currentColor" aria-hidden="true">
    <path d="M85 277.375h259.704L225.002 397.077 256 427l171-171L256 85l-29.922 29.924 118.626 119.701H85v42.75z" />
  </svg>
);

type Props = {
  label: string;
  href?: string;
  onClick?: () => void;
};

export function CtaButton({ label, href, onClick }: Props) {
  if (href) {
    return (
      <a className={styles.btn} href={href}>
        {label}
        <span className={styles.icon}>{ARROW_SVG}</span>
      </a>
    );
  }
  return (
    <button className={styles.btn} type="button" onClick={onClick}>
      {label}
      <span className={styles.icon}>{ARROW_SVG}</span>
    </button>
  );
}

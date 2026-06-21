import Link from "next/link";
import styles from "./FooterColumn.module.css";

type FooterLink = {
  id: string;
  label: string;
  href: string;
};

type Props = {
  title: string;
  links: FooterLink[];
  paymentsLabel?: string;
};

export function FooterColumn({ title, links, paymentsLabel }: Props) {
  return (
    <div className={styles.col}>
      <h4 className={styles.title}>{title}</h4>
      <ul className={styles.list}>
        {links.map((link) => (
          <li key={link.id}>
            <Link href={link.href} className={styles.link}>
              {link.label}
            </Link>
          </li>
        ))}
      </ul>
      {paymentsLabel && <span className={styles.paymentsLabel}>{paymentsLabel}</span>}
    </div>
  );
}

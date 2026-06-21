import type { CaseProject } from "@/lib/types/cases";
import styles from "./CaseCard.module.css";

const AREA_ICON = (
  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" aria-hidden="true">
    <path d="M4 4h16v16H4z" /><path d="M4 9h16M9 4v16" />
  </svg>
);

const TYPE_ICON = (
  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" aria-hidden="true">
    <path d="M3 21V8l9-5 9 5v13H3z" />
  </svg>
);

const TECH_ICON = (
  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" aria-hidden="true">
    <path d="M12 2C8 2 5 5 5 9c0 5 7 13 7 13s7-8 7-13c0-4-3-7-7-7z" /><circle cx="12" cy="9" r="2.5" />
  </svg>
);

export function CaseCard({ slide }: { slide: CaseProject }) {
  return (
    <div className={styles.card}>
      <a className={styles.imageLink} href={slide.href} aria-label={slide.title}>
        <figure className={styles.imageHolder}>
          {/* eslint-disable-next-line @next/next/no-img-element */}
          <img src={slide.image} alt={slide.title} loading="lazy" />
        </figure>
      </a>
      <div className={styles.details}>
        <h4 className={styles.cardTitle}>
          <a href={slide.href}>{slide.title}</a>
        </h4>
        <div className={styles.cardLocation}>{slide.location}</div>
        <div className={styles.caseMeta}>
          <div className={styles.caseMetaItem}>
            {AREA_ICON}
            <span>{"Площадь · "}<b>{slide.meta.area}</b></span>
          </div>
          <div className={styles.caseMetaItem}>
            {TYPE_ICON}
            <span>{"Тип · "}<b>{slide.meta.type}</b></span>
          </div>
          <div className={styles.caseMetaItem}>
            {TECH_ICON}
            <span>{"Технологии "}<b>{slide.meta.tech}</b></span>
          </div>
        </div>
      </div>
    </div>
  );
}

"use client";

import Link from "next/link";
import styles from "./Footer.module.css";
import type { FooterData } from "@/lib/types/footer";

interface FooterProps {
  data: FooterData;
}

export function Footer({ data }: FooterProps) {
  const { columns, contactColumn, copyright, legalLinks } = data;

  return (
    <footer className={`${styles.footer} ${styles.footerStage}`}>
      {/* Columns */}
      <div className={styles.columns}>
        {columns.map((col) => (
          <div key={col.id} className={styles.col}>
            <h4 className={styles.colTitle}>{col.title}</h4>
            <ul className={styles.linkList}>
              {col.links.map((link) => (
                <li key={link.id}>
                  {link.href ? (
                    <Link href={link.href} className={styles.link}>
                      {link.label}
                    </Link>
                  ) : (
                    <span className={styles.link}>{link.label}</span>
                  )}
                </li>
              ))}
            </ul>
            {col.paymentsLabel && (
              <span className={styles.paymentsLabel}>{col.paymentsLabel}</span>
            )}
          </div>
        ))}

        {/* Contact column */}
        <div className={styles.col}>
          <h4 className={styles.colTitle}>{contactColumn.title}</h4>
          <p className={styles.phone}>{contactColumn.phone}</p>
          <p className={styles.contactMeta}>
            {contactColumn.schedule.map((line, i) => (
              <span key={i}>
                {line}
                {i < contactColumn.schedule.length - 1 && <br />}
              </span>
            ))}
            <br />
            {contactColumn.email}
          </p>
        </div>
      </div>

      {/* Scroll to top */}
      <div className={styles.up}>
        <div className={styles.upInner}>
          <button
            type="button"
            className={styles.upLink}
            aria-label="Прокрутить наверх"
            onClick={() => window.scrollTo({ top: 0, behavior: "smooth" })}
          >
            <span className={styles.iconButton} aria-hidden="true">
              <span className={styles.icon}>
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  height="24"
                  viewBox="0 -960 960 960"
                  width="24"
                  fill="currentColor"
                  aria-hidden="true"
                >
                  <path d="M442.5-170v-476L223-426.5 170-480l310-310 310 310-53 53.5L517.5-646v476h-75Z" />
                </svg>
              </span>
            </span>
            <span>Наверх</span>
          </button>
        </div>
      </div>

      {/* Legal */}
      <div className={styles.legal}>
        <div className={styles.legalInner}>
          <span className={styles.copyright}>{copyright}</span>
          <span className={styles.legalLinks}>
            {legalLinks.map((link, i) => (
              <span key={link.id}>
                {i > 0 && " | "}
                {link.href ? <Link href={link.href}>{link.label}</Link> : link.label}
              </span>
            ))}
          </span>
        </div>
      </div>
    </footer>
  );
}

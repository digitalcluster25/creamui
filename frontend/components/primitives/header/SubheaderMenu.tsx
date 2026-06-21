import { Fragment } from "react";

import styles from "./HeaderPrimitives.module.css";

import type { HeaderTopLink } from "@/lib/types/header";

type SubheaderMenuProps = {
  links: HeaderTopLink[];
};

export function SubheaderMenu({ links }: SubheaderMenuProps) {
  return (
    <div className={styles.subheaderStrip}>
      <div className={styles.subheaderInner}>
        <ul className={styles.subheaderList} aria-hidden="true" />
        <ul className={styles.subheaderList}>
          {links.map((link, index) => (
            <Fragment key={link.id}>
              {index > 0 && (
                <li className={styles.subheaderSeparator} aria-hidden="true">|</li>
              )}
              <li className={styles.subheaderItem}>
                {link.href ? (
                  <a className={styles.subheaderLink} href={link.href}>
                    {link.label}
                  </a>
                ) : (
                  <span className={styles.subheaderText}>{link.label}</span>
                )}
              </li>
            </Fragment>
          ))}
        </ul>
      </div>
    </div>
  );
}

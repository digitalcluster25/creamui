"use client";

import { useState } from "react";
import type { ProductSpecsData } from "@/lib/types/productSpecs";
import styles from "./ProductSpecs.module.css";

type Props = { data: ProductSpecsData };

export function ProductSpecs({ data }: Props) {
  const [expanded, setExpanded] = useState(false);

  return (
    <div className={styles.wrapper}>
      <h2 className={styles.sectionTitle}>{data.sectionTitle}</h2>

      <div className={[styles.card, expanded ? styles.cardExpanded : ""].filter(Boolean).join(" ")}>
        {data.groups.map((group) => (
          <div key={group.title} className={styles.group}>
            <h3 className={styles.groupTitle}>{group.title}</h3>
            <dl className={styles.rows}>
              {group.rows.map((row) => (
                <div key={row.label} className={styles.row}>
                  <dt className={styles.label}>{row.label}</dt>
                  <dd className={styles.value}>{row.value}</dd>
                </div>
              ))}
            </dl>
          </div>
        ))}

        {!expanded && <div className={styles.fade} />}
      </div>

      <button
        type="button"
        className={styles.toggle}
        onClick={() => setExpanded((v) => !v)}
      >
        {expanded ? "Скрыть характеристики" : "Все характеристики"}
      </button>
    </div>
  );
}

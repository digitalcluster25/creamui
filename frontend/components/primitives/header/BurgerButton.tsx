"use client";

import styles from "./HeaderPrimitives.module.css";

type BurgerButtonProps = {
  label: string;
  onClick: () => void;
};

export function BurgerButton({ label, onClick }: BurgerButtonProps) {
  return (
    <button
      type="button"
      aria-label={label}
      className={`${styles.iconCircleLarge} ${styles.iconCircleFilled}`}
      onClick={onClick}
    >
      <span className={styles.burger} aria-hidden="true" />
    </button>
  );
}

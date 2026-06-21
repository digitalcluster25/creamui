"use client";

import styles from "./ScrollTopButton.module.css";

export function ScrollTopButton() {
  function handleClick(e: React.MouseEvent<HTMLAnchorElement>) {
    e.preventDefault();
    window.scrollTo({ top: 0, behavior: "smooth" });
  }

  return (
    <a href="#" className={styles.link} aria-label="Прокрутить наверх" onClick={handleClick}>
      <button className={styles.iconButton} type="button" aria-label="Прокрутка">
        <span className={styles.icon}>
          <svg xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 -960 960 960" width="24" fill="currentColor" aria-hidden="true">
            <path d="M442.5-170v-476L223-426.5 170-480l310-310 310 310-53 53.5L517.5-646v476h-75Z" />
          </svg>
        </span>
      </button>
      <span>Наверх</span>
    </a>
  );
}

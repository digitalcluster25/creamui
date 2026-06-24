"use client";

import { useEffect, useRef } from "react";
import styles from "./Brands.module.css";

const brands = [
  { src: "/assets/vvd.png", alt: "VVD" },
  { src: "/assets/easysteam.svg", alt: "EasySteam" },
  { src: "/assets/sangens.png", alt: "Sangens" },
];

const REPEATS = 5;

function Strip({ id }: { id: string }) {
  const items: React.ReactNode[] = [];
  for (let i = 0; i < REPEATS; i++) {
    brands.forEach((b) => {
      items.push(
        // eslint-disable-next-line @next/next/no-img-element
        <img key={`${id}-${i}-${b.alt}`} src={b.src} alt={b.alt} className={styles.logo} />
      );
      items.push(
        <span key={`${id}-sep-${i}-${b.alt}`} className={styles.sep} aria-hidden="true">✦</span>
      );
    });
  }
  return <div className={styles.el}>{items}</div>;
}

export function Brands() {
  const trackRef = useRef<HTMLDivElement>(null);
  const rafRef = useRef<number>(0);
  const posRef = useRef(0);

  useEffect(() => {
    const track = trackRef.current;
    if (!track) return;
    const firstEl = track.children[0] as HTMLElement | undefined;
    if (!firstEl) return;

    // wait one frame for layout to settle
    const start = () => {
      const stripWidth = firstEl.offsetWidth;
      if (!stripWidth) return;

      // Ohio data-dir="ltr": content moves right-to-left → start at 0, move left
      // User confirmed direction wrong → flip to left-to-right: start at -stripWidth, move right
      posRef.current = -stripWidth;

      const SPEED = 0.3; // reduced from 0.6 — Ohio feels much slower visually

      const tick = () => {
        posRef.current += SPEED;
        if (posRef.current >= 0) posRef.current = -stripWidth;
        track.style.transform = `translateX(${posRef.current}px)`;
        rafRef.current = requestAnimationFrame(tick);
      };
      rafRef.current = requestAnimationFrame(tick);
    };

    rafRef.current = requestAnimationFrame(start);
    return () => cancelAnimationFrame(rafRef.current);
  }, []);

  return (
    <section className={styles.section}>
      <div className={styles.line}>
        <div className={styles.stage} ref={trackRef}>
          <Strip id="a" />
          <Strip id="b" />
        </div>
      </div>
    </section>
  );
}

import Image from "next/image";
import type { HeroData } from "@/lib/types/hero";
import styles from "./Hero.module.css";

type Props = {
  data: HeroData;
};

export function Hero({ data }: Props) {
  return (
    <section className={styles.stage}>
      <div
        className={styles.hero}
        style={{ "--hero-bg": data.backgroundImage } as React.CSSProperties}
      >
        <div className={styles.inner}>
          <div className={styles.content}>
            <p className={styles.eyebrow}>{data.eyebrow}</p>
            <h1 className={styles.displayTitle}>{data.title}</h1>
            <p className={styles.lead}>
              {data.lead.split("\n").map((line, i, arr) => (
                <span key={i}>
                  {line}
                  {i < arr.length - 1 && <br />}
                </span>
              ))}
            </p>
            <div className={styles.actions}>
              <a className={styles.ctaButton} href={data.cta.href}>
                {data.cta.label}
                <span className={styles.ctaIcon}>
                  <svg
                    width="18"
                    height="18"
                    viewBox="0 0 512 512"
                    fill="currentColor"
                    aria-hidden="true"
                  >
                    <path d="M85 277.375h259.704L225.002 397.077 256 427l171-171L256 85l-29.922 29.924 118.626 119.701H85v42.75z" />
                  </svg>
                </span>
              </a>
            </div>
          </div>

          <div className={styles.badges}>
            {data.badges.map((badge) => (
              <div key={badge.id} className={styles.badge}>
                <span className={styles.badgeIcon}>
                  {badge.iconType === "svg" && badge.iconSvg ? (
                    <span
                      dangerouslySetInnerHTML={{ __html: badge.iconSvg }}
                    />
                  ) : badge.iconType === "img" && badge.iconSrc ? (
                    <Image
                      src={badge.iconSrc}
                      alt=""
                      width={20}
                      height={20}
                      aria-hidden
                    />
                  ) : null}
                </span>
                <span className={styles.badgeText}>{badge.text}</span>
              </div>
            ))}
          </div>
        </div>
      </div>
    </section>
  );
}

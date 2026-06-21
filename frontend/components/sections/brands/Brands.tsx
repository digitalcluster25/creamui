import Image from "next/image";
import styles from "./Brands.module.css";

const brands = [
  { src: "/assets/vvd.png", alt: "VVD", width: 145, height: 62 },
  { src: "/assets/easysteam.svg", alt: "EasySteam", width: 145, height: 62 },
  { src: "/assets/sangens.png", alt: "Sangens", width: 145, height: 62 },
];

export function Brands() {
  return (
    <section className={styles.section}>
      <ul className={styles.grid}>
        {brands.map((b) => (
          <li key={b.alt} className={styles.item}>
            <a href="#" className={styles.logoLink} aria-label={b.alt}>
              <Image
                src={b.src}
                alt={b.alt}
                width={b.width}
                height={b.height}
                className={styles.logo}
                unoptimized
              />
            </a>
          </li>
        ))}
      </ul>
    </section>
  );
}

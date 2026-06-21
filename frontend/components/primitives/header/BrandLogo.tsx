import Image from "next/image";

import styles from "./HeaderPrimitives.module.css";

type BrandLogoProps = {
  href: string;
  src: string;
  alt: string;
};

export function BrandLogo({ href, src, alt }: BrandLogoProps) {
  return (
    <a className={styles.brand} href={href} aria-label={alt}>
      <Image className={styles.brandImage} src={src} alt={alt} width={179} height={66} priority />
    </a>
  );
}

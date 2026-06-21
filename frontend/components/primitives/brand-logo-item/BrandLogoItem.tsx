import Image from "next/image";
import styles from "./BrandLogoItem.module.css";

type Props = {
  src: string;
  alt: string;
  width: number;
  height: number;
  href?: string;
};

export function BrandLogoItem({ src, alt, width, height, href = "#" }: Props) {
  return (
    <a href={href} className={styles.link} aria-label={alt}>
      <Image
        src={src}
        alt={alt}
        width={width}
        height={height}
        className={styles.logo}
        unoptimized
      />
    </a>
  );
}

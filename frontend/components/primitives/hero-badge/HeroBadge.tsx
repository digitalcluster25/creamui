import Image from "next/image";
import styles from "./HeroBadge.module.css";

type Props = {
  text: string;
  iconType: "svg" | "img";
  iconSvg?: string;
  iconSrc?: string;
};

export function HeroBadge({ text, iconType, iconSvg, iconSrc }: Props) {
  return (
    <div className={styles.badge}>
      <span className={styles.icon}>
        {iconType === "svg" && iconSvg ? (
          <span dangerouslySetInnerHTML={{ __html: iconSvg }} />
        ) : iconType === "img" && iconSrc ? (
          <Image src={iconSrc} alt="" width={20} height={20} aria-hidden />
        ) : null}
      </span>
      <span className={styles.text}>{text}</span>
    </div>
  );
}

import styles from "./HeaderPrimitives.module.css";

import type { HeaderIconAction } from "@/lib/types/header";

type HeaderActionButtonProps = {
  action: HeaderIconAction;
};

function WishlistIcon() {
  return (
    <svg className={styles.actionSvg} viewBox="0 -960 960 960" fill="currentColor" aria-hidden="true">
      <path d="M480-479q-64.5 0-109.75-45.25T325-634q0-64.5 45.25-109.75T480-789q64.5 0 109.75 45.25T635-634q0 64.5-45.25 109.75T480-479ZM169-173v-106q0-33 16.75-60.25t45.272-41.761Q292-411 354.25-426.25 416.5-441.5 480-441.5t125.75 15.25Q668-411 728.978-381.011 757.5-366.5 774.25-339.25 791-312 791-279v106H169Zm75-75h472v-31q0-11.19-5.5-20.345t-15-14.155Q642-340 588.325-353.25 534.651-366.5 480-366.5q-55 0-108.5 13.25t-107 39.75q-9.5 5-15 14.155T244-279v31Zm236-306q33 0 56.5-23.5T560-634q0-33-23.5-56.5T480-714q-33 0-56.5 23.5T400-634q0 33 23.5 56.5T480-554Zm0-80Zm0 386Z" />
    </svg>
  );
}

function CartIcon() {
  return (
    <svg className={styles.actionSvg} viewBox="0 -960 960 960" fill="currentColor" aria-hidden="true">
      <path d="M205-90q-30.94 0-52.97-22.03Q130-134.06 130-165v-472q0-30.94 22.03-52.97Q174.06-712 205-712h82q0-80.5 56.25-136.75T480-905q80.5 0 136.75 56.25T673-712h82q30.94 0 52.97 22.03Q830-667.94 830-637v472q0 30.94-22.03 52.97Q785.94-90 755-90H205Zm0-75h550v-472H205v472Zm274.93-240Q560-405 616.5-461.45 673-517.91 673-598h-75q0 49-34.38 83.5-34.38 34.5-83.5 34.5Q431-480 396.5-514.42 362-548.83 362-598h-75q0 80 56.43 136.5t136.5 56.5ZM362-712h236q0-49-34.38-83.5-34.38-34.5-83.5-34.5Q431-830 396.5-795.58 362-761.17 362-712Z" />
    </svg>
  );
}

export function HeaderActionButton({ action }: HeaderActionButtonProps) {
  const icon = action.kind === "cart" ? <CartIcon /> : <WishlistIcon />;
  const className = `${action.kind === "cart" ? styles.iconCircleLarge : styles.iconCircleSmall} ${
    action.kind === "cart" ? styles.iconCircleFilled : ""
  }`;

  if (!action.href) {
    return (
      <button type="button" aria-label={action.label} className={className}>
        {action.badge !== undefined ? <span className={styles.cartBadge}>{action.badge}</span> : null}
        {icon}
      </button>
    );
  }

  return (
    <a href={action.href} aria-label={action.label} className={className}>
      {action.badge !== undefined ? <span className={styles.cartBadge}>{action.badge}</span> : null}
      {icon}
    </a>
  );
}

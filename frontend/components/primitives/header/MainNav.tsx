"use client";

import Image from "next/image";
import { useEffect, useRef } from "react";

import styles from "./HeaderPrimitives.module.css";

import type { HeaderNavItem } from "@/lib/types/header";

type MainNavProps = {
  items: HeaderNavItem[];
};

const SOURCE_VIEWPORT = 1560;
const SOURCE_LEFT = 29.4140625;
const SOURCE_WIDTH = 1513.1875;
const PANEL_GAP = 12;

function useMegaMenuGeometry(
  navItemRef: React.RefObject<HTMLLIElement | null>,
  megaMenuRef: React.RefObject<HTMLUListElement | null>,
) {
  useEffect(() => {
    const calculate = () => {
      const menu = megaMenuRef.current;
      const item = navItemRef.current;
      if (!menu || !item) return;

      const scale = window.innerWidth / SOURCE_VIEWPORT;
      const itemRect = item.getBoundingClientRect();

      const links = Array.from(menu.querySelectorAll<HTMLElement>(":scope > li > a"));
      links.forEach((link) => (link.style.width = "auto"));

      const maxItemWidth = Math.ceil(
        links.reduce((max, link) => Math.max(max, link.getBoundingClientRect().width), 0),
      );

      const panelWidth = maxItemWidth * 3 + PANEL_GAP * 4;
      const sourcePanelLeft = SOURCE_LEFT * scale - itemRect.left;
      const sourcePanelWidth = SOURCE_WIDTH * scale;
      const centeredLeft = sourcePanelLeft + (sourcePanelWidth - panelWidth) / 2;

      menu.style.setProperty("--catalog-col-gap", `${PANEL_GAP}px`);
      menu.style.setProperty("--catalog-col-width", `${maxItemWidth}px`);
      menu.style.left = `${centeredLeft}px`;
      menu.style.width = `${panelWidth}px`;
    };

    calculate();
    window.addEventListener("resize", calculate);
    return () => window.removeEventListener("resize", calculate);
  }, [navItemRef, megaMenuRef]);
}

function ChevronIcon() {
  return (
    <svg fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
      <path d="M0 7.33 2.829 4.5l9.175 9.339 9.167-9.339L24 7.33 12.004 19.5 0 7.33Z" />
    </svg>
  );
}

type NavItemWithMegaMenuProps = {
  item: HeaderNavItem;
};

function NavItemWithMegaMenu({ item }: NavItemWithMegaMenuProps) {
  const navItemRef = useRef<HTMLLIElement>(null);
  const megaMenuRef = useRef<HTMLUListElement>(null);

  useMegaMenuGeometry(navItemRef, megaMenuRef);

  return (
    <li ref={navItemRef} className={styles.mainNavItem}>
      <a href={item.href} className={styles.mainNavLink}>
        <span>{item.label}</span>
        <span className={styles.mainNavChevron}>
          <ChevronIcon />
        </span>
      </a>

      <ul ref={megaMenuRef} className={styles.megaMenu}>
        {item.megaMenu?.map((megaItem) => (
          <li key={megaItem.id} className={styles.megaMenuItem}>
            <a href={megaItem.href} className={styles.megaMenuLink}>
              <Image
                className={styles.megaMenuIcon}
                src={megaItem.iconSrc}
                alt=""
                width={36}
                height={36}
              />
              <span className={styles.megaMenuLabel}>{megaItem.label}</span>
            </a>
          </li>
        ))}
      </ul>
    </li>
  );
}

export function MainNav({ items }: MainNavProps) {
  return (
    <nav className={styles.mainNav} aria-label="Навигация HWS">
      <ul className={styles.mainNavList}>
        {items.map((item) => {
          if (item.megaMenu?.length) {
            return <NavItemWithMegaMenu key={item.id} item={item} />;
          }

          return (
            <li key={item.id} className={styles.mainNavItem}>
              <a href={item.href} className={styles.mainNavLink}>
                <span>{item.label}</span>
              </a>
            </li>
          );
        })}
      </ul>
    </nav>
  );
}

"use client";

import { useEffect, useState } from "react";

import styles from "./Header.module.css";

import {
  BrandLogo,
  BurgerButton,
  CurrencySwitcher,
  HeaderActionButton,
  MainNav,
  SubheaderMenu,
} from "@/components/primitives/header";
import type { HeaderData } from "@/lib/types/header";

type HeaderProps = {
  data: HeaderData;
  hideBurgerOnDesktop?: boolean;
  hideActionsOnDesktop?: boolean;
};

function CloseIcon() {
  return (
    <svg width="14" height="14" viewBox="0 0 14 14" fill="none" aria-hidden="true">
      <path
        d="M14 1.41 12.59 0 7 5.59 1.41 0 0 1.41 5.59 7 0 12.59 1.41 14 7 8.41 12.59 14 14 12.59 8.41 7 14 1.41Z"
        fill="currentColor"
      />
    </svg>
  );
}

function PlusIcon() {
  return (
    <svg width="14" height="14" viewBox="0 0 14 14" fill="none" aria-hidden="true">
      <path d="M14 8H8V14H6V8H0V6H6V0H8V6H14V8Z" fill="currentColor" />
    </svg>
  );
}

export function Header({ data, hideBurgerOnDesktop, hideActionsOnDesktop }: HeaderProps) {
  const [isOverlayOpen, setIsOverlayOpen] = useState(false);
  const [openSubmenuId, setOpenSubmenuId] = useState<string | null>(null);

  useEffect(() => {
    document.body.style.overflow = isOverlayOpen ? "hidden" : "";

    return () => {
      document.body.style.overflow = "";
    };
  }, [isOverlayOpen]);

  const catalogItem = data.primaryNav.find((item) => item.megaMenu?.length);

  return (
    <div className={styles.stage}>
      <SubheaderMenu links={data.topLinks} />

      <header className={styles.header}>
        <div className={styles.headerInner}>
          <div className={styles.left}>
            <div className={hideBurgerOnDesktop ? styles.burgerWrap : undefined}>
              <BurgerButton label="Открыть меню" onClick={() => setIsOverlayOpen(true)} />
            </div>
            <BrandLogo href={data.brandHref} src={data.brandSrc} alt={data.brandAlt} />
          </div>

          <div className={styles.center}>
            <MainNav items={data.primaryNav} />
          </div>

          <div className={styles.right}>
            <div className={styles.actions}>
              <CurrencySwitcher
                currencies={data.currencies}
                defaultCurrency={data.defaultCurrency}
              />
              <div className={hideActionsOnDesktop ? styles.actionsHidden : styles.actionsInner}>
                <HeaderActionButton action={data.actions[0]} />
                <span className={styles.cartTotal}>{data.cartTotal}</span>
                <HeaderActionButton action={data.actions[1]} />
              </div>
            </div>
          </div>
        </div>
      </header>

      <div
        className={`${styles.overlay} ${isOverlayOpen ? styles.overlayOpen : ""}`}
        aria-hidden={!isOverlayOpen}
      >
        <div className={styles.closeBar}>
          <button
            type="button"
            className={styles.closeButton}
            aria-label="Закрыть"
            onClick={() => setIsOverlayOpen(false)}
          >
            <CloseIcon />
          </button>
        </div>

        <div className={styles.overlayContainer}>
          <div className={styles.overlayNav}>
            <ul className={styles.overlayList}>
              {data.primaryNav.map((item) => {
                const isCatalog = item.id === catalogItem?.id;
                const submenuItems = item.children ?? (isCatalog ? item.megaMenu : undefined);
                const hasSubmenu = Boolean(submenuItems?.length);
                const isActive = hasSubmenu && openSubmenuId === item.id;

                return (
                  <li key={item.id} className={styles.overlayItem}>
                    <a
                      href={item.href}
                      className={`${styles.overlayLink} ${isActive ? styles.overlayLinkActive : ""}`}
                      onClick={(event) => {
                        if (!hasSubmenu) {
                          setIsOverlayOpen(false);
                          return;
                        }

                        event.preventDefault();
                        setOpenSubmenuId((current) => (current === item.id ? null : item.id));
                      }}
                    >
                      <span>{item.label}</span>
                      {hasSubmenu ? (
                        <span
                          className={`${styles.overlayPlus} ${isActive ? styles.overlayPlusActive : ""}`}
                          aria-hidden="true"
                        >
                          <PlusIcon />
                        </span>
                      ) : null}
                    </a>

                    {hasSubmenu ? (
                      <ul
                        className={`${styles.overlaySubmenu} ${
                          isActive ? styles.overlaySubmenuOpen : ""
                        }`}
                      >
                        {submenuItems?.map((submenuItem) => (
                          <li key={submenuItem.id}>
                            <a href={submenuItem.href}>{submenuItem.label}</a>
                          </li>
                        ))}
                      </ul>
                    ) : null}
                  </li>
                );
              })}
            </ul>
          </div>

          <div className={styles.details}>
            {data.hamburgerContacts.map((contact) => (
              <div key={contact.id} className={styles.detailsColumn}>
                {contact.title ? <h6 className={styles.detailsTitle}>{contact.title}</h6> : null}
                <p className={styles.detailsText}>
                  {contact.lines.map((line) => (
                    <span key={line}>
                      {line}
                      <br />
                    </span>
                  ))}
                </p>
              </div>
            ))}

            <div className={`${styles.detailsColumn} ${styles.socials}`}>
              {data.hamburgerSocials.map((social) => (
                <a key={social.id} href={social.href}>
                  {social.label}
                </a>
              ))}
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}

"use client";

import styles from "./page.module.css";

import {
  BrandLogo,
  BurgerButton,
  CurrencySwitcher,
  HeaderActionButton,
  MainNav,
  SubheaderMenu,
} from "@/components/primitives/header";
import { CtaButton } from "@/components/primitives/cta-button";
import { HeroBadge } from "@/components/primitives/hero-badge";
import { BrandLogoItem } from "@/components/primitives/brand-logo-item";
import { ScrollTopButton } from "@/components/primitives/scroll-top-button";
import { headerMock } from "@/lib/mocks/header";
import { heroData } from "@/lib/data/hero";

export default function PrimitivesPage() {
  return (
    <main className="app-shell">
      <header className="app-header">
        <div>
          <h1 className="app-title">Примитивы</h1>
          <p className="app-subtitle">Атомарные UI-элементы — иконочные кнопки, логотипы, переключатели.</p>
        </div>
        <nav className="app-nav" aria-label="Навигация по frontend">
          <a href="/">Главная</a>
          <a href="/primitives" aria-current="page">Примитивы</a>
          <a href="/blocks">Блоки</a>
          <a href="/sections">Секции</a>
          <a href="/pages">Страницы</a>
        </nav>
      </header>

      <div className={styles.stack}>

        <section className={styles.card}>
          <h2 className={styles.title}>SubheaderMenu</h2>
          <SubheaderMenu links={headerMock.topLinks} />
        </section>

        <section className={styles.card}>
          <h2 className={styles.title}>BurgerButton + BrandLogo</h2>
          <div className={styles.inline}>
            <BurgerButton label="Открыть меню" onClick={() => undefined} />
            <BrandLogo
              href={headerMock.brandHref}
              src={headerMock.brandSrc}
              alt={headerMock.brandAlt}
            />
          </div>
        </section>

        <section className={styles.card}>
          <h2 className={styles.title}>MainNav + MegaMenu</h2>
          <div className={styles.navPreview}>
            <MainNav items={headerMock.primaryNav} />
          </div>
        </section>

        <section className={styles.card}>
          <h2 className={styles.title}>CurrencySwitcher + Actions</h2>
          <div className={styles.inline}>
            <CurrencySwitcher
              currencies={headerMock.currencies}
              defaultCurrency={headerMock.defaultCurrency}
            />
            <HeaderActionButton action={headerMock.actions[0]} />
            <HeaderActionButton action={headerMock.actions[1]} />
          </div>
        </section>

        <section className={styles.card}>
          <h2 className={styles.title}>CtaButton</h2>
          <div className={styles.inline}>
            <CtaButton label={heroData.cta.label} href={heroData.cta.href} />
          </div>
        </section>

        <section className={styles.card}>
          <h2 className={styles.title}>HeroBadge</h2>
          <div className={styles.inline} style={{ background: "rgba(17,16,19,0.85)", padding: "20px 24px", borderRadius: 12 }}>
            {heroData.badges.map((badge) => (
              <HeroBadge
                key={badge.id}
                text={badge.text}
                iconType={badge.iconType}
                iconSvg={badge.iconSvg}
                iconSrc={badge.iconSrc}
              />
            ))}
          </div>
        </section>

        <section className={styles.card}>
          <h2 className={styles.title}>BrandLogoItem</h2>
          <div className={styles.inline}>
            <BrandLogoItem src="/assets/vvd.png" alt="VVD" width={145} height={62} href="#" />
            <BrandLogoItem src="/assets/easysteam.svg" alt="EasySteam" width={145} height={62} href="#" />
            <BrandLogoItem src="/assets/sangens.png" alt="Sangens" width={145} height={62} href="#" />
          </div>
        </section>

        <section className={styles.card}>
          <h2 className={styles.title}>ScrollTopButton</h2>
          <div className={styles.inline}>
            <ScrollTopButton />
          </div>
        </section>

      </div>
    </main>
  );
}

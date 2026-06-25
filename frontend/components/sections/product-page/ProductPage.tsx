"use client";

import { useState, useRef } from "react";
import type { ProductPageData } from "@/lib/types/productPage";
import styles from "./ProductPage.module.css";

/* ── Icons ── */
const FB_ICON = <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z" /></svg>;
const PIN_ICON = <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 0C5.373 0 0 5.373 0 12c0 5.084 3.163 9.426 7.627 11.174-.105-.949-.2-2.405.042-3.441.218-.937 1.407-5.965 1.407-5.965s-.359-.719-.359-1.782c0-1.668.967-2.914 2.171-2.914 1.023 0 1.518.769 1.518 1.69 0 1.029-.655 2.568-.994 3.995-.283 1.194.599 2.169 1.777 2.169 2.133 0 3.772-2.249 3.772-5.495 0-2.873-2.064-4.882-5.012-4.882-3.414 0-5.418 2.561-5.418 5.207 0 1.031.397 2.138.893 2.738a.36.36 0 0 1 .083.345l-.333 1.36c-.053.22-.174.267-.402.161-1.499-.698-2.436-2.889-2.436-4.649 0-3.785 2.75-7.262 7.929-7.262 4.163 0 7.398 2.967 7.398 6.931 0 4.136-2.607 7.464-6.227 7.464-1.216 0-2.359-.632-2.75-1.378l-.748 2.853c-.271 1.043-1.002 2.35-1.492 3.146C9.57 23.812 10.763 24 12 24c6.627 0 12-5.373 12-12S18.627 0 12 0z" /></svg>;
const X_ICON = <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.747l7.73-8.835L1.254 2.25H8.08l4.259 5.631 5.905-5.631zm-1.161 17.52h1.833L7.084 4.126H5.117z" /></svg>;
const FULLSCREEN_ICON = <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" aria-hidden="true"><path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3" /></svg>;
const TG_ICON = <svg viewBox="0 0 496 512" fill="currentColor" aria-hidden="true"><path d="M248 8C111 8 0 119 0 256S111 504 248 504 496 393 496 256 385 8 248 8zM363 176.7c-3.7 39.2-19.9 134.4-28.1 178.3-3.5 18.6-10.3 24.8-16.9 25.4-14.4 1.3-25.3-9.5-39.3-18.7-21.8-14.3-34.2-23.2-55.3-37.2-24.5-16.1-8.6-25 5.3-39.5 3.7-3.8 67.1-61.5 68.3-66.7 .2-.7 .3-3.1-1.2-4.4s-3.6-.8-5.1-.5q-3.3 .7-104.6 69.1-14.8 10.2-26.9 9.9c-8.9-.2-25.9-5-38.6-9.1-15.5-5-27.9-7.7-26.8-16.3q.8-6.7 18.5-13.7 108.4-47.2 144.6-62.3c68.9-28.6 83.2-33.6 92.5-33.8 2.1 0 6.6 .5 9.6 2.9a10.5 10.5 0 0 1 3.5 6.7A43.8 43.8 0 0 1 363 176.7z"/></svg>;
const WA_ICON = <svg viewBox="0 0 448 512" fill="currentColor" aria-hidden="true"><path d="M380.9 97.1C339 55.1 283.2 32 223.9 32c-122.4 0-222 99.6-222 222 0 39.1 10.2 77.3 29.6 111L0 480l117.7-30.9c32.4 17.7 68.9 27 106.1 27h.1c122.3 0 224.1-99.6 224.1-222 0-59.3-25.2-115-67.1-157zm-157 341.6c-33.2 0-65.7-8.9-94-25.7l-6.7-4-69.8 18.3L72 359.2l-4.4-7c-18.5-29.4-28.2-63.3-28.2-98.2 0-101.7 82.8-184.5 184.6-184.5 49.3 0 95.6 19.2 130.4 54.1 34.8 34.9 56.2 81.2 56.1 130.5 0 101.8-84.9 184.6-186.6 184.6zm101.2-138.2c-5.5-2.8-32.8-16.2-37.9-18-5.1-1.9-8.8-2.8-12.5 2.8-3.7 5.6-14.3 18-17.6 21.8-3.2 3.7-6.5 4.2-12 1.4-32.6-16.3-54-29.1-75.5-66-5.7-9.8 5.7-9.1 16.3-30.3 1.8-3.7 .9-6.9-.5-9.7-1.4-2.8-12.5-30.1-17.1-41.2-4.5-10.8-9.1-9.3-12.5-9.5-3.2-.2-6.9-.2-10.6-.2-3.7 0-9.7 1.4-14.8 6.9-5.1 5.6-19.4 19-19.4 46.3 0 27.3 19.9 53.7 22.6 57.4 2.8 3.7 39.1 59.7 94.8 83.8 35.2 15.2 49 16.5 66.6 13.9 10.7-1.6 32.8-13.4 37.4-26.4 4.6-13 4.6-24.1 3.2-26.4-1.3-2.5-5-3.9-10.5-6.6z"/></svg>;
const DELIVERY_ICON = <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6.5A2.5 2.5 0 0 1 5.5 4h9A2.5 2.5 0 0 1 17 6.5V8h1.4c.7 0 1.35.33 1.76.9l1.44 2.02c.26.37.4.8.4 1.25V17h-2.05a2.75 2.75 0 0 1-5.4 0h-5.1a2.75 2.75 0 0 1-5.4 0H3V6.5Zm2 0V15h.55a2.75 2.75 0 0 1 4.9 0H15V6.5a.5.5 0 0 0-.5-.5h-9a.5.5 0 0 0-.5.5Zm12 3.5v5h.55a2.75 2.75 0 0 1 2.45-1.5V12.2a.2.2 0 0 0-.04-.12L18.52 10H17ZM6.75 16a.75.75 0 1 0 0 1.5.75.75 0 0 0 0-1.5Zm10.5 0a.75.75 0 1 0 0 1.5.75.75 0 0 0 0-1.5Z"/></svg>;
const PAYMENT_ICON = <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Zm0 2v2h16V7H4Zm0 5v5h16v-5H4Zm2 2h5v2H6v-2Z"/></svg>;
const WARRANTY_ICON = <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2 3 6v6c0 5.25 3.75 10.15 9 11.35C17.25 22.15 21 17.25 21 12V6L12 2Zm-1 13-3-3 1.41-1.41L11 12.17l4.59-4.58L17 9l-6 6Z"/></svg>;

function formatPrice(n: number) {
  return n.toLocaleString("en-US", { minimumFractionDigits: 2 });
}

type Props = { data: ProductPageData };

function buildDefaults(groups: ProductPageData["variantGroups"]): Record<string, string> {
  const out: Record<string, string> = {};
  for (const g of groups) {
    if (g.options.length > 0) out[g.key] = g.options[0].value;
  }
  return out;
}

export function ProductPage({ data }: Props) {
  const [activeImg, setActiveImg] = useState(0);
  const [variants, setVariants] = useState<Record<string, string>>(() => buildDefaults(data.variantGroups));
  const [qty, setQty] = useState(1);
  const dragStartX = useRef<number | null>(null);

  function handlePointerDown(e: React.PointerEvent<HTMLDivElement>) {
    dragStartX.current = e.clientX;
    (e.currentTarget as HTMLDivElement).setPointerCapture(e.pointerId);
  }

  function handlePointerUp(e: React.PointerEvent<HTMLDivElement>) {
    if (dragStartX.current === null) return;
    const delta = e.clientX - dragStartX.current;
    dragStartX.current = null;
    if (delta > 50) setActiveImg((i) => (i - 1 + data.images.length) % data.images.length);
    else if (delta < -50) setActiveImg((i) => (i + 1) % data.images.length);
  }

  function setVariant(key: string, val: string) {
    setVariants((prev) => ({ ...prev, [key]: val }));
  }

  function clearVariants() {
    setVariants(buildDefaults(data.variantGroups));
  }

  const hasVariants = Object.entries(variants).some(([key, val]) => {
    const group = data.variantGroups.find((g) => g.key === key);
    return group && group.options[0]?.value !== val;
  });

  const priceModifierTotal = data.variantGroups.reduce((sum, group) => {
    const selected = variants[group.key];
    const opt = group.options.find((o) => o.value === selected);
    return sum + (opt?.priceModifier ?? 0);
  }, 0);

  const effectivePrice = data.price + priceModifierTotal;

  const minPrice = data.price;
  const maxPrice = data.price + data.variantGroups.reduce((sum, group) => {
    const maxMod = Math.max(...group.options.map((o) => o.priceModifier ?? 0));
    return sum + maxMod;
  }, 0);

  return (
    <>
    {data.breadcrumbs && data.breadcrumbs.length > 0 && (
      <div className={styles.breadcrumbHolder}>
        <ol className={styles.breadcrumb} aria-label="Breadcrumb">
          {data.breadcrumbs.map((item, i) => {
            const isLast = i === data.breadcrumbs!.length - 1;
            return (
              <li key={i} className={styles.breadcrumbItem}>
                {i > 0 && (
                  <svg className={styles.breadcrumbSep} width="5" height="9" viewBox="0 0 9 16" fill="none" aria-hidden="true">
                    <path d="M0 14.5697L1.36504 16L9 8L1.36504 0L0 1.4303L6.26992 8L0 14.5697Z" fill="currentColor" />
                  </svg>
                )}
                {item.href && !isLast
                  ? <a href={item.href} className={styles.breadcrumbLink}>{item.label}</a>
                  : <span className={isLast ? styles.breadcrumbCurrent : styles.breadcrumbLink}>{item.label}</span>}
              </li>
            );
          })}
        </ol>
      </div>
    )}
    <section className={styles.section}>
      {/* ── Gallery ── */}
      <div className={styles.gallery}>
        {/* Thumbs column */}
        <div className={styles.thumbs}>
          {data.images.map((src, i) => (
            <button
              key={i}
              type="button"
              className={[styles.thumb, i === activeImg ? styles.thumbActive : ""].filter(Boolean).join(" ")}
              onClick={() => setActiveImg(i)}
              aria-label={`Фото ${i + 1}`}
            >
              {/* eslint-disable-next-line @next/next/no-img-element */}
              <img src={src} alt="" />
            </button>
          ))}
        </div>

        {/* Slider */}
        <div
          className={styles.slider}
          onPointerDown={handlePointerDown}
          onPointerUp={handlePointerUp}
        >
          {/* eslint-disable-next-line @next/next/no-img-element */}
          <img className={styles.mainImg} src={data.images[activeImg]} alt={data.title} />
        </div>
        <div className={styles.sliderArrows}>
          <button
            type="button"
            className={styles.sliderArrowBtn}
            onClick={() => setActiveImg((i) => (i - 1 + data.images.length) % data.images.length)}
            aria-label="Предыдущее фото"
          >
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true"><polyline points="15 18 9 12 15 6"/></svg>
          </button>
          <button
            type="button"
            className={styles.sliderArrowBtn}
            onClick={() => setActiveImg((i) => (i + 1) % data.images.length)}
            aria-label="Следующее фото"
          >
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true"><polyline points="9 18 15 12 9 6"/></svg>
          </button>
        </div>
      </div>

      {/* ── Info ── */}
      <div className={styles.info}>
        {/* Badges */}
        <div className={styles.badges}>
          {data.badges.map((b) => (
            <span key={b.label} className={[styles.badge, styles[`badge_${b.variant}`]].join(" ")}>
              {b.label}
            </span>
          ))}
        </div>

        <h1 className={styles.title}>{data.title}</h1>

        <p className={styles.categories}>
          {data.categories.map((c, i) => (
            <span key={`${c.href}-${i}`}>
              {i > 0 && ", "}
              <a href={c.href} className={styles.categoryLink}>{c.label}</a>
            </span>
          ))}
        </p>

        <div className={styles.priceRow}>
          <span className={styles.price}>
            {data.currency}{formatPrice(minPrice)}
            {maxPrice > minPrice && (
              <span className={styles.priceRange}> – {data.currency}{formatPrice(maxPrice)}</span>
            )}
          </span>
        </div>

        {/* Commerce info — delivery + payment + warranty, по бренду товара (WooCommerce → Оплата и доставка) */}
        {(() => {
          const ci = data.commerceInfo;
          const items = [
            { text: ci?.deliveryText, title: ci?.deliveryTitle || "Доставка", icon: DELIVERY_ICON },
            { text: ci?.paymentText, title: ci?.paymentTitle || "Оплата", icon: PAYMENT_ICON },
            { text: ci?.warrantyText, title: ci?.warrantyTitle || "Гарантия", icon: WARRANTY_ICON },
          ].filter((item) => !!item.text);

          if (items.length === 0) return null;

          return (
            <section className={styles.commerceInfo} aria-label="Оплата и доставка">
              {items.map((item) => (
                <div key={item.title} className={styles.commerceItem}>
                  <span className={styles.commerceIcon}>{item.icon}</span>
                  <div>
                    <strong className={styles.commerceItemTitle}>{item.title}</strong>
                    <span className={styles.commerceItemDesc}>{item.text}</span>
                  </div>
                </div>
              ))}
              {ci?.note && <p className={styles.commerceNote}>{ci.note}</p>}
            </section>
          );
        })()}

        {(data.sku || data.tag || data.brand) && (
          <p className={styles.meta}>
            {data.sku && <><strong>Артикул:</strong> {data.sku}</>}
            {data.tag && <> · <strong>Тэг:</strong> {data.tag}</>}
            {data.brand && <> · <strong>Бренд:</strong> {data.brand}</>}
          </p>
        )}

        <p className={styles.description}>{data.description}</p>

        <hr className={styles.divider} />

        {/* Variant groups */}
        <div className={styles.variantGroups}>
          {data.variantGroups.map((group) => (
            <div
              key={group.key}
              className={[styles.variantGroup, group.fullWidth ? styles.variantGroupFull : ""].filter(Boolean).join(" ")}
            >
              <span className={styles.variantLabel}>{group.label}:</span>
              <div className={styles.variantOptions}>
                {group.options.map((opt) => {
                  const active = variants[group.key] === opt.value;
                  if (group.type === "color") {
                    return (
                      <button
                        key={opt.value}
                        type="button"
                        title={opt.value}
                        className={[styles.swatchColor, active ? styles.swatchColorActive : ""].filter(Boolean).join(" ")}
                        style={{ "--swatch-color": opt.color } as React.CSSProperties}
                        onClick={() => setVariant(group.key, opt.value)}
                        aria-pressed={active}
                      />
                    );
                  }
                  return (
                    <button
                      key={opt.value}
                      type="button"
                      className={[styles.swatchText, active ? styles.swatchTextActive : ""].filter(Boolean).join(" ")}
                      onClick={() => setVariant(group.key, opt.value)}
                      aria-pressed={active}
                    >
                      {opt.value}
                      {opt.priceModifier ? (
                        <span className={styles.swatchPrice}>+{data.currency}{opt.priceModifier.toLocaleString("en-US")}</span>
                      ) : null}
                    </button>
                  );
                })}
              </div>
            </div>
          ))}
          {hasVariants && (
            <div className={styles.variantGroup} style={{ alignSelf: "end" }}>
              <button type="button" className={styles.clearVariants} onClick={clearVariants}>
                × Сбросить
              </button>
            </div>
          )}
        </div>

        <hr className={styles.divider} />

        {/* Contact panel */}
        <div className={styles.contactPanel}>
          <p className={styles.contactText}>Отправить конфигурацию товара менеджеру и обсудить детали</p>
          <div className={styles.contactBtns}>
            <a href="#" className={styles.contactBtn} aria-label="Telegram">{TG_ICON} Получить товар</a>
            <a href="#" className={styles.contactBtn} aria-label="WhatsApp">{WA_ICON} Получить товар</a>
          </div>
        </div>
      </div>
    </section>
    </>
  );
}

"use client";

import { useRef, useState, useCallback, useEffect, useMemo } from "react";
import type { CasesData, CaseProject } from "@/lib/types/cases";
import { CaseCard } from "@/components/blocks/case-card";
import styles from "./Cases.module.css";

type SlideItem = CaseProject & { _key: string; _clone: boolean };

const PREV_SVG = (
  <svg width="16" height="16" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
    <path d="M8,16l1.4-1.4L3.8,9H16V7H3.8l5.6-5.6L8,0L0,8L8,16z" />
  </svg>
);

const NEXT_SVG = (
  <svg width="16" height="16" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
    <path d="M8 0L6.59 1.41L12.17 7H0V9H12.17L6.59 14.59L8 16L16 8L8 0Z" />
  </svg>
);

const ANIM_MS = 1000;

function getVisibleCount(): number {
  if (typeof window === "undefined") return 3;
  if (window.innerWidth <= 640) return 1;
  if (window.innerWidth <= 1100) return 2;
  return 3;
}


export function Cases({ data }: { data: CasesData }) {
  const { projects } = data;
  const total = projects.length;

  const [dotIdx, setDotIdx] = useState(0);
  const [visibleN, setVisibleN] = useState(3);
  const [textState, setTextState] = useState<"visible" | "hidden">("visible");
  const [edgeCropped, setEdgeCropped] = useState<Set<number>>(new Set());

  const carouselRef = useRef<HTMLDivElement>(null);
  const trackRef = useRef<HTMLDivElement>(null);
  const viewportRef = useRef<HTMLDivElement>(null);
  const animTimerRef = useRef<ReturnType<typeof setTimeout> | null>(null);
  const isAnimRef = useRef(false);
  const curIdxRef = useRef(0);

  const cloneN = Math.min(visibleN, total);

  const allSlides: SlideItem[] = useMemo(() => {
    const pre: SlideItem[] = projects.slice(-cloneN).map((p, i) => ({ ...p, _key: `pre-${i}`, _clone: true }));
    const orig: SlideItem[] = projects.map((p) => ({ ...p, _key: `orig-${p.id}`, _clone: false }));
    const app: SlideItem[] = projects.slice(0, cloneN).map((p, i) => ({ ...p, _key: `app-${i}`, _clone: true }));
    return [...pre, ...orig, ...app];
  }, [projects, cloneN]);

  const getPeek = useCallback((): number => {
    if (!carouselRef.current) return 0;
    return parseFloat(getComputedStyle(carouselRef.current).getPropertyValue("--projects-carousel-peek")) || 0;
  }, []);

  const getSlideSpan = useCallback((): number => {
    const slide = trackRef.current?.firstElementChild as HTMLElement | null;
    if (!slide) return 0;
    return slide.getBoundingClientRect().width + 28;
  }, []);

  const applyTransform = useCallback(
    (idx: number, animate: boolean) => {
      if (!trackRef.current) return;
      const slideSpan = getSlideSpan();
      if (!slideSpan) return;
      const offset = -(cloneN + idx) * slideSpan + getPeek();
      trackRef.current.style.transition = animate ? `transform ${ANIM_MS}ms ease` : "none";
      trackRef.current.style.transform = `translate3d(${offset}px, 0, 0)`;
    },
    [cloneN, getPeek, getSlideSpan]
  );

  const updateHeight = useCallback(
    (idx: number) => {
      if (!viewportRef.current || !trackRef.current) return;
      const slideEls = trackRef.current.children;
      const start = cloneN + idx;
      let maxH = 0;
      for (let i = start; i < start + visibleN; i++) {
        const el = slideEls[i] as HTMLElement | undefined;
        if (el) maxH = Math.max(maxH, el.offsetHeight);
      }
      if (maxH > 0) viewportRef.current.style.height = `${maxH}px`;
    },
    [cloneN, visibleN]
  );

  const updateEdgeCropped = useCallback(
    (idx: number) => {
      setEdgeCropped(new Set([cloneN + idx - 1, cloneN + idx + visibleN]));
    },
    [cloneN, visibleN]
  );

  const stepTo = useCallback(
    (nextIdx: number) => {
      if (isAnimRef.current) return;
      isAnimRef.current = true;
      setTextState("hidden");
      curIdxRef.current = nextIdx;
      applyTransform(nextIdx, true);
      updateHeight(nextIdx);

      if (animTimerRef.current) clearTimeout(animTimerRef.current);
      animTimerRef.current = setTimeout(() => {
        let corrected = nextIdx;
        if (nextIdx < 0) corrected = total - 1;
        else if (nextIdx >= total) corrected = 0;

        if (corrected !== nextIdx) {
          curIdxRef.current = corrected;
          applyTransform(corrected, false);
          updateHeight(corrected);
        }

        const finalIdx = ((corrected % total) + total) % total;
        setDotIdx(finalIdx);
        updateEdgeCropped(finalIdx);
        isAnimRef.current = false;
        setTextState("visible");
      }, ANIM_MS);
    },
    [applyTransform, updateHeight, updateEdgeCropped, total]
  );

  useEffect(() => {
    const id = requestAnimationFrame(() => {
      requestAnimationFrame(() => {
        const idx = curIdxRef.current;
        applyTransform(idx, false);
        updateHeight(idx);
        updateEdgeCropped(idx);
      });
    });
    return () => cancelAnimationFrame(id);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [allSlides]);

  useEffect(() => {
    const handle = () => setVisibleN(getVisibleCount());
    handle();
    window.addEventListener("resize", handle);
    return () => window.removeEventListener("resize", handle);
  }, []);

  return (
    <section className={styles.section}>
      <div className={styles.head}>
        <h2 className={styles.title}>{data.title}</h2>
        <div className={styles.controls} aria-label="Навигация слайдера проектов">
          <div className={styles.dots} role="tablist" aria-label="Индикатор слайдов">
            {projects.map((_, i) => (
              <button
                key={i}
                type="button"
                role="tab"
                className={`${styles.dot}${i === dotIdx ? ` ${styles.dotActive}` : ""}`}
                aria-label={`Слайд ${i + 1}`}
                aria-selected={i === dotIdx}
                tabIndex={i === dotIdx ? 0 : -1}
                onClick={() => stepTo(i)}
              >
                <svg viewBox="0 0 20 20" aria-hidden="true">
                  <circle cx="10" cy="10" r="8.25" />
                </svg>
              </button>
            ))}
          </div>
          <div className={styles.nav} role="group" aria-label="Переключение слайдов">
            <button type="button" className={styles.arrow} onClick={() => stepTo(dotIdx - 1)} aria-label="Предыдущий слайд">
              {PREV_SVG}
            </button>
            <button type="button" className={styles.arrow} onClick={() => stepTo(dotIdx + 1)} aria-label="Следующий слайд">
              {NEXT_SVG}
            </button>
          </div>
        </div>
      </div>

      <div ref={carouselRef} className={styles.carousel} data-text-state={textState}>
        <div ref={viewportRef} className={styles.viewport}>
          <div ref={trackRef} className={styles.track}>
            {allSlides.map((slide, renderedIdx) => (
              <div
                key={slide._key}
                className={[styles.slide, slide._clone ? styles.slideClone : ""].filter(Boolean).join(" ")}
                aria-hidden={slide._clone ? "true" : undefined}
                data-slide-cropped={edgeCropped.has(renderedIdx) ? "" : undefined}
              >
                <CaseCard slide={slide} />
              </div>
            ))}
          </div>
        </div>
      </div>
    </section>
  );
}

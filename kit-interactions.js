document.querySelectorAll(".cfvsw-hidden-select select").forEach((select) => {
  const container = select.parentElement?.nextElementSibling;

  if (!container || !container.classList.contains("cfvsw-swatches-container")) {
    return;
  }

  const options = Array.from(container.querySelectorAll(".cfvsw-swatches-option[data-slug]"));

  const syncSelectionFromSelect = () => {
    const selected = select.value;

    options.forEach((option) => {
      option.classList.toggle("cfvsw-selected-swatch", option.dataset.slug === selected && selected !== "");
    });
  };

  const updateSelectOption = (swatch) => {
    const value = swatch.classList.contains("cfvsw-selected-swatch") ? swatch.dataset.slug || "" : "";
    select.value = value;
    select.dispatchEvent(new Event("change", { bubbles: true }));
  };

  const onClickSwatchesOption = (swatch) => {
    if (swatch.classList.contains("cfvsw-selected-swatch")) {
      swatch.classList.remove("cfvsw-selected-swatch");
    } else {
      options.forEach((option) => option.classList.remove("cfvsw-selected-swatch"));
      swatch.classList.add("cfvsw-selected-swatch");
    }

    updateSelectOption(swatch);
  };

  options.forEach((option) => {
    option.addEventListener("click", () => onClickSwatchesOption(option));

    option.addEventListener("mouseenter", () => {
      const tooltip = option.dataset.tooltip;

      if (!tooltip || option.querySelector(".cfvsw-tooltip")) {
        return;
      }

      option.insertAdjacentHTML(
        "afterbegin",
        `<div class="cfvsw-tooltip"><span class="cfvsw-tooltip-label">${tooltip}</span></div>`
      );

      const tooltipEl = option.querySelector(".cfvsw-tooltip");
      const inner = option.querySelector(".cfvsw-swatch-inner");

      if (tooltipEl && inner) {
        tooltipEl.style.display = "block";
        tooltipEl.style.bottom = `${inner.offsetHeight}px`;
      }
    });

    option.addEventListener("mouseleave", () => {
      option.querySelector(".cfvsw-tooltip")?.remove();
    });
  });

  select.addEventListener("change", syncSelectionFromSelect);

  container.parentElement
    ?.querySelector(".reset_variations")
    ?.addEventListener("click", (event) => {
      event.preventDefault();
      options.forEach((option) => option.classList.remove("cfvsw-selected-swatch"));
      select.value = "";
      select.dispatchEvent(new Event("change", { bubbles: true }));
    });

  syncSelectionFromSelect();
});

document.querySelectorAll("[data-ohio-switcher], [data-primitive-switcher]").forEach((switcher) => {
  const toggle = () => switcher.classList.toggle("dark");

  switcher.setAttribute("tabindex", "0");
  switcher.setAttribute("role", "button");
  switcher.setAttribute("aria-label", "Toggle color mode");

  switcher.addEventListener("click", toggle);
  switcher.addEventListener("keydown", (event) => {
    if (event.key === "Enter" || event.key === " ") {
      event.preventDefault();
      toggle();
    }
  });
});

document.querySelectorAll("[data-local-mega-menu]").forEach((nav) => {
  const topItems = Array.from(nav.querySelectorAll(":scope > .menu > .nav-item.has-submenu"));
  const passiveTopItems = Array.from(nav.querySelectorAll(":scope > .menu > .nav-item:not(.has-submenu)"));
  const widePanels = Array.from(nav.querySelectorAll(":scope > .menu > .nav-item > .sub-menu-wide"));
  let closeTimer = 0;

  const setWidePanelGeometry = () => {
    const sourceViewport = 1560;
    const sourceLeft = 29.4140625;
    const sourceWidth = 1513.1875;
    const scale = window.innerWidth / sourceViewport;

    widePanels.forEach((panel) => {
      const item = panel.parentElement;

      if (!item) {
        return;
      }

      const itemRect = item.getBoundingClientRect();
      panel.style.left = `${sourceLeft * scale - itemRect.left}px`;

      if (panel.classList.contains("catalog-resource-grid")) {
        const panelGap = 12;
        const items = Array.from(panel.querySelectorAll(":scope > li > a.menu-link"));

        items.forEach((link) => {
          link.style.width = "auto";
        });

        const maxItemWidth = Math.ceil(
          items.reduce((maxWidth, link) => Math.max(maxWidth, link.getBoundingClientRect().width), 0)
        );
        const panelWidth = maxItemWidth * 3 + panelGap * 4;
        const sourcePanelLeft = sourceLeft * scale - itemRect.left;
        const sourcePanelWidth = sourceWidth * scale;
        const centeredLeft = sourcePanelLeft + (sourcePanelWidth - panelWidth) / 2;

        panel.style.setProperty("--catalog-col-gap", `${panelGap}px`);
        panel.style.setProperty("--catalog-col-width", `${maxItemWidth}px`);
        panel.style.left = `${centeredLeft}px`;
        panel.style.width = `${panelWidth}px`;
        return;
      }

      panel.style.width = `${sourceWidth * scale}px`;
    });
  };

  const closeAll = () => {
    topItems.forEach((item) => item.classList.remove("is-open"));
  };

  const openItem = (item) => {
    window.clearTimeout(closeTimer);
    topItems.forEach((navItem) => navItem.classList.toggle("is-open", navItem === item));
    setWidePanelGeometry();
  };

  topItems.forEach((item) => {
    item.addEventListener("mouseenter", () => openItem(item));
    item.addEventListener("focusin", () => openItem(item));
    item.addEventListener("mouseleave", () => {
      closeTimer = window.setTimeout(closeAll, 80);
    });
    item.addEventListener("focusout", () => {
      closeTimer = window.setTimeout(() => {
        if (!item.contains(document.activeElement)) {
          closeAll();
        }
      }, 0);
    });
  });

  passiveTopItems.forEach((item) => {
    item.addEventListener("mouseenter", closeAll);
    item.addEventListener("focusin", closeAll);
  });

  nav.addEventListener("mouseleave", () => {
    closeTimer = window.setTimeout(closeAll, 80);
  });

  window.addEventListener("resize", setWidePanelGeometry);
  setWidePanelGeometry();
});

document.querySelectorAll(".currency-switcher--currencies").forEach((switcher) => {
  const items = Array.from(switcher.querySelectorAll(".currency-switcher-item"));

  const setActiveItem = (nextItem) => {
    items.forEach((item) => {
      const isActive = item === nextItem;
      item.classList.toggle("-active", isActive);
      item.setAttribute("aria-selected", isActive ? "true" : "false");
      item.tabIndex = isActive ? 0 : -1;
    });
  };

  items.forEach((item, index) => {
    item.tabIndex = item.classList.contains("-active") ? 0 : -1;

    item.addEventListener("click", () => {
      setActiveItem(item);
    });

    item.addEventListener("keydown", (event) => {
      if (event.key === "ArrowRight" || event.key === "ArrowDown") {
        event.preventDefault();
        const nextItem = items[(index + 1) % items.length];
        setActiveItem(nextItem);
        nextItem.focus();
      }

      if (event.key === "ArrowLeft" || event.key === "ArrowUp") {
        event.preventDefault();
        const nextItem = items[(index - 1 + items.length) % items.length];
        setActiveItem(nextItem);
        nextItem.focus();
      }
    });
  });
});

document.querySelectorAll("[data-projects-carousel-root]").forEach((root) => {
  const carousel = root.querySelector("[data-projects-carousel]");
  const viewport = root.querySelector("[data-projects-carousel-viewport]");
  const track = root.querySelector("[data-projects-carousel-track]");
  const prevButton = root.querySelector("[data-projects-carousel-prev]");
  const nextButton = root.querySelector("[data-projects-carousel-next]");
  const dotsRoot = root.querySelector("[data-projects-carousel-dots]");

  if (!carousel || !viewport || !track || !prevButton || !nextButton || !dotsRoot) {
    return;
  }

  const originalSlides = Array.from(track.children);
  if (!originalSlides.length) {
    return;
  }

  let visibleSlides = 3;
  let cloneSize = 3;
  let currentIndex = 0;
  let renderedSlides = [];
  let slideSpan = 0;
  let isPointerDown = false;
  let isAnimating = false;
  let animationTimer = 0;
  let dragStartX = 0;
  let dragOffset = 0;
  let dragMoved = false;
  const animationDurationMs = 1000;

  const getVisibleSlides = () => {
    if (window.innerWidth <= 640) {
      return Number(carousel.dataset.itemsMobile || 1);
    }

    if (window.innerWidth <= 1100) {
      return Number(carousel.dataset.itemsTablet || 2);
    }

    return Number(carousel.dataset.itemsDesktop || 3);
  };

  const getGap = () => {
    const trackStyles = window.getComputedStyle(track);
    return parseFloat(trackStyles.columnGap || trackStyles.gap || "0");
  };

  const getPeek = () => {
    const carouselStyles = window.getComputedStyle(carousel);
    return parseFloat(carouselStyles.getPropertyValue("--projects-carousel-peek") || "0");
  };

  const hideCardText = () => {
    carousel.classList.remove("is-text-visible");
    carousel.classList.add("is-text-hidden");
  };

  const showCardText = () => {
    carousel.classList.remove("is-text-visible");
    void carousel.offsetWidth;
    carousel.classList.remove("is-text-hidden");
    carousel.classList.add("is-text-visible");
  };

  const updateDots = () => {
    Array.from(dotsRoot.querySelectorAll(".projects-carousel-dot")).forEach((dot, index) => {
      const isActive = index === currentIndex;
      dot.classList.toggle("is-active", isActive);
      dot.setAttribute("aria-selected", isActive ? "true" : "false");
      dot.tabIndex = isActive ? 0 : -1;
    });
  };

  const updateEdgeCroppedSlides = () => {
    const viewportRect = viewport.getBoundingClientRect();

    renderedSlides.forEach((slide) => {
      const slideRect = slide.getBoundingClientRect();
      const overlapsViewport = slideRect.right > viewportRect.left && slideRect.left < viewportRect.right;
      const isFullyVisible = slideRect.left >= viewportRect.left - 1 && slideRect.right <= viewportRect.right + 1;

      slide.classList.toggle("is-edge-cropped", overlapsViewport && !isFullyVisible);
    });
  };

  const updateHeight = () => {
    const start = cloneSize + currentIndex;
    const visibleSet = renderedSlides.slice(start, start + visibleSlides);
    const nextHeight = visibleSet.reduce((maxHeight, slide) => Math.max(maxHeight, slide.offsetHeight), 0);

    if (nextHeight > 0) {
      viewport.style.height = `${nextHeight}px`;
    }
  };

  const setTransform = (animate, extraOffset = 0) => {
    const activeRenderedIndex = cloneSize + currentIndex;
    const offset = -activeRenderedIndex * slideSpan + getPeek() + extraOffset;
    track.style.transition = animate ? "1s ease" : "none";
    track.style.transform = `translate3d(${offset}px, 0, 0)`;
    updateDots();
    updateHeight();

    if (!animate) {
      requestAnimationFrame(updateEdgeCroppedSlides);
    }
  };

  const buildDots = () => {
    dotsRoot.innerHTML = "";

    originalSlides.forEach((_, index) => {
      const dot = document.createElement("button");
      dot.type = "button";
      dot.className = "projects-carousel-dot";
      dot.setAttribute("role", "tab");
      dot.setAttribute("aria-label", `Слайд ${index + 1}`);
      dot.innerHTML =
        '<svg viewBox="0 0 20 20" aria-hidden="true"><circle cx="10" cy="10" r="8.25"></circle></svg>';
      dot.addEventListener("click", () => {
        if (index !== currentIndex) {
          stepTo(index);
        }
      });
      dotsRoot.appendChild(dot);
    });
  };

  const rebuildTrack = () => {
    visibleSlides = getVisibleSlides();
    cloneSize = Math.min(visibleSlides, originalSlides.length);
    carousel.style.setProperty("--projects-visible-slides", String(visibleSlides));

    track.querySelectorAll(".is-clone").forEach((slide) => slide.remove());

    const prependSlides = originalSlides.slice(-cloneSize).map((slide) => {
      const clone = slide.cloneNode(true);
      clone.classList.add("is-clone");
      clone.setAttribute("aria-hidden", "true");
      return clone;
    });

    const appendSlides = originalSlides.slice(0, cloneSize).map((slide) => {
      const clone = slide.cloneNode(true);
      clone.classList.add("is-clone");
      clone.setAttribute("aria-hidden", "true");
      return clone;
    });

    prependSlides.reverse().forEach((slide) => {
      track.insertBefore(slide, track.firstChild);
    });

    appendSlides.forEach((slide) => {
      track.appendChild(slide);
    });

    renderedSlides = Array.from(track.children);

    const gap = getGap();
    const slideWidth = originalSlides[0].getBoundingClientRect().width;
    slideSpan = slideWidth + gap;
    currentIndex = ((currentIndex % originalSlides.length) + originalSlides.length) % originalSlides.length;
    setTransform(false);
    showCardText();
  };

  const stepTo = (nextIndex) => {
    if (isAnimating) {
      return;
    }

    window.clearTimeout(animationTimer);
    isAnimating = true;
    hideCardText();
    currentIndex = nextIndex;
    setTransform(true);

    animationTimer = window.setTimeout(() => {
      if (currentIndex < 0) {
        currentIndex = originalSlides.length - 1;
        setTransform(false);
      } else if (currentIndex >= originalSlides.length) {
        currentIndex = 0;
        setTransform(false);
      }

      isAnimating = false;
      updateEdgeCroppedSlides();
      showCardText();
    }, animationDurationMs);
  };

  buildDots();
  rebuildTrack();

  prevButton.addEventListener("click", () => {
    stepTo(currentIndex - 1);
  });

  nextButton.addEventListener("click", () => {
    stepTo(currentIndex + 1);
  });

  viewport.addEventListener("pointerdown", (event) => {
    if (isAnimating) {
      return;
    }

    isPointerDown = true;
    dragMoved = false;
    dragOffset = 0;
    dragStartX = event.clientX;
    viewport.setPointerCapture(event.pointerId);
    track.style.transition = "none";
  });

  viewport.addEventListener("pointermove", (event) => {
    if (!isPointerDown) {
      return;
    }

    dragOffset = event.clientX - dragStartX;
    dragMoved = Math.abs(dragOffset) > 6;
    setTransform(false, dragOffset);
  });

  const finishDrag = () => {
    if (!isPointerDown) {
      return;
    }

    isPointerDown = false;
    const threshold = slideSpan * 0.22;

    if (dragOffset <= -threshold) {
      stepTo(currentIndex + 1);
    } else if (dragOffset >= threshold) {
      stepTo(currentIndex - 1);
    } else {
      setTransform(true);
    }

    dragOffset = 0;
  };

  viewport.addEventListener("pointerup", finishDrag);
  viewport.addEventListener("pointercancel", finishDrag);
  viewport.addEventListener("lostpointercapture", finishDrag);
  viewport.addEventListener("click", (event) => {
    if (dragMoved) {
      event.preventDefault();
      event.stopPropagation();
      dragMoved = false;
    }
  }, true);

  dotsRoot.addEventListener("keydown", (event) => {
    if (event.key === "ArrowRight") {
      event.preventDefault();
      stepTo(currentIndex + 1);
    }

    if (event.key === "ArrowLeft") {
      event.preventDefault();
      stepTo(currentIndex - 1);
    }
  });

  window.addEventListener("resize", rebuildTrack);
  window.addEventListener("load", rebuildTrack, { once: true });
  originalSlides.forEach((slide) => {
    slide.querySelectorAll("img").forEach((image) => {
      image.addEventListener("load", updateHeight, { once: true });
    });
  });
});

document.querySelectorAll("[data-creative-hamburger-overlay]").forEach((overlay) => {
  const root = overlay.closest(".ohio-header-stage") || document;
  const toggle = root.querySelector("[data-creative-hamburger-toggle]");
  const close = overlay.querySelector("[data-creative-hamburger-close]");
  const items = Array.from(overlay.querySelectorAll(".creative-hamburger-item"));

  const setOpenState = (isOpen) => {
    overlay.classList.toggle("is-open", isOpen);
    overlay.setAttribute("aria-hidden", isOpen ? "false" : "true");
    document.body.classList.toggle("creative-hamburger-open", isOpen);
  };

  const setActiveItem = (nextItem) => {
    items.forEach((item) => item.classList.toggle("is-active", !!nextItem && item === nextItem));
  };

  toggle?.addEventListener("click", () => {
    const nextState = !overlay.classList.contains("is-open");
    setOpenState(nextState);

    if (!nextState) {
      setActiveItem(null);
    }
  });

  close?.addEventListener("click", () => {
    setOpenState(false);
    setActiveItem(null);
  });

  overlay.addEventListener("click", (event) => {
    if (event.target === overlay) {
      setOpenState(false);
      setActiveItem(null);
    }
  });

  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape" && overlay.classList.contains("is-open")) {
      setOpenState(false);
      setActiveItem(null);
    }
  });

  overlay.querySelector(".creative-hamburger-nav-holder")?.addEventListener("mouseleave", () => {
    setActiveItem(null);
  });

  items.forEach((item) => {
    const link = item.querySelector(".creative-hamburger-link");
    const hasSubmenu = item.classList.contains("has-submenu");

    item.addEventListener("pointerenter", () => setActiveItem(item));
    item.addEventListener("focusin", () => setActiveItem(item));

    link?.addEventListener("click", (event) => {
      if (!hasSubmenu) {
        return;
      }

      event.preventDefault();
      setActiveItem(item.classList.contains("is-active") ? null : item);
    });
  });
});

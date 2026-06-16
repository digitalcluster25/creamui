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

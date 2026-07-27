import { expect, test } from "@playwright/test";

const visibleCategory = "Печи для русской бани";
const emptyCategories = ["Печи для сауны", "Парогенераторы и хаммам", "Коммерческие решения"];

test("home categories show only categories with published products", async ({ page }) => {
  await page.setViewportSize({ width: 1440, height: 900 });
  await page.goto("/", { waitUntil: "networkidle" });

  const homeCategories = page.locator('[data-home-block="categories"]');
  await expect(homeCategories.getByRole("link", { name: visibleCategory }).first()).toBeVisible();

  for (const category of emptyCategories) {
    await expect(homeCategories.getByRole("link", { name: category })).toHaveCount(0);
  }
});

test("catalog overview shows only categories with published products", async ({ page }) => {
  await page.setViewportSize({ width: 1440, height: 900 });
  await page.goto("/catalog", { waitUntil: "networkidle" });

  const catalogCategories = page
    .locator("section")
    .filter({ has: page.getByRole("heading", { name: "Основные направления каталога" }) })
    .first();

  await expect(catalogCategories.getByRole("link", { name: visibleCategory }).first()).toBeVisible();

  for (const category of emptyCategories) {
    await expect(catalogCategories.getByRole("link", { name: category })).toHaveCount(0);
  }
});

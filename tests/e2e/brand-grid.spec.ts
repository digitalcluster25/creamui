import { expect, test } from "@playwright/test";

test.describe("brand category grid", () => {
  test("uses the catalog grid on desktop", async ({ page }) => {
    await page.setViewportSize({ width: 1440, height: 900 });
    await page.goto("/brands/eos", { waitUntil: "networkidle" });

    const grid = page.locator('[class*="gridCatalog"]');
    await expect(grid).toHaveCount(1);
    await expect
      .poll(() => grid.evaluate((element) => getComputedStyle(element).gridTemplateColumns.split(" ").length))
      .toBe(4);
  });

  test("collapses without horizontal overflow on mobile", async ({ page }) => {
    await page.setViewportSize({ width: 390, height: 844 });
    await page.goto("/brands/eos", { waitUntil: "networkidle" });

    const grid = page.locator('[class*="gridCatalog"]');
    await expect(grid).toHaveCount(1);
    await expect
      .poll(() => grid.evaluate((element) => getComputedStyle(element).gridTemplateColumns.split(" ").length))
      .toBe(1);
    await expect(page.locator("body")).toHaveCSS("overflow-x", "visible");
    expect(await page.evaluate(() => document.documentElement.scrollWidth)).toBeLessThanOrEqual(390);
  });
});

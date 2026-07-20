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
    const cards = grid.locator('[class*="cardMenu"]');
    expect(await cards.count()).toBeGreaterThan(0);
    await expect(cards.locator("img")).toHaveCount(0);
  });

  test("uses the menu surface without category images and equal card heights", async ({ page }) => {
    await page.setViewportSize({ width: 1440, height: 900 });
    await page.goto("/brands/eos", { waitUntil: "networkidle" });

    const grid = page.locator('[class*="gridCatalog"]');
    const cards = grid.locator('[class*="cardMenu"]');
    const cardCount = await cards.count();
    expect(cardCount).toBeGreaterThan(0);
    await expect(cards).toHaveCount(cardCount);
    await expect(cards.locator("img")).toHaveCount(0);
    await expect(cards.first()).toHaveCSS("background-color", "rgba(136, 136, 137, 0.05)");

    const heights = await cards.evaluateAll((elements) =>
      elements.map((element) => {
        const rect = element.getBoundingClientRect();
        return { height: Math.round(rect.height), top: Math.round(rect.top) };
      }),
    );
    const firstRowTop = heights[0]?.top;
    expect(new Set(heights.filter((rect) => rect.top === firstRowTop).map((rect) => rect.height)).size).toBe(1);
  });
});

import { expect, test } from "@playwright/test";

test.describe("catalog admin filters", () => {
  test("shows configured input attribute filters on desktop and mobile", async ({ page }) => {
    for (const viewport of [
      { width: 1440, height: 900 },
      { width: 390, height: 844 },
    ]) {
      await page.setViewportSize(viewport);
      await page.goto("/catalog/russian-bath-stoves", { waitUntil: "domcontentloaded" });

      await expect(page.getByPlaceholder("Объем парной")).toBeVisible();
    }
  });

  test("matches steam room volume numeric ranges", async ({ page }) => {
    await page.setViewportSize({ width: 1440, height: 900 });
    await page.goto("/catalog/russian-bath-stoves", { waitUntil: "domcontentloaded" });

    const volumeFilter = page.getByPlaceholder("Объем парной");
    await volumeFilter.fill("25");
    await expect.poll(() => page.getByTestId("catalog-preview-grid").locator("a").count()).toBeGreaterThan(0);
    await expect(page.getByTestId("catalog-preview-grid").getByText("Печь Геленджик").first()).toBeVisible();

    await volumeFilter.fill("25 м3");
    await expect.poll(() => page.getByTestId("catalog-preview-grid").locator("a").count()).toBeGreaterThan(0);
    await expect(page.getByTestId("catalog-preview-grid").getByText("Печь Геленджик").first()).toBeVisible();
  });
});

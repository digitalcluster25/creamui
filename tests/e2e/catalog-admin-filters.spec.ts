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
});

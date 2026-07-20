import { expect, test } from "@playwright/test";

for (const viewport of [
  { name: "desktop", width: 1440, height: 900 },
  { name: "mobile", width: 390, height: 844 },
]) {
  test(`disabled home block has no placeholder gap on ${viewport.name}`, async ({ page }) => {
    await page.setViewportSize({ width: viewport.width, height: viewport.height });
    await page.goto("/", { waitUntil: "networkidle" });

    await expect(page.locator('[data-home-block="cases"]')).toHaveCount(0);
    await expect(page.locator('[data-home-block="products"]')).toBeVisible();
  });
}

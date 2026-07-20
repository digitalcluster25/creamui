import { expect, test } from "@playwright/test";

for (const viewport of [
  { name: "desktop", width: 1440, height: 900 },
  { name: "mobile", width: 390, height: 844 },
]) {
  test(`home projects carousel is usable on ${viewport.name}`, async ({ page }) => {
    await page.setViewportSize({ width: viewport.width, height: viewport.height });
    await page.goto("/", { waitUntil: "networkidle" });

    const carousel = page.locator("section").filter({ has: page.locator('[aria-label="Индикатор слайдов"]') }).first();
    await expect(carousel).toBeVisible();
    await expect(carousel.locator('[role="tab"]:visible').first()).toBeVisible();

    const next = carousel.locator('button[aria-label="Следующий слайд"]:visible').first();
    await expect(next).toBeEnabled();
    await next.click();
    await expect(carousel.locator('[role="tab"][aria-selected="true"]:visible')).toHaveCount(1);
  });
}

import { expect, test } from "@playwright/test";

for (const viewport of [
  { name: "desktop", width: 1440, height: 900 },
  { name: "mobile", width: 390, height: 844 },
]) {
  test(`sitemap is available and adapts on ${viewport.name}`, async ({ page }) => {
    await page.setViewportSize({ width: viewport.width, height: viewport.height });
    await page.goto("/sitemap", { waitUntil: "networkidle" });

    await expect(page).toHaveTitle("Карта сайта | HWS");
    await expect(page.getByRole("heading", { level: 1, name: "Карта сайта" })).toBeVisible();
    await expect(page.locator("footer").getByRole("link", { name: "Карта сайта" })).toHaveAttribute("href", "/sitemap");
    await expect(page.getByRole("navigation", { name: "Разделы сайта" })).toBeVisible();
    await expect(page.getByRole("heading", { level: 2, name: "Каталог" })).toBeVisible();
    await expect(page.locator('a[href^="/product/"]')).not.toHaveCount(0);
    await expect(page.locator('a[href^="/knowledge/"]')).not.toHaveCount(0);

    const pageWidths = await page.evaluate(() => ({
      client: document.documentElement.clientWidth,
      scroll: document.documentElement.scrollWidth,
    }));
    expect(pageWidths.scroll).toBeLessThanOrEqual(pageWidths.client);
  });
}

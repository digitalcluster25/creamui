import { expect, test } from "@playwright/test";

for (const viewport of [
  { name: "desktop", width: 1440, height: 900 },
  { name: "mobile", width: 390, height: 844 },
]) {
  test(`main navigation mirrors WordPress menu on ${viewport.name}`, async ({ page }) => {
    await page.setViewportSize({ width: viewport.width, height: viewport.height });
    await page.goto("/", { waitUntil: "networkidle" });

    const labels = await page
      .locator('nav[aria-label="Навигация HWS"] > ul > li > a > span:first-child')
      .allTextContents();

    expect(labels).toEqual(["Каталог", "Бренды", "База знаний"]);
    expect(labels).not.toContain("Контакты");
  });
}

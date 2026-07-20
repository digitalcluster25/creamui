import { expect, test } from "@playwright/test";

for (const viewport of [
  { name: "desktop", width: 1440, height: 900 },
  { name: "mobile", width: 390, height: 844 },
]) {
  test(`main menu uses the supplied category icons on ${viewport.name}`, async ({ page }) => {
    await page.setViewportSize({ width: viewport.width, height: viewport.height });
    await page.goto("/", { waitUntil: "networkidle" });

    for (const asset of [
      "menu-russian-bath.png",
      "menu-sauna.png",
      "menu-hammam.png",
      "menu-commercial.png",
    ]) {
      await expect(page.locator(`img[src*="${asset}"]`), asset).toHaveCount(1);
    }
  });
}

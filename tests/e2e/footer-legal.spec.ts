import { expect, test } from "@playwright/test";

const copyright = "© 2016-2026 HWS. Все права защищены";

for (const viewport of [
  { name: "desktop", width: 1440, height: 900 },
  { name: "mobile", width: 390, height: 844 },
]) {
  test(`legal panel has equal vertical spacing on ${viewport.name}`, async ({ page }) => {
    await page.setViewportSize({ width: viewport.width, height: viewport.height });
    await page.goto("/", { waitUntil: "networkidle" });

    const copyrightText = page.getByText(copyright, { exact: true });
    await expect(copyrightText).toBeVisible();

    const spacing = await copyrightText.evaluate((element) => {
      const legalInner = element.parentElement;
      const legal = legalInner?.parentElement;
      if (!legalInner || !legal) {
        throw new Error("Legal panel structure not found");
      }

      const legalStyle = getComputedStyle(legalInner);
      const textRect = element.getBoundingClientRect();
      const legalRect = legal.getBoundingClientRect();
      return {
        paddingTop: parseFloat(legalStyle.paddingTop),
        paddingBottom: parseFloat(legalStyle.paddingBottom),
        desktopTop: textRect.top - legalRect.top,
        desktopBottom: legalRect.bottom - textRect.bottom,
      };
    });

    expect(Math.abs(spacing.paddingTop - spacing.paddingBottom)).toBeLessThanOrEqual(1);
    if (viewport.name === "desktop") {
      expect(Math.abs(spacing.desktopTop - spacing.desktopBottom)).toBeLessThanOrEqual(1);
    }
  });
}

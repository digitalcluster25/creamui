import { expect, test } from "@playwright/test";

for (const viewport of [
  { name: "desktop", width: 1440, height: 900 },
  { name: "mobile", width: 390, height: 844 },
]) {
  test(`footer shows current contacts and timezones on ${viewport.name}`, async ({ page }) => {
    await page.setViewportSize({ width: viewport.width, height: viewport.height });
    await page.goto("/", { waitUntil: "networkidle" });

    const footerText = await page.locator("footer").innerText();
    expect(footerText).toContain("+994 50 859 98 67");
    expect(footerText).toContain("office@hws.shopping");
    expect(footerText).toContain("10:00 - 19:00 (Узбекистан, UTC+5)");
    expect(footerText).toContain("10:00 - 19:00 (Азербайджан, UTC+4)");
  });
}

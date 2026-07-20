import { expect, test } from "@playwright/test";

for (const viewport of [
  { name: "desktop", width: 1440, height: 900, expectedGap: 60 },
  { name: "mobile", width: 390, height: 844, expectedGap: 20 },
]) {
  test(`homepage sections use 0px top and ${viewport.expectedGap}px bottom spacing on ${viewport.name}`, async ({ page }) => {
    await page.setViewportSize({ width: viewport.width, height: viewport.height });
    await page.goto("/", { waitUntil: "networkidle" });

    const blocks = page.locator("[data-home-page] [data-home-block]");
    expect([8, 9]).toContain(await blocks.count());

    const spacing = await blocks.evaluateAll((elements) => elements.map((element) => {
      const style = getComputedStyle(element);
      return {
        name: element.getAttribute("data-home-block"),
        paddingTop: parseFloat(style.paddingTop),
        paddingBottom: parseFloat(style.paddingBottom),
        marginBottom: parseFloat(style.marginBottom),
      };
    }));

    for (const block of spacing.filter(({ name }) => name !== "footer")) {
      expect(block.paddingTop, `${block.name} top padding`).toBe(0);
      expect(block.paddingBottom + block.marginBottom, `${block.name} bottom spacing`).toBe(viewport.expectedGap);
    }
  });
}

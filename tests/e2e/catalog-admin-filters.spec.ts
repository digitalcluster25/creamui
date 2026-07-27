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
      await expect(page.getByRole("button", { name: /Тип топлива/ })).toBeVisible();
      await expect(page.getByRole("button", { name: /Мощность/ })).toBeVisible();
      await expect(page.getByRole("button", { name: /Напряжение/ })).toBeVisible();
    }
  });

  test("shows and applies configured multicheck filters", async ({ page }) => {
    await page.setViewportSize({ width: 1440, height: 900 });
    await page.goto("/catalog/russian-bath-stoves", { waitUntil: "networkidle" });

    await page.getByRole("button", { name: /Тип топлива/ }).click();
    await page.getByText("дрова", { exact: true }).click();

    await expect(page.getByRole("button", { name: /дрова/i })).toBeVisible();
    await expect.poll(() => page.getByTestId("catalog-preview-grid").locator("a").count()).toBeGreaterThan(0);
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

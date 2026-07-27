import { expect, test } from "@playwright/test";

test.describe("catalog and brand seo texts", () => {
  test("catalog root has rewritten seo text at the bottom", async ({ page }) => {
    await page.setViewportSize({ width: 1440, height: 900 });
    await page.goto("/catalog", { waitUntil: "networkidle" });

    const seo = page.getByTestId("catalog-seo");
    await expect(seo).toBeVisible();
    await expect(seo).toContainText("Оборудование для бани, сауны и СПА");
    await expect(seo).toContainText("Подбор начинается с типа помещения");
    await expect(seo).not.toContainText(/branch-aware|query-фильтр|UX|mojibake/i);
  });

  test("category page has rewritten seo text at the bottom", async ({ page }) => {
    await page.setViewportSize({ width: 1440, height: 900 });
    await page.goto("/catalog/russian-bath-stoves", { waitUntil: "networkidle" });

    const seo = page.getByTestId("catalog-seo");
    await expect(seo).toBeVisible();
    await expect(seo).toContainText("Печи для русской бани");
    await expect(seo).toContainText("При выборе учитывайте объём парной");
    await expect(seo).not.toContainText(/branch-aware|query-фильтр|UX|mojibake/i);
  });

  test("brand page has rewritten seo text at the bottom", async ({ page }) => {
    await page.setViewportSize({ width: 1440, height: 900 });
    await page.goto("/brands/easysteam", { waitUntil: "networkidle" });

    const seo = page.getByTestId("catalog-seo");
    await expect(seo).toBeVisible();
    await expect(seo).toContainText("EasySteam");
    await expect(seo).toContainText("оборудование для бани, сауны и СПА");
    await expect(seo).toContainText("Менеджеры HWS помогут выбрать подходящую модель");
    await expect(seo).not.toContainText(/branch-aware|query-фильтр|UX|mojibake/i);
  });
});

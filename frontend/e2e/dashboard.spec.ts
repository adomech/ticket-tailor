import { test, expect } from "@playwright/test";
import AxeBuilder from "@axe-core/playwright";
test("answers the three organiser questions", async ({ page }) => {
  await page.goto("/");
  await expect(
    page.getByRole("heading", { name: "Earnings during this period" }),
  ).toBeVisible();
  await expect(page.getByText("£988.64").first()).toBeVisible();
  await expect(page.getByText("£445.36").first()).toBeVisible();
  await expect(
    page.getByText("Historical fixture data as at 2026-06-20"),
  ).toBeVisible();
  await expect(
    page.getByRole("heading", { name: "Why sales and your bank differ" }),
  ).toBeVisible();
  await page.getByLabel("Earnings period").selectOption("1");
  await expect(page.getByText("-£43.80").first()).toBeVisible();
});
test("supports keyboard details and accessibility smoke check", async ({
  page,
}) => {
  await page.goto("/");
  const detail = page
    .locator("summary")
    .filter({ hasText: "Details for re_0004" });
  await detail.focus();
  await page.keyboard.press("Enter");
  await expect(
    detail.locator("..").getByText("Balance movement"),
  ).toBeVisible();
  const results = await new AxeBuilder({ page }).analyze();
  expect(
    results.violations.filter((v) =>
      ["serious", "critical"].includes(v.impact ?? ""),
    ),
  ).toEqual([]);
});

import { expect } from "@playwright/test"
import { createBdd } from "playwright-bdd"

const { When } = createBdd()

When("I select the document row {string}", async ({ page }, title: string) => {
  const row = page.locator(".p-datatable tbody tr").filter({ hasText: title })
  await expect(row).toHaveCount(1)
  await row.locator('input[type="checkbox"]').first().check()
})

When("I press the document bulk move button", async ({ page }) => {
  const button = page.locator("button").filter({ hasText: /^\s*Move\s*$/ })
  await expect(button).toHaveCount(1)
  await button.click()
})

When("I choose the document destination {string}", async ({ page }, title: string) => {
  const dialog = page.getByRole("dialog").filter({ hasText: "Select the destination folder" })
  await expect(dialog).toBeVisible()
  await dialog.locator('[role="combobox"]').first().click()
  await page.getByRole("option", { name: title, exact: true }).click()
})

When("I open the document folder {string}", async ({ page }, title: string) => {
  const row = page.locator(".p-datatable tbody tr").filter({ hasText: title })
  await expect(row).toHaveCount(1)
  await row.getByText(title, { exact: true }).first().click()
  await expect(page.locator(".p-datatable tbody")).toBeVisible()
})

When("I return to the previous document folder", async ({ page }) => {
  await page.goBack()
  await expect(page.locator(".p-datatable tbody")).toBeVisible()
})

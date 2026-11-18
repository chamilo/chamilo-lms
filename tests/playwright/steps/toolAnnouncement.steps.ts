import { expect } from "@playwright/test"
import { createBdd } from "playwright-bdd"

const { When } = createBdd()

// Not ported: the Vue announcement form's "Recipients" field is a PrimeVue MultiSelect (BaseMultiSelect),
// whose focusable input carries the id `announcement_recipients` and whose options live in an overlay.
// Picking a person replaces the default "Everyone" chip (see AnnouncementFormView.updateRecipients()).
When("I choose {string} in the announcement recipients", async ({ page }, optionText: string) => {
  await page.locator(".field:has(#announcement_recipients) .p-multiselect").click()
  const option = page.locator('[role="option"]').filter({ hasText: optionText }).first()
  await expect(option).toBeVisible()
  await option.click()
  await page.keyboard.press("Escape")
})

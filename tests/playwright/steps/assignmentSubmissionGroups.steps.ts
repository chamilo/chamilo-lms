import { expect } from "@playwright/test"
import { createBdd } from "playwright-bdd"

const { Then, When } = createBdd()

Then("the assignment list should contain {int} distinct submitters", async ({ page }, count: number) => {
  await expect(page.locator('[data-testid^="assignment-submission-count-"]')).toHaveCount(count)
})

When("I sort the assignment submitters by number submitted descending", async ({ page }) => {
  const header = page.getByRole("columnheader", { name: "Number submitted" })
  // The header and the rows are on screen before the list has settled its sort storage key (it depends on the
  // user and the course context, which load asynchronously). When the key arrives it reloads the saved sort and
  // replaces the one just chosen, so a click can be wiped out. Click again until the sort sticks.
  await expect(page.locator("tbody tr").first()).toBeVisible()
  await expect(async () => {
    await header.click()
    await expect(header).toHaveAttribute("aria-sort", "ascending", { timeout: 2_000 })
  }).toPass({ timeout: 20_000 })
  await expect(async () => {
    await header.click()
    await expect(header).toHaveAttribute("aria-sort", "descending", { timeout: 2_000 })
  }).toPass({ timeout: 20_000 })
})

Then("the assignment submitters should keep that sort after reloading", async ({ page }) => {
  await page.reload()
  await expect(page.getByRole("columnheader", { name: "Number submitted" })).toHaveAttribute("aria-sort", "descending")
})

When("I sort the selected student's submissions by title descending", async ({ page }) => {
  const header = page.locator('[data-testid="assignment-student-submission-details"]').getByRole("columnheader", {
    name: "Title",
  })
  await header.click()
  await expect(header).toHaveAttribute("aria-sort", "ascending")
  await header.click()
  await expect(header).toHaveAttribute("aria-sort", "descending")
})

Then("the selected student's submissions should retain title descending", async ({ page }) => {
  const header = page.locator('[data-testid="assignment-student-submission-details"]').getByRole("columnheader", {
    name: "Title",
  })
  await expect(header).toHaveAttribute("aria-sort", "descending")
})

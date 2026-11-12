import { expect } from "@playwright/test"
import { createBdd } from "playwright-bdd"

const { Then, When } = createBdd()

Then("the assignment list should contain {int} distinct submitters", async ({ page }, count: number) => {
  await expect(page.locator('[data-testid^="assignment-submission-count-"]')).toHaveCount(count)
})

When("I sort the assignment submitters by number submitted descending", async ({ page }) => {
  const header = page.getByRole("columnheader", { name: "Number submitted" })
  await header.click()
  await expect(header).toHaveAttribute("aria-sort", "ascending")
  await header.click()
  await expect(header).toHaveAttribute("aria-sort", "descending")
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

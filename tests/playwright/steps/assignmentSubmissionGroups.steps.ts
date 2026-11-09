import { expect } from "@playwright/test"
import { createBdd } from "playwright-bdd"

const { Then } = createBdd()

Then("the assignment list should contain {int} distinct submitters", async ({ page }, count: number) => {
  await expect(page.locator('[data-testid^="assignment-submission-count-"]')).toHaveCount(count)
})

import { expect } from "@playwright/test"
import { createBdd } from "playwright-bdd"

const { Then } = createBdd()

Then("the Gradebook list view should keep learner names visible while scores scroll horizontally", async ({ page }) => {
  // A narrow viewport exposes horizontal overflow even with one evaluation.
  await page.setViewportSize({ width: 480, height: 800 })

  const table = page.locator(".gradebook-flat-table")
  const scroller = table.locator(".p-datatable-table-container")
  const row = table.locator(".p-datatable-tbody > tr").first()
  const cells = row.locator("td")

  await expect(row).toBeVisible()
  await expect(row.locator("td.p-datatable-frozen-column")).toHaveCount(2)
  await expect(scroller).toBeVisible()
  await expect.poll(() => scroller.evaluate((element) => element.scrollWidth - element.clientWidth)).toBeGreaterThan(12)

  const before = await cells.evaluateAll((elements) => elements.map((cell) => cell.getBoundingClientRect().left))
  await scroller.evaluate((element) => {
    element.scrollLeft = element.scrollWidth - element.clientWidth
  })
  await expect.poll(() => scroller.evaluate((element) => element.scrollLeft)).toBeGreaterThan(12)
  const after = await cells.evaluateAll((elements) => elements.map((cell) => cell.getBoundingClientRect().left))

  // Both parts of the learner's name must stay in place; other columns must move.
  expect(Math.abs(after[0] - before[0])).toBeLessThan(3)
  expect(Math.abs(after[1] - before[1])).toBeLessThan(3)
  expect(after[2]).toBeLessThan(before[2] - 10)
})

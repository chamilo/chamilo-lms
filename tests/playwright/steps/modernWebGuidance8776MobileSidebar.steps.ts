import { expect, type Locator, type Page } from "@playwright/test"
import { createBdd } from "playwright-bdd"

const { When, Then } = createBdd()
const toggles = new WeakMap<Page, Locator>()

When("I inspect the MWG8776 mobile sidebar toggle", async ({ page }) => {
  await page.setViewportSize({ width: 390, height: 844 })
  await page.goto("/account/home", { waitUntil: "domcontentloaded", timeout: 15_000 })
  const toggle = page.locator(".app-sidebar__topbar-button")
  await expect(toggle).toBeVisible({ timeout: 15_000 })
  toggles.set(page, toggle)
})

Then("the MWG8776 mobile sidebar toggle must support accessible keyboard interaction", async ({ page }) => {
  const toggle = toggles.get(page)
  expect(toggle, "The visible mobile sidebar toggle must be available").toBeDefined()
  const control = toggle!
  const issues: string[] = []

  const hasButtonSemantics = await control.evaluate((el) =>
    el.tagName.toLowerCase() === "button" || el.getAttribute("role") === "button",
  )
  if (!hasButtonSemantics) issues.push("Mobile sidebar toggle lacks button semantics")

  const hasAccessibleName = await expect(control).toHaveAccessibleName(/\S/, { timeout: 1_000 }).then(
    () => true,
    () => false,
  )
  if (!hasAccessibleName) issues.push("Mobile sidebar toggle lacks an accessible name")

  const expanded = await control.getAttribute("aria-expanded")
  if (expanded !== "true" && expanded !== "false") {
    issues.push("Mobile sidebar toggle does not expose aria-expanded")
  }

  const root = page.locator("#app")
  const isClosed = () => root.evaluate((el) => el.classList.contains("app--sidebar-inactive"))

  await control.focus()
  await expect(control).toBeFocused()
  const beforeEnter = await isClosed()
  await page.keyboard.press("Enter")
  const enterWorked = await expect.poll(isClosed, { timeout: 1_500, intervals: [100, 200, 400] })
    .not.toBe(beforeEnter).then(() => true, () => false)
  if (!enterWorked) issues.push("Enter does not toggle the mobile sidebar")

  await control.focus()
  const beforeSpace = await isClosed()
  await page.keyboard.press("Space")
  const spaceWorked = await expect.poll(isClosed, { timeout: 1_500, intervals: [100, 200, 400] })
    .not.toBe(beforeSpace).then(() => true, () => false)
  if (!spaceWorked) issues.push("Space does not toggle the mobile sidebar")

  // Restore the initial sidebar state through the pointer if keyboard interaction worked.
  const finalState = await isClosed()
  if (finalState !== beforeEnter) {
    await control.click()
    await expect.poll(isClosed, { timeout: 1_500 }).toBe(beforeEnter)
  }

  console.log(`MWG8776 mobile sidebar: ${JSON.stringify({ issues, keyboard: { enterWorked, spaceWorked } })}`)
  expect(issues, "Mobile navigation accessibility findings in Chromium").toEqual([])
})

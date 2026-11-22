import { expect, test, type Page } from "@playwright/test"
import { createBdd } from "playwright-bdd"

const { When, Then } = createBdd()
const blockedGroupWrites = new WeakMap<Page, string[]>()

async function getGroupCreateButton(page: Page) {
  const groupList = page.locator(".social-groups")
  try {
    await expect(groupList).toBeVisible({ timeout: 8_000 })
  } catch {
    const headings = (await page.locator("h1, h2").allTextContents()).map((value) => value.trim()).slice(0, 6)
    throw new Error(
      `The social groups view is not rendered. Path: ${new URL(page.url()).pathname}; ` +
        `visible headings: ${JSON.stringify(headings)}. Check the route and client-side errors before auditing buttons.`,
    )
  }

  // The label is translated for the current user. Select the only action in the list header.
  return groupList.locator(":scope > div:first-child button").first()
}

When("I open the MWG8776 group creation dialog", async ({ page }) => {
  const createButton = await getGroupCreateButton(page)
  await expect(createButton).toBeVisible({ timeout: 8_000 })
  await createButton.click()
  await expect(page.locator(".p-dialog:visible")).toBeVisible({ timeout: 8_000 })
})

When("I attempt to submit the empty MWG8776 group form", async ({ page }) => {
  const writes: string[] = []
  blockedGroupWrites.set(page, writes)
  await page.route("**/api/usergroups", async (route) => {
    const method = route.request().method().toUpperCase()
    if (["POST", "PUT", "PATCH", "DELETE"].includes(method)) {
      writes.push(method)
      await route.abort("blockedbyclient")
      return
    }
    await route.continue()
  })
  const dialog = page.locator(".p-dialog:visible")
  // Target the Add action, not the upload button rendered earlier in the form.
  const addButton = dialog.locator("form button:has(.mdi-check-circle)")
  await expect(addButton).toHaveCount(1)
  await addButton.click()
})

Then("the MWG8776 group form errors must be visibly associated with their controls", async ({ page }) => {
  const dialog = page.locator(".p-dialog:visible")
  const name = dialog.locator("form .field input").first()
  await expect(name).toHaveCount(1)
  await expect(name).toHaveAttribute("aria-invalid", "true")

  const describedBy = await name.getAttribute("aria-describedby")
  expect(describedBy, "The invalid name input must reference its error element").toBeTruthy()
  const errorId = describedBy?.split(/\s+/).find(Boolean)
  expect(errorId).toBeTruthy()
  const nameError = dialog.locator(`[id="${errorId}"]`)
  await expect(nameError).toBeVisible()
  await expect(nameError).toHaveAttribute("role", "alert")
  await expect(nameError).not.toBeEmpty()

  const permissions = dialog.locator("#groupPermissions")
  await expect(permissions).toHaveCount(1)
  await expect(permissions).toHaveAttribute("aria-invalid", "true")
  await expect(permissions).toHaveAttribute("aria-describedby", /groupPermissions-error/)
  await expect(dialog.locator("#groupPermissions-error")).toBeVisible()
  await expect(dialog.locator("#groupPermissions-error")).toHaveAttribute("role", "alert")

  await expect(dialog).toBeVisible()
  expect(blockedGroupWrites.get(page), "Invalid submission must not attempt an API write").toEqual([])
})

Then("the MWG8776 required group name must be announced to assistive technologies", async ({ page }) => {
  const name = page.locator(".p-dialog:visible form .field input").first()
  await expect(name).toHaveCount(1)
  const isRequired = await name.evaluate(
    (element) => element.hasAttribute("required") || element.getAttribute("aria-required") === "true",
  )
  expect(isRequired, 'The required Name field needs required="" or aria-required="true"').toBe(true)
})

Then("the MWG8776 group dialog must manage focus and Escape", async ({ page }) => {
  const dialog = page.locator(".p-dialog:visible")
  await expect(dialog).toHaveAttribute("role", "dialog")
  await expect.poll(async () => dialog.evaluate((element) => element.contains(document.activeElement))).toBe(true)

  await page.keyboard.press("Escape")
  await expect(dialog).toBeHidden()
  await expect(await getGroupCreateButton(page)).toBeFocused()
})

When("I use a narrow MWG8776 viewport", async ({ page }) => {
  await page.setViewportSize({ width: 390, height: 844 })
})

Then("the MWG8776 group dialog must fit the viewport width", async ({ page }) => {
  const dialog = page.locator(".p-dialog:visible")
  await expect(dialog).toBeVisible()
  const bounds = await dialog.boundingBox()
  expect(bounds, "The dialog must have measurable layout dimensions").not.toBeNull()
  if (!bounds) return
  expect(bounds.x, "The dialog must not extend off the left edge").toBeGreaterThanOrEqual(-1)
  expect(bounds.x + bounds.width, "The dialog must not extend off the right edge").toBeLessThanOrEqual(391)
})

Then("the MWG8776 social search controls must be labeled", async ({ page }) => {
  const search = page.locator(".social-search")
  await expect(search).toBeVisible({ timeout: 20_000 })

  // BaseInputText and BaseSelect also render empty floating labels. Verify the visible labels and accessible names.
  for (const id of ["search-query", "search-type"]) {
    const label = search.locator(`label[for="${id}"]`).filter({ hasText: /\S/ })
    await expect(label).toHaveCount(1)
    await expect(label).toBeVisible()

    const control = search.locator(`[id="${id}"]`)
    await expect(control).toBeVisible()
    await expect(control).toHaveAccessibleName(/\S/)
  }

  // The search action is the final button in the first search card.
  const searchButton = search.locator(".p-card").first().locator("button").last()
  await expect(searchButton).toBeVisible()
  await expect(searchButton).toHaveAccessibleName(/\S/)
})

Then("I record MWG8776 navigation timings", async ({ page }) => {
  const timings = await page.evaluate(() => {
    const navigation = performance.getEntriesByType("navigation")[0] as PerformanceNavigationTiming | undefined
    const paints = performance.getEntriesByType("paint") as PerformanceEntry[]
    return {
      path: location.pathname,
      viewport: { width: innerWidth, height: innerHeight },
      navigation: navigation
        ? {
            responseStartMs: Number(navigation.responseStart.toFixed(1)),
            domContentLoadedMs: Number(navigation.domContentLoadedEventEnd.toFixed(1)),
            loadMs: Number(navigation.loadEventEnd.toFixed(1)),
          }
        : null,
      paintEntriesMs: Object.fromEntries(paints.map((entry) => [entry.name, Number(entry.startTime.toFixed(1))])),
      note: "Navigation timing is diagnostic only; it is not a Core Web Vitals score.",
    }
  })
  await test.info().attach("mwg8776-navigation-timings.json", {
    body: Buffer.from(JSON.stringify(timings, null, 2)),
    contentType: "application/json",
  })
})

When("I open the MWG8776 social search message overlay", async ({ page }) => {
  const search = page.locator(".social-search")
  await expect(search).toBeVisible({ timeout: 20_000 })
  // The last row action is Send message; its label is translated.
  const sendMessage = search.locator("ul > li").first().locator("button").last()
  await expect(sendMessage).toBeVisible({ timeout: 10_000 })
  await sendMessage.click()
  await expect(page.locator(".message-modal-overlay")).toBeVisible()
})

Then("the MWG8776 social search message overlay must manage semantics and keyboard dismissal", async ({ page }) => {
  const overlay = page.locator(".message-modal-overlay")
  const findings: string[] = []
  const hasDialogSemantics = (await overlay.locator('[role="dialog"], [role="alertdialog"], dialog').count()) > 0
  if (!hasDialogSemantics) findings.push("Missing dialog semantics")

  const focusInside = await overlay.evaluate((element) => element.contains(document.activeElement))
  if (!focusInside) findings.push("Keyboard focus remains outside the dialog")

  const closeButton = overlay.locator("button.message-modal-close")
  const accessibleCloseName = await closeButton.getAttribute("aria-label")
  if (!accessibleCloseName?.trim()) findings.push("Close button has no descriptive accessible label")

  const sendButton = overlay.locator("button.message-modal-send")
  await sendButton.focus()
  await page.keyboard.press("Tab")
  if (!(await closeButton.evaluate((element) => element === document.activeElement))) {
    findings.push("Tab navigation escapes the dialog")
  }

  await page.keyboard.press("Escape")
  if (await overlay.isVisible()) {
    findings.push("Escape does not close the dialog")
  } else {
    const opener = page.locator(".social-search ul > li").first().locator("button").last()
    if (!(await opener.evaluate((element) => element === document.activeElement))) {
      findings.push("Focus is not restored to the message button")
    }
  }

  await test.info().attach("mwg8776-social-message-dialog.json", {
    body: Buffer.from(JSON.stringify({ path: new URL(page.url()).pathname, findings }, null, 2)),
    contentType: "application/json",
  })
  expect(findings, "Browser-confirmed message overlay accessibility findings").toEqual([])
})

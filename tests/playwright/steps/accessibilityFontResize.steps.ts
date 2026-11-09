import { expect } from "@playwright/test"
import { createBdd } from "playwright-bdd"

const { Then, When } = createBdd()

When("I set the accessibility font resize setting to {string}", async ({ page }, value: string) => {
  if (value !== "true" && value !== "false") {
    throw new Error(`Unsupported accessibility font resize setting: ${value}`)
  }

  const field = page.locator("#form_accessibility_font_resize")
  await expect(field).toHaveCount(1, { timeout: 5_000 })
  await field.evaluate(
    (element, nextValue) => {
      if (!(element instanceof HTMLSelectElement)) {
        throw new Error("Expected a native settings select element.")
      }
      if (!Array.from(element.options).some((option) => option.value === nextValue)) {
        throw new Error(`Settings select has no option with value: ${nextValue}`)
      }
      element.value = nextValue
      element.dispatchEvent(new Event("input", { bubbles: true }))
      element.dispatchEvent(new Event("change", { bubbles: true }))
    },
    value,
    { timeout: 5_000 },
  )
  await expect(field).toHaveValue(value, { timeout: 5_000 })
})

Then(
  "the accessibility control should increase and reset font size for {string}",
  async ({ page }, selector: string) => {
    const text = page.locator(selector).first()
    await expect(text).toBeVisible()

    const toggle = page.locator(".app-topbar button[aria-haspopup='menu']").filter({
      has: page.locator(".mdi-format-size"),
    })
    await expect(toggle).toBeVisible()
    await toggle.click()

    const buttons = page.locator(".app-topbar [role='menu'] button[role='menuitem']")
    await expect(buttons).toHaveCount(3)

    const currentSize = () => text.evaluate((element) => Number.parseFloat(getComputedStyle(element).fontSize))

    // Establish a baseline even if the browser previously saved a larger font size.
    await buttons.nth(1).click()
    const baseline = await currentSize()
    expect(baseline).toBeGreaterThan(0)

    await buttons.nth(2).click()
    await expect.poll(currentSize).toBeGreaterThan(baseline * 1.08)
    await expect.poll(() => page.evaluate(() => localStorage.getItem("chamiloFontResizeStep"))).toBe("1")

    await buttons.nth(1).click()
    await expect.poll(currentSize).toBeCloseTo(baseline, 1)
    await expect.poll(() => page.evaluate(() => localStorage.getItem("chamiloFontResizeStep"))).toBe("0")
  },
)

Then("the accessibility font resize control should not be available", async ({ page }) => {
  await expect(page.locator(".app-topbar .mdi-format-size")).toHaveCount(0)
  await expect(page.locator("html")).not.toHaveAttribute("style", /font-size:\s*(?:[0-9.]+)%/)
})

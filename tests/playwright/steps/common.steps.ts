import { expect } from "@playwright/test"
import { createBdd } from "playwright-bdd"

const { Given, When, Then } = createBdd()

// Ported from tests/behat/features/bootstrap/FeatureContext.php.
// "I am on"/"I fill in ... for ..."/"I press"/"I should see" are standard
// Mink/MinkContext steps in Behat (not custom code) — reimplemented here.

Given("I am on {string}", async ({ page }, path: string) => {
  await page.goto(path)
})

// Mink's fillField resolves a field by id, then name, then label text (in
// that order) — which is why the Gherkin locator "login" has always worked
// here without anyone needing to know it's actually an `id`, not a `name`
// (assets/vue/components/Login.vue uses id="login" / input-id="password",
// no name attribute at all). Mirror that same id -> name -> label fallback
// so this step keeps working regardless of which attribute a given form uses.
When("I fill in {string} for {string}", async ({ page }, value: string, field: string) => {
  const byId = page.locator(`#${field}`)
  if (await byId.count()) {
    await byId.fill(value)
    return
  }
  const byName = page.locator(`[name="${field}"]`)
  if (await byName.count()) {
    await byName.fill(value)
    return
  }
  await page.getByLabel(field).fill(value)
})

When("I press {string}", async ({ page }, label: string) => {
  await page
    .locator(`button:has-text("${label}"), input[type="submit"][value="${label}"]`)
    .first()
    .click()
})

Then("I should see {string}", async ({ page }, text: string) => {
  await expect(page.getByText(text).first()).toBeVisible()
})

// FeatureContext::waitVeryLongForThePageToBeLoaded() / waitForThePageToBeLoadedWhenReady()
// use hardcoded sleeps (14s / 9s) because Mink/Selenium has no reliable
// auto-wait. Playwright's actions and assertions already auto-wait/retry,
// so these steps are kept only to match the ported scenario 1:1 — a real
// migration would likely delete them and lean on Playwright's own waiting.
Then("wait very long for the page to be loaded", async ({ page }) => {
  await page.waitForLoadState("domcontentloaded")
})

Then("wait for the page to be loaded when ready", async ({ page }) => {
  await page.waitForLoadState("networkidle")
})

// Ported from FeatureContext::iShouldNotSeeAnError().
Then("I should not see an error", async ({ page }) => {
  await expect(page.locator("body")).not.toContainText("Internal server error")
  await expect(page.locator(".alert-danger")).toHaveCount(0)
  await expect(page.locator(".p-message-error")).toHaveCount(0)
})

import { expect, type Locator, type Page } from "@playwright/test"
import { createBdd } from "playwright-bdd"

const { When, Then } = createBdd()
const invitationTrigger = new WeakMap<Page, Locator>()

When("I open the MWG8776 invitation overlay for an invite-eligible user", async ({ page }) => {
  const configuredQuery = process.env.MWG8776_INVITEE_QUERY?.trim()
  const queries = configuredQuery ? [configuredQuery] : ["acostea", "mmosquera", "bproudfoot", "ptook", "a"]
  let eligibleButton: Locator | null = null

  for (const query of queries) {
    const searchResponse = page
      .waitForResponse(
        (response) =>
          new URL(response.url()).pathname === "/social-network/search" && response.request().method() === "GET",
        { timeout: 10_000 },
      )
      .catch(() => null)

    await page.goto(`/social/search?query=${encodeURIComponent(query)}&type=user`, {
      waitUntil: "domcontentloaded",
      timeout: 15_000,
    })
    await expect(page.locator(".social-search")).toBeVisible({ timeout: 10_000 })

    const response = await searchResponse
    if (!response || !response.ok()) {
      throw new Error(`Social search did not return a successful response for query "${query}".`)
    }

    const button = page.locator(".social-search ul > li button:has(.mdi-account)").first()
    const found = await button.waitFor({ state: "visible", timeout: 1_500 }).then(
      () => true,
      () => false,
    )
    if (found) {
      eligibleButton = button
      break
    }
  }

  if (!eligibleButton) {
    throw new Error(
      "INCONCLUSIVE: No user eligible to receive an invitation was found. " +
        "Find a user with the invitation action in SocialSearch and rerun with MWG8776_INVITEE_QUERY set to that user's name. " +
        "No invitation was sent.",
    )
  }

  invitationTrigger.set(page, eligibleButton)
  await eligibleButton.click()
  await expect(page.locator(".invitation-modal-overlay")).toBeVisible({ timeout: 8_000 })
})

Then("the MWG8776 invitation overlay must manage semantics and keyboard dismissal", async ({ page }) => {
  const overlay = page.locator(".invitation-modal-overlay")
  const dialog = overlay.locator('.invitation-modal[role="dialog"]')
  const closeButton = dialog.locator("button.close-button")
  const messageInput = dialog.locator("textarea.invitation-modal-textarea")
  const sendButton = dialog.locator("button.invitation-modal-send")
  const trigger = invitationTrigger.get(page)
  expect(trigger, "The original invitation trigger must be available").toBeDefined()

  await expect(dialog).toHaveAttribute("aria-modal", "true")
  const titleId = await dialog.getAttribute("aria-labelledby")
  expect(titleId, "Dialog must reference its visible heading").toBeTruthy()
  await expect(dialog.locator(`[id="${titleId}"]`)).toBeVisible()
  await expect(closeButton).toHaveAccessibleName(/\S/)
  await expect(messageInput).toBeFocused()

  await sendButton.focus()
  await page.keyboard.press("Tab")
  await expect(closeButton).toBeFocused()
  await page.keyboard.press("Shift+Tab")
  await expect(sendButton).toBeFocused()

  await page.keyboard.press("Escape")
  await expect(overlay).toBeHidden()
  await expect(trigger!).toBeFocused()

  await trigger!.click()
  await expect(overlay).toBeVisible()
  await closeButton.click()
  await expect(overlay).toBeHidden()
  await expect(trigger!).toBeFocused()

  await trigger!.click()
  await expect(overlay).toBeVisible()
  await overlay.click({ position: { x: 5, y: 5 } })
  await expect(overlay).toBeHidden()
  await expect(trigger!).toBeFocused()
})

import { expect, Page } from "@playwright/test"
import { createBdd } from "playwright-bdd"

const { Given, When, Then, After } = createBdd()

const SUPPORT_BOT_SETTINGS_URL = "/admin/settings/search_settings?keyword=ticket_support_bot_enabled"
const SUPPORT_BOT_SETTING_SELECTOR = "#form_ticket_support_bot_enabled"

let originalSupportBotSetting: string | null = null
let disposableTreeTitle: string | null = null
let disposableTreeId: number | null = null

async function saveSettings(page: Page): Promise<void> {
  const saveButton = page.locator("button[type='submit']:has(.mdi-content-save)").first()
  await expect(saveButton).toBeVisible()
  await saveButton.click()
  await page.waitForLoadState("domcontentloaded")
}

async function findDisposableTreeRow(page: Page) {
  if (!disposableTreeTitle) {
    throw new Error("No disposable Support Bot tree title is currently recorded.")
  }

  return page
    .locator("tr")
    .filter({ has: page.getByText(disposableTreeTitle, { exact: true }) })
    .first()
}

async function deleteDisposableTreeIfPresent(page: Page): Promise<void> {
  if (!disposableTreeTitle) {
    return
  }

  await page.goto("/admin/support-bot")
  await page.waitForLoadState("domcontentloaded")

  const row = await findDisposableTreeRow(page)
  if ((await row.count()) === 0) {
    return
  }

  const deleteButton = row.locator("button:has(.mdi-delete)").first()
  await expect(deleteButton).toBeVisible()
  await deleteButton.click()

  const dialog = page.locator(".p-confirmdialog:visible").first()
  await expect(dialog).toBeVisible()
  const buttons = dialog.getByRole("button")
  await expect(buttons).toHaveCount(2)
  await buttons.last().click()

  await expect(page.getByText(disposableTreeTitle, { exact: true })).toHaveCount(0)
}

Given("the Support Bot setting is enabled for this scenario", async ({ page }) => {
  await page.goto(SUPPORT_BOT_SETTINGS_URL)
  await page.waitForLoadState("domcontentloaded")

  const field = page.locator(SUPPORT_BOT_SETTING_SELECTOR)
  await expect(field).toBeVisible()

  originalSupportBotSetting = await field.inputValue()
  if (originalSupportBotSetting !== "true") {
    await field.selectOption("true")
    await saveSettings(page)
    await expect(page.locator(SUPPORT_BOT_SETTING_SELECTOR)).toHaveValue("true")
  }
})

When("I create a disposable Support Bot draft", async ({ page }) => {
  disposableTreeTitle = `Playwright Support Bot ${Date.now()}`
  disposableTreeId = null

  await page.goto("/admin/support-bot/create")
  await page.waitForLoadState("domcontentloaded")

  const title = page.locator("#support_bot_tree_title")
  await expect(title).toBeVisible()
  await title.fill(disposableTreeTitle)

  const nodeContent = page.locator("textarea[name^='node_'][name$='_content']").first()
  const optionLabel = page.locator("input[name^='option_'][name$='_label']").first()
  const closingMessage = page.locator("textarea[name^='option_'][name$='_closing_message']").first()

  await nodeContent.fill("How can automated support help you?")
  await optionLabel.fill("Finish automated support")
  await closingMessage.fill("Automated support completed.")

  const saveButton = page.locator("button:has(.mdi-content-save)").first()
  await expect(saveButton).toBeVisible()
  await saveButton.click()
  await page.waitForURL(/\/admin\/support-bot\/\d+\/edit(?:\?.*)?$/)

  const match = new URL(page.url()).pathname.match(/^\/admin\/support-bot\/(\d+)\/edit$/)
  if (!match) {
    throw new Error(`Could not resolve the saved Support Bot tree id from ${page.url()}.`)
  }
  disposableTreeId = Number(match[1])
})

Then("the disposable Support Bot draft should be editable", async ({ page }) => {
  if (!disposableTreeTitle || !disposableTreeId) {
    throw new Error("The disposable Support Bot tree has not been created yet.")
  }

  await expect(page.locator("#support_bot_tree_title")).toHaveValue(disposableTreeTitle)
  await expect(page.locator("#support_bot_tree_title")).toBeEnabled()
  await expect(page.locator("button:has(.mdi-content-save)").first()).toBeVisible()
})

When("I rename the disposable Support Bot draft", async ({ page }) => {
  if (!disposableTreeTitle || !disposableTreeId) {
    throw new Error("The disposable Support Bot tree has not been created yet.")
  }

  disposableTreeTitle = `${disposableTreeTitle} updated`
  const title = page.locator("#support_bot_tree_title")
  await title.fill(disposableTreeTitle)

  const responsePromise = page.waitForResponse(
    (response) =>
      response.request().method() === "PATCH" &&
      response.url().includes(`/api/support-bot/admin/trees/${disposableTreeId}`),
  )
  await page.locator("button:has(.mdi-content-save)").first().click()
  const response = await responsePromise
  expect(response.ok()).toBeTruthy()
  await expect(title).toHaveValue(disposableTreeTitle)
})

When("I open the Support Bot tree list", async ({ page }) => {
  await page.goto("/admin/support-bot")
  await page.waitForLoadState("domcontentloaded")
})

Then("the disposable Support Bot draft should be listed", async ({ page }) => {
  if (!disposableTreeTitle) {
    throw new Error("The disposable Support Bot tree has not been created yet.")
  }

  await expect(page.getByText(disposableTreeTitle, { exact: true })).toBeVisible()
})

When("I delete the disposable Support Bot draft", async ({ page }) => {
  await deleteDisposableTreeIfPresent(page)
  disposableTreeId = null
})

Then("the disposable Support Bot draft should no longer be listed", async ({ page }) => {
  if (!disposableTreeTitle) {
    throw new Error("The disposable Support Bot tree title is unavailable.")
  }

  await expect(page.getByText(disposableTreeTitle, { exact: true })).toHaveCount(0)
})

After({ tags: "@supportBot" }, async ({ page }) => {
  try {
    if (disposableTreeId && disposableTreeTitle) {
      await deleteDisposableTreeIfPresent(page)
    }
  } catch (error) {
    console.warn(`[support-bot-test] could not clean disposable tree: ${String(error)}`)
  }

  try {
    if (originalSupportBotSetting !== null) {
      await page.goto(SUPPORT_BOT_SETTINGS_URL)
      await page.waitForLoadState("domcontentloaded")
      const field = page.locator(SUPPORT_BOT_SETTING_SELECTOR)
      await expect(field).toBeVisible()
      if ((await field.inputValue()) !== originalSupportBotSetting) {
        await field.selectOption(originalSupportBotSetting)
        await saveSettings(page)
      }
    }
  } catch (error) {
    console.warn(`[support-bot-test] could not restore the Support Bot setting: ${String(error)}`)
  }

  originalSupportBotSetting = null
  disposableTreeTitle = null
  disposableTreeId = null
})

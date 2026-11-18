import { expect } from "@playwright/test"
import { createBdd } from "playwright-bdd"

const { When, Then } = createBdd()

const saveButton = ".tox.tox-tinymce.tox-fullscreen button.tox-tbtn:has(.chamilo-document-save-icon)"
const editorId = "item_content"

When("I open the GH9001 document creation form", async ({ page }) => {
  // Clicking the course tool is a client-side route change: wait for the document list URL.
  await page.waitForURL((u) => /^\/resources\/document\/\d+\/?$/.test(u.pathname))
  const url = new URL(page.url())
  url.pathname = `${url.pathname.replace(/\/$/, "")}/create`
  await page.goto(url.toString())
  await expect(page.locator("#title")).toBeVisible()
  await page.waitForFunction((id) => Boolean((window as any).tinymce?.get(id)), editorId)
})

When("I enable fullscreen for the document editor", async ({ page }) => {
  await page.waitForFunction((id) => Boolean((window as any).tinymce?.get(id)), editorId)
  await page.evaluate((id) => {
    const editor = (window as any).tinymce.get(id)
    editor.execCommand("mceFullScreen")
  }, editorId)
  await expect.poll(async () => page.evaluate((id) => {
    const editor = (window as any).tinymce?.get(id)
    return Boolean(editor?.plugins?.fullscreen?.isFullscreen?.())
  }, editorId)).toBe(true)
})

Then("the document Save button is available in fullscreen", async ({ page }) => {
  const state = await page.evaluate((id) => {
    const editor = (window as any).tinymce?.get(id)
    const toolbarCommands = String(editor?.settings?.toolbar || "").split(/[\s|]+/)
    const buttons = Object.keys(editor?.ui?.registry?.getAll?.()?.buttons || {}).map((key) => key.toLowerCase())
    return { toolbarCommands, buttons }
  }, editorId)

  expect(state.toolbarCommands).toContain("chamiloSave")
  expect(state.buttons).toContain("chamilosave")
  await expect(page.locator(saveButton)).toBeVisible()
})

When("I save the document from the fullscreen toolbar", async ({ page }) => {
  await expect(page.locator(saveButton)).toBeVisible()
  await page.locator(saveButton).click()
  await page.waitForURL((url) => /^\/resources\/document\/\d+\/?$/.test(url.pathname))
})

When("I edit the GH9001 document {string}", async ({ page }, title: string) => {
  const row = page.locator(".p-datatable tbody tr").filter({ hasText: title })
  await expect(row).toBeVisible()
  await row.locator("button:has(.mdi-pencil)").click()
  await page.waitForURL((url) => url.pathname.endsWith("/edit_file"))
  await page.waitForFunction((id) => Boolean((window as any).tinymce?.get(id)), editorId)
})

Then("the GH9001 document editor contains {string}", async ({ page }, text: string) => {
  await expect.poll(async () => page.evaluate((id) => {
    const editor = (window as any).tinymce?.get(id)
    return editor?.getContent?.() || ""
  }, editorId)).toContain(text)
})

When("I return to the GH9001 document list", async ({ page }) => {
  const url = new URL(page.url())
  expect(url.pathname).toMatch(/^\/resources\/document\/\d+\/edit_file$/)
  url.pathname = url.pathname.replace(/\/edit_file$/, "/")
  url.searchParams.delete("id")
  url.searchParams.delete("getFile")
  await page.goto(url.toString())
  await expect(page.locator(".p-datatable")).toBeVisible()
})

// The shared "I delete the document ... if present" reads count() right after the page loads, before the
// Vue table has received its rows, so it silently skipped the delete and left one document behind per run
// (confirmed from the request log: no DELETE was ever sent). This waits for the list before deciding.
When("I delete the GH9001 document {string}", async ({ page }, title: string) => {
  const row = page.locator(".p-datatable tbody tr").filter({ hasText: title })
  await expect(row.first()).toBeVisible()
  await row.first().getByTitle("Delete").click()
  await page.getByRole("button", { name: "Yes" }).click()
  await expect(row).toHaveCount(0)
})

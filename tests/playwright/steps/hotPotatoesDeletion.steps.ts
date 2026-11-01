import path from "node:path"
import { expect, type Page } from "@playwright/test"
import { createBdd } from "playwright-bdd"

const { When, Then, After } = createBdd()
const importedIds = new WeakMap<Page, number>()

function row(page: Page) {
  const id = importedIds.get(page)
  if (!id) throw new Error("Disposable Hot Potatoes archive has not been imported")
  return page.locator(`[data-hotpotatoes-id="${id}"]`)
}

When("I import a disposable Hot Potatoes archive", async ({ page }) => {
  await page.locator('input[name="package"]').setInputFiles(
    path.resolve(__dirname, "../fixtures/gh3018-delete-smoke.zip"),
  )
  const requestFinished = page.waitForResponse((response) =>
    response.url().includes("/api/hotpotatoes/archives/import") && response.request().method() === "POST",
  )
  await page.getByRole("button", { name: "Import", exact: true }).click()
  const response = await requestFinished
  expect(response.status()).toBe(201)
  const result = await response.json()
  expect(Number(result.documentId)).toBeGreaterThan(0)
  importedIds.set(page, Number(result.documentId))
})

Then("the disposable Hot Potatoes archive should be listed", async ({ page }) => {
  await expect(row(page)).toBeVisible()
})

When("I remove the disposable Hot Potatoes archive", async ({ page }) => {
  await row(page).getByRole("button", { name: "Delete" }).click()
  await page.getByRole("button", { name: "Yes", exact: true }).click()
})

Then("the disposable Hot Potatoes archive should no longer be listed", async ({ page }) => {
  await expect(row(page)).toHaveCount(0)
  importedIds.delete(page)
})

When("I complete and inspect the disposable Hot Potatoes attempt", async ({ page }) => {
  const started = page.waitForResponse((response) =>
    response.url().includes("/api/hotpotatoes/archives/") &&
    /\/api\/hotpotatoes\/archives\/\d+\/attempts\?/.test(response.url()) &&
    response.request().method() === "POST",
  )
  await row(page).getByRole("button", { name: "Open" }).click()
  const startResponse = await started
  expect(startResponse.status()).toBe(201)
  const attempt = await startResponse.json()
  expect(Number(attempt.id)).toBeGreaterThan(0)
  expect(attempt.graded).toBe(false)

  const completed = page.waitForResponse((response) =>
    response.url().includes(`/attempts/${Number(attempt.id)}/complete`) &&
    response.request().method() === "POST",
  )
  await page.getByRole("button", { name: "Mark as completed" }).click()
  const completeResponse = await completed
  expect(completeResponse.ok()).toBeTruthy()
  const completeResult = await completeResponse.json()
  expect(completeResult.status).toBe("completed")
  expect(completeResult.graded).toBe(false)

  await page.getByRole("link", { name: "Back" }).click()
  await expect(row(page)).toBeVisible()
  const listResponsePromise = page.waitForResponse((response) =>
    response.url().includes(`/api/hotpotatoes/archives/${importedIds.get(page)}/attempts`) &&
    response.request().method() === "GET",
  )
  await row(page).getByRole("button", { name: "Attempts" }).click()
  const listResponse = await listResponsePromise
  expect(listResponse.ok()).toBeTruthy()
  const listed = await listResponse.json()
  expect(listed.graded).toBe(false)
  expect(listed.items.some((record: { id: number; status: string }) =>
    record.id === Number(attempt.id) && record.status === "completed",
  )).toBeTruthy()
})

// Best-effort cleanup when a test fails after import. Never mutate seeded data.
After({ tags: "@gh3018" }, async ({ page }) => {
  const id = importedIds.get(page)
  if (!id) return
  const url = new URL(page.url())
  const cid = url.searchParams.get("cid")
  if (!cid) return
  await page.request.delete(`${url.origin}/api/hotpotatoes/archives/${id}?cid=${encodeURIComponent(cid)}`, {
    headers: { Origin: url.origin },
  }).catch(() => {})
  importedIds.delete(page)
})

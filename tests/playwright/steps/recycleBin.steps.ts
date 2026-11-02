import { expect } from "@playwright/test"
import { createBdd } from "playwright-bdd"

const { When, Then, After } = createBdd()

let createdLp: { title: string; nodeId: number; courseId: number; lpId: number } | null = null

// Assert the real API responded; rendering an empty panel alone is insufficient.
When(
  "I open the {string} recycle bin and verify its successful API response",
  async ({ page }, type: string) => {
    const endpoints: Record<string, RegExp> = {
      exercise: /^\/api\/exercise\/runtime\/\d+\/recycle-bin$/,
      "learning path": /^\/api\/learning_paths\/\d+\/reporting\/recycle-bin$/,
    }
    const expectedPath = endpoints[type]
    if (!expectedPath) {
      throw new Error(`Unsupported recycle bin type: ${type}`)
    }

    const [response] = await Promise.all([
      page.waitForResponse(
        (candidate) =>
          candidate.request().method() === "GET" && expectedPath.test(new URL(candidate.url()).pathname),
        { timeout: 30000 },
      ),
      page.locator("button[title='Recycle bin']").click(),
    ])

    expect(response.status(), `Recycle bin GET ${response.url()}`).toBe(200)
    await expect(page.getByRole("heading", { name: "Recycle bin" })).toBeVisible()
  },
)

When("I create a temporary GH2872 learning path", async ({ page }) => {
  const title = `GH2872-Recycle-Smoke-${Date.now()}`
  createdLp = null
  await page.locator('input[name="title"]').fill(title)
  await page.locator("button:visible").filter({ hasText: /^Continue$/ }).first().click()
  await expect(page).toHaveURL(/\/resources\/lp\/\d+\/\d+\/builder/)
  const url = new URL(page.url())
  const match = url.pathname.match(/\/resources\/lp\/(\d+)\/(\d+)\/builder/)
  const courseId = Number(url.searchParams.get("cid"))
  if (!match || !courseId) {
    throw new Error(`Cannot resolve the newly created learning path: ${url}`)
  }
  createdLp = { title, nodeId: Number(match[1]), lpId: Number(match[2]), courseId }
})

When("I visit the GH2872 learning path reporting page", async ({ page }) => {
  if (!createdLp) {
    throw new Error("No GH2872 learning path was created")
  }
  const { nodeId, lpId, courseId } = createdLp
  await page.goto(`/resources/lp/${nodeId}/${lpId}/reporting?cid=${courseId}&sid=0&gid=0`)
})

async function deleteCreatedLp(page: import("@playwright/test").Page, allowAlreadyDeleted = false): Promise<void> {
  if (!createdLp) {
    return
  }

  const { title, nodeId, courseId, lpId } = createdLp
  await page.goto(`/resources/lp/${nodeId}?cid=${courseId}&sid=0&gid=0`)
  await expect(page.locator(".lp-list")).toBeVisible({ timeout: 20000 })
  await expect(page.locator(".lp-list .animate-pulse")).toHaveCount(0, { timeout: 20000 })

  // Scope cleanup to the exact link for the learning path created by this scenario.
  const panel = page.locator(".lp-panel").filter({
    has: page.getByRole("link", { name: title, exact: true }),
  })

  // The explicit cleanup must find its LP. Only the fallback After hook may
  // accept an absent row, because the preceding step may already have deleted it.
  if (await panel.count() === 0) {
    if (!allowAlreadyDeleted) {
      throw new Error(`Temporary learning path ${lpId} (${title}) is missing from the course list.`)
    }
    createdLp = null
    return
  }

  // The first More actions button is a hidden mobile control on desktop.
  await panel.locator(".lp-panel__action-buttons button[title='More actions']:visible").click({ timeout: 10000 })
  await panel.getByRole("button", { name: "Delete", exact: true }).click({ timeout: 10000 })
  await page.getByRole("button", { name: "Yes", exact: true }).click({ timeout: 10000 })
  await expect(panel).toHaveCount(0, { timeout: 15000 })
  createdLp = null
}

Then("I delete the temporary GH2872 learning path", async ({ page }) => {
  await deleteCreatedLp(page)
})

// Retry cleanup only if the scenario did not finish it. A row already deleted
// by the explicit step must not cause a second deletion or another timeout.
After({ tags: "@gh2872-lp-smoke" }, async ({ page }) => {
  if (createdLp) {
    await deleteCreatedLp(page, true)
  }
})

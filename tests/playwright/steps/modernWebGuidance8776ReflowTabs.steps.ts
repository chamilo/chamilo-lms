import { expect, test, type Page } from "@playwright/test"
import { createBdd } from "playwright-bdd"

const { When, Then } = createBdd()
const inspectedPaths = new WeakMap<Page, string>()

Then("the MWG8776 social group tabs must expose accessible tab semantics and keyboard operation", async ({ page }) => {
  const navigation = page.locator(".social-groups > .tabs")
  const tabs = navigation.locator("button.tab")
  await expect(navigation).toBeVisible({ timeout: 15_000 })
  await expect(tabs).toHaveCount(4)

  const findings: string[] = []
  if ((await navigation.getAttribute("role")) !== "tablist") {
    findings.push("Tab navigation does not expose role=tablist")
  }

  let selectedCount = 0
  for (let index = 0; index < 4; index++) {
    const tab = tabs.nth(index)
    await expect(tab).toHaveAccessibleName(/\S/)

    if ((await tab.getAttribute("role")) !== "tab") {
      findings.push(`Tab ${index + 1} does not expose role=tab`)
    }

    const selected = await tab.getAttribute("aria-selected")
    const visuallySelected = await tab.evaluate((element) => element.classList.contains("tab-active"))
    if (selected === "true") selectedCount++
    if (selected !== String(visuallySelected)) {
      findings.push(`Tab ${index + 1} has no matching aria-selected state`)
    }

    const controls = await tab.getAttribute("aria-controls")
    if (!controls) {
      findings.push(`Tab ${index + 1} does not identify its panel`)
    } else {
      const panel = page.locator(`[id="${controls}"]`)
      if ((await panel.count()) !== 1 || (await panel.getAttribute("role")) !== "tabpanel") {
        findings.push(`Tab ${index + 1} does not control a tabpanel`)
      }
    }
  }

  if (selectedCount !== 1) {
    findings.push(`Exactly one tab must expose aria-selected=true; found ${selectedCount}`)
  }

  const first = tabs.nth(0)
  const second = tabs.nth(1)
  await first.focus()
  await page.keyboard.press("ArrowRight")
  const arrowMovedFocus = await second.evaluate((element) => document.activeElement === element)
  if (!arrowMovedFocus) {
    findings.push("ArrowRight does not move focus to the next tab")
  }

  await second.focus()
  await page.keyboard.press("Enter")
  await expect(second).toHaveClass(/tab-active/)
  await expect(first).not.toHaveClass(/tab-active/)
  const selectedAfterEnter = await second.getAttribute("aria-selected")
  if (selectedAfterEnter !== "true") {
    findings.push("Enter activates the visible panel but does not expose its selected state")
  }

  await first.click()
  await expect(first).toHaveClass(/tab-active/)

  await test.info().attach("mwg8776-group-tabs.json", {
    body: Buffer.from(JSON.stringify({ findings, arrowMovedFocus }, null, 2)),
    contentType: "application/json",
  })
  console.log(`MWG8776 group tabs: ${JSON.stringify({ findings, arrowMovedFocus })}`)
  expect(findings, "Social group tab semantics and keyboard findings").toEqual([])
})

When("I inspect MWG8776 narrow reflow on {string}", async ({ page }, path: string) => {
  expect(["/resources/usergroups", "/social/search"]).toContain(path)
  await page.setViewportSize({ width: 320, height: 720 })
  await page.goto(path, { waitUntil: "domcontentloaded", timeout: 20_000 })
  const root = page.locator(path === "/resources/usergroups" ? ".social-groups" : ".social-search")
  await expect(root).toBeVisible({ timeout: 20_000 })
  inspectedPaths.set(page, path)
})

Then("the MWG8776 page must reflow without horizontal document overflow", async ({ page }) => {
  const path = inspectedPaths.get(page)
  expect(path, "The inspected route must be recorded").toBeTruthy()
  const metrics = await page.evaluate(() => {
    const root = document.documentElement
    const viewportWidth = root.clientWidth
    const documentWidth = Math.max(root.scrollWidth, document.body?.scrollWidth || 0)
    const overflowPx = Math.max(0, documentWidth - viewportWidth)
    const offenders = Array.from(document.body.querySelectorAll("*"))
      .filter((node) => {
        const element = node as HTMLElement
        const rect = element.getBoundingClientRect()
        const style = getComputedStyle(element)
        return (
          rect.width > 0 &&
          rect.height > 0 &&
          style.display !== "none" &&
          style.visibility !== "hidden" &&
          (rect.right > viewportWidth + 4 || rect.left < -4)
        )
      })
      .slice(0, 12)
      .map((element) => {
        const rect = element.getBoundingClientRect()
        return {
          tag: element.tagName.toLowerCase(),
          className: typeof element.className === "string" ? element.className.slice(0, 100) : "",
          left: Math.round(rect.left),
          right: Math.round(rect.right),
        }
      })
    return { viewportWidth, documentWidth, overflowPx, offenders }
  })

  await test.info().attach("mwg8776-narrow-reflow.json", {
    body: Buffer.from(JSON.stringify({ path, ...metrics }, null, 2)),
    contentType: "application/json",
  })
  console.log(`MWG8776 reflow: ${JSON.stringify({ path, ...metrics })}`)
  expect(metrics.viewportWidth, "The CSS viewport should be 320 pixels").toBe(320)
  expect(metrics.overflowPx, `Unexpected document horizontal overflow on ${path}`).toBeLessThanOrEqual(4)
})

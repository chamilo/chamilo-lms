import { expect, test, type Page } from "@playwright/test"
import { createBdd } from "playwright-bdd"

const { When, Then } = createBdd()
const inspectedTopbars = new WeakMap<Page, string>()

When("I inspect MWG8776 topbar at 320 pixels on {string}", async ({ page }, path: string) => {
  expect(["/resources/usergroups", "/social/search"]).toContain(path)
  await page.setViewportSize({ width: 320, height: 720 })
  await page.goto(path, { waitUntil: "domcontentloaded", timeout: 20_000 })
  await expect(page.locator(path === "/resources/usergroups" ? ".social-groups" : ".social-search")).toBeVisible({
    timeout: 20_000,
  })
  await expect(page.locator(".app-topbar")).toBeVisible()
  await expect(page.locator(".app-topbar__end .app-sidebar__topbar-button")).toBeVisible()
  inspectedTopbars.set(page, path)
})

Then("the MWG8776 focusable topbar controls must remain visually reachable", async ({ page }) => {
  const path = inspectedTopbars.get(page)
  expect(path, "The route being audited must be known").toBeTruthy()

  const controls = page.locator(
    ".app-topbar a[href], .app-topbar button:not([disabled]), .app-topbar [tabindex='0'], .app-topbar [role='button']",
  )
  const findings: string[] = []
  const observations: Array<Record<string, unknown>> = []
  const count = await controls.count()
  let inspected = 0

  for (let index = 0; index < count; index++) {
    const control = controls.nth(index)
    if (!(await control.isVisible())) continue
    const isEnabled = await control.isEnabled()
    if (!isEnabled) continue

    inspected++
    await control.focus()
    const observation = await control.evaluate((element) => {
      const rect = element.getBoundingClientRect()
      const width = document.documentElement.clientWidth
      const visibleWidth = Math.max(0, Math.min(rect.right, width) - Math.max(rect.left, 0))
      const visibleRatio = rect.width > 0 ? visibleWidth / rect.width : 0
      const root = document.documentElement
      return {
        tag: element.tagName.toLowerCase(),
        className: typeof element.className === "string" ? element.className.slice(0, 110) : "",
        name: (element.getAttribute("aria-label") || element.getAttribute("title") || element.textContent || "")
          .trim()
          .replace(/\s+/g, " ")
          .slice(0, 80),
        left: Math.round(rect.left),
        right: Math.round(rect.right),
        visibleRatio: Math.round(visibleRatio * 100) / 100,
        focused: document.activeElement === element,
        viewportWidth: width,
        documentWidth: Math.max(root.scrollWidth, document.body?.scrollWidth || 0),
      }
    })
    observations.push(observation)
    if (!observation.focused) {
      findings.push(`Focusable action ${index + 1} cannot receive focus`)
    }
    if ((observation.visibleRatio as number) < 0.75) {
      findings.push(`Topbar action ${index + 1} is clipped after focus (${observation.left}..${observation.right})`)
    }
  }

  if (inspected === 0) findings.push("No visible focusable topbar controls were found")
  const report = { path, inspected, findings, observations }
  console.log(`MWG8776 mobile topbar reachability: ${JSON.stringify(report)}`)
  await test.info().attach("mwg8776-topbar-reachability.json", {
    body: Buffer.from(JSON.stringify(report, null, 2)),
    contentType: "application/json",
  })
  expect(findings, "Mobile topbar keyboard-focus visibility findings").toEqual([])
})

Then("the MWG8776 user menu trigger must work with the keyboard", async ({ page }) => {
  const avatar = page.locator(".app-topbar__end .user-avatar").first()
  await expect(avatar).toBeVisible()
  const details = await avatar.evaluate((element) => ({
    tag: element.tagName.toLowerCase(),
    role: element.getAttribute("role"),
    tabIndex: (element as HTMLElement).tabIndex,
    name: element.getAttribute("aria-label") || element.getAttribute("title") || "",
    hasPopup: element.getAttribute("aria-haspopup"),
    controls: element.getAttribute("aria-controls"),
  }))
  const findings: string[] = []
  const nativeButton = details.tag === "button"
  const nativeLink = details.tag === "a"
  if (!nativeButton && !nativeLink && details.role !== "button") {
    findings.push("The user menu trigger has no button semantics")
  }
  if (details.tabIndex < 0) {
    findings.push("The user menu trigger is absent from the keyboard tab order")
  }
  if (!details.name.trim()) {
    findings.push("The user menu trigger has no accessible name")
  }
  if (details.hasPopup !== "menu" || details.controls !== "user-submenu") {
    findings.push("The user menu trigger does not identify its popup menu")
  }

  if (details.tabIndex >= 0) {
    await avatar.focus()
    if (!(await avatar.evaluate((element) => document.activeElement === element))) {
      findings.push("The user menu trigger does not receive keyboard focus")
    } else {
      const menu = page.locator("#user-submenu")
      for (const key of ["Enter", "Space"]) {
        await page.keyboard.press(key)
        const opened = await menu.isVisible()
        if (!opened) findings.push(`${key} does not open the user menu`)
        if (opened) {
          await page.keyboard.press("Escape")
          await expect(menu).toBeHidden({ timeout: 5_000 })
          await avatar.focus()
        }
      }
    }
  }

  const report = { details, findings }
  console.log(`MWG8776 user menu trigger: ${JSON.stringify(report)}`)
  await test.info().attach("mwg8776-topbar-user-menu.json", {
    body: Buffer.from(JSON.stringify(report, null, 2)),
    contentType: "application/json",
  })
  expect(findings, "Mobile user menu keyboard accessibility findings").toEqual([])
})

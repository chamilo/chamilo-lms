import { expect, test } from "@playwright/test"
import { createBdd } from "playwright-bdd"

const { When, Then } = createBdd()
const checkMode = () => process.env.CSP2575_MODE || ""

When("I verify the configured CSP2575 mode on the login shell", async ({ page }) => {
  const mode = checkMode()
  test.skip(!mode, "Run explicitly against a test portal with CSP2575_MODE configured.")
  expect(["disabled", "report_only", "enforce"]).toContain(mode)

  await page.addInitScript(() => {
    const state = { trusted: false, ran: false, violations: [] as string[] }
    Object.defineProperty(window, "__csp2575Test", { value: state })
    document.addEventListener("securitypolicyviolation", (event) => {
      if (event.blockedURI === "inline" && event.effectiveDirective.startsWith("script-src")) {
        state.violations.push(event.disposition)
      }
    })
  })

  const openLogin = async () => {
    const response = await page.goto("/login", { waitUntil: "domcontentloaded" })
    expect(response?.status()).toBe(200)
    const active = (await response?.headerValue("content-security-policy")) || ""
    const report = (await response?.headerValue("content-security-policy-report-only")) || ""
    if (mode === "disabled") {
      expect(active).toBe("")
      expect(report).toBe("")
    } else {
      expect(mode === "report_only" ? active : report).toBe("")
      const policy = mode === "report_only" ? report : active
      const nonce = /'nonce-([^']+)'/.exec(policy)?.[1]
      expect(nonce, "Configure the documented test policy with the nonce placeholder.").toBeTruthy()
      expect(policy).not.toContain("{nonce}")
      const coreNonce = await page
        .locator("script")
        .evaluateAll((scripts) => scripts.map((script) => (script as HTMLScriptElement).nonce).filter(Boolean))
      expect(coreNonce).toContain(nonce)
      expect(new Set(coreNonce).size).toBe(1)
      expect(await response?.headerValue("cache-control")).toContain("no-store")
    }
    await expect(page.locator("#app[data-v-app]")).toBeAttached()
    return /'nonce-([^']+)'/.exec(mode === "report_only" ? report : active)?.[1]
  }

  const firstNonce = await openLogin()
  const secondNonce = await openLogin()
  if (mode !== "disabled") {
    expect(secondNonce).not.toBe(firstNonce)
  }
})

Then("the CSP2575 browser probe must match the configured mode", async ({ page }) => {
  const mode = checkMode()
  const loginUrl = page.url()
  let serverPolicy = ""

  // The two preceding navigations validate the unmodified login shell. For this
  // isolated probe, keep the server's actual response policy and let Chromium
  // parse a minimal HTML document instead of injecting a script from DevTools.
  await page.route(
    (url) => url.href === loginUrl,
    async (route) => {
      const response = await route.fetch({ maxRedirects: 0 })
      expect(response.status()).toBe(200)
      const headers = response.headers()
      const active = headers["content-security-policy"] || ""
      const report = headers["content-security-policy-report-only"] || ""
      expect(mode === "enforce" ? report : active).toBe("")
      if (mode === "disabled") {
        expect(report).toBe("")
      }
      serverPolicy = mode === "report_only" ? report : active
      const nonce = /'nonce-([A-Za-z0-9+/]+={0,2})'/.exec(serverPolicy)?.[1]
      if (mode !== "disabled") {
        expect(nonce, "The server must provide a fresh nonce for the browser probe.").toBeTruthy()
        expect(headers["cache-control"]).toContain("no-store")
      }

      // No CSP header is added, removed, or changed by the test. Only the body
      // and its transport metadata change. The positive control uses the nonce
      // from this response; the negative control has no nonce or hash.
      delete headers["content-length"]
      delete headers["content-encoding"]
      delete headers["transfer-encoding"]
      const trustedAttribute = nonce ? ` nonce="${nonce}"` : ""
      const body = `<!doctype html>
<html><head><meta charset="utf-8"><title>CSP validation</title></head><body>
<script${trustedAttribute}>window.__csp2575Test.trusted = true;</script>
<script id="csp2575-untrusted-probe">window.__csp2575Test.ran = true;</script>
</body></html>`
      await route.fulfill({ response, headers, body })
    },
    { times: 1 },
  )

  const response = await page.goto(loginUrl, { waitUntil: "load" })
  expect(response?.status()).toBe(200)
  const active = (await response?.headerValue("content-security-policy")) || ""
  const report = (await response?.headerValue("content-security-policy-report-only")) || ""
  expect(active).toBe(mode === "enforce" ? serverPolicy : "")
  expect(report).toBe(mode === "report_only" ? serverPolicy : "")
  await expect(page.locator("#csp2575-untrusted-probe")).toBeAttached()

  const readProbe = () =>
    page.evaluate(
      () => Reflect.get(window, "__csp2575Test") as { trusted: boolean; ran: boolean; violations: string[] },
    )

  await expect.poll(readProbe).toMatchObject({
    trusted: true,
    ran: mode !== "enforce",
  })
  if (mode !== "disabled") {
    await expect
      .poll(readProbe)
      .toMatchObject({ violations: expect.arrayContaining([mode === "report_only" ? "report" : "enforce"]) })
  } else {
    expect((await readProbe()).violations).toEqual([])
  }
  console.log("CSP browser probe:", mode, await readProbe())
})

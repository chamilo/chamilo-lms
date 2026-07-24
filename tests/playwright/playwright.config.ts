import path from "node:path"
import { defineConfig, devices } from "@playwright/test"
import { defineBddConfig } from "playwright-bdd"

// Spike config: proves the Behat -> Playwright migration path works.
// Points at a single copied .feature file to keep the blast radius small
// while the approach is being validated; scope will expand once proven.
//
// playwright-bdd resolves `features`/`steps`/its generated output dir
// relative to this config file's own directory, so .features-gen lands
// under tests/playwright/ automatically.
//
// Playwright's own `outputDir` (test results/traces/screenshots) and the
// html reporter's `outputFolder`, by contrast, default to being resolved
// relative to the process cwd. They're pinned here to var/test-results/
// (a new sibling of the existing var/cache, var/log, var/upload — already
// writable by whatever runs the tests, with no extra chmod needed, and
// already covered by the project's blanket `/var/` .gitignore rule) rather
// than under tests/, so generated test output never lives inside source
// control's working tree at all.
const repoRoot = path.resolve(__dirname, "../..")

const testDir = defineBddConfig({
  features: "features/**/*.feature",
  steps: "steps/**/*.ts",
})

export default defineConfig({
  testDir,
  // 60s, not the Playwright default 30s: adminSettings.feature's @settings
  // AfterAll hook does its own login plus 3 rounds of navigate+select+
  // save+wait (see common.steps.ts) — more work than any single scenario —
  // and test.beforeAll()/afterAll() wrappers inherit this same config
  // value when a hook doesn't set its own override (playwright-bdd's own
  // per-hook `timeout` option doesn't reach that outer wrapper, confirmed
  // by testing it directly). Regular scenarios run in ~23s in production
  // mode, well under either value, so this doesn't mask a genuinely hung
  // test for long.
  timeout: 60_000,
  fullyParallel: false,
  retries: 0,
  outputDir: path.join(repoRoot, "var/test-results/playwright/results"),
  reporter: [
    ["list"],
    ["html", { open: "never", outputFolder: path.join(repoRoot, "var/test-results/playwright/report") }],
  ],
  use: {
    baseURL: process.env.BASE_URL || "http://my.chamilo.net",
    trace: "retain-on-failure",
    screenshot: "only-on-failure",
  },
  projects: [
    {
      name: "chromium",
      use: { ...devices["Desktop Chrome"] },
    },
  ],
})

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
  timeout: 30_000,
  fullyParallel: false,
  retries: 0,
  outputDir: path.join(repoRoot, "var/test-results/playwright/results"),
  reporter: [
    ["list"],
    ["html", { open: "never", outputFolder: path.join(repoRoot, "var/test-results/playwright/report") }],
  ],
  use: {
    baseURL: process.env.BASE_URL || "http://my.chamilo.net",
    // Temporarily "on" (always trace, not just on failure) instead of
    // "retain-on-failure" — actionInstall.feature's "Installation process"
    // scenario currently passes, but its resulting admin password doesn't
    // verify server-side (see project memory / recent CI investigation), so
    // there's nothing to inspect from a passing test by default. Once that's
    // root-caused, revert this to "retain-on-failure" to keep artifacts lean.
    trace: "on",
    screenshot: "only-on-failure",
  },
  projects: [
    {
      name: "chromium",
      use: { ...devices["Desktop Chrome"] },
    },
  ],
})

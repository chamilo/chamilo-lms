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
  // Default is 5s. Bumped after a real CI failure: career.feature's "Create a
  // career" is the first ported scenario to touch a legacy jqGrid page
  // (careers.php) — every prior feature (login, adminFillUsers,
  // adminSettings) is Vue-only. Trace analysis of the failure showed the
  // jqGrid data AJAX call (get_careers) didn't even fire until ~6.6s after
  // the redirect-after-submit response, because on a fresh, nothing-cached
  // CI install the Vue admin-layout bundle, jQuery, jqGrid, and its i18n
  // chunk all have to load and initialize serially first — well past the
  // default 5s assertion window, so "Then I should see Developer" timed out
  // before the grid's own AJAX call even started (confirmed via the aborted
  // request's status: -1 in the trace, a side effect of the test's own
  // teardown, not a server error). Edit/Copy/Delete (same file, same worker,
  // right after) all passed fine once those bundles were warm. Raised
  // instead of touching career.feature or the shared steps: every assertion
  // still resolves as soon as it's satisfied, so this only adds patience,
  // never slows down anything that already passes.
  expect: { timeout: 15_000 },
  fullyParallel: false,
  // Real CI run hit a genuine cross-file race: course_user_registration.
  // feature hardcodes cid=1, assuming course.feature's "Create a course
  // before testing" scenario has already created "TEMP" (which becomes id 1
  // on a fresh install) — an implicit ordering dependency the original Behat
  // suite got "for free" from running everything single-threaded. With the
  // default multi-worker pool, `fullyParallel: false` only serializes
  // scenarios WITHIN one file; DIFFERENT files (course.feature vs.
  // course_user_registration.feature) still run concurrently across
  // workers, so course_user_registration's very first scenario reached
  // /main/user/subscribe_user.php?...&cid=1 before "TEMP" existed yet —
  // confirmed directly from a real CI log: its "Subscribe amann" scenario
  // (test #17) started before course.feature's own "Create a course before
  // testing" (test #20) had even run. 12 more remaining feature files also
  // hardcode cid=1/depend on "TEMP", so this isn't a one-off — workers: 1
  // fixes the whole class of cross-file ordering dependencies at once by
  // matching the original suite's own sequential execution, at the cost of
  // losing multi-worker parallelism (roughly doubles total CI wall-clock
  // time versus the previous default of 2 workers on this runner).
  workers: 1,
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

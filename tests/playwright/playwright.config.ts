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
  // 90s, not the Playwright default 30s (and up from an earlier 60s):
  // adminSettings.feature's @settings AfterAll hook does its own login
  // plus 3 rounds of navigate+select+save+wait (see common.steps.ts) —
  // more work than any single scenario — and test.beforeAll()/afterAll()
  // wrappers inherit this same config value when a hook doesn't set its
  // own override (playwright-bdd's own per-hook `timeout` option doesn't
  // reach that outer wrapper, confirmed by testing it directly). Also
  // covers createUser.feature's "Create a HRM user", which enables a
  // platform setting via the settings UI before filling user_add.php —
  // login + settings Save + interrupt-retry navigation + form submit
  // regularly lands in the 55–65s range on cold CI even when every step
  // is healthy (a real CI run timed out at exactly 60s after a successful
  // submit, with the next four HRM scenarios all passing on the user it
  // had already created). Regular scenarios run in ~23s in production
  // mode, well under either value, so this doesn't mask a genuinely hung
  // test for long.
  timeout: 90_000,
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
  // DETERMINISTIC EXECUTION (2026-08-23), a deliberate trade of wall-clock for
  // reproducibility, decided after weeks of chasing "passes locally, fails in
  // CI" failures.
  //
  // `fullyParallel: false` alone only serializes scenarios WITHIN one file.
  // Different files still ran concurrently across workers, and this suite is
  // full of shared mutable fixtures — above all course "TEMP", whose user list
  // is subscribed to and unsubscribed from by course_user_registration,
  // toolUsers, toolAssessments and toolReporting. That is a data race, and it
  // produced failures that no amount of seeding could fix: a seed step can
  // guarantee a fixture EXISTS at the start, but not that another file will not
  // delete it at minute 12. Measured directly on a full local run: the seed
  // subscribed fapple to TEMP successfully, yet by the end of the run
  // course_rel_user for cid=3 held only acostea/admin/pperez — fapple had been
  // unsubscribed mid-flight, which failed five toolGroup "Add fapple ..."
  // scenarios and cascaded into six more (announcements cannot target a
  // non-member; the two "Check ... access" scenarios read back savedUrls those
  // announcements never wrote). 11 of 18 failures, one race.
  //
  // The deeper problem was that these failures were NOT reproducible: whether
  // toolGroup passed depended on which file won a scheduling race, so the same
  // commit could pass one CI run and fail the next, every fix was a guess, and
  // guesses shipped as regressions. Worse, four separate "shared fixture
  // created inside the parallel batch" bugs had each been patched with its own
  // sequential seed step (TEMP, TEMPPRIVATE, allow_group_categories, course
  // subscriptions) — scaffolding that had itself started causing bugs.
  //
  // With one worker, file execution order is fixed (Playwright sorts by path),
  // so cross-file dependencies resolve by construction: course.feature <
  // course_user_registration.feature < toolGroup.feature < toolUsers.feature,
  // which is exactly the order those fixtures need. It also makes a local run
  // and a CI run the same experiment, so "green locally" finally means
  // something — the single biggest cause of wasted effort here.
  //
  // Cost is wall-clock only (roughly 1.5-3h vs ~26-42m), which is cheap against
  // the debugging it removes. Re-introduce parallelism later per-file, and only
  // for files proven not to touch shared fixtures.
  workers: 1,
  // A real CI run hit a genuine cross-file race: course_user_registration.
  // feature hardcodes the shared course's cid, assuming course.feature's
  // "Create a course before testing" scenario has already created "TEMP" —
  // an implicit ordering dependency the original Behat suite got "for free"
  // from running everything single-threaded. With the
  // default multi-worker pool, `fullyParallel: false` only serializes
  // scenarios WITHIN one file; DIFFERENT files still run concurrently across
  // workers, so course_user_registration's very first scenario could reach
  // /main/user/subscribe_user.php?...&cid=3 before "TEMP" existed yet.
  // 13 more feature files also hardcode that cid/depend on "TEMP", so
  // this wasn't a one-off. First fix tried here was `workers: 1` (serialize
  // everything) — worked, but cost roughly 2x total CI wall-clock time.
  // Superseded by extracting "Create a course before testing" into its own
  // "Seed test course" CI step (package.json's test:playwright:seed-course),
  // run once, sequentially, BEFORE the main parallel "Playwright tests" step
  // even starts — same proven pattern as "Seed test users". That guarantees
  // "TEMP" exists before any worker in the main batch begins, regardless of
  // which file/worker gets it, without sacrificing parallelism. No `workers`
  // override needed once that dependency is resolved at its actual source.
  //
  // WHICH cid "TEMP" ACTUALLY GETS (2026-08-19): cid=3, not cid=1. The
  // installer's own DemoCoursesFixtures creates "AI Act" (cid=1) and "Using
  // Chamilo" (cid=2) before any suite seeding runs, so TEMP — the first
  // course the suite itself creates — lands on 3. Those demo courses were
  // added to the product AFTER this suite was written, silently invalidating
  // the old "TEMP is cid=1" assumption: cid=1 stayed a real, working course,
  // so affected scenarios kept PASSING while exercising the demo course
  // instead of TEMP. Whole suite migrated cid=1 -> cid=3 in one pass. See
  // .github/workflows/playwright.yml's "Seed test course" step for why that
  // step must create exactly one course for the id to stay deterministic.
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

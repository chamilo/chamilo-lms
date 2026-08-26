import path from "node:path"
import { defineConfig, devices } from "@playwright/test"
import { defineBddConfig } from "playwright-bdd"

// Config for the project's only browser-driven suite, which replaced Behat.
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
    // Machine-readable twin of the html report, consumed by
    // tests/playwright/scripts/check-results.mjs so CI can decide pass/fail on
    // TEST OUTCOMES rather than on `playwright test`'s exit code.
    //
    // Why that distinction earns a whole extra reporter: the exit code is
    // non-zero for anything that went wrong anywhere, including work that is
    // not a test. Twice now a run finished 410/410 green and still failed the
    // job, because a settings-restore hook in the worker-teardown phase blew
    // its budget AFTER the last test had already passed. The html report is
    // only readable by a human (its data is a base64 zip inside a <template>
    // tag), so it cannot gate anything automatically.
    //
    // NOTE: the json reporter's `stats` has expected/unexpected/flaky/skipped
    // but NO `total` — the checker sums them. Don't assume `total` exists.
    ["json", { outputFile: path.join(repoRoot, "var/test-results/playwright/results.json") }],
  ],
  // One retry, paired with `trace: "on-first-retry"` below — the two are a set,
  // do not change one without the other.
  //
  // Not here to paper over flaky TESTS. The suite is deterministic by
  // construction now (workers: 1, fixed file order), and every test-level flake
  // chased this month turned out to be a real bug. This exists for a failure
  // class that no amount of test-fixing can reach: Playwright's own internals.
  //
  // Observed twice in five CI runs, byte-identical both times:
  //
  //   TypeError: browserContext._wrapApiCall:
  //     Cannot read properties of undefined (reading 'traceName')
  //
  // Zero seconds, no stack, no error-context.md — the test never began. It hit
  // toolDropbox's "Admin opens Dropbox..." on 2026-08-25 and course.feature's
  // "Make sure the surveys tool is available" on 2026-08-26, and each of those
  // passed in every other run, INCLUDING the run where the other one failed. It
  // is one roving error landing on an arbitrary victim, not a regression — but
  // it is attributed to a test, so check-results.mjs cannot tell it apart from a
  // genuine failure. A retry can: a real failure fails twice, this does not.
  //
  // With retries, a test that passes on the second attempt is reported `flaky`
  // rather than failed, and check-results.mjs surfaces flaky as a WARNING
  // without failing the build — so this stops turning CI red while staying
  // visible. If a `flaky` count starts creeping up, that is a real signal;
  // do not let it become background noise.
  //
  // Cost: a retry of specialCase1Sessions' @long-scenario can add ~15 minutes.
  // Judged acceptable against a red build on a green suite.
  //
  // NOT universal: package.json's test:playwright:specialcase1 overrides this
  // with `--retries=0 --trace=retain-on-failure`, because a retry shares the
  // DATABASE with the attempt that just failed and SpecialCase1's scenarios are
  // heavily non-idempotent — a retry there turns one clear failure into a second,
  // different, more confusing one. See that step's comment in playwright.yml.
  // Keep the two in sync: changing the values here does NOT change that batch.
  retries: 1,
  use: {
    baseURL: process.env.BASE_URL || "http://my.chamilo.net",
    // "on-first-retry", NOT "retain-on-failure" — this is the half that removes
    // the CAUSE rather than absorbing the symptom.
    //
    // The error above is tracing bookkeeping failing: the failing read is
    // `traceName: this._state.traceName`, i.e. a browser context is asked for
    // its trace state when no chunk is active on it. This suite has contexts in
    // exactly that shape — the 7 settings guards each call browser.newContext()
    // inside BeforeAll (see registerSettingsGuard in steps/common.steps.ts), so
    // they are created OUTSIDE any test and stay open for the whole run, yet
    // Playwright's tracing instrumentation still knows about them. Under
    // "retain-on-failure" the runner manages a trace chunk at every test
    // boundary, which is when it trips over one of those.
    //
    // "on-first-retry" does no tracing at all on the first attempt, so there is
    // no per-test chunk bookkeeping to break. Tracing turns on only for a retry
    // — precisely when a trace is worth having.
    //
    // The trade, stated plainly: a test that fails once and passes on retry
    // leaves NO trace for that first failure. Accepted because the retry itself
    // produces a fully traced run of the same test, and because a first-attempt
    // trace is worth less than a build that is not red for a reason unrelated to
    // the tests. If you ever need to debug a genuinely one-shot failure, flip
    // this back to "retain-on-failure" for that run.
    trace: "on-first-retry",
    screenshot: "only-on-failure",
  },
  projects: [
    {
      name: "chromium",
      use: { ...devices["Desktop Chrome"] },
    },
  ],
})

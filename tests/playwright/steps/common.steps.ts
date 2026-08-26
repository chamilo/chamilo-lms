import path from "node:path"
import { expect, Page, test } from "@playwright/test"
import { createBdd, DataTable } from "playwright-bdd"

const { Given, When, Then, BeforeAll, AfterAll, Before } = createBdd()

// Not ported — new. specialCase1PlatformSettings.feature's own scenarios are
// far larger than anything else in this suite (dozens of settings toggles
// plus 41/12 extra-field creations each involving a full page navigation),
// and a real run confirmed even the FIRST two scenarios ("Initial platform
// searches and basic settings", "Add user extra fields") individually
// exceed this config's global 90s per-test timeout well before finishing —
// not a hung step, just genuinely more real work than any other single
// scenario in this suite does. `test.info().setTimeout()` (a real Playwright
// API, not a custom fixture) raises the CURRENT test's own timeout from
// inside a Before hook; scoping it to this file's own tag keeps every other
// feature's default 90s budget completely unchanged.
Before({ tags: "@long-scenario" }, async () => {
  test.info().setTimeout(15 * 60_000)
})

// Try-exercise scenarios walk 11 question types with a draft-save on every
// "Next question". CI spent the whole 90s budget still on question 10 with
// the Next button stuck on "Saving". Four minutes is enough for the saves
// without using the 15-minute specialCase1 budget.
Before({ tags: "@slow-scenario" }, async () => {
  test.info().setTimeout(4 * 60_000)
})

// File paths in .feature files are relative to the repo root, not to this
// steps file (inherited from Mink's `files_path`, which the old Behat config
// pointed at the repo root too). tests/playwright/steps -> repo root.
// Fixture files of our own live in tests/playwright/fixtures/.
const repoRoot = path.resolve(__dirname, "../../..")

// Ported from tests/behat/features/bootstrap/FeatureContext.php.
// "I am on"/"I fill in ... for ..."/"I press"/"I should see"/"I check"/
// "I fill in the following:" are all standard Mink/MinkContext steps in
// Behat (not custom code) — reimplemented here.

// Uses gotoReliably (defined further below) rather than a bare page.goto():
// a real CI failure in createUser.feature's "Create a HRM user" showed that
// a plain goto right after a settings form Save can lose a race against the
// Save's own still-settling POST-redirect-GET / Vue SPA re-navigation
// ("Navigation to .../user_add.php is interrupted by another navigation to
// .../search_settings?keyword=admins_can_set_users_pass"). That same race
// class is what gotoReliably was built for (adminSettings @settings hooks);
// every "I am on" is a candidate for it, not just those two call sites.
Given("I am on {string}", async ({ page }, path: string) => {
  await gotoReliably(page, path)
})

// Mink's "I am on the homepage" (MinkContext::iAmOnHomepage(), visits the
// base URL "/") — registration.feature is the first ported scenario to use
// it; every other ported file navigates to an explicit path instead.
Given("I am on the homepage", async ({ page }) => {
  await gotoReliably(page, "/")
})

// Ported from FeatureContext::iAmOnCourseXHomepage(): navigates via the
// legacy redirect entry point (cidReq resolves the course by its code, not
// its numeric id) and asserts no visible error, matching the original's own
// assertElementNotOnPage('.alert-danger') right after navigating.
Given("I am on course {string} homepage", async ({ page }, courseCode: string) => {
  // gotoReliably, not a bare page.goto(): a real CI failure in
  // translateHtmlFallback.feature showed this step racing the previous
  // course_add.php success redirect ("Navigation to .../redirect.php?cidReq=
  // TrHtmlDe is interrupted by another navigation to .../admin/course-list").
  await gotoReliably(page, `/main/course_home/redirect.php?cidReq=${encodeURIComponent(courseCode)}`)
  await expect(page.locator(".alert-danger:visible")).toHaveCount(0)
})

// Ported from FeatureContext::iAmOnCourseXHomepageInSessionY() — same
// redirect entry point, with the session name appended so the course loads
// in that session's context (sessionAccess.feature checks access is/isn't
// allowed depending on the visiting user's session subscription).
Given(
  "I am on course {string} homepage in session {string}",
  async ({ page }, courseCode: string, sessionName: string) => {
    await gotoReliably(
      page,
      `/main/course_home/redirect.php?cidReq=${encodeURIComponent(courseCode)}&session_name=${encodeURIComponent(sessionName)}`,
    )
    await expect(page.locator(".alert-danger:visible")).toHaveCount(0)
  },
)

// Ported from FeatureContext::iHaveAPublicPasswordProtectedCourse() /
// iAmOnTheModernHomepageOfCourse() / iShouldBeOnTheModernHomepageOfCourse()
// (added upstream by e56f09bb221, "Course: Fix password-protected course
// entry test" — a real fix, not just a test rewrite: CidReqListener now
// redirects an unauthorized visitor of a public+passworded course to
// set_temp_password.php via CourseAccessResolver::requiresRegistrationPassword()).
// A course code isn't resolvable to its numeric id from Gherkin alone (the
// modern course routes are id-based), so this map caches it exactly like the
// Behat original's own $courseIdsByCode, split into 3 composable steps here
// instead of one big custom step so the mundane form-filling in between
// (title/visual_code/visibility, then the registration password itself)
// reuses this file's existing generic "I fill in"/"I check the ... radio
// button"/"I press" steps rather than duplicating them in raw Playwright code.
const passwordProtectedCourseIds = new Map<string, number>()

Then(
  "I resolve the numeric id of course {string}",
  async ({ page }, courseCode: string) => {
    await page.goto(`/courses/${encodeURIComponent(courseCode)}/index.php`)
    await page.waitForLoadState("domcontentloaded")
    const path = new URL(page.url()).pathname
    const match = path.match(/^\/course\/(\d+)\/home$/)
    if (!match) {
      throw new Error(`Could not resolve the modern course home URL for course ${courseCode} (got ${path}).`)
    }
    passwordProtectedCourseIds.set(courseCode, Number(match[1]))
  },
)

function getResolvedCourseId(courseCode: string): number {
  const courseId = passwordProtectedCourseIds.get(courseCode)
  if (!courseId) {
    throw new Error(`No resolved course id is available for course ${courseCode}.`)
  }
  return courseId
}

Given("I am on the course settings page of course {string}", async ({ page }, courseCode: string) => {
  await gotoReliably(page, `/main/course_info/infocours.php?cid=${getResolvedCourseId(courseCode)}`)
  await page.waitForLoadState("domcontentloaded")
})

Given("I am on the modern homepage of course {string}", async ({ page }, courseCode: string) => {
  await gotoReliably(page, `/course/${getResolvedCourseId(courseCode)}/home?sid=0&gid=0`)
  await page.waitForLoadState("domcontentloaded")
})

Then("I should be on the modern homepage of course {string}", async ({ page }, courseCode: string) => {
  const expectedPath = `/course/${getResolvedCourseId(courseCode)}/home`
  await expect(page).toHaveURL(new RegExp(`${expectedPath.replace(/\//g, "\\/")}(?:[/?#]|$)`))
})

// Ported from FeatureContext::iAmLoggedAs() / iAmAPlatformAdministrator() /
// iAmATeacher() / iAmAStudent() / iAmAnHR() / iAmAStudentBoss() /
// iAmAnInvitee(): each fixed test account uses its own username as both
// login and password, and all six "I am a ..." steps are thin wrappers
// around the same login flow, keyed by username. Doesn't reuse resolveField
// (defined further below) because the login form's fields are known and
// fixed here, not table/option-driven like the rest of the file's steps.
//
// iAmLoggedAs() itself waits (waitForThePageToBeLoaded(), an 8s sleep) right
// after pressing "Sign in", before returning control to the caller — its
// absence on a first pass here is what broke adminFillUsers.feature's first
// scenario: without it, the very next step (navigating straight to
// filler.php?fill=users) could fire before the session cookie from the
// login redirect was actually set, so the app bounced it back to the login
// page as unauthenticated. A follow-up fix added waitForLoadState
// ("networkidle") here, which was NOT enough on its own — the app has
// background polling (notifications, chat presence, same kind of thing
// DockedChat.vue does) that can make "network idle for 500ms" resolve on a
// brief lull before the actual post-login redirect lands, rather than after
// it. Waiting for the browser to actually leave /login is the deterministic
// signal that matters (session cookie is set by then). A trailing
// networkidle after that was later dropped: it added multi-second cost on
// every login (the bulk of "Create a HRM user"'s ~35s wall time) without
// buying reliability, because background polling means networkidle is neither
// a fast nor a trustworthy "page is ready" signal on this app. Downstream
// steps' own locator auto-wait / explicit "wait for the page..." covers
// destination-page readiness.
// Clears the terms-and-conditions interstitial if a login landed on it.
//
// The "Registration > Enable terms and conditions" platform setting
// (public/main/auth/tc.php) defaults OFF, so this is a no-op almost always. It
// matters because specialCase1PlatformSettings turns it ON partway through its
// own run, and every LATER login in the suite then lands here instead of its
// normal destination — a login that genuinely succeeded but stopped one page
// short. Left unhandled, the symptom appears much later and looks unrelated:
// subsequent navigations bounce to /login?redirect=... with "You are not allowed
// to see this page".
//
// Extracted into a helper so it can be called at BOTH points in loginAs() that
// need it — before the logout-link assertion (where it is load-bearing, since
// tc.php has no logout link) and again after the post-login navigation settles.
// Duplicating the block instead would invite the two copies to drift.
async function acceptTermsInterstitialIfPresent(page: Page): Promise<void> {
  // Detect by URL **or** by the presence of the accept control. URL alone is not
  // enough: a redirect chain into tc.php can still be in flight when this runs,
  // so page.url() may report the previous page (proven — see the call log quoted
  // at the call site). Checking the DOM as well makes the detection independent
  // of whether navigation has settled.
  const acceptButton = page.locator('button:has-text("Accept Terms and Conditions")')
  const onInterstitial =
    page.url().includes("/main/auth/tc.php") || (await acceptButton.first().isVisible().catch(() => false))
  if (!onInterstitial) {
    return
  }
  // registration.hide_legal_accept_checkbox=Yes makes ChamiloHelper::displayLegalTermsPage()
  // render legal_accept as <input type="hidden" value="1"> instead of a real checkbox (already
  // implicitly accepted) — real CI failure: specialCase1PlatformSettings.feature's "Add minimal
  // session extra fields" turns this setting ON mid-scenario, then a later login (studentone)
  // lands on this same tc.php interstitial and .check() threw "Not a checkbox or radio button"
  // on the now-hidden field. Only check() it when it's actually a checkbox.
  const legalAccept = page.locator('input[name="legal_accept"]')
  if ("checkbox" === (await legalAccept.getAttribute("type").catch(() => null))) {
    await legalAccept.check().catch(() => {})
  }
  await page
    .locator('button:has-text("Accept Terms and Conditions"), input[type="submit"]')
    .first()
    .click({ timeout: 15_000 })
    .catch(() => {})
  await page.waitForLoadState("domcontentloaded")
}

async function loginAs(page: Page, username: string) {
  // Real CI failure: admin/fileIntegrity.feature's "Non-administrators
  // cannot access ..." scenario has a Background that logs in as admin,
  // then the scenario itself switches to "I am a student" — logging in as
  // one user and then a DIFFERENT one within the same browser context,
  // with no explicit logout step of its own in between (unlike toolGroup.
  // feature's "I am not logged" -> "I am logged as 'acostea'" pattern
  // below, which already handles this explicitly). Confirmed live:
  // navigating to /login while already authenticated redirects straight to
  // /home without ever rendering the login form, so #login never appears
  // and the fill() below hangs for the rest of the test timeout.
  //
  // Only logging out when actually still authenticated (rather than
  // unconditionally, as a first version of this fix did) matters: a real CI
  // failure showed that unconditional extra round-trip shifting the timing
  // of the ALREADY-delicate session-establishment race documented below
  // (toolGroup.feature's own scenarios, which already do their own explicit
  // "I am not logged" first) — the redundant second logout added just
  // enough extra delay/variance to make that pre-existing race resurface.
  // Checking whether #login actually rendered costs nothing extra in the
  // overwhelmingly common case (a fresh, already-logged-out context, or a
  // file that already logs out explicitly like toolGroup.feature) and only
  // pays the logout+retry cost in the genuine cross-login-call case this
  // fix targets.
  // Decide whether a previous session is still active WITHOUT racing the DOM.
  //
  // The previous approach (kept in history above) navigated to /login first and
  // then raced `#login` becoming visible against `a[href="/logout"]` becoming
  // visible, treating whichever won as the truth. That race is genuinely
  // unwinnable: on an authenticated visit /login renders the login form for a
  // moment and only THEN gets replaced by the SPA's redirect to /home, so the
  // form frequently wins the race even though a session is active. The
  // logout branch is then skipped, `expect(#login).toBeVisible()` passes on
  // that same flash, and the very next `fill()` finds the node detached —
  // reproduced locally and identically to CI by running all of
  // tests/playwright/features/admin/ in order: the three admin scenarios in
  // fileIntegrity.feature leave a session behind, and its fourth scenario
  // ("Given I am a student") then died on
  // `locator.fill: Timeout 10000ms exceeded waiting for locator('#login')`.
  // Run alone, the same scenario passes, which is why this only ever showed up
  // in full-suite runs.
  //
  // /account/home is an authoritative, non-racy signal instead: it answers 200
  // for everyone, embeds `"username":"<current user>"` when a session exists,
  // and contains NO such marker at all when anonymous (all verified live).
  // Probing it before touching /login means the logout decision is made from
  // server state rather than from whatever the SPA happens to be painting.
  // TRI-STATE on purpose: "authenticated as X" / "definitely anonymous" /
  // "could not tell". The first version of this returned `string | null` and so
  // collapsed the last two into null — and that collapse is exactly how this
  // step failed in CI on 2026-08-26 (specialCase1PlatformSettings, twice in one
  // run). If the probe request throws, answers non-2xx, or returns a body
  // without the marker, "I could not determine the session state" was reported
  // as "there is no session". The logout was then skipped even though a session
  // WAS live, /login redirected straight to /home, `#login` never rendered, and
  // the failure surfaced 15s later as `expect(locator('#login')).toBeVisible()
  // failed — element(s) not found` with a page snapshot showing the fully
  // authenticated dashboard.
  //
  // Guessing "anonymous" is the DANGEROUS guess: it skips a logout that was
  // needed. Guessing "logged in" merely costs one harmless /logout round trip
  // (logout while anonymous just redirects). So unknown must resolve toward
  // logging out, never away from it.
  type AuthProbe = { state: "authenticated"; username: string } | { state: "anonymous" } | { state: "unknown" }

  const probeAuth = async (): Promise<AuthProbe> => {
    const response = await page.request.get("/account/home").catch(() => null)
    if (!response || !response.ok()) {
      return { state: "unknown" }
    }
    const body = await response.text().catch(() => null)
    if (null === body) {
      return { state: "unknown" }
    }
    const match = body.match(/"username":"([^"]+)"/)
    return match ? { state: "authenticated", username: match[1] } : { state: "anonymous" }
  }

  const authenticatedUsername = async (): Promise<string | null> => {
    const probe = await probeAuth()
    return "authenticated" === probe.state ? probe.username : null
  }

  // Still conditional, not unconditional: an earlier fix established that an
  // unconditional extra logout round trip perturbs the delicate
  // session-establishment timing for files that already log out explicitly
  // (toolGroup.feature). This keeps that property — the logout only happens
  // when a session genuinely exists — while no longer depending on the flash.
  // "not definitely anonymous", NOT "definitely authenticated" — see probeAuth().
  // Only a positive anonymous answer earns the skip; unknown takes the logout.
  if ("anonymous" !== (await probeAuth()).state) {
    await page.goto("/logout")
  }
  await page.goto("/login")
  await page.waitForLoadState("domcontentloaded")
  const loginField = page.locator("#login")

  // Self-heal the one failure this step cannot otherwise recover from: the login
  // form is absent because we are still logged in, so /login bounced to /home.
  // The tri-state probe above makes that far less likely, but it cannot be
  // airtight — the session can also be established by something other than this
  // step. Rather than spending the full 15s assertion budget to then report
  // "element(s) not found" (which reads as "the login page is broken" and sent
  // this investigation looking at the wrong thing entirely), check for the
  // tell-tale signature and fix it in place.
  //
  // Cheap by construction: the extra probe only runs when #login is genuinely
  // missing after a short wait, so the healthy path is unaffected.
  if (!(await loginField.isVisible().catch(() => false))) {
    const stillLoggedIn = await page
      .locator('a[href="/logout"]')
      .isVisible()
      .catch(() => false)
    if (stillLoggedIn) {
      await page.goto("/logout")
      await page.goto("/login")
      await page.waitForLoadState("domcontentloaded")
    }
  }

  await expect(loginField).toBeVisible({ timeout: 15_000 })
  await loginField.fill(username, { timeout: 10_000 })
  await page.locator("#password").fill(username, { timeout: 10_000 })
  // Scope to the login form. A broader `button:has-text('Sign in')` can race
  // a SPA redirect after fill: the form is already gone, click() then waits
  // the rest of the test timeout for a Sign in button that will never return
  // (webserverLoad's non-admin scenario: snapshot already showed Sign out).
  const signIn = page.locator("form.login-section__form button[type='submit']")
  if (await signIn.isVisible().catch(() => false)) {
    await signIn.click({ timeout: 15_000 }).catch(() => {})
  }
  // The terms-and-conditions interstitial MUST be cleared here — before the
  // logout-link assertion below, not after it.
  //
  // It used to sit ~40 lines further down, which made it dead code in exactly
  // the situation it was written for: tc.php carries NO `a[href="/logout"]`, so
  // whenever a login landed on the interstitial the assertion below burned its
  // full 25s and threw, and control never reached the handler. The failure then
  // read as "login produced no session" when the truth was "login succeeded and
  // stopped on an interstitial".
  //
  // Cost of getting this order wrong, measured: specialCase1PlatformSettings
  // enables "Registration > Enable terms and conditions" partway through its own
  // run, so every LATER login in that file hit this. On 2026-08-26 that was one
  // hard failure plus one flaky in CI, and locally it reproduced as scenario 2
  // flaky + scenario 4 failed — all four with the same signature
  // (`a[href="/logout"]` not found, call log showing a pending navigation to
  // /main/auth/tc.php).
  // Wait for whichever of the TWO possible post-login outcomes materialises,
  // rather than assuming it is the normal one. A point-in-time
  // `page.url().includes("/main/auth/tc.php")` check cannot do this and a first
  // attempt at exactly that failed: the observed call log was
  //
  //   waiting for ".../main/auth/tc.php?return=%2Fsessions" navigation to finish...
  //   navigated to ".../login"
  //
  // i.e. the redirect chain to the interstitial was still IN FLIGHT, so the URL
  // did not read as tc.php yet, the check returned early, and the logout-link
  // assertion then sat for 25s while the browser navigated somewhere that has no
  // logout link. Racing the two DOM outcomes has no such window — whichever
  // lands first wins, and neither needs the URL to have settled.
  const logoutLink = page.locator('a[href="/logout"]')
  const termsControl = page
    .locator('button:has-text("Accept Terms and Conditions"), input[name="legal_accept"]')
    .first()
  await Promise.race([
    logoutLink.waitFor({ state: "visible", timeout: 25_000 }).catch(() => {}),
    termsControl.waitFor({ state: "visible", timeout: 25_000 }).catch(() => {}),
  ])
  await acceptTermsInterstitialIfPresent(page)

  // Do not waitForURL({ waitUntil: "load" }): SPA login never fires load, and
  // that hung the full test timeout. Do not use "commit" either: a CI run
  // continued before the session cookie was stored, then the next navigation
  // showed "Your session details have been lost, please login again." and
  // every subsequent Save/settings step failed. The logout link only renders
  // after the login XHR has been handled (Set-Cookie included).
  await expect(logoutLink).toBeVisible({ timeout: 25_000 })
  // Real CI failure: toolGroup.feature's "Create an announcement as acostea
  // ..." scenario does "I am not logged" -> "I am logged as 'acostea'" ->
  // immediately navigates to group.php, which then rendered with a genuine
  // PHP warning ("Trying to access array offset on false" in
  // groupmanager.lib.php's processGroups(), where api_get_user_info()
  // returned false) — the server-side session wasn't fully established yet
  // for this brand-new login by the time the very next request landed,
  // leaving the group list broken for that one render (confirmed via the
  // provisioning log; the page snapshot at failure time showed an
  // anonymous/logged-out-looking page). `waitForURL` only confirms the
  // browser left /login, not that the landing page (and whatever it fires
  // on mount) has settled — same root class of "acted too fast right after
  // a redirect" issue gotoReliably() already hardens against elsewhere, just
  // session-establishment instead of navigation-interruption. Waiting for
  // the landing page's own load state gives that a moment to finish first.
  await page.waitForLoadState("domcontentloaded")

  // Not a normal-path concern — defensive only. Real CI failure this session
  // (toolExerciseTeacher.feature's "Try exercise", reproduced live via a
  // direct check): the "Registration > Enable terms and conditions" platform
  // setting (public/main/auth/tc.php) defaults OFF, but was found transiently
  // ON on this shared box mid-run — some OTHER concurrent feature file's own
  // test toggles it and had not yet restored it. Any login lands on tc.php
  // instead of its normal destination while that setting is on, and every
  // subsequent navigation in the scenario bounces to /login?redirect=...
  // instead ("You are not allowed to see this page..."), which is what
  // actually broke: the login step itself never threw (it did leave /login),
  // so the failure only surfaced several steps later as an unrelated-looking
  // timeout. Confirmed transient, not this file's own bug: the exact same
  // login moments later (setting back off, presumably that other file's own
  // teardown having run by then) landed on the normal destination with no
  // special handling needed. Since concurrent worker execution is a real,
  // supported mode for this suite (not a hypothetical), accepting the
  // interstitial here — a no-op the overwhelming rest of the time, when the
  // setting is off and tc.php never appears — is cheap insurance against the
  // same contamination hitting some other file's login next.
  // Second call, deliberately. The one that matters is BEFORE the logout-link
  // assertion above; this one catches an interstitial that appears only after
  // the post-login navigation settles. Both are no-ops when the setting is off.
  await acceptTermsInterstitialIfPresent(page)

  // Verify WHICH user we ended up as, not merely that *a* session exists.
  //
  // Real CI failure that forced this (admin/fileIntegrity.feature's
  // "Non-administrators cannot access the file integrity page"): the scenario
  // is only "Given I am a student" + open /admin/security/file-integrity +
  // assert "Run a scan now" is absent, and it FAILED with the admin page fully
  // rendered. Verified live that the application itself is correct — logging
  // in as acostea and opening that URL redirects to / and never renders "Run a
  // scan now", while admin stays and does — so the only way that assertion can
  // fail is if the browser was still authenticated as the PREVIOUS user
  // (admin) when it got there. It also passes in isolation, which is exactly
  // the signature of leaked session state rather than a selector bug.
  //
  // Every success signal above is satisfied by a pre-existing session: the
  // final `a[href="/logout"]` check just means "somebody is logged in", and
  // the sign-in click is both conditional (`isVisible()`, a one-shot check)
  // and has its errors swallowed (`.catch(() => {})`) — so if the form never
  // submitted, nothing above notices and this function returns "success" while
  // still being the old user. That is a silent wrong-user run, which is worse
  // than a failure because it produces a confident, wrong assertion result.
  //
  // /account/home is served for every authenticated user (checked live for
  // admin, acostea, mmosquera, ptook, abaggins and bproudfoot: all 200) and
  // its HTML embeds the CURRENT user as `"username":"<name>"` — confirmed
  // discriminating in both directions (as acostea the page contains
  // "username":"acostea" and NOT "username":"admin", and vice versa). Cheaper
  // and far more stable than scraping a display name out of the header, which
  // would need a username -> full-name map per fixture.
  //
  // On mismatch, retry the whole login once through a real logout before
  // giving up: the observed cause is a stale session that a single
  // logout+login round trip clears. Failing loudly on the second attempt is
  // still strictly better than today's silent wrong-user behaviour.
  const currentUserIs = async (expected: string): Promise<boolean> => {
    const response = await page.request.get("/account/home").catch(() => null)
    if (!response || !response.ok()) {
      // Never let this guard itself become a new failure mode: if the probe
      // cannot run, fall through and let the scenario's own steps decide.
      return true
    }
    return (await response.text()).includes(`"username":"${expected}"`)
  }

  if (!(await currentUserIs(username))) {
    await page.goto("/logout")
    await page.goto("/login")
    await page.waitForLoadState("domcontentloaded")
    await expect(loginField).toBeVisible({ timeout: 15_000 })
    await loginField.fill(username, { timeout: 10_000 })
    await page.locator("#password").fill(username, { timeout: 10_000 })
    await page.locator("form.login-section__form button[type='submit']").click({ timeout: 15_000 })
    // Same two-outcome race + interstitial accept as the primary path above.
    // This retry branch is a near-duplicate of that flow and originally omitted
    // the T&C handling, which is where the failure moved to once the primary
    // path was fixed: with terms enabled mid-run, BOTH logins land on tc.php, so
    // fixing only the first just relocated the 25s timeout a few lines down.
    await Promise.race([
      logoutLink.waitFor({ state: "visible", timeout: 25_000 }).catch(() => {}),
      termsControl.waitFor({ state: "visible", timeout: 25_000 }).catch(() => {}),
    ])
    await acceptTermsInterstitialIfPresent(page)
    await expect(logoutLink).toBeVisible({ timeout: 25_000 })
    if (!(await currentUserIs(username))) {
      throw new Error(
        `loginAs("${username}") finished while authenticated as a different user. ` +
          "The login form did not take effect and a previous session is still active.",
      )
    }
  }
}

Given("I am a platform administrator", async ({ page }) => {
  await loginAs(page, "admin")
})

Given("I am a teacher", async ({ page }) => {
  await loginAs(page, "mmosquera")
})

Given("I am a student", async ({ page }) => {
  await loginAs(page, "acostea")
})

Given("I am an HR manager", async ({ page }) => {
  await loginAs(page, "ptook")
})

Given("I am a student boss", async ({ page }) => {
  await loginAs(page, "abaggins")
})

Given("I am an invitee", async ({ page }) => {
  await loginAs(page, "bproudfoot")
})

// Ported from FeatureContext::iAmLoggedAs() — same login flow as the fixed
// "I am a ..." steps above, but for an arbitrary username (createUser.feature
// logs in as a user it just created, e.g. "hrm", not one of the fixed roles).
Given("I am logged as {string}", async ({ page }, username: string) => {
  await loginAs(page, username)
})

// Ported from FeatureContext::iAmNotLogged() — just visits /logout.
// gotoReliably() (not a plain page.goto()) since specialCase1PlatformSettings.
// feature's dense "Save a setting -> immediately switch user" pattern hit
// the exact same "Navigation to '/logout' is interrupted by another
// navigation to '.../search_settings?keyword=...'" race gotoReliably() was
// already built to absorb for "I am on ..." (see its own comment above) —
// a settings Save's redirect was still lagging when this step fired right
// after it. Confirmed live this was a genuine race, not a hung step: the
// interrupted navigation's target was the PREVIOUS step's own page, not a
// bug in "I am not logged" itself.
Given("I am not logged", async ({ page }) => {
  await gotoReliably(page, "/logout")
})

// Mink's fillField/checkField both resolve a field by id, then name, then
// label text (in that order) — which is why Gherkin locators like "login"
// have always worked here without anyone needing to know whether that's
// actually an `id` or a `name` attribute (e.g. assets/vue/components/Login.vue
// uses id="login" with no name attribute at all, while the install wizard's
// PrimeVue-based fields use name="..." — see below). Mirror that same
// id -> name -> label fallback for both fill and check so these steps keep
// working regardless of which attribute a given form happens to use.
//
// `:visible` is only a tie-breaker for genuine ambiguity, never a blanket
// requirement — two opposite real cases have shown up so far:
//   - PrimeVue form fields (e.g. the install wizard's database step) render
//     a hidden proxy <input type="hidden" name="dbNameForm"> kept in sync
//     alongside the actual visible <input name="dbNameForm"
//     input-id="dbNameForm">, so a plain [name="..."] locator matches BOTH
//     and Playwright's strict mode rightly refuses to guess — :visible
//     disambiguates correctly here.
//   - PrimeVue's <Select> component (e.g. adminSettings.feature's platform
//     settings dropdowns) is the opposite: it renders a real, SINGLE native
//     <select id="form_x" class="p-select ...">, deliberately hidden via
//     CSS, with a separate custom-styled widget handling the visible
//     interaction. Requiring :visible here would wrongly exclude the one
//     correct element — but selectOption() (unlike .fill()/.click()) works
//     directly on the underlying <select> regardless of visibility, since
//     it doesn't simulate a real pointer interaction.
// So: use a plain id/name match first; only add :visible when that match is
// ambiguous (count > 1), to break the tie in favor of the real one.
//
// Only attempt the bare `#field` selector when `field` is actually shaped
// like a valid id (a real CSS bug, not a hypothetical): createUser.feature
// needed to target user_add.php's "roles" multi-select by its real `name`
// attribute, "roles[]" (see the name-tier comment on FormValidator's select
// rendering below) — `#roles[]` is not valid CSS (unescaped brackets), and
// page.locator() throws a SyntaxError immediately instead of just finding
// zero matches, crashing every step that ever resolves a field whose only
// valid attribute is a bracketed multi-select name.
const looksLikeIdentifier = (value: string) => /^[\w-]+$/.test(value)

// The installer writes the default AccessUrl as http://localhost/ (the host
// the web installer itself was browsed on — CI's gh-apache ServerName, and
// a typical first-run). Admins then change that row to the real public URL.
// Tests that assert the URL as it appears on /admin/urls must follow the
// same rule: the displayed value is whatever we store, not the browser's
// current host. Derived from BASE_URL (CI: http://localhost, config default:
// http://my.chamilo.net, local playwright.chamilo.net override) so one
// assertion works in every environment.
function currentSiteAccessUrl(): string {
  const base = process.env.BASE_URL || "http://my.chamilo.net"
  return `${base.replace(/\/+$/, "")}/`
}

const escapeRegExp = (value: string) => value.replace(/[.*+?^${}()|[\]\\]/g, "\\$&")

// Bounded grace period before a tier's .count() snapshot, mirroring
// pressButton()'s isSoonVisible() below for the same reason: a plain
// .count() is an instant, non-retrying read of the CURRENT DOM. On a page
// whose form renders asynchronously (e.g. TicketCreateView.vue's
// onMounted(() => loadForm())), a fill attempt that starts before that
// request resolves can see 0 matches for BOTH the id and name tiers — not
// because the field doesn't exist under that identifier, but because it
// simply hasn't rendered yet — and permanently commit to the getByLabel()
// fallback below, which then hangs for the full test timeout if the visible
// label text doesn't happen to exact-match the identifier too. Real failure:
// ticket.feature's "Create a ticket" hung 90s on
// getByLabel('subject', { exact: true }) because BaseInputText's real
// name="subject" attribute hadn't rendered yet when this ran.
async function resolveField(page: Page, field: string) {
  const byId = looksLikeIdentifier(field) ? page.locator(`#${field}`) : null
  const byName = page.locator(`[name="${field}"]`)

  // Wait for whichever tier attaches first, instead of the old sequential
  // id-then-name grace periods. Real CI failures: group_creation.php's row
  // inputs (name="group_0_name", NO matching id — public/main/group/
  // group_creation.php) and the forum reply form's post_title field (same
  // name-only shape) each used to burn the full 5000ms id-tier wait — twice
  // per "I fill in the following:" table row (once per pass) — before ever
  // falling back to the [name=...] tier that actually matches. That alone
  // ate 50s+ of the 90s test timeout on toolGroup's "Create 5 groups" before
  // it ever reached the category selects, cascading into ~20 dependent
  // scenarios that all depend on those groups existing. Racing both tiers
  // means a name-only field resolves as fast as the name tier alone would,
  // while a genuinely id-only field is unaffected (id tier still wins the
  // race on its own).
  await Promise.race(
    [
      byId
        ?.first()
        .waitFor({ state: "attached", timeout: 5000 })
        .catch(() => {}),
      byName
        .first()
        .waitFor({ state: "attached", timeout: 5000 })
        .catch(() => {}),
    ].filter(Boolean),
  )

  if (byId) {
    const idCount = await byId.count()
    if (idCount === 1) return byId
    if (idCount > 1) {
      const visibleById = page.locator(`#${field}:visible`)
      if (await visibleById.count()) return visibleById
    }
  }

  const nameCount = await byName.count()
  if (nameCount === 1) return byName
  if (nameCount > 1) {
    const visibleByName = page.locator(`[name="${field}"]:visible`)
    if (await visibleByName.count()) return visibleByName
  }

  // {exact: true}: getByLabel() defaults to a case-insensitive SUBSTRING
  // match, which silently matches an unrelated field when the intended one
  // genuinely doesn't exist. Real CI failure: createUser.feature filling
  // "password" on user_add.php when `security.admins_can_set_users_pass` is
  // off (the fresh-install default) — the whole password field/group never
  // renders, but getByLabel('password') still matched an unrelated, hidden
  // extra field ("Moodle password", id="extra_moodle_password", tucked
  // inside the collapsed advanced-settings panel) and hung retrying an
  // interaction with a permanently-invisible element for the full test
  // timeout. Same substring-match trap already documented for
  // page.getByText() in course.feature's "TEMP"/"template" gotcha, just on
  // the fill side instead of the assertion side — exact matching makes a
  // genuinely-missing field fail fast and clearly instead of silently
  // latching onto the wrong one.
  //
  // A plain `{ exact: true }` string still isn't enough, though: BaseInputText
  // /BaseInputTextWithVuelidate render a required field's label as "* Title",
  // not "Title" (labelWithRequiredIfNeeded() prepends "* "), so an exact
  // match against the bare field name can never succeed for any required
  // field — not "close enough to eventually match once it renders", but a
  // permanent mismatch that hangs for the rest of the test's timeout. Real CI
  // failure: toolDocument.feature's document-edit form (UpdateFile.vue via
  // FormNewDocument.vue, "title" is vuelidate `required`) fetches the
  // existing document over the network before rendering, so its #title/
  // [name] tiers above can genuinely still be unattached once this fallback
  // is reached — and from there `getByLabel("title", { exact: true })` could
  // never match "* Title" no matter how much longer it waited. The regex
  // keeps the same whole-string precision (no accidental "Moodle password"
  // -style substring matches) while tolerating an optional leading "* ".
  //
  // Needs `\s*` on BOTH sides of the optional star, not just after it: real
  // CI failure on skill.feature's "argumentation" field (legacy QuickForm,
  // public/main/skills/assign.php) — its actual label markup is
  // `<label><span class="form_required">*</span>\n    Argumentation\n
  // </label>`, i.e. NEWLINE-and-indentation whitespace before the "*" too,
  // and trailing whitespace after the field name, neither of which the
  // original `^\*?\s*...$` accounted for — confirmed live that regex matched
  // zero elements against this exact label, while adding a leading `\s*`
  // (before the star) and a trailing `\s*` (before `$`) matched it correctly.
  // This fallback tier is only reached when id/name both miss, which is rare
  // but not impossible under CI timing (see resolveField above) — when it IS
  // reached, it must actually match, not hang for the rest of the test.
  const byLabel = page.getByLabel(new RegExp(`^\\s*\\*?\\s*${escapeRegExp(field)}\\s*$`, "i"))

  // FAIL FAST when NOTHING matches, instead of handing back a locator that
  // silently absorbs the entire test budget.
  //
  // This is the single biggest time-sink in debugging this suite. When a field
  // identifier is wrong or the field has genuinely moved, all three tiers miss,
  // and the caller then performs an auto-waiting action (fill/selectOption/
  // clear) on a locator that will never resolve — burning the full 90s, or a
  // full FIFTEEN MINUTES for anything tagged @long-scenario, and reporting it
  // as an opaque timeout on the label regex rather than "this field does not
  // exist". Several multi-hour investigations in this suite were exactly that:
  // a stale identifier (legacy `forum_comment` vs the Vue dialog's
  // `forum-comment`, the English label "Learning path name" vs the real id
  // `lp-title`, the long-dead `gradebook_add_eval` link) presenting as a hang.
  //
  // A bounded 15s existence check (matching the config's own expect timeout)
  // preserves every legitimate case — a field rendered late by an async fetch
  // still resolves, which is precisely what the tier comments above describe —
  // while converting "wrong identifier" from a 15-minute mystery into an
  // immediate, self-explaining error naming all three things that were tried.
  //
  // Deliberately does NOT throw when the element exists but is merely hidden or
  // disabled: that is a real, different condition the callers' own actionability
  // waits should report in their own words.
  await byLabel
    .first()
    .waitFor({ state: "attached", timeout: 15_000 })
    .catch(() => {})
  if (0 === (await byLabel.count())) {
    throw new Error(
      `No form field found for "${field}". Tried, in order: #${field} (id), ` +
        `[name="${field}"], and a label matching /^\\s*\\*?\\s*${field}\\s*$/i. ` +
        "If the page has migrated to Vue, the real id is often hyphenated " +
        "(e.g. legacy forum_comment -> forum-comment) — dump the live DOM " +
        "rather than trusting the legacy name or a .po translation.",
    )
  }
  return byLabel
}

// A plain .fill() sets the DOM value and dispatches one `input` event, which
// is enough for a plain <input> (e.g. Login.vue's fields) but was proven NOT
// enough for actionInstall.feature's Step5.vue admin password field: it's
// PrimeVue's <Password> widget, which auto-generates its own suggested value
// on mount. Confirmed via network trace that the installer's own
// auto-generated password went through to the real submission every time,
// never "admin" — first with plain .fill(), and clear()+pressSequentially()
// (simulated real keystrokes, the standard fix for rich components that
// ignore a batch value assignment) did NOT fix it either, so whatever's
// going on here isn't (only) an event-dispatch problem.
//
// Rather than guess at a third fill strategy blind, this now verifies its
// own result immediately via inputValue() and throws with full context if
// it doesn't match — turning a silent mismatch into an immediate, precise
// failure right here, instead of a confusing symptom several steps later
// (in this case: a 401 on a completely different login attempt). This is
// worth keeping permanently regardless of this specific bug — the same
// silent-mismatch class of issue could hit any field on any future feature.
//
// Atomic .fill() recovery on mismatch, not an immediate throw: real CI
// failure on ticket.feature's "title" field (BaseInputText inside a fresh
// BaseDialog — id="ticket-setting-title", name="title", TicketSettingsView.
// vue) — pressSequentially() landed a truncated value ("Vue Ti" instead of
// "Vue Ticket Project"), i.e. a PREFIX survived and the rest was lost, the
// signature of something interrupting mid-keystroke (most likely a re-render
// of the input triggered by its own first keystrokes' reactive side effects,
// e.g. an :is-invalid recompute) rather than a simple slow-render race.
// First tried a same-strategy retry (clear + pressSequentially again on
// mismatch) — that made things WORSE on the next CI run: it turned this
// truncation into 3 separate TinyMCE-never-initializes hangs plus a
// cascading 4th failure, i.e. repeating the exact per-keystroke interaction
// re-triggers whatever disruption caused the truncation in the first place.
// A single atomic .fill() as the recovery path sidesteps that entirely —
// one DOM value assignment + one input event, nothing for a mid-typing
// re-render to interrupt — while still preserving the original detection
// design for a field that's genuinely broken both ways (e.g. the PrimeVue
// <Password> widget below, which was already confirmed to defeat both
// pressSequentially AND a plain .fill() — this recovery attempt will fail
// exactly the same way for that case, and the throw below still fires).
async function fillReliably(locator: ReturnType<Page["locator"]>, value: string) {
  await locator.clear()
  await locator.pressSequentially(value)
  let actual = await locator.inputValue()
  if (actual !== value) {
    await locator.fill(value)
    actual = await locator.inputValue()
  }
  if (actual !== value) {
    throw new Error(
      `Fill did not take effect: expected "${value}", but the field's value is "${actual}" ` +
        `immediately after filling. This field likely isn't a plain <input> — see the ` +
        `comment above fillReliably() for the known case (PrimeVue's <Password> widget).`,
    )
  }
}

When("I fill in {string} for {string}", async ({ page }, value: string, field: string) => {
  await fillReliably(await resolveField(page, field), value)
})

// Mink actually registers TWO separate step regexes for a single-field fill —
// "I fill in X with Y" (field, value) and "I fill in Y for X" (value, field),
// same underlying fillField() action, just opposite argument order. Only the
// "for" form had been ported so far; course.feature is the first to use the
// "with" form ("I fill in 'title' with 'TEMP'").
When("I fill in {string} with {string}", async ({ page }, field: string, value: string) => {
  await fillReliably(await resolveField(page, field), value)
})

// Not ported — new. Submits a search/filter field by pressing Enter IN the
// field, rather than hunting for a separate submit button.
//
// Added for toolUsers.feature's course-user Subscribe view, where
// `I press "Search"` demonstrably fails to apply the filter: the failure
// snapshot shows the list still unfiltered (page 1 of ~60 available users,
// all Baggins/Boffin/Bolger) so the target user's row is simply not present,
// and the following assertion fails with a confusing "element(s) not found"
// about a name that does exist in the database. Verified live that pressing
// Enter in that same field DOES filter, reducing the table to the single
// expected row — the form's native submit path is reliable where locating the
// button was not.
//
// Worth knowing why the button is hard to hit there: its accessible name does
// not match `getByRole("button", { name: "Search", exact: true })` at all
// (confirmed live: 0 matches, the PrimeVue icon+label composition quirk
// already documented in pressButton), so pressButton falls through several
// tiers before reaching a text-content match — and this view additionally
// re-renders its list asynchronously around that moment. Pressing Enter
// sidesteps the whole question of which element is the submit control.
//
// Deliberately a separate step rather than a change to pressButton: plenty of
// other pages have a real, reliably-clickable "Search" button and there is no
// reason to route those through the keyboard.
When("I submit the field {string}", async ({ page }, field: string) => {
  const target = (await resolveField(page, field)).first()
  await target.click()
  await target.press("Enter")
})

// Mink's "I fill in the following:" (MinkContext::fillFields(), via Behat's
// TableNode::getRowsHash()) treats EVERY row as a field/value pair — there is
// no header row in this table shape. playwright-bdd's DataTable mirrors
// Cucumber's JS convention instead, where `.rows()` assumes row 0 IS a header
// and strips it (correct for `.hashes()`-style tables, wrong here) while
// `.raw()` returns every row verbatim. Using `.rows()` here silently dropped
// the FIRST field/value pair of every such table — e.g. career.feature's
// single-row table (career_title) fills nothing at all, and
// actionInstall.feature's Step 5 table silently never filled "passForm"
// (found in this session while porting career.feature, whose 1-row table
// made the bug immediately obvious: 0 rows survived `.rows()`, so nothing was
// ever filled). This retroactively explains the earlier, never-fully-closed
// "actionInstall.feature admin password" investigation (see gotcha 10 in
// memory) — the working theory then was a second, timing-related reset
// mechanism on top of the real Step5.vue hidden-input bug, but the simplest
// explanation is this: passForm was the first row in that table and was
// never actually filled by this step at all, so of course the installer's
// own auto-generated suggestion always went through. The CI escape hatch
// (CHAMILO_INSTALLER_DEFAULT_ADMIN_PASSWORD) and the Step5.vue fix are both
// still correct/worth keeping independently, but this was likely the actual
// proximate cause all along.
//
// The settle pass (re-check every field after the initial fill, re-fill any
// that drifted) is kept regardless — still a reasonable defense against a
// later row's fill resetting an earlier one, whatever the mechanism.
Then("I fill in the following:", async ({ page }, dataTable: DataTable) => {
  const rows = dataTable.raw()

  for (const [field, value] of rows) {
    await fillReliably(await resolveField(page, field), value)
  }

  for (const [field, value] of rows) {
    const locator = await resolveField(page, field)
    if ((await locator.inputValue()) !== value) {
      await fillReliably(locator, value)
    }
  }
})

// Mink's built-in attachFileToField (MinkContext, not custom FeatureContext
// code) — resolves relative paths against `files_path` (repo root, see
// `repoRoot` above); path.join here mirrors that same concatenation, and
// correctly collapses a leading "/" in `filePath` since it's not the first
// segment (unlike path.resolve, which would treat a leading-"/" second arg as
// re-rooting and drop repoRoot entirely).
When("I attach the file {string} to {string}", async ({ page }, filePath: string, field: string) => {
  await (await resolveField(page, field)).setInputFiles(path.join(repoRoot, filePath))
})

// Not ported — new, for toolDocument.feature's Uppy-based upload dialog
// (DocumentsUpload.vue). Uppy's <Dashboard> renders a real, visually-hidden
// <input type="file"> with no id/name/label resolveField() could match, so
// this bypasses that cascade entirely and targets the raw CSS selector
// directly — confirmed via a real run that setInputFiles() works on it
// despite the element being hidden (Playwright's own file-chooser action
// doesn't require visibility the way a click/fill does).
When("I attach the file {string} to the upload dropzone", async ({ page }, filePath: string) => {
  // Uppy's <Dashboard> renders more than one <input type="file"> (confirmed
  // via a real run: 2 matches, "strict mode violation") — .first() is the
  // one actually wired to the visible dropzone.
  await page.locator('input[type="file"]').first().setInputFiles(path.join(repoRoot, filePath))
})

// How long to wait for a TinyMCE instance to finish mounting. 20s matches the
// TINYMCE_READY_TIMEOUT already used by the ticket-settings step below, which
// was tuned against real CI.
//
// The bound matters far more than its exact value. `page.waitForFunction()` with
// NO timeout inherits the whole test timeout — which for an @long-scenario is
// FIFTEEN MINUTES. Real CI failure (2026-08-25): specialCase1Sessions' "Create
// courses, multilingual documents, exercises, forum, learning path and
// assessment activity" burned its entire 900s budget inside one such wait and
// then reported only `page.waitForFunction: Test timeout of 900000ms exceeded`
// — no editor id, no step name, and a page snapshot showing the dashboard
// rather than any editor, i.e. fifteen minutes spent to learn nothing.
//
// Same lesson as resolveField()'s fail-fast rewrite: an unbounded wait does not
// buy reliability, it converts a precise failure into an expensive mystery.
const TINYMCE_MOUNT_TIMEOUT = 20_000

// Wait for a specific TinyMCE editor, failing FAST and by name. `whatFor`
// should say which step is waiting, since the editor id alone is rarely enough
// to locate the culprit in a long scenario.
async function waitForTinymceEditor(page: Page, editorId: string, whatFor: string): Promise<void> {
  try {
    await page.waitForFunction((id) => Boolean((window as any).tinymce?.get(id)), editorId, {
      timeout: TINYMCE_MOUNT_TIMEOUT,
    })
  } catch {
    throw new Error(
      `TinyMCE editor "#${editorId}" never mounted within ${TINYMCE_MOUNT_TIMEOUT}ms (${whatFor}). ` +
        `The element exists but tinymce.get("${editorId}") stayed undefined — usually the page ` +
        `navigated away/redirected before the editor initialised, or the legacy_app bundle that ` +
        `boots TinyMCE never loaded on this page. Current URL: ${page.url()}`,
    )
  }
}

// Ported from FeatureContext::iFillInWysiwygOnFieldWith(). The legacy admin
// pages (e.g. careers.php) use a TinyMCE editor bound to a hidden <textarea>,
// not a plain field — window.setContentFromEditor(id, content) (assets/js/
// legacy/app.js) is the same helper the original Behat step's JS shelled out
// to inline; only legacy pages load it (bundled in the legacy_app webpack
// entry), never the Vue SPA. Behat blindly slept 2000ms first "just in case
// ckeditor is loaded" (its own comment — the app actually uses TinyMCE, not
// CKEditor); waitForFunction polls for the real readiness signal instead.
Then("I fill in editor field {string} with {string}", async ({ page }, field: string, value: string) => {
  const fieldId = await (await resolveField(page, field)).getAttribute("id")
  if (!fieldId) {
    throw new Error(`Could not find an id for field with locator: ${field}`)
  }
  await waitForTinymceEditor(page, fieldId, `filling editor field "${field}"`)
  await page.evaluate(
    ({ id, value }) => (window as any).setContentFromEditor(id, value),
    { id: fieldId, value },
  )
})

// Ported from FeatureContext::iFillInTinyMceOnFieldWith() — distinct from
// "I fill in editor field ... with ..." above, which relies on
// window.setContentFromEditor(), a legacy-pages-only helper (assets/js/legacy/
// app.js, bundled only in the legacy_app webpack entry). ticket.feature's forms
// are Vue (TicketCreateView.vue/TicketSettingsView.vue), whose BaseTinyEditor
// wraps @tinymce/tinymce-vue with no custom `model-events` prop, so it falls
// back to that library's default modelEvents list, 'change input undo redo'
// (node_modules/@tinymce/tinymce-vue/lib/es2015/main/ts/Utils.js) to know when
// to emit update:modelValue. TinyMCE's own setContent() only fires internal
// SetContent/BeforeSetContent events, neither of which is in that list — so a
// bare setContent() would silently never reach the Vue v-model, and
// TicketCreateView.vue's submit blocks entirely on empty content
// (hasMessageContent check, content is a required field there). Firing
// 'change' explicitly after setContent() is what actually satisfies
// bindModelHandlers()'s listener.
Then("I fill in tinymce field {string} with {string}", async ({ page }, field: string, value: string) => {
  const fieldId = await (await resolveField(page, field)).getAttribute("id")
  if (!fieldId) {
    throw new Error(`Could not find an id for field with locator: ${field}`)
  }
  await waitForTinymceEditor(page, fieldId, `filling tinymce field "${field}"`)
  await page.evaluate(({ id, value }) => {
    const editor = (window as any).tinymce.get(id)
    editor.setContent(value)
    editor.fire("change")
    editor.fire("input")
    editor.save()
  }, { id: fieldId, value })
})

// Not ported — new, replaces the separate "click add" + "fill title" +
// "fill tinymce" step sequence for ticket.feature's 4 "Create a Ticket X"
// scenarios (project/category/status/priority) with one atomic, retriable
// unit. Real, confirmed CI-only flake, never once reproduced across repeated
// local runs on a stable, uncontended box: TinyMCE's own init() for the
// dialog's description editor occasionally never completes — no console
// error, no exception, completely silent — and the target element itself
// stops existing in the DOM shortly after (confirmed live via a direct
// diagnostic script). A real CI trace showed the page load and the
// dialog-open click alone had already taken ~10s combined before the dialog
// even opened on the run that hit this, pointing to a resource-contention-
// sensitive race inside TinyMCE's own third-party init code — more likely
// to surface on a loaded shared CI runner than a dedicated local box — not
// a bug in our own step code or the Vue component. Once it happens, waiting
// longer never recovers it (the element is just gone); the only real fix is
// a fresh mount, i.e. closing and reopening the dialog. A prior, narrower
// retry attempt elsewhere (just re-typing a truncated title on mismatch)
// made a DIFFERENT failure worse, so this retries the WHOLE open+fill
// sequence as one unit rather than repeating just the doomed action in place.
//
// A single retry (2 attempts total) still surfaced this in real CI runs —
// each fresh mount is an independent roll of the same race, not a guaranteed
// recovery, so one retry only halves the odds of hitting the ceiling rather
// than closing it. Bumped to 2 retries (3 attempts total), which is as far
// as this can go without risking the outer 90s test timeout (playwright.
// config.ts): 3 attempts at the existing 10s/20s sub-timeouts fit with room
// to spare for the rest of the scenario's own steps, but raising either
// sub-timeout too would eat back into that margin, so both are left as-is —
// the extra attempt is the lever that actually helps here (a fresh mount
// each time), not more patience on a mount that's already stuck.
When(
  "I create a ticket setting with title {string} and description {string}",
  async ({ page }, title: string, description: string) => {
    const STEP_TIMEOUT = 10_000
    const TINYMCE_READY_TIMEOUT = 20_000
    const CANCEL_TIMEOUT = 5_000
    const MAX_ATTEMPTS = 3

    async function openAndFill(): Promise<boolean> {
      await page.locator("#ticket-settings-add:visible").first().click({ timeout: STEP_TIMEOUT })
      await fillReliably(await resolveField(page, "title"), title)

      // Known id from TicketSettingsView.vue's `editor-id="ticket-setting-description"`,
      // set on BaseTinyEditor's own element as soon as it mounts, well before
      // tinymce.init() itself completes — located directly with a bounded wait
      // rather than through resolveField()'s generic id/name/label tiers.
      // BaseTinyEditor renders no <label> at all, so resolveField's getByLabel
      // fallback can never match and would wait UNBOUNDED (no timeout of its
      // own) for a match that can never come. Real CI failure: this exact path
      // burned the entire 90s test timeout before ever reaching the tinymce
      // wait below, leaving no time for the close-and-retry recovery.
      const fieldId = "ticket-setting-description"
      await page.locator(`#${fieldId}`).waitFor({ state: "attached", timeout: STEP_TIMEOUT })

      try {
        await page.waitForFunction((id) => Boolean((window as any).tinymce?.get(id)), fieldId, {
          timeout: TINYMCE_READY_TIMEOUT,
        })
      } catch {
        return false
      }
      await page.evaluate(
        ({ id, value }) => {
          const editor = (window as any).tinymce.get(id)
          editor.setContent(value)
          editor.fire("change")
        },
        { id: fieldId, value: description },
      )
      return true
    }

    async function tryOpenAndFill(): Promise<boolean> {
      try {
        return await openAndFill()
      } catch {
        return false
      }
    }

    for (let attempt = 1; attempt <= MAX_ATTEMPTS; attempt++) {
      if (await tryOpenAndFill()) return

      if (attempt === MAX_ATTEMPTS) {
        throw new Error(
          `TinyMCE editor "ticket-setting-description" never became ready, even after closing and reopening the dialog ${MAX_ATTEMPTS - 1} times.`,
        )
      }

      // Recovery: discard the stuck dialog (and its never-initialized editor)
      // and reopen it fresh. Bounded AND tolerant of the click itself
      // failing: a real CI trace showed the whole dialog — not just the
      // editor — can already be gone by this point, so there may be no
      // Cancel button left to click at all. Either way, only a fresh mount
      // for the retry matters; a prior version of this recovery clicked
      // Cancel with no timeout, which then hung for the rest of the 90s test
      // timeout in 4 of 5 observed CI failures once the button was never
      // going to appear.
      await page
        .getByRole("button", { name: "Cancel", exact: true })
        .click({ timeout: CANCEL_TIMEOUT })
        .catch(() => {})
    }
  },
)

// Not ported — new, for toolGlossary.feature's "Create glossary term"
// scenario. Real, confirmed live flake on the shared box (reproduced
// directly via a raw script, not just from a single test failure):
// GlossaryForm.vue's submitGlossaryForm() sometimes shows the "Could not
// create glossary term" error toast and stays on the create form EVEN
// THOUGH the POST to /api/glossaries already returned 201 and the row was
// actually persisted — confirmed by querying /api/glossaries?q=<title>
// immediately after such a "failure" and finding the row already there.
// Repeating the same title on a naive retry would then collide with that
// already-created row (CGlossary enforces a "glossary term already exists"
// uniqueness check) and fail a SECOND time for a different reason, making
// a blind retry actively worse. Instead: after Save, if the error toast
// shows up, check whether the term was actually created despite it (GET
// the collection filtered by title) before deciding whether to retry —
// only a genuine failure (term truly absent) retries the form submit once.
When(
  "I create the glossary term {string} with description {string}",
  async ({ page }, title: string, description: string) => {
    async function submit() {
      await page.locator("#term-name").fill(title)
      await page.locator("#term-description").fill(description)
      await page.locator("button", { hasText: "Save term" }).first().click()
      // Race the two possible outcomes rather than waiting for either one's
      // own fixed timeout first — whichever happens first tells us which
      // path to take next.
      await Promise.race([
        page.waitForURL((url) => !url.pathname.includes("/create"), { timeout: 10_000 }).catch(() => {}),
        page.getByText("Could not create glossary term").waitFor({ timeout: 10_000 }).catch(() => {}),
      ])
    }

    async function termExists(): Promise<boolean> {
      const cid = new URLSearchParams(new URL(page.url()).search).get("cid") ?? ""
      const gid = new URLSearchParams(new URL(page.url()).search).get("gid") ?? ""
      const response = await page.request.get(
        `/api/glossaries?cid=${encodeURIComponent(cid)}&gid=${encodeURIComponent(gid)}&q=${encodeURIComponent(title)}`,
        { headers: { Accept: "application/ld+json" } },
      )
      const rows = await response.json().catch(() => [])
      return Array.isArray(rows) && rows.some((row) => row.title === title)
    }

    await submit()
    if (await termExists()) {
      // Created for real, whether or not the error toast fired — the SPA
      // may still be sitting on the create form if it did; navigate to the
      // list so the caller's own "I should see" assertion has something to
      // find.
      if (page.url().includes("/create")) {
        await page.goBack()
        await page.waitForLoadState("domcontentloaded")
      }
      return
    }
    // Genuinely not created — retry the whole submit exactly once.
    await submit()
    if (!(await termExists())) {
      throw new Error(`Glossary term "${title}" was not created after two attempts.`)
    }
    if (page.url().includes("/create")) {
      await page.goBack()
      await page.waitForLoadState("domcontentloaded")
    }
  },
)

// Not ported — new, for toolWork.feature's Vue create/edit assignment
// dialogs (AssignmentForm.vue-style). BaseTinyEditor generates a fresh,
// unpredictable instance id per mount (`tiny-vue_<random>`) rather than a
// stable one tied to the field name, so there's no id to resolveField()
// against at all — confirmed via a real DOM dump. Since these dialogs only
// ever have ONE editor open at a time, `tinymce.activeEditor` reliably
// identifies it without needing an id.
Then("I fill in the active tinymce editor with {string}", async ({ page }, value: string) => {
  // Bounded for the same reason as waitForTinymceEditor() — see its comment.
  // Unbounded, this inherits the test timeout (15 minutes on an @long-scenario).
  try {
    await page.waitForFunction(() => Boolean((window as any).tinymce?.activeEditor), undefined, {
      timeout: TINYMCE_MOUNT_TIMEOUT,
    })
  } catch {
    throw new Error(
      `No active TinyMCE editor appeared within ${TINYMCE_MOUNT_TIMEOUT}ms. ` +
        `tinymce.activeEditor stayed undefined, so the dialog holding the editor probably never ` +
        `opened (or closed again before this step ran). Current URL: ${page.url()}`,
    )
  }
  await page.evaluate((value) => {
    const editor = (window as any).tinymce.activeEditor
    editor.setContent(value)
    editor.fire("change")
    editor.fire("input")
    editor.save()
  }, value)
})

// Mink's "I check ..." checks a checkbox, same id -> name -> label resolution.
Then("I check {string}", async ({ page }, field: string) => {
  await (await resolveField(page, field)).check()
})

// Not ported — new, symmetric counterpart for toolAssessments.feature's
// teardown (unchecking "Generate certificates" again after the scenario
// that turned it on). Mink has no built-in "I uncheck" step; same
// resolution cascade as "I check" above.
Then("I uncheck {string}", async ({ page }, field: string) => {
  await (await resolveField(page, field)).uncheck()
})

// Not ported — new, for toolAssessments.feature's "Create an evaluation"
// scenario. gradebook_add_result.php's per-learner score field is
// genuinely id/name "score[<numeric user id>]" (confirmed live) — the
// Behat original hardcoded "score[5]", assuming a fixed seed order that
// doesn't hold on this box (acostea is id 57 here). Same
// look-up-by-username-instead-of-hardcoding pattern already established
// for "I have a friend named ..." above, via the same /api/users endpoint
// used elsewhere in this file.
When("I fill in the score for {string} with {string}", async ({ page }, username: string, value: string) => {
  const response = await page.request.get(`/api/users?username=${encodeURIComponent(username)}`, {
    headers: { Accept: "application/ld+json" },
  })
  const { "hydra:member": members } = await response.json()
  const userId = members?.[0]?.id
  if (!userId) {
    throw new Error(`Could not resolve a user id for username "${username}"`)
  }
  // Legacy gradebook_add_result.php used name="score[<id>]". The Vue
  // GradebookEvaluationResultsView uses id="gradebook-score-<id>" (a
  // PrimeVue InputNumber; name is not forwarded onto the inner <input>).
  const locator = page.locator(`[name="score[${userId}]"], #gradebook-score-${userId}`).first()
  await locator.waitFor({ state: "visible" })
  await fillReliably(locator, value)
})

// Ported from FeatureContext::iCheckTheRadioButton(): resolves a radio input
// by its associated <label> text (Mink's findField() also allows id/name,
// but course.feature's own usage — "Private access (access authorized to
// group members only)" — is plainly label text, not an id/name value) and
// checks it. getByLabel handles both a wrapping <label> and a <label for="">
// pointing at the input, matching findField()'s own resolution.
//
// {exact: true} is required: createUser.feature's "No" (the "Send mail to
// new user" radio, on the Vue user_add.php replacement) is a case-insensitive
// SUBSTRING of an unrelated extra field's aria-label, "Event notifications"
// ("**no**tifications") — confirmed live, a real CI failure. Same class of
// false-positive-substring-match trap as course.feature's "TEMP"/"template"
// gotcha; a radio button's own label is always a short, exact phrase like
// "Yes"/"No", never one meant to substring-match something else.
When("I check the {string} radio button", async ({ page }, label: string) => {
  await page.getByLabel(label, { exact: true }).check()
})

// Ported from FeatureContext::iCheckTheRadioButtonBasedInSelector(). Unlike
// the label-based step above, this one takes a raw CSS selector directly
// (createUser.feature's "#send_mail_no") — the original PHP step just set the
// DOM `checked` property via jQuery with no real user interaction/events;
// .check() is the direct Playwright equivalent (a real click, which also
// covers any onclick/change listener the original's bare .prop() skipped).
When("I check the {string} radio button selector", async ({ page }, selector: string) => {
  await page.locator(selector).check()
})

// Ported from FeatureContext::iCheckTheRadioButtonWithValue(). The original
// shelled out to jQuery to force the DOM `checked` property directly
// (`$('input[type="radio"][name=X][value=Y]').prop('checked', true)`) — a
// real .check() click is the direct Playwright equivalent and also fires any
// onclick/change listener the original bypassed. Used for platform settings
// rendered as name/value radio groups (e.g. toolLp.feature's
// "hide_scorm_pdf_link" YesNoType setting), where the two options share one
// `name` and are distinguished only by `value`, not by a stable id.
Given("I check the {string} radio button with {string} value", async ({ page }, name: string, value: string) => {
  await page.locator(`input[type="radio"][name="${name}"][value="${value}"]`).check()
})

// Not ported — new, for toolLp.feature's PDF-export-icon platform-setting
// check (an icon whose only identifier is its `title` attribute, shown/
// hidden entirely based on a setting — no surrounding text to assert on).
Then("I should see an icon with title {string}", async ({ page }, title: string) => {
  await expect(page.locator(`[title="${title}"]`).first()).toBeVisible()
})

Then("I should not see an icon with title {string}", async ({ page }, title: string) => {
  await expect(page.locator(`[title="${title}"]`)).toHaveCount(0)
})

// Not ported — new. Mirrors FeatureContext's own generic Select2/ajax-select
// steps (iFillInSelectInputWithAndSelect / iFillInAjaxSelectInputWithAndSelect)
// but drives the REAL widget through the UI instead of shelling out to jQuery,
// for any FormValidator::addSelectAjax() field (Select2 + a real AJAX search
// endpoint — course_add.php's course_categories, session_add.php's
// coach_username/courses/users — not a plain <select>, so resolveField()/
// selectOption() don't apply). Typing into the search box triggers the AJAX
// call; getByRole('option') naturally waits out the "Searching…" placeholder
// (no result exists until the request resolves) instead of racing it.
//
// Tries an exact-ish named match first (safer when a search can return more
// than one loosely-matching result, e.g. course_add.php's category search),
// then falls back to whatever the server returned first: for some fields the
// searched text is not what's DISPLAYED — session_add.php's coach_username
// searches by username (e.g. "mmosquera") but Select2 there renders full
// names ("Michela Mosquera Guardamino"), so a name-based match would never
// find anything even though the correct (and only) result is right there.
// The server already did the actual matching (that's what the search query
// just triggered), so falling back to "first result" is safe once a named
// match is confirmed absent, not just slow to appear.
//
// Uses the raw `[role="option"]` ATTRIBUTE selector, not getByRole("option"):
// two failed attempts first. (1) Unscoped getByRole("option") matches ANY
// option-role element on the page, including a plain native <select>'s
// <option> children (implicit ARIA role, no literal attribute) — on
// session_add.php's "advanced settings" panel, which has three native
// <select>s (session template, category, status) visible at the same time
// as the coach_username Select2, this reliably grabbed the category select's
// static "none" option instead of the real search result. (2) Scoping to the
// field's own DOM parent (on the theory the results dropdown renders
// alongside the search box) also failed — Select2 renders its dropdown/
// listbox APPENDED TO document.body as a floating overlay, not nested inside
// the original field's container at all, so that scoped locator never found
// anything, even for the previously-working simple case. `[role="option"]`
// (an attribute selector) only matches elements with that EXACT literal HTML
// attribute — Select2's real dropdown items have it (confirmed via a real
// accessibility snapshot), native <option> elements never do (their role is
// computed/implicit, not a DOM attribute) — so this naturally excludes
// unrelated native selects without needing correct DOM scoping at all.
When("I select {string} from the ajax select {string}", async ({ page }, optionText: string, fieldId: string) => {
  const searchField = page.locator(`#${fieldId}`).locator("..").locator(".select2-search__field")
  await searchField.click()
  await searchField.fill(optionText)
  const named = page.locator('[role="option"]', { hasText: optionText })
  if (await isSoonVisible(named, 3000)) {
    await named.first().click()
    return
  }
  await page.locator('[role="option"]').first().click()
})

// Mink's "I select X from Y" (a <select>'s option, matched by its visible
// label) replaces the current selection entirely — correct for a plain
// single-select, and for a multi-select it's always the FIRST of a
// "select" + N "additionally select" sequence in these .feature files, so
// starting from a clean selection is exactly right.
Then("I select {string} from {string}", async ({ page }, optionLabel: string, field: string) => {
  await failIfLoginPage(page, `select "${optionLabel}" from "${field}"`)
  await (await resolveField(page, field)).selectOption({ label: optionLabel })
})

// Not ported — new, for translateHtmlFallback.feature's YesNoType toggle
// (form_translate_html). Unlike a language field's option labels (e.g.
// course_language's "Deutsch"/"Español" — a language's own native name,
// invariant regardless of UI locale), YesNoType's "Yes"/"No" choice labels
// (src/CoreBundle/Form/Type/YesNoType.php) are real translatable UI chrome:
// confirmed live on this shared box, currently rendering as "Sí"/"No" (its
// current admin/session locale is Spanish), which made the label-based step
// above hang forever looking for a literal "Yes" option that doesn't exist
// under that locale. The underlying <option value="true"|"false"> values are
// never translated, so selecting by value sidesteps the whole problem.
Then("I select the value {string} from {string}", async ({ page }, optionValue: string, field: string) => {
  await failIfLoginPage(page, `select value "${optionValue}" from "${field}"`)
  await (await resolveField(page, field)).selectOption({ value: optionValue })
})

// Mink's "I additionally select X from Y" adds one more option to a
// <select multiple> without clearing whatever's already selected.
// Playwright's selectOption() always sets the *entire* selection to
// whatever array it's given (there's no built-in "add one" variant), so
// this reads the currently-selected option labels first and re-selects
// the union of those plus the new one.
Then("I additionally select {string} from {string}", async ({ page }, optionLabel: string, field: string) => {
  const locator = await resolveField(page, field)
  const currentLabels: string[] = await locator.evaluate((el) =>
    Array.from((el as HTMLSelectElement).selectedOptions).map((option) => option.label),
  )
  await locator.selectOption([...currentLabels, optionLabel].map((label) => ({ label })))
})

// Not ported from Behat — the original adminSettings.feature (and every
// OTHER feature below found to mutate a platform setting) has no teardown
// at all and just leaves platform settings permanently changed for the rest
// of the run. Real, confirmed consequence: createUser.feature's "Create a
// HRM user" sets admins_can_set_users_pass to Yes and never reverts it;
// sessionManagement.feature's two "Check session description..." scenarios
// leave show_session_description at Yes (its schema default is No — this
// one was a genuine unrestored leak, not a coincidence); toolLp.feature's
// two "Check the PDF export..." scenarios happen to leave hide_scorm_pdf_
// link at Yes, its actual schema default, but only because that scenario
// runs last — pure luck of ordering, not a deliberate guarantee. All three
// were confirmed live on this project's own long-lived test instance,
// already stuck at Yes from earlier runs. And this is exactly the same
// class of bug that caused toolGroup.feature's "0 categories rendered"
// mystery (allow_group_categories toggled by adminSettings.feature,
// restored too late relative to other files running concurrently) —
// except these three were never even caught by an @settings-style restore
// in the first place.
//
// registerSettingsGuard() makes this pattern reusable instead of the one
// hardcoded copy adminSettings.feature used to have: each feature file that
// touches a platform setting gets its OWN tag and its OWN small pages list
// (not one shared global list every tagged file would otherwise redundantly
// snapshot/restore in full) — snapshot whatever each setting's actual
// current value already is (not a hardcoded assumed default, which could be
// wrong for a given instance and would itself be an unwanted mutation)
// before any scenario in that file mutates it, then restore exactly that
// once after the file's last scenario finishes.
//
// CORRECTION (2026-08-25) — the paragraph that used to sit here claimed these
// were "Playwright's own per-file hooks ... not a single global per-worker
// lifetime", and that each tag therefore got its own independent restore
// cycle. That is wrong for AfterAll, and believing it is what let the bug
// below ship. playwright-bdd's own source says so explicitly
// (node_modules/playwright-bdd/dist/runtime/bddWorkerFixtures.js):
//
//   "We can't detect when the last test for tagged AfterAll hook is executed
//    ... The solution: collect and run all AfterAll hooks in worker teardown
//    phase."
//
// So BeforeAll really is per-file, but EVERY tagged AfterAll in the worker is
// deferred and run together at the very end, inside one `$registerAfterAllHooks`
// fixture teardown governed by a SINGLE budget (the config `timeout`, 90s).
// Two consequences that drive the design below:
//
//  1. The budget is GLOBAL across all guards, not per guard. Under `workers: 1`
//     one worker runs every file, so all ~9 restores land in that one teardown.
//     Nine restores x (admin page load + Save + settle) does not fit in 90s.
//  2. The restores therefore happen AFTER every test has finished, so they
//     cannot protect any test in the same run — their only real job is leaving
//     a persistent box clean for the NEXT run (CI reinstalls the DB each time,
//     so this matters locally far more than in CI).
//
// Given (2), a restore that cannot finish must never fail the run: a cleanup
// failure is not a test failure. Blowing the budget made CI report the whole
// job red on a run where all 410 tests PASSED — a maximally confusing signal.
// Hence the shared deadline and the per-restore try/catch below: the teardown
// does as much as it can inside a budget it is guaranteed to fit in, and
// reports anything it had to skip instead of throwing.
//
// The deadline is module-level and lazily initialised on first use precisely
// because it must be shared by every guard, not restarted by each one.
const TEARDOWN_BUDGET_MS = 50_000
let teardownDeadline: number | null = null

function teardownBudgetExhausted(): boolean {
  if (null === teardownDeadline) {
    teardownDeadline = Date.now() + TEARDOWN_BUDGET_MS
  }
  return Date.now() > teardownDeadline
}

// 50s of restoring, inside a 90s fixture budget, deliberately leaves ~40s of
// slack: the check happens BEFORE each restore, so one already-in-flight
// restore (whose gotoReliably() can itself retry a slow admin page) still has
// room to finish without tripping the fixture timeout.
function warnSkippedRestore(field: string, reason: string): void {
  // Deliberately console.warn and not a throw — see the note above on why a
  // cleanup failure must not fail the run. This still surfaces in CI logs, so
  // a box that stops getting restored is visible rather than silent.
  console.warn(`[settings-guard] did NOT restore "${field}" (${reason}). It may be left mutated.`)
}

function registerSettingsGuard(tag: string, pages: { path: string; field: string }[]) {
  const snapshot = new Map<string, string[]>()

  // Shared between BeforeAll and AfterAll deliberately: creating a *fresh*
  // browser context in AfterAll (as a first attempt did) raced against
  // Playwright's own worker teardown — by the time AfterAll runs, the worker
  // has finished all its assigned tests and is winding down, and a brand new
  // context's navigation could never complete (a plain "Sign in" click hung
  // past even a very generous per-action timeout). Keeping the one context
  // BeforeAll already opened alive in between, instead of closing and
  // recreating it, sidesteps that race entirely — it's an already-running,
  // healthy context the whole time, just idle between the two hooks.
  let guardPage: import("@playwright/test").Page | undefined

  BeforeAll({ tags: tag }, async ({ browser, baseURL }) => {
    const page = await loginAsAdminOnFreshPage(browser, baseURL)
    guardPage = page
    for (const { path, field } of pages) {
      await gotoReliably(page, path)
      const values: string[] = await page.locator(`#${field}`).evaluate((el) =>
        Array.from((el as HTMLSelectElement).selectedOptions).map((option) => option.value),
      )
      snapshot.set(field, values)
    }
  })

  AfterAll({ tags: tag }, async () => {
    if (!guardPage) return
    const page = guardPage
    for (const { path, field } of pages) {
      const values = snapshot.get(field)
      if (!values) continue
      if (teardownBudgetExhausted()) {
        warnSkippedRestore(field, "shared teardown budget exhausted")
        continue
      }
      try {
        // Deliberately NOT gotoReliably() here, even though every other
        // navigation in this file uses it. It retries up to 5 times, and with
        // no `navigationTimeout` configured each attempt gets page.goto's own
        // 30s default — so one call can occupy 150s. Inside a teardown whose
        // TOTAL budget is 90s that is not a retry policy, it is an overrun
        // waiting to happen, and it is why the 50s deadline above was not
        // enough on its own: the deadline is only consulted BETWEEN restores,
        // so a single call that runs long sails straight past it.
        //
        // A bounded single attempt is the right trade for cleanup: worst case
        // per restore drops from ~150s to ~15s, so nine restores fit inside the
        // deadline with room to spare, and a restore that genuinely cannot load
        // its page is exactly the one we want to skip-and-warn rather than
        // retry four more times.
        await page.goto(path, { timeout: 10_000, waitUntil: "domcontentloaded" })
        await page.locator(`#${field}`).selectOption(
          values.map((value) => ({ value })),
          { timeout: 5_000 },
        )
        // "Save settings" (not "Save"): matches the literal button text on
        // every /admin/settings/* page confirmed live (both the
        // search_settings?keyword=... pages and category pages like
        // /admin/settings/lp) — pressButton()'s early exact-match tiers need
        // the literal text, not a substring; "Save" alone only happens to
        // work via its much later substring-fallback tier.
        await pressButton(page, "Save settings")
        // "Save settings" is a form submit — likely POST-redirect-GET under
        // the hood. The NEXT iteration's own gotoReliably() absorbs a
        // still-lagging redirect from this Save if one occurs, so this wait
        // just needs to be reasonable, not airtight.
        //
        // 3s, not 10s, and that number is the whole fix rather than a tweak.
        // This app has persistent background polling (notifications, chat
        // presence), so networkidle frequently NEVER settles here and the wait
        // runs to its full bound every time. At 10s x 9 restores that is 90s
        // of pure waiting — the entire shared fixture budget, spent doing
        // nothing, which is precisely how a run with 410/410 passing tests
        // still reported a red CI job. Measured, not assumed: the failing CI
        // teardown died on the 6th of 9 restores.
        await page.waitForLoadState("networkidle", { timeout: 3_000 }).catch(() => {})
      } catch (error) {
        // One bad restore must not abandon the ones queued behind it — that
        // was the old failure mode, where a single slow field silently took
        // every later setting down with it.
        warnSkippedRestore(field, String(error).split("\n")[0])
      }
    }
    await page.context().close().catch(() => {})
  })
}

registerSettingsGuard("@settings", [
  { path: "/admin/settings/search_settings?keyword=changeable_options", field: "form_changeable_options" },
  { path: "/admin/settings/search_settings?keyword=allow_registration", field: "form_allow_registration" },
  { path: "/admin/settings/search_settings?keyword=allow_group_categories", field: "form_allow_group_categories" },
])

// createUser.feature's "Create a HRM user" needs this on temporarily (see
// that scenario's own comment) to make the manual-password field appear.
registerSettingsGuard("@settings-createUser", [
  {
    path: "/admin/settings/search_settings?keyword=admins_can_set_users_pass",
    field: "form_admins_can_set_users_pass",
  },
])

// sessionManagement.feature's two "Check session description..." scenarios
// toggle this between No and Yes to test the settings-toggle UI itself
// (the setting has no actual effect on session display in the current Vue
// frontend — see that file's own header comment).
registerSettingsGuard("@settings-sessionManagement", [
  { path: "/admin/settings/search_settings?keyword=show_session_description", field: "form_show_session_description" },
])

// toolLp.feature's two "Check the PDF export..." scenarios toggle this
// between No and Yes to exercise the LP list's PDF-export icon under both
// states.
registerSettingsGuard("@settings-toolLp", [
  { path: "/admin/settings/lp", field: "form_hide_scorm_pdf_link" },
])

// toolGlossary.feature's "Enable glossary display in extra tools" scenario
// sets this to "Learning path" with no teardown in the original Behat file —
// same leaked-setting pattern already fixed for toolLp/sessionManagement.
registerSettingsGuard("@settings-toolGlossary", [
  { path: "/admin/settings/glossary", field: "form_show_glossary_in_extra_tools" },
])

// translateHtmlFallback.feature turns editor.translate_html on for all of
// its scenarios, and its "platform default" scenario additionally repoints
// language.platform_language at a language neither the viewer nor the test
// course uses, so that fallback tier is exercised deterministically instead
// of depending on whatever this instance's actual default happens to be.
//
// Uses the category pages (like @settings-toolLp/@settings-toolGlossary
// below), not search_settings?keyword=... (like @settings/@settings-createUser/
// @settings-sessionManagement above): confirmed live that keyword=... no
// longer pre-fills the "Palabra clave" search box on this Vue-rendered
// settings page — the URL param is silently ignored on load, leaving
// whatever field happened to be shown last (a real screenshot showed the
// Language category's own field still selected after navigating to
// keyword=translate_html) instead of a translate_html field ever
// appearing. The direct category page has no such dependency.
registerSettingsGuard("@settings-translateHtml", [
  { path: "/admin/settings/editor", field: "form_translate_html" },
  { path: "/admin/settings/language", field: "form_platform_language" },
])

// Same snapshot-before/restore-after intent as registerSettingsGuard() above,
// but for a single free-text <textarea> setting instead of a <select> —
// courseCatalogue.feature's "course_catalog_settings" field (CatalogSettingsSchema,
// rendered as a TextareaType on /admin/settings/catalog) has no
// .selectedOptions to read, so that helper's snapshot logic doesn't apply.
// The original Behat courseCatalogue.feature scenario left this setting
// permanently mutated (same leaked-setting class already fixed above for
// several other files) — restoring whatever value this box actually had
// before the scenario ran (confirmed live: blank on this shared box, but not
// hardcoding that — a different install could have a real configured value)
// keeps every other suite/session that depends on catalogue behavior
// unaffected once this file's scenarios finish.
//
// Needs an explicit bounded networkidle wait (not just domcontentloaded)
// before reading/writing the field: confirmed live that /admin/settings/catalog
// is a Vue SPA settings page whose form (including the "Save settings"
// button) only appears once its own settings fetch resolves — a plain
// page.goto() alone can still observe an empty textarea and 0 Save buttons.
function registerTextSettingsGuard(tag: string, path: string, field: string) {
  let snapshot: string | null = null
  let guardPage: Page | undefined

  async function settle(page: Page) {
    await page.waitForLoadState("domcontentloaded")
    await page.waitForLoadState("networkidle", { timeout: 10_000 }).catch(() => {})
  }

  BeforeAll({ tags: tag }, async ({ browser, baseURL }) => {
    const page = await loginAsAdminOnFreshPage(browser, baseURL)
    guardPage = page
    await gotoReliably(page, path)
    await settle(page)
    snapshot = await page.locator(`#${field}`).inputValue()
  })

  AfterAll({ tags: tag }, async () => {
    if (!guardPage || snapshot === null) return
    const page = guardPage
    // Shares the SINGLE global teardown budget with every registerSettingsGuard()
    // above (see the long note there) — so it checks the same deadline and can be
    // starved by them even though its own restore is just one field. Whether it
    // gets skipped is therefore an ordering accident, which is exactly why the
    // skip has to be announced rather than silent.
    if (teardownBudgetExhausted()) {
      warnSkippedRestore(field, "shared teardown budget exhausted")
      await page.context().close().catch(() => {})
      return
    }
    try {
      // Bounded single attempt, same reasoning as registerSettingsGuard()'s
      // teardown above (gotoReliably's 5 x 30s can outlast the whole budget).
      await page.goto(path, { timeout: 10_000, waitUntil: "domcontentloaded" })
      await settle(page)
      await page.locator(`#${field}`).fill(snapshot, { timeout: 5_000 })
      await pressButton(page, "Save settings")
      await page.waitForLoadState("networkidle", { timeout: 3_000 }).catch(() => {})
    } catch (error) {
      warnSkippedRestore(field, String(error).split("\n")[0])
    }
    await page.context().close().catch(() => {})
  })
}

// courseCatalogue.feature's "Update catalogue settings..." scenario is the
// only place in this suite that mutates course_catalog_settings.
registerTextSettingsGuard("@settings-courseCatalogue", "/admin/settings/catalog", "form_course_catalog_settings")

// Wraps page.goto() for the settings BeforeAll/AfterAll loops specifically.
// Both hooks have now independently hit the SAME failure, despite two
// separate rounds of adjusting the wait BEFORE each goto() (domcontentloaded
// -> networkidle, applied first to AfterAll's post-Save wait, then to
// BeforeAll's own loop): "Navigation to X is interrupted by another
// navigation to Y" — a still-lagging navigation from an EARLIER step (a
// previous iteration's page load, or its Save-triggered redirect) hasn't
// actually finished by the time THIS iteration's goto() fires, even though
// the preceding wait already resolved. This app has documented background
// polling (notifications, chat presence — see loginAs()'s own comment
// elsewhere in this file) that can make `networkidle` resolve on a brief
// lull before a redirect chain is truly done, so neither domcontentloaded
// nor networkidle is a fully reliable "everything has settled" signal here.
//
// A first version of this just retried the goto() ONCE, immediately, right
// after catching the error. That wasn't enough either — a real CI run then
// showed "Navigation to allow_registration is interrupted by ANOTHER
// navigation to allow_registration" (same URL both times): a rejected
// goto() doesn't necessarily mean the browser fully cancelled that
// navigation at the frame level, so firing a new goto() to the same URL
// immediately can collide with the very attempt that just "failed". This
// version instead waits for whatever's CURRENTLY in flight to actually
// finish (`waitForLoadState("load")`, swallowed if it also errors — we only
// care that things go quiet, not what specifically was loading) before
// trying again, and allows a few attempts rather than exactly one, since
// there's no guarantee a single retry lands cleanly.
//
// Deliberately does NOT wait for networkidle after a successful goto: this
// helper is also used by every "I am on" step, and createUser.feature's
// "Create a HRM user" (settings enable + form + submit, with 1–2 interrupt
// retries on the post-Save jump to user_add.php) burned the entire 60s
// test budget on networkidle waits alone — the form submitted fine and
// subsequent HRM scenarios all passed, but the final "I should not see an
// error" assertion never got a chance to run. page.goto()'s default
// waitUntil:"load" is enough for the next step's own locator auto-wait /
// explicit "wait for the page..." steps to take over; the value of this
// helper is the interrupt-retry, not a second settle strategy.
// Tracks the last navigation's HTTP response, for "the response status code
// should be ..." below — module-scoped like lastCreatedAttendanceId elsewhere
// in this file, since step definitions don't share a return value with each
// other otherwise.
let lastNavigationResponse: import("@playwright/test").Response | null = null

// 2026-08-06: real CI failure (courseCatalogue.feature's "Create three
// courses for catalogue testing") threw "page.goto: net::ERR_ABORTED; maybe
// frame was detached?" navigating to "/admin/course-list?keyword=..." right
// after "And I press submit" -> "And I wait for the page to be loaded" on
// course_add.php's legacy redirect-on-save flow — the SAME lingering-
// previous-navigation race documented above ("is interrupted by another
// navigation"), just surfacing as Playwright's OTHER wording for a frame
// whose in-flight navigation got superseded, not Playwright's dedicated
// "browser/context closed" crash message (that one is a real, different,
// separately-documented signature elsewhere in this suite — see toolGroup.
// feature/adminChamiloOrgBlock.feature). The previous string match only
// covered one of the two phrasings this same race can throw, so this exact
// class of failure fell through to an immediate, non-retried throw on
// attempt 1 instead of getting the same retry treatment. Broadened rather
// than added as a second `if`, since the retry body below applies
// identically to both messages.
function isRetryableNavigationRace(error: unknown): boolean {
  const message = String(error)
  return (
    message.includes("is interrupted by another navigation") ||
    message.includes("maybe frame was detached") ||
    // Third wording of the SAME race, and the one that actually broke
    // specialCase1Sessions.feature's first scenario on CI:
    //   page.goto: net::ERR_ABORTED at .../course_home/redirect.php?cidReq=TESTINGCOURSEFR
    // Playwright only phrases it as "is interrupted by another navigation" when
    // it can NAME the interrupting URL. A HISTORY TRAVERSAL cancels a pending
    // cross-document navigation without giving it a URL to name, so the same
    // collision surfaces as a bare net::ERR_ABORTED and fell straight through
    // to `throw` on attempt 1 — no retry at all, despite the step already
    // going through gotoReliably().
    //
    // The traversal is real and sits in product code, not the test:
    // assets/vue/views/ctoolintro/Update.vue's onSendForm() finishes with
    // router.go(-1), and it only runs AFTER its update request resolves. The
    // preceding Gherkin steps cannot see that coming — the click sub-action
    // returns in ~28ms and "wait for the page to be loaded"
    // (domcontentloaded) resolves in ~1ms because the SPA never left the
    // document — so the scenario moves on, fires this goto, and ~690ms later
    // the save resolves and yanks history out from under it. This is exactly
    // what gotoReliably exists to absorb, so it belongs in the retry set
    // rather than being special-cased at the one call site that hit it.
    //
    // Scoped deliberately to ERR_ABORTED and nothing broader: a genuinely
    // unreachable host or a real load failure reports ERR_CONNECTION_REFUSED /
    // ERR_NAME_NOT_RESOLVED / ERR_EMPTY_RESPONSE etc., none of which match
    // here, so a real breakage still fails fast instead of being retried 5x.
    message.includes("net::ERR_ABORTED")
  )
}

async function gotoReliably(page: Page, path: string, maxAttempts = 5) {
  for (let attempt = 1; attempt <= maxAttempts; attempt++) {
    try {
      lastNavigationResponse = await page.goto(path)
      return
    } catch (error) {
      if (!isRetryableNavigationRace(error) || attempt === maxAttempts) {
        throw error
      }
      // A real CI failure (toolLp.feature, under CI's slower/loaded response
      // times vs local) still exhausted the previous 3 attempts: the
      // interrupting navigation was still in flight, so waitForLoadState
      // resolved against it near-instantly and the very next goto() raced
      // it again. A short fixed pause between attempts (on top of the
      // existing wait) gives that in-flight navigation more real wall-clock
      // room to actually finish settling before retrying, without needing
      // this to be airtight — it only has to outlast the interrupting
      // navigation often enough that 5 attempts don't run out first.
      await page.waitForLoadState("load").catch(() => {})
      await page.waitForTimeout(1000)
    }
  }
}

async function loginAsAdminOnFreshPage(browser: import("@playwright/test").Browser, baseURL?: string) {
  const page = await (await browser.newContext({ baseURL })).newPage()
  await page.goto("/login")
  await page.locator("#login").fill("admin", { timeout: 10_000 })
  await page.locator("#password").fill("admin", { timeout: 10_000 })
  const signIn = page.locator("form.login-section__form button[type='submit']")
  if (await signIn.isVisible().catch(() => false)) {
    await signIn.click({ timeout: 15_000 }).catch(() => {})
  } else {
    await page
      .locator('button:has-text("Sign in"), input[type="submit"][value="Sign in"]')
      .first()
      .click({ timeout: 15_000 })
  }
  // Same settle rule as loginAs(): leave /login, no networkidle (see comment there).
  await expect(page.locator('a[href="/logout"]')).toBeVisible({ timeout: 25_000 })
  return page
}

// pressButton()'s tiers each need to decide "does THIS locator apply" before
// falling through to the next one — but a plain `.count()` is an instant,
// non-retrying snapshot of the CURRENT DOM. A real CI failure showed why
// that's fragile: career.feature's "Edit a career" calls this right after
// filling the TinyMCE editor (setContentFromEditor), and the trace showed
// EVERY tier's queryCount happening within ~60ms of that call, all reporting
// 0 matches — including `[name="submit"]:visible`, on a button that
// definitely exists and definitely has that name. TinyMCE's own content-
// driven reflow (resizing itself, shifting whatever's below it) can make a
// perfectly real, normally-visible element transiently not-visible for a
// brief moment right after its content changes — and since `.count()` never
// retries, a tier check landing in that exact window permanently commits to
// the WRONG fallback (here: text-matching "submit" against a button whose
// actual visible label is "Edit", which can never match). `waitForSelector`
// with a short, bounded timeout gives each tier a brief grace period to
// settle before being ruled out, without meaningfully slowing down the
// normal case where a tier genuinely doesn't apply (labels not shaped like
// an id, e.g. "Sign in", still skip the id/name tiers entirely and never pay
// this cost).
async function isSoonVisible(locator: ReturnType<Page["locator"]>, timeoutMs = 2000): Promise<boolean> {
  try {
    await locator.first().waitFor({ state: "visible", timeout: timeoutMs })
    return true
  } catch {
    return false
  }
}

async function failIfLoginPage(page: Page, action: string): Promise<void> {
  // The logged-out homepage IS the login page (heading "Sign in" plus a
  // "Sign up" link). registration.feature's "I follow Sign up" is a real
  // starting action there, not a lost session.
  if (/sign in|sign up|register|forgot|registration|password/i.test(action)) {
    return
  }
  const lostSession = await page
    .getByText(/session details have been lost/i)
    .isVisible()
    .catch(() => false)
  const onLogin = await page
    .getByRole("heading", { name: "Sign in", exact: true })
    .isVisible()
    .catch(() => false)
  if (lostSession || onLogin) {
    throw new Error(`Cannot ${action}: the session was lost and the login page is showing.`)
  }
}

async function dismissBlockingUi(page: Page): Promise<void> {
  const toastClose = page.locator(".p-toast-close-button:visible")
  if ((await toastClose.count()) > 0) {
    await toastClose.first().click({ timeout: 1_000 }).catch(() => {})
  }
  const cookieAccept = page.getByRole("button", { name: /^(Accept|Accepter)$/i })
  if (await cookieAccept.isVisible().catch(() => false)) {
    await cookieAccept.click({ timeout: 2_000 }).catch(() => {})
  }
}

async function clickFirstOrForce(locator: ReturnType<Page["locator"]>, page: Page): Promise<void> {
  const target = locator.first()
  const urlBeforeClick = page.url()
  try {
    await target.click({ timeout: 8_000 })
  } catch {
    // Before force-retrying, check whether the FIRST click already worked.
    //
    // Real CI failure (adminPlatformBlock.feature's "Open Reports catalog",
    // reported as `locator.click: Timeout 5000ms exceeded` — note 5000, i.e.
    // the force-retry below, not the 8s attempt above): the first click DID
    // land and did request the navigation, but /main/admin/reports_catalog.php
    // is a slow legacy page (measured 3.3-4.0s just to COMMIT on a warm box),
    // and Playwright's post-click signal barrier waits for that commit — so on
    // a loaded CI runner the 8s budget expires with the navigation still in
    // flight and click() throws even though nothing is actually wrong.
    //
    // `target` is a LOCATOR, so the retry re-resolves it against whatever
    // document is current by then — which is the destination page. That page's
    // own action bar carries a SELF-link with the identical accessible name
    // (Display::toolbarButton(get_lang('Reports catalog'), $baseUrl, ...) in
    // display.lib.php), so the retry force-clicked the destination's link to
    // itself, starting a SECOND ~4s load of the same page, which 5s could not
    // cover either. The test therefore failed on a navigation that had already
    // succeeded twice over. Confirmed NOT an overlay/intercept problem:
    // elementFromPoint at the link's centre returns the link itself.
    //
    // Waiting for the URL to change absorbs exactly that case. It is bounded,
    // and it only ADDS an early-success path: when the click genuinely did not
    // navigate, the URL never changes, the wait expires, and the original
    // dismiss + force-click behaviour runs unchanged.
    await page.waitForURL((url) => String(url) !== urlBeforeClick, { timeout: 5_000 }).catch(() => {})
    if (page.url() !== urlBeforeClick) {
      return
    }
    await dismissBlockingUi(page)
    await target.click({ force: true, timeout: 5_000 })
  }
}

async function syncTinymce(page: Page): Promise<void> {
  await page
    .evaluate(() => {
      const tinymce = (window as any).tinymce
      if (tinymce?.triggerSave) {
        tinymce.triggerSave()
      }
    })
    .catch(() => {})
}

async function pressButton(page: Page, label: string) {
  await failIfLoginPage(page, `press "${label}"`)
  await dismissBlockingUi(page)
  if ("Save" === label || "Save settings" === label) {
    await syncTinymce(page)
  }
  if (looksLikeIdentifier(label)) {
    // Same hidden-proxy-vs-visible-widget situation as resolveField() above
    // can apply to buttons too, hence :visible here as well.
    const byId = page.locator(`#${label}:visible`)
    if (await isSoonVisible(byId)) {
      await byId.first().click()
      return
    }
    const byName = page.locator(`[name="${label}"]:visible`)
    if (await isSoonVisible(byName)) {
      await byName.first().click()
      return
    }
  }
  // Prefer a visible PrimeVue dialog when one is open. Vue create/edit
  // dialogs often reuse the same accessible name as the toolbar button that
  // opened them (GradebookListView: "Add classroom activity" is both the
  // toolbar trigger AND the dialog submit). The toolbar button sits behind
  // the dialog mask and is not clickable; matching it first hangs the full
  // test timeout. Confirmed via toolAssessments.feature after the gradebook
  // Vue migration.
  //
  // Exclude TinyMCE's own role=dialog chrome (.tox-dialog / .tox-tbtn): a
  // real CI failure in toolPortfolio.feature's comment dialog showed
  // getByTitle("Save") resolving to TinyMCE's disabled toolbar Save button
  // (aria-disabled=true, title=Save) inside the PrimeVue dialog, then
  // click() retrying until the test timeout because that button is never
  // enabled. The real submit is a BaseButton, not a tox toolbar control.
  const openDialog = page.locator(".p-dialog:visible, [role='dialog']:visible:not(.tox-dialog)").last()
  if (await openDialog.count()) {
    const notToxOrDisabled =
      'xpath=self::*[not(@aria-disabled="true") and not(contains(@class,"tox-tbtn"))]'
    const dialogExact = openDialog.getByRole("button", { name: label, exact: true }).locator(notToxOrDisabled)
    if (await isSoonVisible(dialogExact, 1000)) {
      await dialogExact.first().click()
      return
    }
    const dialogTitle = openDialog.locator(`button[title="${label}"]:not(.tox-tbtn):not([aria-disabled="true"])`)
    if (await isSoonVisible(dialogTitle, 1000)) {
      await dialogTitle.first().click()
      return
    }
    const dialogText = openDialog
      .locator("button", { hasText: new RegExp(`^\\s*${escapeRegExp(label)}\\s*$`) })
      .locator(notToxOrDisabled)
    if (await isSoonVisible(dialogText, 1000)) {
      await dialogText.first().click()
      return
    }
  }
  // Exact match first — `:has-text()` (the final fallback below) is a
  // SUBSTRING match, and a real bug surfaced by class.feature showed why
  // that's not safe as the only option: UsergroupList.vue has both a
  // page-header "Add a class" button and, inside its create dialog, a plain
  // "Add" submit button — "Add" is a substring of "Add a class", so
  // `button:has-text("Add")` matched BOTH, and `.first()` (DOM order)
  // silently picked the header button, which sits BEHIND the dialog's
  // backdrop and can never actually be clicked while the dialog is open
  // (confirmed via a real Playwright actionability timeout: "the backdrop
  // div intercepts pointer events", retried for the full test timeout).
  // `getByRole("button", { name, exact: true })` matches on accessible name
  // exactly, so "Add" no longer matches "Add a class". Tried `:text-is()`
  // first — surprisingly returned 0 matches even for the correct element
  // (PrimeVue's Button renders the label inside a <span> flanked by Vue's
  // `<!---->` comment placeholders for its unused icon/badge slots, which
  // apparently trips up Playwright's text-content normalization here) —
  // getByRole is also the more standard tool for "does this look like a
  // button with this exact name" in the first place.
  // Real bug found porting toolAgenda/toolAttendance: any page with a
  // TinyMCE editor above the real submit button can ALSO match "Save" (or
  // whatever) here — TinyMCE's own toolbar has a "Save" plugin button
  // (aria-label="Save", disabled by default via aria-disabled="true"
  // unless the save plugin is actually wired up), which sits earlier in
  // DOM order than the form's real submit button. Worse: the form's own
  // real Save/Update button is often a PrimeVue icon+label Button, which
  // (per the getByRole/exact-match limitation documented above) frequently
  // DOESN'T match {exact: true} at all — so the disabled TinyMCE button
  // ends up the ONLY exact match, not just the first among several.
  // `.first()` on that one-and-only match silently grabbed it and retried
  // the click for the full test timeout ("element is not enabled"),
  // never falling through to a tier that could find the real button.
  // Filtering out aria-disabled matches (self-referential XPath, since
  // Playwright locators can't filter on the matched element's OWN
  // attributes any other way) BEFORE deciding whether anything usable
  // exists — not just as a tie-breaker when there happen to be several
  // matches — is what actually avoids ever preferring a disabled decoy.
  const exact = page.getByRole("button", { name: label, exact: true })
  const enabledExact = exact.locator(
    'xpath=self::*[not(@disabled) and not(@aria-disabled="true") and not(contains(@class,"tox-tbtn"))]',
  )
  if (await isSoonVisible(enabledExact)) {
    // Prefer a NON-TOGGLE match when several buttons share the accessible name.
    //
    // This app's persistent sidebar-collapse control is a PrimeVue
    // ToggleButton that renders its state as the literal text "Yes"
    // (`<button aria-pressed="true" class="p-togglebutton ... app-sidebar...">`)
    // — a pre-existing i18n bug in the app, out of scope to fix here, but it
    // means EVERY page has a permanently-present button whose accessible name
    // is "Yes". "I press 'Yes'" is this suite's standard way to accept a
    // PrimeVue ConfirmDialog, so the two collide, and the sidebar toggle wins
    // `.first()` because it sits far earlier in DOM order (measured y=97 vs
    // the dialog's y=397).
    //
    // Real CI failure this fixes (specialCase1Sessions.feature's "Teardown
    // special case 1 sessions"): the delete icon was clicked, the confirm
    // dialog opened, "I press 'Yes'" collapsed the SIDEBAR instead of
    // confirming, the dialog stayed open, the session was never deleted, and
    // the step's own `not.toContainText("Present session")` then failed 15s
    // later still showing the row. Verified live by dumping every element whose
    // trimmed text is exactly "Yes" before and after opening the dialog: one
    // before (the toggle, aria-pressed="true"), two after (the toggle plus the
    // dialog's `p-confirmdialog` accept button, aria-pressed absent).
    //
    // Written as a TIE-BREAKER rather than a blanket `not(@aria-pressed)`
    // filter: if a scenario ever legitimately presses a real toggle button by
    // its label, the non-toggle set is empty and the original locator is used
    // unchanged, so that keeps working. The sibling exact-text-content tier
    // further below already excludes toggles for this same reason; this closes
    // the same hole in the role tier, which is the one that actually matched
    // here.
    const notToggle = enabledExact.locator("xpath=self::*[not(@aria-pressed)]")
    const target = (await notToggle.count()) > 0 ? notToggle : enabledExact
    await target.first().click()
    return
  }
  const exactSubmit = page.locator(`input[type="submit"][value="${label}"]`)
  if (await isSoonVisible(exactSubmit)) {
    await exactSubmit.first().click()
    return
  }
  // Real bug found porting toolAgenda's Vue calendar-event form: it has
  // both a plain "Add" submit button and an "Add reminder" button — the
  // getByRole exact tier above matches NEITHER (PrimeVue's icon-left Button
  // layout breaks {exact: true} the same way already documented above for
  // "Save"/fm-button, confirmed here too via a real DOM check: no
  // aria-label, clean textContent "Add", yet exact accessible-name
  // matching still returns 0) — so it falls through to here, where the
  // final blind `:has-text()` fallback would be a SUBSTRING match and
  // could pick "Add reminder" instead (same class.feature "Add"/"Add a
  // class" trap, just with getByRole unable to help this time). Filtering
  // by the button's own exact (trimmed, whitespace-normalized) text content
  // — not its computed accessible name — sidesteps the icon issue entirely
  // and reliably disambiguates.
  //
  // Second real bug, found porting toolDocument.feature: the app shell's own
  // persistent sidebar-collapse toggle (present on every page, `class=
  // "app-sidebar__button"`) is a PrimeVue ToggleButton whose current-state
  // label happens to render as literally "Yes" (confirmed via a live DOM
  // check: `<span class="p-togglebutton-label">Yes</span>`, likely a
  // mistranslated i18n key on the toggle itself — a pre-existing app bug,
  // out of scope to fix here) — so a blind textExact match for "I press
  // 'Yes'" (a PrimeVue confirm dialog's real Yes/No buttons, used all over
  // this suite) can match that unrelated sidebar toggle instead, which
  // sits BEHIND the dialog's own backdrop and can never be clicked while
  // open. Every real dialog action button in this app is a plain, non-
  // toggling button (no `aria-pressed`), while every PrimeVue ToggleButton
  // (the only other thing that could coincidentally share a button's exact
  // label) always carries `aria-pressed` — filtering those out keeps this
  // tier safe without needing to special-case "Yes" by name.
  const textExact = page
    .locator("button", { hasText: new RegExp(`^\\s*${escapeRegExp(label)}\\s*$`) })
    .locator(
      'xpath=self::*[not(@aria-pressed) and not(@disabled) and not(@aria-disabled="true") and not(contains(@class,"tox-tbtn"))]',
    )
  if (await isSoonVisible(textExact)) {
    await textExact.first().click()
    return
  }
  // jqGrid's own native add/edit/delete confirmation dialogs (navGrid's
  // built-in forms — toolAttendance/toolAnnouncement's delete flow, likely
  // others) render their action buttons as `<a role="button" class="fm-
  // button">`, not a real <button>/<input type=submit> — confirmed via a
  // real DOM inspection of the "Delete selected record(s)?" dialog
  // (jqGrid's del options), which the CSS-only final fallback below can
  // never match (it only ever looks at button/input elements). The exact
  // getByRole tier above ALSO misses it: fm-button renders an icon glyph
  // ahead of the label text (class "fm-button-icon-left"), which becomes
  // part of the computed accessible name and breaks an {exact: true} match
  // even though a non-exact getByRole (or this CSS selector) matches fine.
  const submitExact = page
    .locator('button[type="submit"]:not([disabled]):not([aria-disabled="true"]):not(.tox-tbtn)')
    .filter({ hasText: new RegExp(`^\\s*${escapeRegExp(label)}\\s*$`) })
  if (await isSoonVisible(submitExact, 1000)) {
    await submitExact.first().click()
    return
  }
  const fmButton = page.locator(`a.fm-button:has-text("${label}")`)
  if (await isSoonVisible(fmButton)) {
    await fmButton.first().click()
    return
  }
  // Not ported — new, for toolDocument.feature's icon-only toolbar buttons
  // (DocumentsList.vue's "New folder"/"New document"/"Upload" etc. — plain
  // `<button title="...">` with an icon and NO text content or aria-label
  // at all). Confirmed via a real run that even the exact getByRole tier
  // above doesn't resolve these reliably, so this targets the `title`
  // attribute directly rather than relying on accessible-name computation.
  const byTitle = page.locator(`button[title="${label}"]:not(.tox-tbtn):not([aria-disabled="true"]), a[title="${label}"]`)
  if (await isSoonVisible(byTitle)) {
    await byTitle.first().click()
    return
  }
  // Last-resort, locale-independent fallback for legacy Admin/Settings
  // pages' "Save settings" button (src/CoreBundle/Resources/views/Admin/
  // Settings/actions.html.twig): a plain <button type="submit"> with no
  // id/name, whose translated {{ 'Save settings'|trans }} label every tier
  // above (and the final blind text fallback below) depends on — needed by
  // registerSettingsGuard()'s own restore step, confirmed live to hang
  // forever here whenever the admin session's interface language isn't
  // English (every text-based tier necessarily also fails in that case, so
  // this can only recover an otherwise-hung click, never mask a genuinely
  // wrong/missing button). The icon markup itself (mdi-content-save) is not
  // translated.
  const bySaveIcon = page.locator("button:has(.mdi-content-save)")
  if ("Save settings" === label && (await isSoonVisible(bySaveIcon))) {
    await bySaveIcon.first().click()
    return
  }
  // Exclude disabled/aria-disabled here too. A real hang in
  // toolExerciseAdmin.feature's "Try exercise": after answering, ExercisePlayerView
  // disables "Next question" while `isSavingAnswer` is true. Every exact/enabled
  // tier above correctly skips that button, then this last-resort `:has-text()`
  // matched it anyway and click() retried actionability until the full 90s test
  // timeout. The until-appears loop never got a second attempt.
  await page
    .locator(
      `button:has-text("${label}"):not([disabled]):not([aria-disabled="true"]):not(.tox-tbtn), input[type="submit"][value="${label}"]:not([disabled])`,
    )
    .first()
    .click()
}

When("I press {string}", async ({ page }, label: string) => {
  await pressButton(page, label)
})

// Ported from FeatureContext::iClickTheElement(): a plain CSS-selector click,
// first match (career.feature's row-action icons, e.g. "i.mdi-pencil").
//
// career.feature's delete/copy icons trigger a native `confirm()` (careers.php
// builds its jqGrid action links with a plain `onclick="if(!confirm(...))
// return false;"`, not a SweetAlert2 modal) and Playwright auto-dismisses any
// native dialog that has no handler attached *before* it fires — since the
// dialog opens synchronously inside this click, a handler registered by the
// separate later "I confirm the popup" step would always be too late. The
// only correct place to attach it is here, right before the click that may
// trigger one; `once` means it's a no-op for elements that don't.
// `:visible` here isn't a tie-breaker (contrast resolveField() above) — it's
// the wait condition itself. course.feature's "button.p-button-icon-only"
// also matches an always-present, permanently-hidden global modal close
// button (id="close-global-model") that happens to come first in the DOM on
// some pages. Without `:visible`, `.first()` locks onto that hidden button
// and Playwright's click retries for the full 60s waiting for IT to become
// visible (it never does) — even though the real, intended button (e.g. a
// course's "More actions" toggle) does render shortly after, just not
// instantly. Filtering the locator itself to `:visible` makes it dynamically
// re-resolve to whichever matching element is visible, so it naturally
// waits out the real button's render delay instead of being stuck on a
// decoy. Safe for career.feature's existing usage too — those icons are
// already visible the moment they're queried, so this changes nothing there.
Then("I click the {string} element", async ({ page }, selector: string) => {
  page.once("dialog", (dialog) => dialog.accept())
  await page.locator(`${selector}:visible`).first().click()
})

// Ported from FeatureContext::assertElementOnPage() as used via a raw CSS
// selector (Mink's "I should see the ... element" idiom) — specialCase1
// PlatformSettings.feature's own porting is the first user of this exact
// phrase in this suite. Auto-retrying (expect().toBeVisible()) rather than a
// one-shot count, since several of its callers assert this right after a
// setting save whose effect (e.g. a profile field newly becoming visible)
// only reflects once the next page has actually re-rendered.
Then("I should see the {string} element", async ({ page }, selector: string) => {
  await expect(page.locator(selector).first()).toBeVisible()
})

// The negative counterpart. Asserts on the first match rather than the count,
// so it reads as "this element is gone" for the callers that toggle a state and
// expect an icon or action to disappear without navigating.
//
// `.first()` is load-bearing, not cosmetic: without it a selector matching more
// than one element raises a strict-mode violation instead of asserting, which
// fails as a confusing harness error rather than a clean assertion.
Then("I should not see the {string} element", async ({ page }, selector: string) => {
  await expect(page.locator(selector).first()).toBeHidden()
})

// Ported from FeatureContext::iWaitForTheElementToAppear() — a bounded wait
// for a CSS selector to become visible, distinct from the id/name-based
// resolveField() cascade above (this is for arbitrary icons/markers, e.g.
// "i.mdi-chart-box"/"i.mdi-heart-plus", not form fields). Default Playwright
// expect() timeout (15s, see playwright.config.ts) applies.
When(/^(?:|I )wait for the element "([^"]*)" to appear$/, async ({ page }, selector: string) => {
  await expect(page.locator(selector).first()).toBeVisible()
})

// Ported from FeatureContext::iWaitUpToSecondsForTheElementToAppear() — same
// as above with an explicit, longer timeout for slower-to-render elements
// (e.g. TinyMCE's own ".tox-tinymce" toolbar, which only mounts after its
// JS bundle initializes).
When(
  /^(?:|I )wait up to (\d+) seconds for the element "([^"]*)" to appear$/,
  async ({ page }, seconds: string, selector: string) => {
    await expect(page.locator(selector).first()).toBeVisible({ timeout: Number(seconds) * 1000 })
  },
)

// Not ported — new, for toolAnnouncement.feature's bulk-delete flow
// (jqGrid's own "select all" header checkbox, `#cb_<gridName>`). A real CI
// run showed the previous approach — a blind "I click the ... element" on
// the header `<th>`, relying on jqGrid's own click delegation to toggle
// the checkbox inside it — occasionally not actually checking the row
// (cold jqGrid bootstrap timing, same general class of issue already
// documented for career.feature's jqGrid): the delete flow then showed a
// "Please, select row" warning dialog instead of the real delete
// confirmation, and everything downstream failed confusingly. This clicks
// the real checkbox input directly and asserts it's actually checked
// (auto-retrying) before returning, so a failed toggle fails LOUDLY right
// here instead of cascading into a wrong-dialog mess several steps later.
Then("I select all rows in the {string} grid", async ({ page }, gridName: string) => {
  // Even after that fix, a real CI run showed the SAME "Please, select row"
  // warning again — turns out clicking+verifying the header checkbox alone
  // isn't enough. jqGrid loads its row data via its own AJAX call, separate
  // from and after the initial page load; clicking the header checkbox
  // before that AJAX response has rendered any `<tbody>` rows toggles the
  // checkbox's own visual state (querying it in isolation, as the previous
  // fix did, sees exactly this and reports "success") but has nothing to
  // actually select — jqGrid's internal selected-row bookkeeping stays
  // empty regardless, since it never propagates to rows that didn't exist
  // yet at click time. Confirmed locally: with rows already loaded, every
  // row's own checkbox correctly ends up checked; the fix is to wait for at
  // least one real data row to exist FIRST (the actual "grid is ready"
  // signal), not just for the header checkbox's own state.
  await page.locator(`#${gridName} tbody input[type="checkbox"]`).first().waitFor({ state: "attached" })
  const checkbox = page.locator(`#cb_${gridName}`)
  await checkbox.click()
  await expect(checkbox).toBeChecked()
})

// Not ported — new, for pages with real pre-existing data alongside whatever
// a scenario creates (e.g. class.feature's /admin/usergroups: the shared dev
// box already has other classes). A blind `.first()` (as "I click the ...
// element" above does, safe for career.feature since careers.php had zero
// pre-existing rows) picks whatever row the table happens to sort first —
// confirmed the hard way: class.feature's "Delete a class" scenario deleted
// a real, unrelated, pre-existing "Another Class" row instead of its own,
// because that row sorted before it. Scoping the click to the table row
// that actually contains our own row's identifying text avoids this
// entirely — use this instead of "I click the ... element" on any page
// where other rows might already exist. Also registers the same native-
// dialog handler as "I click the ... element" (courseCategory.feature's
// delete icon triggers a native confirm(), same as career.feature's) — a
// no-op for class.feature's PrimeVue ConfirmDialog usage, which never fires
// a native dialog.
//
// Real CI failure: courseCatalogue.feature's cleanup scenario calls this
// step 3 times in a row (once per course) — `page.once` only removes a
// listener once ITS event actually fires, so when the underlying page never
// triggers a native dialog (PrimeVue-only, as above), all 3 registrations
// stay attached simultaneously. If a genuine native dialog does eventually
// fire once (e.g. a `beforeunload` prompt from navigating away), Playwright
// invokes every still-registered listener for it, not just one — the first
// to call `.accept()` succeeds and every other throws "Cannot accept dialog
// which is already handled!". The dialog IS handled correctly either way,
// so swallowing that specific race is safe.
Then("I click the {string} icon in the row for {string}", async ({ page }, selector: string, rowText: string) => {
  page.once("dialog", (dialog) => dialog.accept().catch(() => {}))
  // Prefer an exact cell/link match. A substring `hasText` match is how
  // toolExerciseAdmin.feature's "Teacher checks exercise results" opened
  // "Exercise 1 - Copy" (created by "Duplicate exercise") instead of
  // "Exercise 1" — Copy has no student attempt, so the report stayed on
  // "Attempts: 0" / "No attempts found".
  const exactRow = page.locator("tr").filter({ has: page.getByText(rowText, { exact: true }) })
  if (await isSoonVisible(exactRow.locator(selector), 2000)) {
    await exactRow.locator(selector).first().click()
    return
  }
  await page.locator("tr", { hasText: rowText }).locator(selector).first().click()
})

// Not ported — new, for toolGroup.feature's group_category action icons
// (edit/create-groups/delete). Unlike a group ROW (a real `<tr>`), a
// category's own header is a `<div class="page-header section-header">`
// (the title) with its action icons as SIBLING `<a>` elements, both inside
// a shared `<div class="p-toolbar-group-start p-toolbar-group-left">` —
// confirmed via a real DOM dump; the existing row-scoped step above can't
// find anything here since there's no enclosing `<tr>` at all.
Then(
  "I click the {string} icon in the group category header for {string}",
  async ({ page }, selector: string, categoryText: string) => {
    page.once("dialog", (dialog) => dialog.accept())
    await page
      .locator("div.p-toolbar-group-start.p-toolbar-group-left", { hasText: categoryText })
      .locator(selector)
      .first()
      .click()
  },
)

// Not ported — new, for toolLink.feature (and any other page laying its list
// out as `<div class="card">` items rather than a `<table>`). Same row-
// scoping intent as the step above, but matches the card by its own EXACT
// title text (not a substring "hasText") — link.php's card layout means
// two of this feature's own test items ("Chamilo" / "Chamilo in category
// 1", "Category 1" / "...category 1") are substrings of each other, so a
// plain substring match would ambiguously match more than one card.
Then(
  "I click the {string} icon in the card for {string}",
  async ({ page }, selector: string, cardText: string) => {
    page.once("dialog", (dialog) => dialog.accept())
    await page
      .locator(".card")
      .filter({ has: page.getByText(cardText, { exact: true }) })
      .locator(selector)
      .first()
      .click()
  },
)

// Not ported — new, for toolForum.feature's teardown. public/main/template/
// default/forum/list.html.twig (viewed directly) renders each forum as
// `<div class="card-forum">` (not `.card`, so the generic card step above
// can't find it) and each category as `<div class="category-forum">`.
// Scoping matters here for a genuine, reproduced-live reason, not just
// hygiene: "Forum Test"/"Forum Category Test" have no unique-name
// constraint, so a rerun of this file against a database that already has
// one (confirmed by simply running the file twice locally without
// reseeding) leaves TWO of each on this same page — and a category's own
// delete-category icon and a forum's own delete-forum icon both render via
// the exact same Display::getMdiIcon(ActionIcon::DELETE, ...) call, i.e. the
// identical `i.mdi-delete` class, as every OTHER category/forum's own delete
// icon. An unscoped "I click the 'i.mdi-delete' element" resolves to
// whichever renders FIRST in DOM order, which is not guaranteed to be the
// one this run itself just created — silently tearing down (or acting on)
// the wrong, stale duplicate instead.
Then(
  "I click the {string} icon for the forum {string}",
  async ({ page }, selector: string, forumTitle: string) => {
    page.once("dialog", (dialog) => dialog.accept())
    await page
      .locator(".card-forum")
      .filter({ has: page.getByText(forumTitle, { exact: true }) })
      .locator(selector)
      .first()
      .click()
  },
)

Then(
  "I click the {string} icon for the forum category {string}",
  async ({ page }, selector: string, categoryTitle: string) => {
    page.once("dialog", (dialog) => dialog.accept())
    await page
      .locator(".category-forum")
      .filter({ has: page.getByText(categoryTitle, { exact: true }) })
      .locator(selector)
      .first()
      .click()
  },
)

// Not ported — new, for toolNotebook.feature. PrimeVue's <Card> (used by
// NotebookListView.vue) renders `class="p-card ..."`, a single class token —
// not a substring match for ".card", so the step above can't find these at
// all (confirmed live). Also genuinely needed, not just a selector nuance:
// the shared dev box always has other real notebook entries alongside
// whatever this feature creates, so a blind "I click the ... element"
// .first() would silently act on an unrelated pre-existing note instead of
// this scenario's own one (same trap class.feature's own header comment
// documents). Scopes by the card's own `data-type="notebook"` marker
// (NotebookListView.vue sets this on every BaseCard) filtered to the one
// containing the given exact title text.
Then(
  "I click the {string} icon in the notebook entry for {string}",
  async ({ page }, selector: string, entryTitle: string) => {
    page.once("dialog", (dialog) => dialog.accept())
    await page
      .locator("[data-type='notebook']")
      .filter({ has: page.getByText(entryTitle, { exact: true }) })
      .locator(selector)
      .first()
      .click()
  },
)

// Not ported — new, for toolLp.feature's "Add document to LP" scenario. Real
// CI failure root-caused (not guessed): course TEMP's LP list is genuinely
// never empty on the shared dev box — toolGlossary.feature's own "Create
// Learning path named Glossary in course TEMP" scenario creates an LP titled
// "Glossary" on every run with no teardown, so it accumulates (confirmed via
// a direct DB query: 6 stray rows in c_lp titled "Glossary" from a single
// day's runs). "I follow 'Edit learnpath'" resolves via the shared step's
// a[title=]:visible tier and clicks the FIRST such icon in DOM order, which
// isn't guaranteed to be the LP this scenario just created — confirmed live
// the new document ends up attached to whichever LP happened to sort first
// (a pre-existing "Glossary" one), not "LP 1", leaving "LP 1" itself with
// zero items. That is the actual root cause of a real "Enter LP" scenario
// failure downstream (its runtime view for "LP 1" has nothing to show,
// because "Document 1" was never added to it). Same fix shape as the
// card/notebook-entry steps above: scope the click to the LP's own panel
// (`.lp-panel`, LpRowItem.vue), matched by its exact title text, so this
// scenario is correct regardless of how many other LPs already exist.
Then(
  "I click the {string} icon in the LP panel for {string}",
  async ({ page }, selector: string, lpTitle: string) => {
    await page
      .locator(".lp-panel")
      .filter({ has: page.getByText(lpTitle, { exact: true }) })
      .locator(selector)
      .first()
      .click()
  },
)

// Not ported — new, for toolLp.feature's "Delete a LP category" scenario.
// Same ambiguity class as the LP-panel step above, applied to categories:
// "i.mdi-dots-vertical" unscoped would hit the FIRST category's menu icon in
// DOM order, not necessarily "LP category 1"'s own one, if the shared dev
// box ever has more than one LP category in course TEMP. LpCategorySection.vue
// renders each category as its own `<header>` containing both the category
// title (`<h2>`) and its "More actions" dots-vertical icon, so scoping to the
// header containing the exact category title is collision-proof the same way.
Then(
  "I click the {string} icon in the LP category header for {string}",
  async ({ page }, selector: string, categoryTitle: string) => {
    await page
      .locator("header")
      .filter({ has: page.getByText(categoryTitle, { exact: true }) })
      .locator(selector)
      .first()
      .click()
  },
)

// Not ported — new, for toolGlossary.feature's "Create Learning path named
// Glossary in course TEMP" scenario. Without this, the LP created here was
// never cleaned up (confirmed live: 6 stray "Glossary" LPs had accumulated
// in c_lp from repeated runs before this fix), which is what broke
// toolLp.feature's own "Add document to LP" scenario downstream — see the
// step above for the full chain. Deletes by the LP's own numeric id
// (extracted from the current URL, `/resources/lp/<node>/<iid>/...`, right
// after "Continue" lands on the builder), not by title: titles aren't
// unique here (this suite's own convention allows creating a second
// "Glossary" LP fine), so a title-based delete could hit the WRONG one.
// `.lp-panel[data-lp-id="..."]` (confirmed live via a real DOM dump) is
// exact and collision-proof regardless of how many same-titled LPs exist.
// Each panel actually renders TWO "More actions" buttons (LpRowItem.vue's
// desktop button plus a `.lp-panel__mobile-dropdown` duplicate for narrow
// viewports) — `:visible` picks whichever one is actually shown at the
// current viewport, confirmed live (a plain, unfiltered locator throws a
// strict-mode "resolved to 2 elements" error).
Then("I delete the learning path I just created", async ({ page }) => {
  const match = page.url().match(/\/resources\/lp\/\d+\/(\d+)\//)
  if (!match) {
    throw new Error(`Could not find a learning path id in the current URL: ${page.url()}`)
  }
  const lpId = match[1]
  await gotoReliably(page, "/main/course_home/redirect.php?cidReq=TEMP")
  await page.getByRole("link", { name: "Learning paths", exact: true }).first().click()
  // Real CI failure: an unbounded `waitForLoadState("networkidle")` here can hang for the
  // whole test timeout if this app's own persistent background polling never lets
  // networkidle resolve — the same class of hang already documented (and fixed) for
  // toolGroup.feature's "wait for the page to be loaded when ready" step. Bounded +
  // tolerant of a timeout, same pattern as "I wait for the page content to settle".
  await page.waitForLoadState("networkidle", { timeout: 10_000 }).catch(() => {})
  const panel = page.locator(`.lp-panel[data-lp-id="${lpId}"]`)
  await panel.locator('button[title="More actions"]:visible').first().click()
  await page.getByText("Delete", { exact: true }).click()
  await page
    .locator(".p-confirmdialog, [role='alertdialog']")
    .getByRole("button", { name: /Yes|Delete/i })
    .first()
    .click()
  await expect(panel).toHaveCount(0)
})

// Not ported — new, for toolDocument.feature's own cleanup scenarios
// specifically (deleting several test-created documents back to back). A
// real CI run (twice, with a different document missing each time) showed
// a row genuinely absent by the time its own delete step ran, even though
// nothing in THIS feature's own scenarios removes it early — most likely
// cross-file worker interference on the same shared "TEMP" course's
// Documents tool (e.g. course.feature's own "Make sure the documents tool
// is available" check runs concurrently, in a different worker, against
// the exact same course). Since these particular steps exist purely to
// clean up this feature's own test data (the actual delete FLOW is already
// covered by "Search for ... and delete it" above), skip gracefully
// instead of hanging for the full test timeout when the row is already
// gone for any reason — the end state ("row X no longer present") is the
// same either way.
Then("I delete the document {string} if present", async ({ page }, rowText: string) => {
  const row = page.locator("tr", { hasText: rowText })
  if ((await row.count()) === 0) {
    return
  }
  await row.getByTitle("Delete").first().click()
  await pressButton(page, "Yes")
  await expect(page.locator("body")).not.toContainText(rowText)
})

// Not ported — new, for specialCase1PlatformSettings.feature's "Tear down"
// scenario's own cleanup of "Order By Id Test Session" (created by "Add
// minimal session extra fields", the scenario right before this one in the
// same file). That scenario has a real, unresolved, real-CI-only "browser
// has been closed" crash (2026-08-06, never reproduced locally — see its own
// header comment) but can't be @skip'd: specialCase1Sessions.feature hard-
// depends on the session extra fields it creates. If it ever crashes BEFORE
// actually creating "Order By Id Test Session", Tear down's own cleanup step
// used to fail outright trying to click a delete icon in a row that was
// never created, compounding one real-CI crash into two reported failures.
// Reuses the exact "if present" guard convention from "I delete the
// document ... if present" above so Tear down keeps working either way:
// still cleans up a stray session left behind by an earlier partial/crashed
// run, but no longer fails when there isn't one.
Then("I delete the session {string} if present", async ({ page }, sessionName: string) => {
  const row = page.locator("tr").filter({ has: page.getByText(sessionName, { exact: true }) })
  // Bounded wait before the count() below decides "absent". /admin/session-list
  // is a Vue page whose table body arrives from its own async data request,
  // well after the "I wait for the page to be loaded" (domcontentloaded) step
  // that normally precedes this one — so a bare, instantaneous count() can read
  // 0 for a row that renders a moment later and silently skip a session that
  // really does exist. Confirmed exactly that: a teardown run reported PASS
  // while leaving "Past session" behind, even though loading the same URL by
  // hand rendered the row (with its delete icon) within ~3s.
  // Swallowing the rejection is the whole point — a genuinely absent session
  // must still fall through to the early return below rather than throw, which
  // is this step's entire reason for existing. Strictly more patient than the
  // previous bare count(), so it cannot regress a case that already worked;
  // same reasoning as isSoonVisible() in pressButton().
  await row
    .first()
    .waitFor({ state: "visible", timeout: 5000 })
    .catch(() => {})
  if ((await row.count()) === 0) {
    return
  }
  // Two attempts, each: click the row's delete control, WAIT FOR THE DIALOG
  // ITSELF, then click that dialog's accept button, then check the row really
  // went. Retrying is what makes this robust — the previous version had no way
  // to notice that its own confirm click had missed.
  //
  // Real CI + locally-reproduced failure ("Teardown special case 1 sessions"):
  // the dialog ended up CLOSED with the session still listed, i.e. something
  // dismissed the dialog without confirming. Two distinct traps combine here:
  //
  // 1. Resolving the accept button THROUGH a `.last()` of a currently-empty
  //    set. The old code built `.p-confirmdialog:visible, .p-dialog:visible`
  //    then `.last()` then `.getByRole(...)` and waited on that chain, at a
  //    moment when the dialog had not been inserted yet (it animates in behind
  //    `p-overlay-mask-enter-active`). Waiting for the DIALOG first, and only
  //    then querying inside it, removes that whole class of problem.
  // 2. Falling back to `pressButton(page, "Yes")`. Every page in this app
  //    carries a permanently-visible sidebar-collapse ToggleButton whose label
  //    renders as the literal text "Yes" (verified live: one element with text
  //    exactly "Yes" before the dialog opens, two after). Clicking it collapses
  //    the sidebar, and that re-render DISMISSES the confirm dialog — which is
  //    precisely the observed end state (no dialog, row intact). pressButton
  //    now prefers non-toggle matches, but this step no longer needs that
  //    fallback at all: it scopes strictly to the dialog it just opened.
  //
  // Verified live that this exact sequence works and that the delete itself was
  // never the problem: clicking the dialog's own Yes fires
  // POST /admin/session-list-data-action -> 200 and the row disappears
  // immediately (dialog text is "Confirmation / Please confirm your choice /
  // Cancel / Yes"), reproduced on several different sessions.
  const deleteControl = row.locator(
    "button[title='Delete'], button[title='Supprimer'], a[title='Delete'], a[title='Supprimer'], .mdi-delete",
  )
  const dialog = page.locator(".p-confirmdialog, [role='alertdialog']")

  for (let attempt = 1; attempt <= 2; attempt++) {
    page.once("dialog", (nativeDialog) => nativeDialog.accept().catch(() => {}))
    await deleteControl.first().click()

    await dialog
      .first()
      .waitFor({ state: "visible", timeout: 10_000 })
      .catch(() => {})
    const accept = dialog.getByRole("button", { name: /^(Yes|Oui)$/i })
    if (await isSoonVisible(accept, 5_000)) {
      await accept.first().click()
    }
    // The table refetches after the delete, so give the row a real chance to
    // disappear before judging the attempt.
    await row
      .first()
      .waitFor({ state: "detached", timeout: 10_000 })
      .catch(() => {})
    if ((await row.count()) === 0) {
      break
    }
  }

  await expect(page.locator(".p-confirmdialog:visible")).toHaveCount(0)
  await expect(page.locator("body")).not.toContainText(sessionName)
})

// Mink's built-in "I follow" (MinkContext::iClickLink(), not custom
// FeatureContext code) clicks a link resolved by id, then title attribute,
// then visible text/label (e.g. an image link's alt text) — course.feature
// uses plain visible text throughout ("Course description", "Documents",
// etc.), so the text tier is what actually matters here, but the id/title
// tiers are kept for parity with Mink's own resolution order.
// Not ported — new, for toolAssessments.feature's subscribe scenario. The
// subscribe_user.php picker only lists users NOT already in the course, so
// "Then I should see Noa" / "I follow Register" hard-fails on a reused box
// where a previous run of this file already subscribed norizales (or a
// later scenario's teardown didn't run). Clicking Register when it's there,
// and treating its absence as "already subscribed", is the same outcome the
// rest of the file needs.
Then("I follow {string} if it is visible", async ({ page }, link: string) => {
  const candidates = [
    page.getByRole("link", { name: link, exact: true }),
    page.getByRole("button", { name: link, exact: true }),
    page.locator(`a:has-text("${link}")`),
  ]
  for (const locator of candidates) {
    if (await isSoonVisible(locator, 8000)) {
      await locator.first().click()
      return
    }
  }
})

When("I follow {string}", async ({ page }, link: string) => {
  await failIfLoginPage(page, `follow "${link}"`)
  await dismissBlockingUi(page)
  if (looksLikeIdentifier(link)) {
    const byId = page.locator(`#${link}:visible`)
    if (await byId.count()) {
      await byId.first().click()
      return
    }
  }
  // Real CI failure: questionPool.feature's course-tool toolbars (Vue
  // "Tests"/"Exercises" views) render their icon-only links a moment after
  // the page itself settles — not necessarily gated by any network request
  // Playwright can observe (client-side reactive state), so even a
  // preceding bounded "wait for the page content to settle" step doesn't
  // reliably cover it. This tier's own instant, non-retrying `.count()`
  // check could lose that race and fall through to the roleLink/exact-text
  // tiers below for a link that WOULD have matched a moment later — the
  // same brief-render-delay tolerance the roleLink tier right below
  // already gets via isSoonVisible(), just not applied here until now.
  //
  // Real CI failure (2nd occurrence, toolExerciseAdmin.feature's "Import
  // exercise to test questions categories"): the same toolbar's whole
  // container is gated behind ExerciseListView.vue's `canManage` ref, which
  // starts `false` and only flips after an async fetch resolves — confirmed
  // by reading the component directly. On a loaded/slow CI runner (this
  // failure's own run took 1.8h vs a prior run's 1.4h) that fetch can
  // outlast this tier's 2s isSoonVisible() window, so both this tier AND
  // roleLink below lose the race and execution falls through to the final
  // exact-text tier — which can NEVER match an icon-only button (no text
  // node, only a `title` attribute), turning a transient render delay into
  // a hard 90s timeout instead of a slightly slower click. Widened the
  // window on both of these two tiers to give that async gate real headroom
  // without weakening the final tier's semantics for links that genuinely
  // don't exist.
  const byTitle = page.locator(`a[title="${link}"]:visible`)
  if (await isSoonVisible(byTitle, 6000)) {
    await byTitle.first().click()
    return
  }
  const roleLinkExact = page.getByRole("link", { name: link, exact: true })
  if (await isSoonVisible(roleLinkExact, 6000)) {
    await clickFirstOrForce(roleLinkExact, page)
    return
  }
  // Non-exact last among role matches: "Exercise 1" would otherwise also
  // match "Exercise 1 - Copy" and click() the first of two, which is usually
  // the intended row — keep it only when an exact accessible name is absent.
  const roleLink = page.getByRole("link", { name: link })
  if (await isSoonVisible(roleLink, 3000)) {
    await clickFirstOrForce(roleLink, page)
    return
  }
  // Not in the original Mink cascade — new. toolWork.feature's Vue
  // assignment list renders its row title as a plain `<a>` with NO `href`
  // attribute at all (confirmed via a real DOM dump) — without an href, it
  // has no implicit "link" role, so getByRole("link") never matches it.
  // Falls back to a plain exact-text match, which works regardless of the
  // element's actual role/tag. Visible-only: a hidden sidebar label with the
  // same text sorts first in DOM order and is never actionable.
  await page.getByText(link, { exact: true }).filter({ visible: true }).first().click()
})

// Not ported — new, for toolUsers.feature. Real CI failure on a fresh,
// cold-cache install: "I follow 'Users'" on a course homepage fell all the
// way through to the plain exact-text tier above and clicked the
// Administration SIDEBAR's own "Users" PanelMenu entry instead of the
// course tool's own "Users" link — confirmed live both exist simultaneously
// on the same page (`getByText("Users", { exact: true })` matches 2:
// the sidebar's `<span class="p-panelmenu-item-label">` inside a collapsed
// submenu, genuinely never visible, and the course-tools list's own
// `<a class="course-tool__title">`, visible). The sidebar span sorts first
// in DOM order, so a bare `.first().click()` hangs the whole test timeout
// waiting for an element that will never become visible on its own.
// The byTitle/roleLink tiers above don't save this case either: the
// sidebar item is an `<a>` with no `href` (so it doesn't qualify for
// getByRole("link")) and neither element has a `title` attribute — and on
// a slow/cold box the course-tools list's own client-side render (a
// separate async fetch) can still lose the race against those two tiers'
// combined timeout budget, which is exactly what made this reproduce in CI
// but not on a warmed-up local dev box. Scoping directly to the
// course-tools list's own link class sidesteps the ambiguity entirely
// instead of racing it.
When("I follow the course tool {string}", async ({ page }, toolName: string) => {
  await page
    .locator("a.course-tool__title")
    .filter({ hasText: new RegExp(`^\\s*${escapeRegExp(toolName)}\\s*$`) })
    .first()
    .click()
})

// Ported from FeatureContext::confirmPopup(). Native `confirm()` dialogs are
// already handled by the listener "I click the ... element" attaches above —
// by the time this step runs any such dialog is already gone, so this is a
// no-op for career.feature's own scenarios. Kept as a real fallback (not
// deleted) for the SweetAlert2 (.swal2-container) case Behat's original step
// also handled, for whichever future ported feature uses that instead.
When(/^(?:|I )confirm the popup$/, async ({ page }) => {
  const modal = page.locator(".swal2-container")
  if (await modal.count()) {
    await modal.locator(".swal2-confirm").click()
  }
})

// Ported from FeatureContext::iHaveAFriend(). Establishes a real, accepted
// friend relationship between the fixed admin account and an arbitrary user,
// entirely via the same two legacy AJAX endpoints the original Behat step
// shelled out to (GET requests, matching Mink's plain visit() semantics) —
// admin sends the invitation, then the target user's own session accepts it.
// Ends logged back in as admin, matching the original's own final
// iAmAPlatformAdministrator() call, since the next Gherkin step generally
// assumes an admin session is still current.
//
// Unlike the original PHP (which re-logs-in as admin as its own first action,
// redundantly — both socialGroup.feature scenarios using this step already
// have "Given I am a platform administrator" immediately before it), this
// does NOT repeat that login: the Vue /login route redirects straight to the
// dashboard when a session cookie is already active (confirmed directly —
// navigating there mid-admin-session lands on the Home page, never rendering
// #login), so a redundant loginAs("admin") right after one that already
// happened hangs forever waiting for a login form that will never appear.
// Mink's Selenium session apparently tolerated the original's redundant call
// (or a subtly different /login behavior in the legacy stack did); this
// doesn't, so the fix is to simply not repeat it.
// friendId used to be a hardcoded literal passed in from the Gherkin step
// ("with id \"11\"", later "with id \"10\""), assuming a fixed seed order —
// disproven TWICE by real CI runs (fbaggins landed at different ids across
// runs depending on exactly what else seeded first). Looked up dynamically
// instead via message.ajax.php's own working "find_users" action (the same
// endpoint the real "New message" recipient search box uses), which resolves
// a username to its real id regardless of what that id happens to be this run.
let lastFriendUserId: string | null = null

Given("I have a friend named {string}", async ({ page }, friendUsername: string) => {
  const searchResponse = await page.request.get(
    `/main/inc/ajax/message.ajax.php?a=find_users&q=${encodeURIComponent(friendUsername)}&page_limit=10`,
  )
  const { items } = await searchResponse.json()
  const match = items?.[0]
  if (!match) {
    throw new Error(`find_users returned no match for username "${friendUsername}"`)
  }
  const friendId = String(match.id)
  lastFriendUserId = friendId
  // Resolve admin's id WHILE still authenticated as admin. /api/users is
  // not listable as a student (fbaggins), which is who we switch to next
  // to accept the invitation — a first version of this lookup ran after
  // loginAs(friendUsername) and got an empty hydra:member.
  const adminLookup = await page.request.get("/api/users?username=admin", {
    headers: { Accept: "application/ld+json" },
  })
  const { "hydra:member": adminMembers } = await adminLookup.json()
  const adminId = adminMembers?.[0]?.id
  if (!adminId) {
    throw new Error("Could not resolve the admin user's id")
  }
  await page.goto(
    `/main/inc/ajax/message.ajax.php?a=send_invitation&user_id=${encodeURIComponent(friendId)}&content=${encodeURIComponent("Add me")}`,
  )
  // An explicit /logout before switching to friendUsername is needed for the
  // same reason noted above — this page already carries the admin cookie
  // from the Gherkin's preceding "Given I am a platform administrator".
  await page.goto("/logout")
  await loginAs(page, friendUsername)
  // Ported from FeatureContext, this originally called
  // social.ajax.php?a=add_friend — but that action doesn't exist in
  // social.ajax.php at all (confirmed via a full-repo grep: no case handles
  // it, nothing else in the codebase calls it either — it silently falls
  // through to social.ajax.php's own `default:` case and no-ops, always
  // returning 200 without ever creating a friend relation). The real,
  // currently-working implementation is SocialController::user()'s
  // 'add_friend' case (the same modern /social-network/user-action endpoint
  // the Vue "Add friend" UI itself posts to) — uses page.request.post() (not
  // page.goto(), since this needs a JSON body) so friendUsername's own
  // session cookie is used, matching "the target user's own session
  // accepts it".
  //
  // Real CI failure (socialGroup.feature "Invite a friend to group"): the
  // first version of this post used Playwright's default form encoding
  // (`data: { ... }` without a JSON Content-Type). SocialController::user()
  // reads json_decode($request->getContent()), so the body never parsed,
  // add_friend never ran, and group_invitation.php rendered "You already
  // invite all your contacts" with an empty dual-list (admin had leftover
  // non-friend relations from other fixtures, but fbaggins was not among
  // them). The Vue UI itself sends application/json; match that, and fail
  // loud if the endpoint doesn't accept the friendship.
  // Use in-page fetch (not page.request.post): CsrfProtectionListener
  // requires Origin/Referer/Sec-Fetch-Site from a same-origin browser
  // request. Playwright's APIRequestContext does not send those, so the
  // first version of this post returned 200/4xx without creating the
  // user_rel_user row — group_invitation.php then showed "You already
  // invite all your contacts" with an empty dual-list.
  const addFriendResult = await page.evaluate(async (targetUserId: number) => {
    const response = await fetch("/social-network/user-action", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ targetUserId, action: "add_friend", is_my_friend: true }),
    })
    return { status: response.status, body: await response.text() }
  }, Number(adminId))
  if (addFriendResult.status >= 400 || !addFriendResult.body.includes("success")) {
    throw new Error(`add_friend failed with ${addFriendResult.status}: ${addFriendResult.body}`)
  }
  await page.goto("/logout")
  await loginAs(page, "admin")
})

// Not ported — new. socialGroup.feature originally hardcoded the just-created
// group's id (assuming it's always "1", the first-ever usergroup row) — a
// real CI run disproved that: usergroup rows are shared with class.feature's
// classes (different group_type, same table/id sequence, different worker,
// no ordering guarantee), so another file can legitimately win id 1. Reads
// the real id off group_add.php's own success redirect instead
// (`social/group_view.php?id=<id>`, confirmed in its source) — module-level,
// not page-scoped, matching this file's existing settingsPage/
// settingsSnapshot pattern for state shared across scenarios in the same
// worker (fullyParallel:false keeps a file's scenarios sequential in one
// worker, so this is safe).
let lastCreatedGroupId: string | null = null

Then("I remember the created group id", async ({ page }) => {
  // Success path: group_add.php redirects to group_view.php?id=<id>.
  // Duplicate-title path: UserGroupModel::save() returns false when
  // usergroup_exists(title), and group_add.php then api_location()'s back
  // to itself — no id in the URL. That happens on a reused box that already
  // has "Behat Test Group" from a previous run of this file. Look the
  // existing group up from the groups list instead of failing; CI's fresh
  // install still takes the success-redirect path.
  const fromUrl = page.url().match(/[?&]id=(\d+)/)
  if (fromUrl) {
    lastCreatedGroupId = fromUrl[1]
    return
  }
  await gotoReliably(page, "/main/social/groups.php")
  const myGroupsTab = page.getByRole("tab", { name: "My groups" })
  if (await myGroupsTab.count()) {
    await myGroupsTab.click()
  }
  // groups.php cards put the group title in an image overlay, not in the
  // <a> text (the link's accessible name is empty; confirmed via a real
  // snapshot: heading+link to group_view.php?id=2 with no text). Match by
  // href, scoped to this user's groups tab.
  const link = page.locator('a[href*="group_view.php?id="]').first()
  // The card's title is an image overlay; the <a href="group_view.php?id=N">
  // is present but CSS-hidden (Playwright: "34 × locator resolved to hidden").
  // We only need the id from the href, not a click.
  await link.waitFor({ state: "attached", timeout: 15_000 })
  const href = (await link.getAttribute("href")) || ""
  const fromList = href.match(/[?&]id=(\d+)/)
  if (!fromList) {
    throw new Error(`Could not find a group id in the current URL (${page.url()}) or on the groups list`)
  }
  lastCreatedGroupId = fromList[1]
})

// Ported from FeatureContext::iInviteAFriendToASocialGroup(), but the
// original's own assumption about this field was wrong (confirmed by
// actually reading the rendered HTML, not just the PHP source):
// FormValidator::addMultiSelect('invitation', ...) does NOT render a single
// plain <select multiple name="invitation[]"> — it renders a dual-listbox
// widget (jQuery "multiselect" plugin, initialized by an inline <script>):
// a LEFT <select id="invitation" name="invitation-f[]"> holding every
// available friend as a real <option>, and a RIGHT <select id="invitation_to"
// name="invitation[]"> — the one actually submitted — which starts EMPTY and
// only gets populated when the widget's own JS moves an option over (via the
// #invitation_rightSelected button, or a double-click). Directly calling
// selectOption() on name="invitation[]" (the right side) can never work: it
// has no options to select until this step runs. Mink's original fillField()
// on the same target likely had the identical problem — plausibly another
// case (like the DataTable .rows() bug elsewhere in this suite) where the
// original Behat step never actually worked either, just never got caught.
// Fixed by moving the friend's real <option> node from the left list to the
// right one directly (same "shell out to the DOM" approach already used
// elsewhere in this file for other JS-widget quirks, e.g. the Select2 steps
// above) rather than relying on the multiselect plugin's own click handler —
// clicking #invitation_rightSelected directly did not reliably move the
// option in practice (its init script may not always be attached in time);
// moving the actual <option> element and firing 'change' produces the exact
// same DOM state the plugin's own handler would, which is all the
// subsequent form submit cares about.
When("I invite the friend to the social group I just created", async ({ page }) => {
  if (!lastCreatedGroupId) {
    throw new Error("No group id remembered — run \"I remember the created group id\" right after creating it first.")
  }
  if (!lastFriendUserId) {
    throw new Error("No friend id remembered — run \"I have a friend named ...\" first.")
  }
  await page.goto(`/main/social/group_invitation.php?id=${encodeURIComponent(lastCreatedGroupId)}`)
  const alreadyInvited = page.getByText("Users already invited")
  try {
    await page.waitForSelector("#invitation option", { timeout: 15_000 })
  } catch {
    // Reused box: this group already has the friend pending, so the
    // available list is empty and the "Users already invited" panel is
    // the durable success signal the scenario asserts next.
    if (await alreadyInvited.first().isVisible().catch(() => false)) {
      return
    }
    const bodyText = ((await page.locator("body").textContent()) || "").replace(/\s+/g, " ").trim()
    throw new Error(
      `Available-friends list never populated for group ${lastCreatedGroupId} / friend ${lastFriendUserId}. Page text: ${bodyText.slice(0, 500)}`,
    )
  }
  await page.evaluate((value) => {
    const left = document.querySelector("#invitation") as HTMLSelectElement
    const right = document.querySelector("#invitation_to") as HTMLSelectElement
    const option = Array.from(left.options).find((o) => o.value === value)
    if (!option) {
      throw new Error(`No option with value "${value}" found in the available-friends list`)
    }
    option.selected = true
    right.appendChild(option)
    right.dispatchEvent(new Event("change", { bubbles: true }))
  }, lastFriendUserId)
  await pressButton(page, "submit")
})

Then("I should see {string}", async ({ page }, text: string) => {
  // `.filter({ visible: true })` before `.first()`, so this picks the first
  // VISIBLE match rather than the first match in DOM order.
  //
  // getByText() matches by case-insensitive SUBSTRING, which makes short or
  // common assertion strings collide with unrelated content — and if the
  // colliding element happens to be hidden, the assertion fails with a
  // baffling `unexpected value "hidden"` about an element nobody was looking
  // for. Real instance: admin/fileIntegrity.feature asserted "Actions" (a
  // column header) and resolved instead to a hidden <li> in a collapsed
  // file-report list, because
  // "tests/CoreBundle/.../TrainingSatisfactionSurveyCreatorTest.php" contains
  // "...Satisfaction'S'urvey..." -> "actionsurvey" -> "actions".
  //
  // Substring matching is deliberately KEPT rather than switched to
  // { exact: true }: most callers assert a name or phrase nested inside a
  // larger element (a table row, a toast, a heading), so exact matching would
  // break a great many currently-correct assertions. Filtering to visible
  // fixes the actual failure mode — picking an element the user cannot see —
  // without changing what counts as a match. Strictly more permissive than
  // before for passing cases; it can only change the outcome where the old
  // code would have chosen a hidden element and failed.
  //
  // Note this does NOT rescue a genuinely ambiguous VISIBLE match (the
  // "TEMP" matching the word "template" trap documented in course.feature).
  // For short strings, prefer asserting something distinctive.
  await expect(page.getByText(text).filter({ visible: true }).first()).toBeVisible()
})

Then("I should see the current access URL", async ({ page }) => {
  await expect(page.getByText(currentSiteAccessUrl()).first()).toBeVisible()
})

// Not ported — new. The installer always inserts access_url.id=1 as
// http://localhost/ (see actionInstall.feature browsing CI's localhost
// vhost). The admin UI at /admin/urls/manage is how that row gets changed
// to the site's real public URL afterwards. adminMultiUrls.feature asserts
// the displayed URL, so this has to run first in that file (and is
// idempotent if the row is already correct).
When("I set the default access URL to the current site URL", async ({ page }) => {
  const target = currentSiteAccessUrl()
  await gotoReliably(page, "/admin/urls/manage")
  await expect(page.getByText("Multiple access URL").first()).toBeVisible()
  await pressButton(page, "Edit")
  await fillReliably(await resolveField(page, "access-url-url"), target)
  await pressButton(page, "Save")
  await expect(page.getByText(target).first()).toBeVisible()
})

// URL assertions — used when a success path is a redirect (e.g. extra_fields.php
// leaves action=add only on validation failure; on save it Location-redirects
// to the list). More reliable than flash toasts for legacy header()+exit flows
// where the Symfony flash bag does not always survive into the next request's
// #app[data-flashes] payload. Use expect().toHaveURL (auto-retrying), never a
// one-shot page.url() read — form submit is POST-redirect-GET and a plain
// url() snapshot can still see the pre-redirect address.
Then("the URL should contain {string}", async ({ page }, part: string) => {
  await expect(page).toHaveURL(new RegExp(part.replace(/[.*+?^${}()|[\]\\]/g, "\\$&")))
})

Then("the URL should not contain {string}", async ({ page }, part: string) => {
  // Negative URL match: poll until the forbidden substring is gone (or timeout).
  await expect
    .poll(() => page.url(), { timeout: 15_000 })
    .not.toContain(part)
})

// Pathname-only deny check. Needed because a Vue-router access deny can land
// on /login?redirect=/admin/system-status... — the full URL still CONTAINS
// that path as a query value, so "the URL should not contain" would false-
// fail a genuine redirect-to-login. Polls until the Vue guard (or a Symfony
// 302) has actually left the protected path.
Then("the page path should not start with {string}", async ({ page }, prefix: string) => {
  await expect
    .poll(() => new URL(page.url()).pathname, { timeout: 15_000 })
    .not.toMatch(new RegExp(`^${prefix.replace(/[.*+?^${}()|[\]\\]/g, "\\$&")}(?:/|$)`))
})

// Used for the CidReqListener access-denied redirect chain (AccessDeniedHttpException
// -> ExceptionListener flashes + redirects to the `index` route, "/"). A plain
// "the URL should contain '/'" is unusable here since every URL's path starts
// with "/" — this asserts the path component is genuinely empty (root), not a
// substring match.
Then("the URL should be the site root", async ({ page }) => {
  await expect(page).toHaveURL(/^https?:\/\/[^/]+\/(?:\?.*)?$/)
})

// Mirrors Mink's assertPageNotContainsText: checks the page's raw text, not
// a specific element's visibility, since there's nothing to select when the
// text is genuinely absent (career.feature's delete scenario: confirms
// "Developer Copy" then "Developer" are both gone after each delete).
Then("I should not see {string}", async ({ page }, text: string) => {
  await expect(page.locator("body")).not.toContainText(text)
})

When("I wait until I no longer see {string}", async ({ page }, text: string) => {
  await expect(page.locator("body")).not.toContainText(text, { timeout: 30_000 })
})

// FeatureContext::waitForThePageToBeLoaded() / waitVeryLongForThePageToBeLoaded()
// / waitForThePageToBeLoadedWhenReady() / waitOneMinuteForThePageToBeLoaded()
// all use hardcoded sleeps (8s / 14s / 9s / 60s) because Mink/Selenium has no
// reliable auto-wait. Playwright's actions and assertions already auto-wait/
// retry, so these are kept only to match ported scenarios 1:1 rather than as
// blind sleeps — including "wait one minute", used by actionInstall.feature
// right after the final install button, where the real wait is however long
// schema creation actually takes: the subsequent "I should see" step's own
// polling (up to the test timeout) covers that, not this step itself. A real
// migration would likely delete all four and lean on Playwright's waiting.
//
// Behat's own regexes for these four allow an optional "I " prefix
// (`^(?:|I )wait ...$`), and actionInstall.feature genuinely uses both forms
// interchangeably (e.g. "Then wait for the page to be loaded" vs "And I wait
// for the page to be loaded") — so these are registered as regexes matching
// that same optional prefix, rather than as plain Cucumber-expression
// strings that would only match one exact form.
Then(/^(?:|I )wait for the page to be loaded$/, async ({ page }) => {
  await page.waitForLoadState("domcontentloaded")
})

Then(/^(?:|I )wait very long for the page to be loaded$/, async ({ page }) => {
  await page.waitForLoadState("domcontentloaded")
})

Then(/^(?:|I )wait for the page to be loaded when ready$/, async ({ page }) => {
  await page.waitForLoadState("networkidle")
})

Then(/^(?:|I )wait one minute for the page to be loaded$/, async ({ page }) => {
  await page.waitForLoadState("networkidle")
})

// Not ported — new. Bounded middle ground between the two waits above: a
// plain domcontentloaded settles fast but can leave a full-page-reload SPA
// route's own async content-fetch still in flight when the very next
// assertion's own (finite) auto-retry window starts, while "when ready"
// (networkidle) can hang the ENTIRE test timeout on this app's persistent
// background polling (already documented). Real CI failure: toolGroup.
// feature's "Check fapple's access to group announcements" / "Check
// acostea's access to group announcements" (originally one combined
// scenario, later split — see that file's own header comment on the split)
// visit 5 group-scoped announcement URLs back-to-back per user via "I visit
// URL saved with name ..." (a full page.goto() reload each time,
// re-bootstrapping the whole Vue app from scratch, not a cheap SPA-internal
// transition) — the underlying API call itself was confirmed live to be
// fast and correct (~300ms, right error body) for the specific check that
// failed, so the gap was in the FRONTEND finishing its async fetch-and-render
// within the assertion's own window, not the backend. Catching networkidle's
// own timeout (rather than using its default, which is the FULL test
// timeout) gives real extra settling time when the network genuinely does
// go quiet soon, without the unbounded hang risk.
Then(/^(?:|I )wait for the page content to settle$/, async ({ page }) => {
  await page.waitForLoadState("domcontentloaded")
  await page.waitForLoadState("networkidle", { timeout: 10_000 }).catch(() => {})
})

// Ported from FeatureContext::iShouldNotSeeAnError(). Its own .alert-danger
// check explicitly tolerates one that exists but has `display:none;` in its
// style attribute — jqGrid (careers.php and other legacy jqGrid list pages)
// always renders an empty, permanently display:none .alert-danger error bar
// in the DOM regardless of whether an error ever occurred, only populating/
// showing it on an actual AJAX error. A blanket toHaveCount(0) is stricter
// than the original and false-positives on that always-present element;
// :visible mirrors the original's actual intent (fail only on one that's
// really shown).
Then("I should not see an error", async ({ page }) => {
  await expect(page.locator("body")).not.toContainText(/internal server error/i)
  await expect(page.locator(".alert-danger:visible")).toHaveCount(0)
  await expect(page.locator(".p-message-error, .p-toast-message-error, .p-message-severity-error")).toHaveCount(0)
})

// Mink's "the response status code should be ..." (MinkContext::
// assertResponseStatus()), for admin/fileIntegrity.feature's access-control
// scenario. Reads the response captured by the last "I am on ..." navigation
// (gotoReliably) rather than making its own request, matching the original's
// own semantics of asserting on the CURRENT page's response, not a fresh one.
Then("the response status code should be {int}", async ({}, statusCode: number) => {
  if (!lastNavigationResponse) {
    throw new Error(
      'No navigation response recorded yet — "the response status code should be ..." must follow an "I am on ..." step.',
    )
  }
  expect(lastNavigationResponse.status()).toBe(statusCode)
})

// Ported from FeatureContext::focus() (Mink's findField() id -> name -> label
// cascade, then .focus()). toolAgenda.feature needs this before filling
// "date_range": DateRangePicker.php binds a jQuery daterangepicker widget to
// a plain text input, and focusing first mirrors the original Behat step's
// intent (a real user would click/focus the field before the picker
// initializes its popup) — though the actual value-setting still goes
// through the existing "I fill in ... with ..." step, which is enough on
// its own since .fill() dispatches both input and change events and the
// widget's bound '#id'.on('change', ...) handler (populates the two hidden
// _start/_end fields the form actually submits) only cares about the latter.
When(/^(?:|I )focus "([^"]*)"$/, async ({ page }, field: string) => {
  await (await resolveField(page, field)).focus()
})

// Not ported — new, generalizes the dual-listbox "move option across" DOM
// manipulation already used for socialGroup.feature's group_invitation.php
// (FormValidator::addMultiSelect() widget: a left `#<name>` <select> holding
// every available option, a right `#<name>_to` <select> — the one actually
// submitted — empty until JS moves an option over). That earlier version
// matched by the option's numeric `value`, which requires knowing an id in
// advance; toolAnnouncement.feature's "Choose recipients" widget
// (CourseManager::addUserGroupMultiSelect(), same underlying widget, field
// name "users") only ever needs to select a user by their visible name
// ("John Doe"), so this matches by trimmed option text instead. Same
// left->right DOM move + 'change' event dispatch as the original, just a
// different match key — kept as a separate step rather than changing the
// group-invitation one, since that one's numeric-value matching is still
// correct for its own use and changing it isn't needed here.
When(
  "I select {string} from the multiselect {string}",
  async ({ page }, optionText: string, fieldName: string) => {
    const leftSelector = `#${fieldName}`
    const rightSelector = `#${fieldName}_to`
    await page.waitForSelector(`${leftSelector} option`)
    await page.evaluate(
      ({ leftSelector, rightSelector, optionText }) => {
        const left = document.querySelector(leftSelector) as HTMLSelectElement
        const right = document.querySelector(rightSelector) as HTMLSelectElement
        const option = Array.from(left.options).find((o) => o.text.trim() === optionText)
        if (!option) {
          throw new Error(`No option with text "${optionText}" found in the "${fieldName}" list`)
        }
        option.selected = true
        right.appendChild(option)
        right.dispatchEvent(new Event("change", { bubbles: true }))
      },
      { leftSelector, rightSelector, optionText },
    )
  },
)

// Not ported — new, for toolAttendance.feature. attendance_add's own success
// redirect appends a real `attendance_id=<id>` query param (public/main/
// attendance/index.php: `header('Location: '.$currentUrl.'&action=
// calendar_add&attendance_id='.$attendanceId)`) — same "capture the real id
// instead of assuming 1" pattern already established for socialGroup's
// created-group id, needed here because `c_attendance.iid` is a single
// GLOBAL AUTO_INCREMENT shared across every course on the platform (not
// scoped per-course), so a fresh install's first-ever attendance is NOT
// guaranteed to land on id=1 the way a fresh course does.
let lastCreatedAttendanceId: string | null = null

Then("I remember the created attendance id", async ({ page }) => {
  const match = page.url().match(/[?&]attendance_id=(\d+)/)
  if (!match) {
    throw new Error(`Could not find an attendance_id in the current URL: ${page.url()}`)
  }
  lastCreatedAttendanceId = match[1]
})

// Not ported — new. Substitutes the id remembered by the step above into a
// path containing the literal placeholder "ATTENDANCE_ID", then navigates —
// avoids hardcoding attendance_id=1 (see gotcha above) while keeping the
// Gherkin readable (a plain "I am on {string}" with the placeholder baked
// into the string, resolved here instead of at authoring time).
Given("I am on the attendance page {string}", async ({ page }, pathTemplate: string) => {
  if (!lastCreatedAttendanceId) {
    throw new Error(
      "No attendance id remembered — run \"I remember the created attendance id\" right after creating it first.",
    )
  }
  await gotoReliably(page, pathTemplate.replace("ATTENDANCE_ID", lastCreatedAttendanceId))
})

// Ported from FeatureContext::saveCurrentUrlWithName() / visitUrlSavedWithName().
// toolGroup.feature's cross-user access-check scenario creates several
// announcements as one user, captures each one's own URL, logs in as a
// DIFFERENT user, then revisits those exact URLs to check access. A plain
// module-level Map is enough — every save/visit pair happens within the
// same scenario, and each scenario file gets a fresh module instance.
const savedUrls = new Map<string, string>()

Then("I save current URL with name {string}", async ({ page }, name: string) => {
  savedUrls.set(name, page.url())
})

Then("I visit URL saved with name {string}", async ({ page }, name: string) => {
  const url = savedUrls.get(name)
  if (!url) {
    throw new Error(`No URL was saved under the name "${name}"`)
  }
  await gotoReliably(page, url)
})

// Ported from FeatureContext::iMoveBackwardOnePage() (Mink's back() call).
Then("I move backward one page", async ({ page }) => {
  await page.goBack()
})

// Not ported — new, for toolGroup.feature's persistence checks after a
// settings save that shows no flash message at all (see common.steps.ts's
// other "no flash appears" notes for this feature) — re-opening the same
// form and reading a plain field's real value back is the only way left to
// confirm a save actually took effect.
Then("the field {string} should have value {string}", async ({ page }, field: string, value: string) => {
  await expect(await resolveField(page, field)).toHaveValue(value)
})

// Not ported — new, companion to "I check the ... radio button with ...
// value" above, for the same reason as the step just above: confirming a
// radio-group save actually persisted, since this tool's own settings
// saves show no flash message.
Then(
  "the {string} radio button with {string} value should be checked",
  async ({ page }, name: string, value: string) => {
    await expect(page.locator(`input[type="radio"][name="${name}"][value="${value}"]`)).toBeChecked()
  },
)

// Not ported — new, for toolGroup.feature's Vue announcement recipients
// field (PrimeVue MultiSelect, id="multiSelect") — a checkbox-list dropdown
// replacing the legacy dual-listbox "choose_recipients"/"users" widget
// entirely for this form. Matches an option by its exact visible text
// (e.g. "Fiona Apple Maggart (fapple)"), same text-matching rationale as
// "I select ... from the multiselect ..." above.
When("I press the multiselect option {string} in {string}", async ({ page }, optionText: string, fieldId: string) => {
  const field = page.locator(`#${fieldId}`)
  await field.scrollIntoViewIfNeeded()
  await field.click({ force: true })
  await page.getByText(optionText, { exact: true }).click()
  await page.keyboard.press("Escape")
})

// Not ported — new, for toolExerciseAdmin.feature. ExerciseQuestionSelectorView.vue's
// question-type picker (the icon grid on the "Add a question" page) renders each type as
// an icon-only <a> with NO visible text at all — a visually-hidden `sr-only` span carries
// the bare label, but both the real `title` AND `aria-label` attributes are set to the
// much longer `"{Label} - {help text}"` string (questionTypeTitle()) instead. That breaks
// both existing "I follow" tiers that could otherwise apply here: the exact-title tier
// needs the FULL string including the help text (which a Gherkin author shouldn't have to
// spell out and which differs per question type), and getByRole("link", { name }) computes
// its accessible name from that same long aria-label, not the sr-only text, so a plain
// label wouldn't exact-match either. Matches on the title attribute's own PREFIX instead —
// stable regardless of how long or how frequently-changed the trailing help text is.
When("I follow the question type {string}", async ({ page }, label: string) => {
  await page.locator(`a[title^="${label} - "]`).first().click()
})

// Not ported — new, for toolExerciseAdmin.feature's question-creation scenarios.
// ExerciseQuestionEditorView.vue's answer-table rows (Unique/Multiple answer, Exact
// selection, True-false variants, Global multiple answer, etc.) render each row's
// Answer/Comment TinyMCE editor with an id built from a per-row `localId` that embeds
// `Date.now()` (`exercise-answer-answer-${localId}` / `exercise-answer-comment-${localId}`,
// regenerated on every page load) — there is no fixed id "I fill in tinymce field ... with
// ..." could target for a specific row. `localId` itself always starts with the row's own
// zero-based index (`${index}-${timestamp}`), which IS stable, so this locates the row's
// real (random) editor id via that index prefix instead, then reuses the same
// set-content-and-fire-change approach as this file's other tinymce steps.
async function fillExerciseAnswerCell(page: Page, idPrefix: string, rowNumber: number, value: string) {
  const locator = page.locator(`textarea[id^="${idPrefix}${rowNumber - 1}-"]`)
  await locator.first().waitFor({ state: "attached" })
  const id = await locator.first().getAttribute("id")
  await waitForTinymceEditor(page, id as string, "filling a table-row editor")
  await page.evaluate(
    ({ id, value }) => {
      const editor = (window as any).tinymce.get(id)
      editor.setContent(value)
      editor.fire("change")
    },
    { id, value },
  )
}

When("I fill in the answer {int} text with {string}", async ({ page }, rowNumber: number, value: string) => {
  await fillExerciseAnswerCell(page, "exercise-answer-answer-", rowNumber, value)
})

When("I fill in the answer {int} comment with {string}", async ({ page }, rowNumber: number, value: string) => {
  await fillExerciseAnswerCell(page, "exercise-answer-comment-answer-", rowNumber, value)
})

// Not ported — new, companion to the two steps above. A row's own "correct" control is
// EITHER a single shared-name radio group with no per-row id at all (single-correct
// question types, e.g. Unique answer — every row's radio is literally
// `name="correct_answer"`) OR a per-row-indexed checkbox (multi-correct types, e.g.
// Multiple answer — `name="correct_answer_{index}"`), depending on the question type being
// created — tries the indexed checkbox first (only ever present for multi-correct types)
// and falls back to the Nth radio in the shared group otherwise.
When("I mark answer {int} as correct", async ({ page }, rowNumber: number) => {
  const indexed = page.locator(`input[name="correct_answer_${rowNumber - 1}"]`)
  if (await indexed.count()) {
    await indexed.check()
    return
  }
  await page.locator('input[name="correct_answer"]').nth(rowNumber - 1).check()
})

// Not ported — new, for the Matching question type's own "Match them" pair-answer
// editors — same randomized-id problem as the answer-table cells above
// (`exercise-matching-pair-${pair.localId}`, where `localId` is `pair-${timestamp}-
// {position}`), fixed the same way: locate the Nth pair's real editor id via DOM order
// (there are only ever two pair editors on this form) rather than a fixed id. Unlike the
// answer-table cells, the two MATCH OPTION editors ("A"/"B") are NOT randomized
// (`exercise-matching-option-option-1` / `-2`, confirmed live) and so need no equivalent
// step — the existing "I fill in tinymce field ... with ..." already works on those.
When("I fill in matching pair {int} with {string}", async ({ page }, pairNumber: number, value: string) => {
  const id = await page
    .locator('textarea[id^="exercise-matching-pair-pair-"]')
    .nth(pairNumber - 1)
    .getAttribute("id")
  if (!id) {
    throw new Error(`Could not find matching pair ${pairNumber}'s answer editor.`)
  }
  await waitForTinymceEditor(page, id as string, "filling a table-row editor")
  await page.evaluate(
    ({ id, value }) => {
      const editor = (window as any).tinymce.get(id)
      editor.setContent(value)
      editor.fire("change")
    },
    { id, value },
  )
})

// Not ported — new, for toolExerciseAdmin.feature's "Try exercise" scenario.
// ExercisePlayerView.vue's Matching question renders each pair's "Matches To" <select>
// with NO associated <label> at all (just a plain "Answer A"/"Answer B" header cell, not a
// real <label for="...">) — resolveField()'s getByLabel fallback can never match it. Only
// one matching question is ever on screen at a time (one-question-per-page runtime), so
// targeting by DOM order (the Nth <select> on the whole page) is unambiguous.
When("I select {string} from matching select {int}", async ({ page }, optionLabel: string, selectNumber: number) => {
  await page.locator("select").nth(selectNumber - 1).selectOption({ label: optionLabel })
})

// Not ported — new, for the Fill in blanks question's runtime answer inputs. Their real
// name (`question_{questionId}_blank_{n}`) embeds the question's numeric id, only known at
// runtime — matches on the stable "_blank_{n}" SUFFIX instead of the full name.
When("I fill in blank {int} with {string}", async ({ page }, blankNumber: number, value: string) => {
  await page.locator(`input[name$="_blank_${blankNumber}"]`).fill(value)
})

// Not ported — new, for the Open question type's runtime answer field — a plain
// <textarea> at runtime (ExercisePlayerView.vue), unlike the question-EDITOR's own
// TinyMCE-based answer fields, with a dynamic `name="question_{id}_text"` and no
// <label>. Only one such textarea is ever on screen (one-question-per-page runtime).
When("I fill in the open answer with {string}", async ({ page }, value: string) => {
  await page.locator("textarea").first().fill(value)
})

// Not ported — new, for the True/False/Don't-know question types' runtime radios
// (Multiple answer true/false/don't know, Combination true/false/don't-know) — every row
// repeats the exact same three labels ("True"/"False"/"Don't know"), so a single
// getByLabel() is inherently ambiguous across rows; this checks EVERY row's matching
// option in one step, which is what both scenarios using it actually want (answer every
// row the same way).
//
// Real CI/local failure (toolExerciseAdmin.feature's "Try exercise", reproduced live):
// a `count()` snapshot taken right after "Next question" can still reflect the OUTGOING
// question's own row count — the SPA's question transition (unmount old question, mount
// the new one) doesn't always finish within "wait for the page content to settle"'s
// networkidle check, since the DOM swap can land a tick after the network itself goes
// quiet. Looping `.nth(i)` up to that stale, too-high count then targets an index that
// no longer exists once the real (smaller) question finishes mounting — Playwright
// retries forever ("element was detached from the DOM, retrying") since that index never
// reappears, hanging until the scenario times out with the new question's rows left
// entirely unanswered (confirmed via a real trace: "Combination true/false/don't-know",
// only 2 rows, snapshotted with NEITHER row checked, stuck retrying a 3rd match left over
// from the previous 4-row question). Waiting for the match count to be STABLE across two
// reads before looping avoids acting on that transient, too-high count.
When("I check every {string} option on the page", async ({ page }, label: string) => {
  const options = page.getByLabel(label, { exact: true })
  await options.first().waitFor({ state: "visible" })

  let count = await options.count()
  for (let attempt = 0; attempt < 10; attempt++) {
    await page.waitForTimeout(150)
    const recount = await options.count()
    if (recount === count) {
      break
    }
    count = recount
  }

  for (let i = 0; i < count; i++) {
    await options.nth(i).check()
  }
})

// Not ported — new, for toolExerciseAdmin.feature's "Try exercise" scenario. Waits for
// the NEXT question's own title <h2> to actually appear before returning, re-clicking
// "Next question" if it hasn't — defensive against ExercisePlayerView.vue's "Next
// question" button only being disabled while `isSavingAnswer` is true, which leaves a
// narrow window where a click could in principle land right as the previous save
// resolves and get swallowed. Kept as cheap insurance even though it turned out NOT to
// be the cause of this scenario's real scoring gap — see "I check the ... answer and let
// it register" below for what that actually was.
When("I press \"Next question\" until {string} appears", async ({ page }, nextTitle: string) => {
  const heading = page.locator("h2").filter({ hasText: new RegExp(`^\\s*${escapeRegExp(nextTitle)}\\s*$`) })
  const saving = page.getByRole("button", { name: "Saving", exact: true })
  const nextControl = page.locator("button:not([disabled]):not([aria-disabled='true'])").filter({
    hasText: /^\s*(Next question|Next page)\s*$/,
  })
  const deadline = Date.now() + 50_000
  while (Date.now() < deadline) {
    if (await heading.first().isVisible().catch(() => false)) {
      return
    }
    if (await saving.isVisible().catch(() => false)) {
      await saving.waitFor({ state: "hidden", timeout: 20_000 }).catch(() => {})
      continue
    }
    if (await isSoonVisible(nextControl, 3_000)) {
      await nextControl.first().click()
    }
    if (await isSoonVisible(heading, 3_000)) {
      return
    }
  }
  await expect(heading.first()).toBeVisible()
})

When("I start the exercise", async ({ page }) => {
  await failIfLoginPage(page, "start the exercise")
  const startControl = page.locator("button:not([disabled]):not([aria-disabled='true'])").filter({
    hasText: /^\s*(Start test|Proceed with the test)\s*$/,
  })
  await expect(startControl.first()).toBeVisible({ timeout: 20_000 })
  await startControl.first().click()
})

// Not ported — new, for specialCase1Sessions.feature's session_add.php/session_edit.php
// date fields. These render as a flatpickr-style widget: a real `<input type="hidden"
// name="access_start_date">` carrying the "Y-m-d H:i" value actually submitted, paired
// with a separate visible text input the picker itself manages for display. Confirmed
// live: the hidden field already comes pre-filled with a sensible default (today's date/
// time) rather than empty, so a raw `.fill()` (which only works on visible, editable
// elements anyway — this is `type="hidden"`) was never an option here. Setting the DOM
// value directly and firing both `input` and `change` (mirroring `fillReliably()`'s own
// event pair elsewhere in this file) is enough for the FormValidator-rendered legacy page
// to pick up the new value on submit; no visual confirmation from the paired display
// input is needed since nothing here asserts against it.
When("I set hidden field {string} to {string}", async ({ page }, fieldName: string, value: string) => {
  await page.locator(`input[name="${fieldName}"]`).evaluate((el, value) => {
    ;(el as HTMLInputElement).value = value
    el.dispatchEvent(new Event("input", { bubbles: true }))
    el.dispatchEvent(new Event("change", { bubbles: true }))
  }, value)
})

// Not ported — new, for specialCase1Sessions.feature's session competency extra fields
// (extra_ecouter/extra_lire/etc. — CEFR-style "select your level" multi-selects). Every
// one of these FormValidator::addSelect()-rendered fields has a synthetic index-0
// "Please select an option" placeholder (confirmed live), so "select index 1" reliably
// picks the first REAL option regardless of how many meaningful options a given field
// has — same convention as course_add.php's own category/language selects, just without
// needing to know or spell out the option's actual (long, French) label text.
When("I select the first option from {string}", async ({ page }, fieldId: string) => {
  await page.locator(`#${fieldId}`).evaluate((el) => {
    const select = el as HTMLSelectElement
    select.selectedIndex = select.options.length > 1 ? 1 : 0
    select.dispatchEvent(new Event("change", { bubbles: true }))
  })
})

// Not ported — new, for specialCase1Sessions.feature's Learning Path builder. The
// resource panel (right-hand side of ExerciseLpBuilderView.vue-style pages, listing the
// course's own documents/exercises/etc. available to add) renders each item as a plain
// `<button>` with its exact title as text, inside a container confirmed live to be the
// ONLY element on the page matching this exact class combination
// (`div.divide-y.divide-gray-20.rounded-lg.border.border-gray-20.bg-white`) — scoping to
// it avoids also matching the SAME item's name once it's been added to the LP tree on the
// left (a real ambiguity: both panels can show an identically-labelled button at once).
// A single click (no drag-and-drop simulation needed) adds the item to the tree,
// confirmed live via the "Ajouté"/"Added" toast.
When("I add LP item {string} from the resource panel", async ({ page }, itemName: string) => {
  await page
    .locator("div.divide-y.divide-gray-20.rounded-lg.border.border-gray-20.bg-white")
    .getByRole("button", { name: itemName, exact: true })
    .click()
})

// Not ported — new, companion to the step above. The LP builder's resource panel shows
// one resource TYPE at a time (Documents/Tests/Links/Assignments/Forums/...), switched via
// a row of icon-only toolbar buttons whose `title` attribute is the (locale-dependent)
// type name — confirmed live both in English ("Tests") and French ("Exercices") courses.
// Needed before "I add LP item ... from the resource panel" for any item that isn't a
// plain document (the panel defaults to the Documents type on first load).
When("I switch the LP resource panel to {string}", async ({ page }, resourceType: string) => {
  await page.locator(`[title="${resourceType}"]`).click()
})

// Not ported — new, for specialCase1Sessions.feature's "final" document prerequisite
// (must complete the "Open question exercise" first, minimum score 0). Each LP tree item
// has its own "Prerequisites" icon (confirmed live via a real accessibility snapshot,
// title="Prerequisites") opening an inline panel below with one radio per EARLIER item in
// the LP (an item can only require something that precedes it — confirmed live: a later
// sibling never appears as an option), plus a Minimum/Maximum score pair that only
// applies — and only renders — for a prerequisite item that actually carries a score
// (an exercise), not a plain document. The minimum/maximum inputs' ids embed the
// prerequisite item's own numeric id (`lp-prerequisite-min-<id>`/`-max-<id>`, confirmed
// live), extracted here from the just-checked radio's own id
// (`lp-prerequisite-<id>`) rather than hardcoded, since that id is only known at runtime.
When(
  "I set the prerequisite of LP item {string} to {string} with minimum score {string}",
  async ({ page }, targetItem: string, sourceItem: string, minimumScore: string) => {
    // specialCase1 switches the interface language to French mid-file.
    // BaseButton only-icon uses the translated label as `title`/`aria-label`
    // ("Pré-requis" / "Modifier les prérequis"), so an English-only title
    // selector hung the full 15-minute budget with the French button visible.
    await page
      .locator(".rounded-lg.border.px-2.py-2", { hasText: targetItem })
      .locator('[title="Prerequisites"], [title="Pré-requis"], [aria-label="Prerequisites"], [aria-label="Pré-requis"]')
      .first()
      .click()
    const radio = page.getByRole("radio", { name: sourceItem, exact: true })
    await radio.check()
    const radioId = await radio.getAttribute("id")
    const numericId = radioId?.replace("lp-prerequisite-", "")
    const minimumInput = page.locator(`#lp-prerequisite-min-${numericId}`)
    if (await minimumInput.count()) {
      await minimumInput.fill(minimumScore)
    }
    await page
      .getByRole("button", { name: /Save prerequisites settings|Modifier les prérequis/i })
      .click()
  },
)


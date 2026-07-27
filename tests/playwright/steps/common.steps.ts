import path from "node:path"
import { expect, Page } from "@playwright/test"
import { createBdd, DataTable } from "playwright-bdd"

const { Given, When, Then, BeforeAll, AfterAll } = createBdd()

// Mirrors Mink's `files_path` (tests/behat/behat.yml: "%paths.base%/../../",
// i.e. repo root) — attachFileToField() paths in .feature files are relative
// to repo root, not this steps file. tests/playwright/steps -> repo root.
const repoRoot = path.resolve(__dirname, "../../..")

// Ported from tests/behat/features/bootstrap/FeatureContext.php.
// "I am on"/"I fill in ... for ..."/"I press"/"I should see"/"I check"/
// "I fill in the following:" are all standard Mink/MinkContext steps in
// Behat (not custom code) — reimplemented here.

Given("I am on {string}", async ({ page }, path: string) => {
  await page.goto(path)
})

// Ported from FeatureContext::iAmOnCourseXHomepage(): navigates via the
// legacy redirect entry point (cidReq resolves the course by its code, not
// its numeric id) and asserts no visible error, matching the original's own
// assertElementNotOnPage('.alert-danger') right after navigating.
Given("I am on course {string} homepage", async ({ page }, courseCode: string) => {
  await page.goto(`/main/course_home/redirect.php?cidReq=${encodeURIComponent(courseCode)}`)
  await page.waitForLoadState("domcontentloaded")
  await expect(page.locator(".alert-danger:visible")).toHaveCount(0)
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
// ("networkidle") here, which was NOT enough — the app has background
// polling (notifications, chat presence, same kind of thing DockedChat.vue
// does) that can make "network idle for 500ms" resolve on a brief lull
// before the actual post-login redirect lands, rather than after it.
// Waiting for the browser to actually leave /login is a deterministic
// signal that networkidle isn't; keeping the networkidle wait afterward too
// as a second guard, in case some async data-loading on the destination
// page still matters to whatever step comes next.
async function loginAs(page: Page, username: string) {
  await page.goto("/login")
  await page.locator("#login").fill(username)
  await page.locator("#password").fill(username)
  await page
    .locator('button:has-text("Sign in"), input[type="submit"][value="Sign in"]')
    .first()
    .click()
  await page.waitForURL((url) => !url.pathname.startsWith("/login"))
  await page.waitForLoadState("networkidle")
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
async function resolveField(page: Page, field: string) {
  const byId = page.locator(`#${field}`)
  const idCount = await byId.count()
  if (idCount === 1) return byId
  if (idCount > 1) {
    const visibleById = page.locator(`#${field}:visible`)
    if (await visibleById.count()) return visibleById
  }

  const byName = page.locator(`[name="${field}"]`)
  const nameCount = await byName.count()
  if (nameCount === 1) return byName
  if (nameCount > 1) {
    const visibleByName = page.locator(`[name="${field}"]:visible`)
    if (await visibleByName.count()) return visibleByName
  }

  return page.getByLabel(field)
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
async function fillReliably(locator: ReturnType<Page["locator"]>, value: string) {
  await locator.clear()
  await locator.pressSequentially(value)
  const actual = await locator.inputValue()
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
  await page.waitForFunction((id) => Boolean((window as any).tinymce?.get(id)), fieldId)
  await page.evaluate(
    ({ id, value }) => (window as any).setContentFromEditor(id, value),
    { id: fieldId, value },
  )
})

// Mink's "I check ..." checks a checkbox, same id -> name -> label resolution.
Then("I check {string}", async ({ page }, field: string) => {
  await (await resolveField(page, field)).check()
})

// Ported from FeatureContext::iCheckTheRadioButton(): resolves a radio input
// by its associated <label> text (Mink's findField() also allows id/name,
// but course.feature's own usage — "Private access (access authorized to
// group members only)" — is plainly label text, not an id/name value) and
// checks it. getByLabel handles both a wrapping <label> and a <label for="">
// pointing at the input, matching findField()'s own resolution.
When("I check the {string} radio button", async ({ page }, label: string) => {
  await page.getByLabel(label).check()
})

// Not ported — new. Mirrors FeatureContext's own generic Select2/ajax-select
// steps (iFillInSelectInputWithAndSelect / iFillInAjaxSelectInputWithAndSelect)
// but drives the REAL widget through the UI instead of shelling out to jQuery,
// for course_add.php's course_categories field (FormValidator's addSelectAjax,
// backed by Select2 + an AJAX search endpoint — course.ajax.php?a=search_category
// — not a plain <select>, so resolveField()/selectOption() don't apply).
// Typing into the search box triggers the AJAX call; filtering the results
// locator by the exact option text (rather than grabbing whatever's first)
// means this naturally waits out the "Searching…" placeholder instead of
// racing it.
When("I select {string} from the ajax select {string}", async ({ page }, optionText: string, fieldId: string) => {
  const searchField = page.locator(`#${fieldId}`).locator("..").locator(".select2-search__field")
  await searchField.click()
  await searchField.fill(optionText)
  await page.locator(".select2-results__option", { hasText: optionText }).first().click()
})

// Mink's "I select X from Y" (a <select>'s option, matched by its visible
// label) replaces the current selection entirely — correct for a plain
// single-select, and for a multi-select it's always the FIRST of a
// "select" + N "additionally select" sequence in these .feature files, so
// starting from a clean selection is exactly right.
Then("I select {string} from {string}", async ({ page }, optionLabel: string, field: string) => {
  await (await resolveField(page, field)).selectOption({ label: optionLabel })
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

// Not ported from Behat — the original adminSettings.feature has no
// teardown at all and just leaves platform settings permanently changed.
// Added here specifically so this feature is safe to run repeatedly against
// a real, shared instance. Runs once for the whole file (BeforeAll/AfterAll
// tagged to @settings, not a Before/After per scenario), matching how
// "Seed test users" is also a single one-time action, not per-scenario:
// snapshot whatever each setting's actual current value already is (not a
// hardcoded assumed default, which could be wrong for a given instance and
// would itself be an unwanted mutation) before any @settings scenario
// mutates it, then restore exactly that once after the last one finishes.
// BeforeAll/AfterAll are worker-scoped: with fullyParallel:false a single
// feature file's scenarios run sequentially within one worker, so this
// runs once per worker touching @settings scenarios, not once per scenario.
const SETTINGS_PAGES = [
  { path: "/admin/settings/search_settings?keyword=changeable_options", field: "form_changeable_options" },
  { path: "/admin/settings/search_settings?keyword=allow_registration", field: "form_allow_registration" },
  { path: "/admin/settings/search_settings?keyword=allow_group_categories", field: "form_allow_group_categories" },
]

const settingsSnapshot = new Map<string, string[]>()

// Shared between BeforeAll and AfterAll deliberately: creating a *fresh*
// browser context in AfterAll (as a first attempt did) raced against
// Playwright's own worker teardown — by the time AfterAll runs, the worker
// has finished all its assigned tests and is winding down, and a brand new
// context's navigation could never complete (a plain "Sign in" click hung
// past even a very generous per-action timeout). Keeping the one context
// BeforeAll already opened alive in between, instead of closing and
// recreating it, sidesteps that race entirely — it's an already-running,
// healthy context the whole time, just idle between the two hooks.
let settingsPage: import("@playwright/test").Page | undefined

async function loginAsAdminOnFreshPage(browser: import("@playwright/test").Browser, baseURL?: string) {
  const page = await (await browser.newContext({ baseURL })).newPage()
  await page.goto("/login")
  await page.locator("#login").fill("admin")
  await page.locator("#password").fill("admin")
  await page
    .locator('button:has-text("Sign in"), input[type="submit"][value="Sign in"]')
    .first()
    .click()
  await page.waitForURL((url) => !url.pathname.startsWith("/login"))
  await page.waitForLoadState("networkidle")
  return page
}

BeforeAll({ tags: "@settings" }, async ({ browser, baseURL }) => {
  const page = await loginAsAdminOnFreshPage(browser, baseURL)
  settingsPage = page
  for (const { path, field } of SETTINGS_PAGES) {
    await page.goto(path)
    await page.waitForLoadState("domcontentloaded")
    const values: string[] = await page.locator(`#${field}`).evaluate((el) =>
      Array.from((el as HTMLSelectElement).selectedOptions).map((option) => option.value),
    )
    settingsSnapshot.set(field, values)
  }
})

AfterAll({ tags: "@settings" }, async () => {
  if (!settingsPage) return
  const page = settingsPage
  for (const { path, field } of SETTINGS_PAGES) {
    const values = settingsSnapshot.get(field)
    if (!values) continue
    await page.goto(path)
    await page.waitForLoadState("domcontentloaded")
    await page.locator(`#${field}`).selectOption(values.map((value) => ({ value })))
    await pressButton(page, "Save")
    // "Save" is a form submit — likely POST-redirect-GET under the hood —
    // and domcontentloaded can resolve on an intermediate state before that
    // redirect chain actually settles. A real CI run hit exactly this: the
    // *next* iteration's page.goto() got interrupted by a still-in-flight
    // navigation left over from *this* iteration's Save. networkidle is a
    // stronger signal that the whole chain, not just the first response,
    // has actually finished before the loop moves on.
    await page.waitForLoadState("networkidle")
  }
  await page.context().close()
})

// Mink's pressButton resolves a button/submit input by id, then name, then
// value/visible text (e.g. actionInstall.feature's "step4"/"step5"/
// "button_step6"/"license-next" are id attributes on the legacy install
// wizard's submit buttons, not their visible labels — unlike "Sign in",
// which is only ever visible text, never a valid id/name value in the
// first place since it contains a space). Only attempt the id/name lookup
// when the label is actually shaped like one, to avoid feeding something
// like "#Sign in" to a CSS selector.
const looksLikeIdentifier = (value: string) => /^[\w-]+$/.test(value)

async function pressButton(page: Page, label: string) {
  if (looksLikeIdentifier(label)) {
    // Same hidden-proxy-vs-visible-widget situation as resolveField() above
    // can apply to buttons too, hence :visible here as well.
    const byId = page.locator(`#${label}:visible`)
    if (await byId.count()) {
      await byId.first().click()
      return
    }
    const byName = page.locator(`[name="${label}"]:visible`)
    if (await byName.count()) {
      await byName.first().click()
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
  const exact = page.getByRole("button", { name: label, exact: true })
  if (await exact.count()) {
    await exact.first().click()
    return
  }
  const exactSubmit = page.locator(`input[type="submit"][value="${label}"]`)
  if (await exactSubmit.count()) {
    await exactSubmit.first().click()
    return
  }
  await page
    .locator(`button:has-text("${label}"), input[type="submit"][value="${label}"]`)
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
Then("I click the {string} icon in the row for {string}", async ({ page }, selector: string, rowText: string) => {
  page.once("dialog", (dialog) => dialog.accept())
  await page.locator("tr", { hasText: rowText }).locator(selector).first().click()
})

// Mink's built-in "I follow" (MinkContext::iClickLink(), not custom
// FeatureContext code) clicks a link resolved by id, then title attribute,
// then visible text/label (e.g. an image link's alt text) — course.feature
// uses plain visible text throughout ("Course description", "Documents",
// etc.), so the text tier is what actually matters here, but the id/title
// tiers are kept for parity with Mink's own resolution order.
When("I follow {string}", async ({ page }, link: string) => {
  if (looksLikeIdentifier(link)) {
    const byId = page.locator(`#${link}:visible`)
    if (await byId.count()) {
      await byId.first().click()
      return
    }
  }
  const byTitle = page.locator(`a[title="${link}"]:visible`)
  if (await byTitle.count()) {
    await byTitle.first().click()
    return
  }
  await page.getByRole("link", { name: link }).first().click()
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

Then("I should see {string}", async ({ page }, text: string) => {
  await expect(page.getByText(text).first()).toBeVisible()
})

// Mirrors Mink's assertPageNotContainsText: checks the page's raw text, not
// a specific element's visibility, since there's nothing to select when the
// text is genuinely absent (career.feature's delete scenario: confirms
// "Developer Copy" then "Developer" are both gone after each delete).
Then("I should not see {string}", async ({ page }, text: string) => {
  await expect(page.locator("body")).not.toContainText(text)
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
  await expect(page.locator("body")).not.toContainText("Internal server error")
  await expect(page.locator(".alert-danger:visible")).toHaveCount(0)
  await expect(page.locator(".p-message-error")).toHaveCount(0)
})

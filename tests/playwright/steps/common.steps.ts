import { expect, Page } from "@playwright/test"
import { createBdd, DataTable } from "playwright-bdd"

const { Given, When, Then, BeforeAll, AfterAll } = createBdd()

// Ported from tests/behat/features/bootstrap/FeatureContext.php.
// "I am on"/"I fill in ... for ..."/"I press"/"I should see"/"I check"/
// "I fill in the following:" are all standard Mink/MinkContext steps in
// Behat (not custom code) — reimplemented here.

Given("I am on {string}", async ({ page }, path: string) => {
  await page.goto(path)
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

// Mink's "I fill in the following:" takes a table of |field|value| rows and
// fills each one the same way the single-field step does.
//
// actionInstall.feature's Step 5 table showed something new: filling
// "passForm" (first row) verified correctly right after typing (inputValue()
// matched "admin"), yet the network trace at actual submission — after every
// other row in the table had also been filled — still showed the installer's
// auto-generated suggestion, not "admin". So the fill itself works; something
// triggered by filling a LATER field (emailForm/mailerDsn/etc.) resets an
// EARLIER one. Rather than single out which field causes it, this does a
// settle pass after the initial fill: re-check every field and re-fill any
// that drifted from what was intended, so a later row clobbering an earlier
// one gets caught and corrected regardless of which row is the trigger.
Then("I fill in the following:", async ({ page }, dataTable: DataTable) => {
  const rows = dataTable.rows()

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

// Mink's "I check ..." checks a checkbox, same id -> name -> label resolution.
Then("I check {string}", async ({ page }, field: string) => {
  await (await resolveField(page, field)).check()
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
    await page.waitForLoadState("domcontentloaded")
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
  await page
    .locator(`button:has-text("${label}"), input[type="submit"][value="${label}"]`)
    .first()
    .click()
}

When("I press {string}", async ({ page }, label: string) => {
  await pressButton(page, label)
})

Then("I should see {string}", async ({ page }, text: string) => {
  await expect(page.getByText(text).first()).toBeVisible()
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

// Ported from FeatureContext::iShouldNotSeeAnError().
Then("I should not see an error", async ({ page }) => {
  await expect(page.locator("body")).not.toContainText("Internal server error")
  await expect(page.locator(".alert-danger")).toHaveCount(0)
  await expect(page.locator(".p-message-error")).toHaveCount(0)
})

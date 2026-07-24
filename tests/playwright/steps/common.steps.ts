import { expect, Page } from "@playwright/test"
import { createBdd, DataTable } from "playwright-bdd"

const { Given, When, Then } = createBdd()

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
// `:visible` matters here: PrimeVue form fields (e.g. the install wizard's
// database step) render a hidden proxy <input type="hidden" name="dbNameForm">
// kept in sync alongside the actual visible <input name="dbNameForm"
// input-id="dbNameForm">, so a plain [name="..."] locator matches both and
// Playwright's strict mode rightly refuses to guess. Restricting to the
// visible one is also just the more correct thing to do regardless — a real
// user can't type into a hidden field.
async function resolveField(page: Page, field: string) {
  const byId = page.locator(`#${field}:visible`)
  if (await byId.count()) return byId
  const byName = page.locator(`[name="${field}"]:visible`)
  if (await byName.count()) return byName
  return page.getByLabel(field)
}

// A plain .fill() sets the DOM value and dispatches one `input` event, which
// is enough for a plain <input> (e.g. Login.vue's fields) but was proven NOT
// enough for actionInstall.feature's Step5.vue admin password field: it's
// PrimeVue's <Password> widget, which auto-generates its own suggested value
// on mount and apparently doesn't react to a batch .fill() the way a plain
// input's v-model does — confirmed via a network trace showing the
// installer's own auto-generated password ("7%eDtmXb4SrBT"-style) went
// through to the real submission on every request, never "admin", even
// though the fill() call itself didn't error. clear() + pressSequentially()
// simulates real keystrokes (one native keydown/input per character), which
// is the standard, more broadly-compatible fix for rich components that
// don't pick up a single programmatic value assignment.
async function fillReliably(locator: ReturnType<Page["locator"]>, value: string) {
  await locator.clear()
  await locator.pressSequentially(value)
}

When("I fill in {string} for {string}", async ({ page }, value: string, field: string) => {
  await fillReliably(await resolveField(page, field), value)
})

// Mink's "I fill in the following:" takes a table of |field|value| rows and
// fills each one the same way the single-field step does.
Then("I fill in the following:", async ({ page }, dataTable: DataTable) => {
  for (const [field, value] of dataTable.rows()) {
    await fillReliably(await resolveField(page, field), value)
  }
})

// Mink's "I check ..." checks a checkbox, same id -> name -> label resolution.
Then("I check {string}", async ({ page }, field: string) => {
  await (await resolveField(page, field)).check()
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

When("I press {string}", async ({ page }, label: string) => {
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

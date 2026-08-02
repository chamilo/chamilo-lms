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

// Ported from FeatureContext::iAmOnCourseXHomepage(): navigates via the
// legacy redirect entry point (cidReq resolves the course by its code, not
// its numeric id) and asserts no visible error, matching the original's own
// assertElementNotOnPage('.alert-danger') right after navigating.
Given("I am on course {string} homepage", async ({ page }, courseCode: string) => {
  await page.goto(`/main/course_home/redirect.php?cidReq=${encodeURIComponent(courseCode)}`)
  await page.waitForLoadState("domcontentloaded")
  await expect(page.locator(".alert-danger:visible")).toHaveCount(0)
})

// Ported from FeatureContext::iAmOnCourseXHomepageInSessionY() — same
// redirect entry point, with the session name appended so the course loads
// in that session's context (sessionAccess.feature checks access is/isn't
// allowed depending on the visiting user's session subscription).
Given(
  "I am on course {string} homepage in session {string}",
  async ({ page }, courseCode: string, sessionName: string) => {
    await page.goto(
      `/main/course_home/redirect.php?cidReq=${encodeURIComponent(courseCode)}&session_name=${encodeURIComponent(sessionName)}`,
    )
    await page.waitForLoadState("domcontentloaded")
    await expect(page.locator(".alert-danger:visible")).toHaveCount(0)
  },
)

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
async function loginAs(page: Page, username: string) {
  await page.goto("/login")
  await page.locator("#login").fill(username)
  await page.locator("#password").fill(username)
  await page
    .locator('button:has-text("Sign in"), input[type="submit"][value="Sign in"]')
    .first()
    .click()
  await page.waitForURL((url) => !url.pathname.startsWith("/login"))
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
Given("I am not logged", async ({ page }) => {
  await page.goto("/logout")
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
  return page.getByLabel(new RegExp(`^\\s*\\*?\\s*${escapeRegExp(field)}\\s*$`, "i"))
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
  await page.waitForFunction((id) => Boolean((window as any).tinymce?.get(id)), fieldId)
  await page.evaluate(({ id, value }) => {
    const editor = (window as any).tinymce.get(id)
    editor.setContent(value)
    editor.fire("change")
  }, { id: fieldId, value })
})

// Not ported — new, for toolWork.feature's Vue create/edit assignment
// dialogs (AssignmentForm.vue-style). BaseTinyEditor generates a fresh,
// unpredictable instance id per mount (`tiny-vue_<random>`) rather than a
// stable one tied to the field name, so there's no id to resolveField()
// against at all — confirmed via a real DOM dump. Since these dialogs only
// ever have ONE editor open at a time, `tinymce.activeEditor` reliably
// identifies it without needing an id.
Then("I fill in the active tinymce editor with {string}", async ({ page }, value: string) => {
  await page.waitForFunction(() => Boolean((window as any).tinymce?.activeEditor))
  await page.evaluate((value) => {
    const editor = (window as any).tinymce.activeEditor
    editor.setContent(value)
    editor.fire("change")
  }, value)
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
async function gotoReliably(page: Page, path: string, maxAttempts = 5) {
  for (let attempt = 1; attempt <= maxAttempts; attempt++) {
    try {
      await page.goto(path)
      return
    } catch (error) {
      if (!String(error).includes("is interrupted by another navigation") || attempt === maxAttempts) {
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
  await page.locator("#login").fill("admin")
  await page.locator("#password").fill("admin")
  await page
    .locator('button:has-text("Sign in"), input[type="submit"][value="Sign in"]')
    .first()
    .click()
  // Same settle rule as loginAs(): leave /login, no networkidle (see comment there).
  await page.waitForURL((url) => !url.pathname.startsWith("/login"))
  return page
}

BeforeAll({ tags: "@settings" }, async ({ browser, baseURL }) => {
  const page = await loginAsAdminOnFreshPage(browser, baseURL)
  settingsPage = page
  for (const { path, field } of SETTINGS_PAGES) {
    await gotoReliably(page, path)
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
    await gotoReliably(page, path)
    await page.locator(`#${field}`).selectOption(values.map((value) => ({ value })))
    await pressButton(page, "Save")
    // "Save" is a form submit — likely POST-redirect-GET under the hood.
    // The NEXT iteration's own gotoReliably() now absorbs a still-lagging
    // redirect from this Save if one occurs, so this wait just needs to be
    // reasonable, not airtight.
    await page.waitForLoadState("networkidle")
  }
  await page.context().close()
})

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

async function pressButton(page: Page, label: string) {
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
  const enabledExact = exact.locator('xpath=self::*[not(@aria-disabled="true")]')
  if (await isSoonVisible(enabledExact)) {
    await enabledExact.first().click()
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
    .locator("xpath=self::*[not(@aria-pressed)]")
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
  const byTitle = page.getByTitle(label, { exact: true })
  if (await isSoonVisible(byTitle)) {
    await byTitle.first().click()
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
Then("I click the {string} icon in the row for {string}", async ({ page }, selector: string, rowText: string) => {
  page.once("dialog", (dialog) => dialog.accept())
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
  const roleLink = page.getByRole("link", { name: link })
  if (await isSoonVisible(roleLink)) {
    await roleLink.first().click()
    return
  }
  // Not in the original Mink cascade — new. toolWork.feature's Vue
  // assignment list renders its row title as a plain `<a>` with NO `href`
  // attribute at all (confirmed via a real DOM dump) — without an href, it
  // has no implicit "link" role, so getByRole("link") never matches it.
  // Falls back to a plain exact-text match, which works regardless of the
  // element's actual role/tag.
  await page.getByText(link, { exact: true }).first().click()
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
  const adminId = 1
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
  await page.request.post("/social-network/user-action", {
    data: { targetUserId: adminId, action: "add_friend", is_my_friend: true },
  })
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
  const match = page.url().match(/[?&]id=(\d+)/)
  if (!match) {
    throw new Error(`Could not find a group id in the current URL: ${page.url()}`)
  }
  lastCreatedGroupId = match[1]
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
  await page.waitForSelector("#invitation option")
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
  await expect(page.getByText(text).first()).toBeVisible()
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

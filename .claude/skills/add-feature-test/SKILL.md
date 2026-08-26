---
name: add-feature-test
description: >
  Given a plain-language description of an EXISTING Chamilo feature (a tool, a
  form, an admin page — anything reachable in the running app), locate its
  current implementation (legacy PHP or Vue SPA), explore the live rendered UI
  to confirm real selectors/behavior, and author a complete, non-duplicating
  Playwright/Gherkin test suite for it in tests/playwright/features/. Use when
  the user describes a feature and asks for test coverage, wants to "add
  tests for X", asks to confirm a feature "keeps working", or runs
  /add-feature-test. Companion to the Playwright suite described in CLAUDE.md —
  read that file's "Discovered Patterns" section first, it documents
  conventions this skill assumes (resolveField/pressButton cascades, base
  component mapping, breadcrumb rules, etc.).
---

# Add Feature Test

Turn a plain-language feature description into a Playwright/Gherkin test suite
that actually reflects the live application — not assumptions from reading
source code alone. The single most repeated lesson across this whole
migration: **static code reading gets selectors, field names, dialog types,
and even which page is actually live WRONG often enough that every one of
those claims must be confirmed against a real running instance before being
written into a test.** Budget time for that verification step; do not skip it
to save time.

Scope, per the user's own framing: **wide-ranging, not exhaustive.** Cover the
feature's real create/read/update/delete flows and role-based access variants
that a normal user would actually hit. Do not chase combinatorial platform-
setting states or contrived edge cases nobody would realistically trigger.

---

## Step 0 — Get the feature description straight

If the description could point at more than one page (a feature that exists
both as a legacy tool and a parallel/newer Vue equivalent, or a name that's
ambiguous inside Chamilo's domain), ask which one before doing any real work.
Otherwise proceed — most descriptions ("the Agenda tool", "creating a session",
"the document manager") are unambiguous enough to just go find.

## Step 1 — Locate the current implementation

Search BOTH possible homes — this codebase is mid-migration and a feature can
live in either, or have moved without every caller being updated:

- Legacy PHP: `public/main/<domain>/*.php`, `public/main/inc/lib/*.lib.php` for
  the form-building logic.
- Vue SPA: `assets/vue/views/<domain>/*.vue`, `assets/vue/router/<domain>.js`.

If both exist, determine which is actually **reachable** in the live app —
course-tool links and admin-panel entries have been silently repointed to a
new Vue route while the old URL still technically loads (`toolAnnouncement`'s
announcement tool is exactly this: direct URL still hits the legacy page, but
the course-tool link now goes to `/resources/announcement/:id`). Don't assume;
check `src/CoreBundle/Tool/*.php` / grep the link's actual `href`/route target.

If the legacy page is now a dead stub (`header('Location: ...'); exit;` or
similar), this is a **fresh-scenario** case, not a port — write scenarios
against the current Vue page's real intent, not the old page's old flow.
`class.feature`'s rewrite of the dead `usergroups.php` → `UsergroupList.vue`
and `toolDocument.feature`'s full rewrite against the Vue Document tool are
the reference examples for this.

## Step 2 — Check for existing coverage (don't duplicate)

1. `tests/playwright/features/*.feature` — if a file for this feature
   **already exists**, this is an EXTEND task: read it fully, understand what
   it already covers, and only add what's missing (new scenarios, new roles,
   edge cases) rather than starting over or duplicating scenarios.
2. The deleted Behat suite — the files are in git history
   (`git show 98c77757ea6:tests/behat/features/<name>.feature`;
   `git ls-tree -r --name-only 98c77757ea6 tests/behat` for the list, 84 files
   at that commit). If a
   same-topic file existed there, it's a **hint** of intended scenarios (what
   create/edit/delete flows the original author thought mattered) — never a
   source of truth for selectors. Every field name, button label, and dialog
   type it assumes must still be verified live in Step 4; those files rotted
   constantly (name→title renames, dead pages, changed widgets, typo'd step
   phrases that never even matched in the original suite).
3. If genuinely new (nothing in either place — e.g. a feature added since
   then), design scenarios from CLAUDE.md's own mandatory
   rule (it applies here too): cover create/read/update/delete at
   minimum; run the full scenario set once per role if the page is reachable
   by more than one role; add an explicit access-denied scenario for
   role-restricted pages.

## Step 3 — Get a safe place to test

Prefer a disposable, dedicated fresh install where creating/deleting data
freely is fine — ask the user if one exists for this repo (one may already be
set up as a separate vhost/DB pointed at this exact worktree). Confirm:

- It's actually serving **this worktree's** code, not a shared box serving a
  different branch. A vhost `DocumentRoot` edit alone is not enough — the web
  server process must also be reloaded to pick it up. Verify with a plain
  marker file (`echo x > public/marker.txt && curl .../marker.txt`) before
  trusting ANY test result against it — a stale-server false-positive/negative
  wastes the rest of the session's work.
- The one-time seed sequence has run if the feature needs course context
  (`package.json`'s `test:playwright:seed` → `:seed-course` →
  `:seed-private-course`, in that order — creates the test users and the
  `TEMP`/`TEMPPRIVATE` courses most course-tool features assume exist).

If no such environment is available and only a shared/production-like box
exists, get explicit confirmation before creating or deleting ANY data there,
and prefer read-only verification (log in, look, don't submit forms) until
that's granted.

## Step 4 — Explore the live UI (this is the core of the skill)

Log in as the relevant role(s) and physically use the feature. For anything
non-trivial (a form with more than plain text inputs, any delete/confirm
action, any grid), don't just eyeball it in a browser — write a short,
disposable Node script using the already-installed `playwright` package to
log in, navigate, and dump the actual DOM: field `id`/`name` attributes,
button structure and `title`/`aria-label`, dialog markup, icon classes. This
is dramatically faster and more reliable than reading PHP/Vue source and
guessing what renders — this migration's own history is full of cases where
the source *looked* like it would produce one thing and the live DOM showed
another (QuickForm's `id` prefixing by form name while `name` stays
unprefixed; a "Save" button that's actually a disabled TinyMCE toolbar
button sharing the same accessible name; an icon-only button whose only
identifier is a `title` attribute; a jqGrid "select all" checkbox that
visually toggles but does nothing if data rows haven't loaded yet).

Specifically confirm, don't assume:
- **Form fields**: plain input, or one of the richer widgets below?
- **Multi-value fields**: a plain `<select>`, an AJAX/Select2 search
  (`FormValidator::addSelectAjax`), or a dual-listbox multiselect
  (`FormValidator::addMultiSelect` — two `<select>`s, left = available,
  right = `<name>_to` = actually submitted, empty until JS moves an option
  across)?
- **Rich text fields**: legacy pages use TinyMCE via
  `window.setContentFromEditor(id, content)`; Vue pages use `BaseTinyEditor`
  (`@tinymce/tinymce-vue`) which needs a `change` event fired after
  `setContent()` to reach `v-model`. Different steps in
  `tests/playwright/steps/common.steps.ts` handle each — check which applies.
- **Date/time pickers**: PrimeVue's `<Calendar>`/`<DatePicker>`
  (`BaseCalendar.vue`) is genuinely `readonly` — no keyboard entry works at
  all, and its time picker is increment/decrement buttons only, impractical
  to drive to an exact value. If the field already defaults to a sane
  current value when opened, prefer relying on that default over trying to
  set an exact one.
- **Delete/destructive confirmation** — there are at least four distinct
  mechanisms in this codebase; identify which one before writing the step:
  native browser `confirm()`, SweetAlert2, a PrimeVue `ConfirmDialog` (labels
  vary — "Yes"/"No", "Confirm"/plain button text — read the actual rendered
  buttons), or jqGrid's own built-in del dialog (its buttons are
  `<a role="button" class="fm-button">`, not real `<button>` elements).
- **Row actions on a table with other pre-existing data**: never blind
  `.first()` a delete/edit icon unless the page is guaranteed to start with
  zero rows. Scope by the row's own identifying text.

Clean up any data created purely for this exploration before moving to Step 6.

## Step 5 — Known landmines (read before writing new steps)

Read `resolveField()` and `pressButton()` in `tests/playwright/steps/common.steps.ts`
first — most field/button interactions are already covered by their id → name
→ label/role → text cascades, including hard-won fixes for: PrimeVue
icon+label buttons breaking exact accessible-name matching, TinyMCE toolbar
buttons sharing a label with the real submit button, jqGrid's `fm-button`
dialogs, and app-shell chrome (e.g. a sidebar toggle) coincidentally sharing a
button's label. Only add a new step when the interaction genuinely isn't
covered — and when you do, document *why* with the same rationale-comment
convention already used throughout that file (what was tried, what broke,
what the live DOM actually showed), not just what the code does.

Other recurring traps to actively check for:
- **Never hardcode a numeric database ID** unless it's backed by a dedicated,
  ordered, one-time seed step (this repo's `TEMP` course = cid 1,
  `TEMPPRIVATE`, the seeded test users). Anything else — a just-created
  group, attendance, document — capture the real ID dynamically (from a
  redirect URL or a lookup endpoint) and remember it for later steps, the
  same way already done for socialGroup's group id / toolAttendance's
  attendance id.
- **Transient toasts are not a valid assertion target.** A flash/toast can
  legitimately not be in the DOM by the time an assertion polls, even when
  the underlying action succeeded. Assert a durable signal instead: a URL
  change, an inline alert with its own dismiss button, page content that only
  renders once the action truly landed.
- **"wait for the page to be loaded" is a no-op for dialog-based SPA actions**
  where nothing navigates (most Vue create/edit/delete dialogs). Chaining
  several such actions back-to-back needs a durable "should not see X"
  between them, not a blind wait — otherwise the next lookup can race the
  previous action's still-in-flight refetch.
- **jqGrid's "select all" needs real data rows loaded first** — clicking the
  header checkbox before the grid's own AJAX data call has rendered any rows
  toggles the checkbox's own visual state but selects nothing.
- **`name` → `title` field-rename drift** recurs across pages migrated from
  1.11.x — if a field name taken from an older test doesn't resolve, check for
  this exact rename before assuming something else changed.
- **Cross-file test interference on shared fixtures.** Any feature touching
  the shared `TEMP` course (cid=1) can run concurrently, in a different
  worker, with any OTHER feature file also touching it — don't assume
  exclusive access to that course's data. If a cleanup/delete step in your
  new scenarios could plausibly race another file's read of the same
  resource, make it tolerate the target already being gone rather than
  hard-failing.

## Step 6 — Write the feature file

- Path: `tests/playwright/features/<name>.feature`, keeping the name the old
  Behat file used if a same-topic one existed (the other files here follow that
  naming).
- Start with a header comment documenting what was actually verified live vs.
  assumed, and any real drift found from the old Behat scenario (if any) — this
  is a load-bearing convention in every existing file in this directory, not
  decoration. A future reader (including a future you) needs to know *why*
  a selector/assertion is what it is, not just what it is.
- Reuse existing steps wherever the interaction pattern already exists; only
  extend `common.steps.ts` for genuinely new patterns (Step 5).
- Structure scenarios per Step 2's coverage rule: create/read/update/delete at
  minimum, once per accessible role, plus an access-denied check for
  restricted pages. Skip anything that isn't a real, likely user path.
- If a Vue form field is missing a `name` attribute (breaks the id→name→label
  cascade and violates this project's own CLAUDE.md convention), add the
  attribute directly — a small, safe, real fix, not a workaround.

## Step 7 — Verify against the live instance

- Regenerate after every `.feature`/step change:
  `node_modules/.bin/bddgen --config=tests/playwright/playwright.config.ts`
- Run just the new feature file, not the full suite, unless the user asks for
  a broader regression — this migration has repeatedly found that full-suite
  runs are expensive and mostly redundant once the targeted file is green,
  especially against a shared step-definitions file where changes are scoped.
- Root-cause real failures from actual evidence (DOM dump, network trace) —
  never guess-and-retry blindly. If a fix touches shared step code
  (`resolveField()`/`pressButton()`/etc.), re-run at least one other
  already-passing feature file afterward to confirm no regression, since
  those helpers are used everywhere.
- Clean up any test data left over from verification runs (the dev instance
  persists across sessions) before finishing.

## Step 8 — Report

Summarize: scenarios added (and to which file — new or extended), any real
product bugs found and fixed along the way (call these out explicitly,
they're often more valuable than the tests themselves), local verification
results, and — always — that **local verification against a reused dev
instance is not the same guarantee as a fresh CI run.** A dev instance
accumulates state (course subscriptions, leftover rows, prior test runs) that
a genuinely fresh install never has, and this migration has hit real cases
where a fix that was "confirmed" locally turned out to be environment-
specific and got reverted after the next real CI run disagreed. Say so
plainly rather than implying local == done; the next CI run is the actual
verification for anything state-sensitive.

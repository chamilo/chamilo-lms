# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Generic rules

These rules apply to every task in this project unless explicitly overridden.
Bias: caution over speed on non-trivial work. Use judgment on trivial tasks.

### Rule 1 — Think Before Coding
State assumptions explicitly. If uncertain, ask rather than guess.
Present multiple interpretations when ambiguity exists.
Push back when a simpler approach exists.
Stop when confused. Name what's unclear.

### Rule 2 — Simplicity First
Minimum code that solves the problem. Nothing speculative.
No features beyond what was asked. No abstractions for single-use code.
Test: would a senior engineer say this is overcomplicated? If yes, simplify.

### Rule 3 — Surgical Changes
Touch only what you must. Clean up only your own mess.
Don't "improve" adjacent code, comments, or formatting.
Don't refactor what isn't broken. Match existing style.

### Rule 4 — Goal-Driven Execution
Define success criteria. Loop until verified.
Don't follow steps. Define success and iterate.
Strong success criteria let you loop independently.

### Rule 5 — Use Claude only for judgment calls
Use Claude for: classification, drafting, summarization, extraction.
Do NOT use Claude for: routing, retries, deterministic transforms.
If code can answer, code answers.

### Rule 6 — Token budgets are not advisory
Per-task: 20,000 tokens. Per-session: 100,000 tokens.
If approaching budget, summarize and start fresh.
Surface the breach. Do not silently overrun.

### Rule 7 — Surface conflicts, don't average them
If two patterns contradict, pick one (more recent / more tested).
Explain why. Flag the other for cleanup.
Don't blend conflicting patterns.

### Rule 8 — Read before you write
Before adding code, read exports, immediate callers, shared utilities.
"Looks orthogonal" is dangerous. If unsure why code is structured a way, ask.

### Rule 9 — Tests verify intent, not just behavior
Tests must encode WHY behavior matters, not just WHAT it does.
A test that can't fail when business logic changes is wrong.

### Rule 10 — Checkpoint after every significant step
Summarize what was done, what's verified, what's left.
Don't continue from a state you can't describe back.
If you lose track, stop and restate.

### Rule 11 — Match the codebase's conventions, even if you disagree
Conformance > taste inside the codebase.
If you genuinely think a convention is harmful, surface it. Don't fork silently.

### Rule 12 — Fail loud
"Completed" is wrong if anything was skipped silently.
"Tests pass" is wrong if any were skipped.
Default to surfacing uncertainty, not hiding it.

### Rule 13 - OWASP security review

Review **every** new and modified file (controller, Vue component, updated legacy links) for common vulnerabilities. This step must be re-run after any subsequent change request (e.g., adding features, fixing bugs, updating links) — not only on the initial pass.

### Checklist

- **CSRF on state-changing endpoints.** Any POST/PUT/DELETE controller that performs destructive or sensitive actions (delete, copy, anonymize, restore, status toggle, etc.) must validate a CSRF token. Use `$this->isCsrfTokenValid('intent_name', $token)` in the controller. Generate the token via `CsrfTokenManagerInterface::getToken('intent_name')`, return it in the data endpoint JSON, and include it as a hidden `_token` field in the Vue form submission.
- **Broken access control.** Verify that `#[IsGranted(...)]` on the controller matches the legacy page's access checks. If the legacy page allowed both admins and session admins, use the `Expression` form. Verify that **every** destructive action inside the controller re-checks the role (e.g., session admins should not be able to delete sessions they don't manage just because they can reach the endpoint). For non-admin roles, filter actionable entity IDs to only those the current user is authorized to manage.
- **SQL injection.** Never interpolate user input into DQL/SQL strings. Always use bound parameters (`:paramName` + `setParameter()`). The QueryBuilder already does this — verify no raw concatenation slipped in. Sort field values must use an allowlist mapping, never be passed directly into `orderBy()`.
- **XSS.** Vue's template syntax (`{{ }}`) auto-escapes by default. Verify no `v-html` is used with user-supplied data. If linking to legacy PHP pages with query params built from data, ensure values are not attacker-controlled HTML. Check that dynamic `:href` bindings only interpolate integer IDs or known-safe strings.
- **Open redirects.** If a controller builds a redirect URL using user-supplied or database values (e.g., a username), always `urlencode()` those values before embedding them in the URL. Verify that the Vue component does not use `window.location.href` with unsanitized query param values.
- **Mass parameter manipulation.** Verify that array parameters from the client (e.g., `sessionIds[]`) are cast to safe types (`array_map('intval', ...)`) before use. Verify that a non-admin user cannot supply IDs of entities they do not own/manage.

## Project Overview

Chamilo LMS — an open-source e-learning platform built on **Symfony 7.4** (PHP 8.3/8.4/8.5) with a **Vue 3.5** frontend. Uses **API Platform 4.2** for REST/GraphQL APIs, **Doctrine ORM 3.3** for persistence, and **Webpack Encore 5** for asset compilation.

## Common Commands

### Frontend
```bash
yarn install              # Install JS dependencies
yarn dev                  # Development build (Webpack Encore)
yarn watch                # Dev build with file watching
yarn build                # Production build
```

### Backend
```bash
composer install                              # Install PHP dependencies
php bin/console doctrine:migrations:migrate   # Run database migrations
php bin/console doctrine:fixtures:load        # Load test fixtures
php bin/console cache:clear                   # Clear Symfony cache
```

### Code Quality
```bash
composer phpcs              # Check PHP code style (ECS/Symfony standard)
composer phpcs-fix          # Auto-fix PHP code style
composer phpstan            # Static analysis (level 5)
composer psalm              # Type checking
```

### Testing
```bash
php bin/phpunit                                    # Run full test suite
php bin/phpunit tests/CoreBundle/Path/To/Test.php  # Run a single test file
php bin/phpunit --filter testMethodName             # Run a single test method
```

Tests use PHPUnit 9.6 with DAMA DoctrineTestBundle for transaction isolation. The test environment is configured via `.env.test` with `APP_ENV=test`.

### JavaScript Linting
```bash
npx eslint assets/          # Lint JS/Vue files
npx prettier --check .      # Check formatting
```

### Browser testing

It is possible to test the application through the web, as admin, by calling locally: http://my.chamilo.net with credentials admin/admin.

### Playwright (browser automation tests)

The browser-driven test suite uses **Playwright** with `playwright-bdd`, so `.feature` files stay plain Gherkin — only the step definitions are TypeScript. It replaced Behat, which has been removed entirely (see the note at the end of this section). This is the only browser-driven suite: all new coverage goes here.

- Feature files: `tests/playwright/features/*.feature` (organised by domain where it matters, e.g. `admin/`).
- Step definitions: `tests/playwright/steps/common.steps.ts` — all steps, one shared file.
- Config: `tests/playwright/playwright.config.ts`.

Run the main suite (excludes the install/seed scenarios below, and SpecialCase1):
```bash
yarn test:playwright
```

SpecialCase1 (`specialCase1PlatformSettings.feature` + `specialCase1Sessions.feature`, both tagged `@specialcase1`) runs **separately and single-threaded**, and is deliberately excluded from the command above:
```bash
yarn test:playwright:specialcase1
```
Three reasons it cannot share the parallel pool: `specialCase1Sessions` depends on session extra fields and a platform setting that `specialCase1PlatformSettings` creates, and only `--workers=1` plus `fullyParallel: false` guarantees the file order that satisfies that; it mutates ~100 platform settings, several of them globally UI-visible on every page (a cookie banner that intercepts clicks, header changes), which corrupts any file running concurrently; and its scenarios are `@long-scenario` (15-minute budget), which starves other files of workers. CI runs it as its own step after the main batch.

Run/debug interactively:
```bash
yarn test:playwright:ui
```

One-time environment setup, in this order, before any course-tool feature will pass (creates the fixed test users and the `TEMP`/`TEMPPRIVATE` courses most scenarios assume exist):
```bash
yarn test:playwright:seed                 # test users
yarn test:playwright:seed-course          # TEMP course
yarn test:playwright:seed-private-course  # TEMPPRIVATE course
yarn test:playwright:seed-settings        # settings some scenarios assume are enabled
```
The web installer itself is also covered (`yarn test:playwright:install`) — it's destructive (recreates the DB) and CI-only; never run it against a real environment.

**Stale-cache trap:** after editing a `.feature` file or anything in `tests/playwright/steps/`, force-regenerate the compiled specs before trusting a run — `playwright-bdd`'s own auto-regen isn't always reliable:
```bash
node_modules/.bin/bddgen --config=tests/playwright/playwright.config.ts
```

**When to write Playwright tests — mandatory rule:**
Every new feature and every new interface added to an existing feature **must** be accompanied by a `.feature` file (or additions to an existing one) that covers all user interactions: create, read, edit, and delete at minimum. The `add-feature-test` skill (`/add-feature-test`) automates this — it explores the live UI to confirm real selectors before writing steps, since static code reading gets them wrong often enough to matter.

- Place feature files in `tests/playwright/features/`, using a domain subfolder (e.g. `admin/`) where one already exists for that area.
- Form inputs in Vue dialogs/forms **must** have a `name` attribute so steps can target them by name.
- If an entity or page is accessible to more than one role, **run all scenarios once per role that has access**. Do not test only as admin if other roles can also interact with the feature. For access-restricted pages (e.g. admin-only), add a scenario verifying that non-privileged roles are denied access (redirected or do not see the management UI).
- Login steps available: `I am a platform administrator` (admin), `I am a teacher` (mmosquera), `I am a student` (acostea), `I am an HR manager` (ptook), `I am a student boss` (abaggins), `I am an invitee` (bproudfoot), `I am logged as "<username>"` (any other account, e.g. one just created in the scenario), `I am not logged` (explicit logout). Add a new named step to `common.steps.ts` whenever a test user with non-standard credentials is needed.
- Each feature file must be self-contained: it creates all data it needs and deletes it at the end, leaving the database in the same state it found it — except the shared, one-time fixtures above (test users, `TEMP`/`TEMPPRIVATE` courses), which are meant to persist for the whole suite.
- **Two SPA-timing traps that look identical to generic flakiness but have a definite root cause** — confirmed via `trace.zip`'s network log (not guessed), while stabilizing `createUser.feature`'s edit scenarios:
  1. **Clicking into a client-side route change (`router.push`/`<router-link>`) never fires a real navigation event.** `"wait ... for the page to be loaded"` (`page.waitForLoadState("domcontentloaded"/"networkidle")`) resolves immediately regardless of whether the SPA has actually swapped routes — a script that clicks an edit-row icon then immediately checks `page.url()` after that wait can still show the OLD page's URL. Worse, if the old and new page happen to share a field `name` (e.g. a list page's own "Advanced search" filter having the same `name="email"` as the destination edit form), the next `"I fill in ..."` step silently fills the WRONG page's field instead of erroring. Fix: wait for an element unique to the destination page instead — `"I wait up to N seconds for the element {string} to appear"` with a selector/text only the target page has.
  2. **A component whose `onMounted` async-fetches existing data (any edit form) has a fill-before-load race.** The static template (headings, labels) renders immediately on mount, before the fetch resolves; if a test fills a field in that window, the fetch's `.then()` handler overwrites the typed value with the loaded one moments later, and the eventual save submits the ORIGINAL data — no error, because nothing was actually wrong with it. Symptom: a "wrong value should be rejected" scenario times out waiting for a validation message that never appears, because the request that went out was valid. Confirmed by inspecting the actual submitted `multipart/form-data` in the trace's network log, not by re-reading the frontend code. Fix: assert the field already holds its expected LOADED value (`"the field {string} should have value {string}"`) before overwriting it — this doubles as proof the load finished.

**Behat is gone.** `tests/behat/`, `.github/workflows/behat.yml` and the `behat/*` composer dependencies have all been deleted — Playwright is the only browser-driven suite. Do not add Behat scenarios, and do not restore the directory.

The old scenarios remain in git history and are still useful when writing new coverage for an area Behat once covered: `git show 98c77757ea6:tests/behat/features/<name>.feature`, or `git ls-tree -r --name-only 98c77757ea6 tests/behat` for the full list (84 files at that commit). Treat anything found there as a **hint** of intended scenarios — never as a source of truth for selectors, since field names, button labels and dialog types have all rotted since. Verify everything against the live app.


## Architecture

### Backend Structure (`src/`)

Three Symfony bundles:
- **CoreBundle** — Main application logic (~215 entities, controllers, services, API resources, migrations)
- **CourseBundle** — Course-specific entities and logic (entities prefixed with `C`, e.g., `CDocument`, `CCalendarEvent`)
- **LtiBundle** — LTI (Learning Tools Interoperability) integration

Standard Symfony patterns within each bundle: `Entity/`, `Repository/`, `Controller/`, `Service/`, `EventListener/`, `EventSubscriber/`, `Form/`, `Command/`.

API Platform resources are defined via PHP attributes on entities. Custom API resources live in `src/CoreBundle/ApiResource/`. GraphQL queries/mutations are in `src/CoreBundle/GraphQL/`.

Database migrations are in `src/CoreBundle/Migrations/Schema/V200/`.

### Legacy Code

`public/main/` contains legacy PHP code (pre-Symfony). `public/legacy.php` bootstraps legacy class autoloading via `composer.json` classmap entries. Legacy code is gradually being migrated to Symfony.

### Frontend Structure (`assets/`)

Vue 3 SPA in `assets/vue/`:
- **Entry point**: `main.js` — bootstraps Vue app with Pinia + Vuex stores, Vue Router, i18n, PrimeVue, and registers CRUD store modules per entity
- **Router**: `router/index.js` — imports route modules per feature domain (documents, messages, courses, etc.)
- **Views**: `views/` — page components organized by feature (e.g., `views/documents/`, `views/message/`)
- **Components**: `components/` — reusable components, `components/basecomponents/` for shared UI primitives
- **Composables**: `composables/` — Vue 3 composition API hooks
- **Services**: `services/` — API client wrappers using Axios; `services/baseService.js` provides generic CRUD methods returning Hydra-format collections
- **Store**: `store/` — Pinia stores (`securityStore.js`, `cidReq.js`, etc.) plus Vuex `modules/crud.js` for entity CRUD operations
- **GraphQL**: `graphql/queries/` — GraphQL query definitions used via Apollo Client

The API returns JSON-LD/Hydra format (`hydra:member`, `hydra:totalItems`, etc.).

CSS uses **Tailwind CSS 3.4** with SCSS. Legacy pages also use Bootstrap 5 and jQuery.

### Key Patterns

- **Entity IRIs**: The frontend references entities by their API Platform IRI (e.g., `/api/users/1`) rather than raw IDs
- **CidReq**: Course/session/group context mirrors the backend's `CidReqListener` (see "Contextual roles" below) via the `useCidReqStore` Pinia store (`assets/vue/store/cidReq.js`). The router's global `beforeResolve` guard (`assets/vue/router/index.js`) reads `cid`/`sid`/`gid` from `route.query` on every navigation and calls `setCourseAndSessionById()`, which fetches the full `course`/`session` **API resources** (not raw IDs) and re-fetches the `ROLE_CURRENT_COURSE_*` contextual roles whenever the `cid:sid:gid` triple changes. Components read IDs defensively — `cidReqStore.course?.id ?? route.query.cid` — since the store may not be populated yet on first render.
- **Resource system**: Content entities extend `AbstractResource` and use Chamilo's resource node/link system for access control and file management
- **Services pattern (frontend)**: Each entity type has a service file that wraps `baseService` to interact with a specific API endpoint

## Configuration

- Symfony config: `config/packages/` (doctrine, security, api_platform, messenger, etc.)
- Environment variables: `.env` (copy from `.env.dist`), `.env.test` for tests
- Webpack: `webpack.config.js` — entry points for `vue` (main SPA), `legacy_*` (legacy pages), style entries
- ESLint: `eslint.config.mjs` (flat config, Vue 3 + Prettier)
- Prettier: `.prettierrc.json` (120 width, no semicolons, single attribute per line)
- PHP code style: `ecs.php` (Symfony standard + PSR-12)
- PHPStan: `phpstan.neon` (level 5)

## Coding Conventions

### PHP (enforced by `vendor/bin/ecs check` and `vendor/bin/psalm`)

- `declare(strict_types=1);` at the top of every file.
- Yoda conditions: `'all' === $listType`, not `$listType === 'all'`.
- String concatenation with **no spaces** around `.`: `'%'.$keyword.'%'`.
- Short array syntax `[]`, trailing commas in multiline constructs.
- No useless `else` — use early returns. No useless `return` at end of void methods.
- No empty or superfluous phpdoc (tags that duplicate type hints).
- Declare `void` return type on methods that return nothing.
- Import classes via `use` statements — no inline `\Fully\Qualified\Name` in code.
- Ordered class elements: constants → properties → constructor → public → protected → private.
- Modern type casting: `(int)` not `intval()`.
- `setParameter()` in QueryBuilder infers the binding type automatically for integers, string/int arrays, `DateTime` instances and managed entities, so passing those directly is **functionally** correct. However, inferring the type of an **entity object** forces Doctrine to resolve its metadata to extract the identifier — Psalm's `QueryBuilderSetParameter` flags this as a performance cost. So: **prefer passing the scalar identifier** of an entity, e.g. `->setParameter('course', (int) $course->getId())` (Core entities) or `(int) $entity->getIid()` (CourseBundle entities). Once the value is a cast scalar, **omit** the 3rd-arg type — inference is trivial and `Types::INTEGER` is redundant. Reserve the explicit 3rd arg (`Types::*` / `ParameterType::*`) for when you need a binding type different from the inferred one (e.g. forcing `Types::STRING` on a numeric string) or for genuinely non-scalar values. Note: some existing code still passes `..., Types::INTEGER` after a cast — harmless legacy redundancy, not a pattern to copy.
- All methods must have return types; all parameters must have type hints.

### JavaScript / Vue

- No semicolons, 120 char line width, Prettier formatting
- Vue components use Composition API with `<script setup>` pattern preferred
- Frontend route files mirror the view directory structure (one route file per feature domain)

### General

- CourseBundle entities are prefixed with `C` (e.g., `CDocument`, `CBlog`, `CForumPost`)
- API responses use JSON-LD/Hydra vocabulary

## Discovered Patterns

### Vue SPA routing — the two-URL rule

The Vue SPA is **not** a catch-all. Every URL the browser can navigate to directly must be registered in **two** places:

1. `src/CoreBundle/Controller/IndexController.php` — as a `#[Route(...)]` attribute on `index()`, so Symfony serves the HTML shell that boots the SPA.
2. `assets/vue/router/admin.js` (or the relevant domain router file) — so the Vue Router handles it client-side.

Data/API endpoints **must use a different URL** from the SPA route they serve. Convention: append `-data` (e.g., SPA at `/skill/ranking`, data endpoint at `/skill/ranking-data`). Failing to split these causes Symfony to return raw JSON when the user navigates directly to the page.

### Translation pipeline

`assets/locales/*.json` files are **generated** by the command:
```bash
php bin/console chamilo:update_vue_translations
```
The pipeline is: `translations/messages.pot` → `translations/messages.en_US.po` (and other `.po` files) → `assets/locales/*.json`.

- `assets/locales/en_US.json` is the **master key list** — every key that should exist in all languages must appear here.
- If a key is missing from `messages.pot` / `messages.en_US.po`, the raw English key is returned for all languages (no translation possible).
- Vue `{0}` / `{1}` placeholders are stored as `%s` / `%d` in `.po` files; the command converts them automatically.
- `t()` from `useI18n()` and `$t()` in templates behave identically when `legacy: false` is set (which it is). If a key returns untranslated, it is missing from the locale files — not a code bug.
- **Placeholders in `assets/locales/en_US.json` values must use `{0}`, `{1}`, etc.** (vue-i18n positional syntax) — never `%s` or `%d`. In the corresponding `.po`/`.pot` entries, use `%s` / `%d`; the generation command converts them to `{0}` / `{1}` automatically. Call site: `t("key", [value])` with an array, not `t("key", { count: value })` (named params).
- **`chamilo:update_vue_translations` does NOT add new keys** — it only re-syncs whatever keys already exist in `assets/locales/en_US.json`, pulling each one's translation from the `.po` catalog for every other locale. Adding a brand-new key that's already an existing legacy string (e.g. reusing `FormValidator`'s own labels in a new Vue component) still requires manually adding it to `en_US.json` yourself first — only then does re-running the command populate the other ~70 locale files with the real (already-translated) value instead of leaving the key entirely absent there.
- **Running the command re-serializes *every* locale file with `JSON_UNESCAPED_UNICODE`,** turning any previously `\uXXXX`-escaped accented character into a raw UTF-8 character across the *entire* file — same content, different bytes. If the files hadn't been regenerated in a while, this alone produces a multi-thousand-line diff across ~70 files that has nothing to do with the actual change. Don't ship that diff: capture just the new key(s)' translated values from the regenerated files, `git checkout --` the untouched locale files back to their original encoding, then hand-append only the new key/value lines (matching the pattern already used for the existing keys) so the diff stays scoped to what actually changed.

### Doctrine / DQL gotchas

- **DQL uses entity property names, not column names.** Always read the entity file before writing a query. Example trap: `SkillRelGradebook` has property `$gradeBookCategory` (capital B), not `gradebookCategory`.
- **Do not order by SELECT aliases** in DQL (`orderBy('myAlias', 'DESC')` is unreliable). Use the full expression: `orderBy('COUNT(sru.id)', 'DESC')`.
- **`USER_SOFT_DELETED = -2`** (defined in `public/main/inc/lib/api.lib.php`). The value `-1` is `INACTIVE_AUTOMATIC` — a different state. The User entity constants are `SOFT_DELETED = -2`, `INACTIVE_AUTOMATIC = -1`, `INACTIVE = 0`, `ACTIVE = 1`.
- DQL has no `UNION`. Replace with two separate queries merged in PHP via `array_merge`.

### Adding a new platform setting

Adding a variable to a `*SettingsSchema.php` file (e.g. `SecuritySettingsSchema.php`) only defines its default value and form type — that alone is **not enough** for the setting to reach existing installations:

- **Fresh installs** pick it up automatically: `public/main/install/index.php` calls `SettingsManager::installSchemas()`, which reads every registered `*SettingsSchema` class and inserts a row per setting.
- **Existing/upgraded installations never run this** — `doctrine:migrations:migrate` is the only thing that runs on upgrade, and it does not load fixtures or re-run `installSchemas()`. Without a migration, the setting reads back its schema default via `SettingsManager::getSetting()` (so code doesn't crash), but there is no row in `settings`/`settings_current`, and it is **invisible in Administration > Settings** until one is added.

So a new setting needs both:

1. An entry under the right category key in `SettingsCurrentFixtures::getNewConfigurationSettings()` (`src/CoreBundle/DataFixtures/SettingsCurrentFixtures.php`) — `['name' => ..., 'title' => ..., 'comment' => ...]`.
2. A fixtures-upsert migration that re-runs the same logic as `Version20250926174000.php`: it reads every setting from `SettingsCurrentFixtures` plus the schema-declared defaults (`SettingsManager::getSchemas()`), `UPDATE`s category/title/comment for settings that already have a row (never touching `selected_value` — an admin's configured value survives being re-synced), and `INSERT`s a row (using the schema default) for ones that don't yet exist. Its `down()` is intentionally a no-op — this is a one-way sync, not meant to be reversed.

**Only one such migration is needed per version** (i.e. per `Schema/V210/` directory, or whichever is current) — **check that directory first**:
- If one already exists, **don't create a second one**. Just add the fixture entry from step 1; whoever needs the DB updated re-executes the existing migration manually (`doctrine:migrations:execute '<FQCN>' --up`), since Doctrine won't automatically re-run a migration already marked as executed. Developers/ops are expected to know this and re-run it after pulling changes that add settings.
- Only create a **new** migration (copy `Version20250926174000.php`'s body verbatim into a freshly dated `Version<timestamp>` class, same namespace) if the current version's directory doesn't have a fixtures-upsert migration yet.

`AbstractMigrationChamilo::addSettingCurrent()` (`src/CoreBundle/Migrations/AbstractMigrationChamilo.php`) exists as a single-setting-insert helper but has zero callers repo-wide — the fixtures-upsert migration above is the actual convention to copy, not that helper.

### Legacy link locations

When replacing a legacy PHP page, search these locations for old links:
- `src/CoreBundle/Controller/Admin/IndexBlocksController.php` — admin panel block links
- `public/main/template/default/` — legacy Twig templates
- `assets/vue/components/` — Vue components linking to legacy pages (e.g., `social/MySkillsCard.vue`)
- `public/main/inc/ajax/model.ajax.php` — jqGrid AJAX allowlists; remove the action from both the allowlist arrays and the `case` blocks

### Porting `api_get_setting()` calls verbatim

Copy the exact string a legacy page passes to `api_get_setting()` — never "simplify" it by dropping what looks like a redundant category prefix (e.g. `registration.user_hide_never_expire_option` → `user_hide_never_expire_option`). `SettingsManager::validateSetting()` only resolves a bare (no-dot) name automatically when that exact string is listed in `getVariablesAndCategories()`; most settings are **not** listed there and throw `InvalidArgumentException` (`Parameter must be in format "category.name"`) at runtime — a bug that unit/functional tests catch immediately (a bare `api_get_setting()` call that never throws in a real request is not proof every other bare call is safe too). A few settings genuinely are registered bare in that resolver (e.g. `limit_session_admin_role`, `login_is_email`, `account_valid_duration`) — matching the legacy page's own exact call is what tells you which is which, not guessing from the pattern.

### Native input `type` attributes can silently defeat server-side validation

Don't add `type="email"` (or other browser-validated types) to a `BaseInputText` when the field's validation is meant to happen server-side with a custom message, as legacy `FormValidator` pages always do (`addEmailRule()`, `addRule(..., 'username')`, etc.). The browser's own native format check on `type="email"` fires on any non-empty value regardless of `required`, and **silently blocks the form's native submit event before Vue's `@submit.prevent` handler ever runs** — no request is sent at all (confirmed via the network trace showing no request whatsoever), so the server's own validation message can never be returned. Leave the field as the default `type="text"` and let the existing server-side check own the error message, matching the legacy page's own behavior exactly.

### `User::getRoles()` is not a valid `roles[]` form payload

`User::getRoles()` returns Symfony's full effective role set, which includes implicit base
roles (`ROLE_USER`) that are **not** selectable entries in `UserManager::getAllowedRoleOptionsForUserForm()`.
Feeding `getRoles()` straight back into a "Roles" multiselect's pre-filled value (e.g. an edit
form) and resubmitting it verbatim fails `UserManager::areRolesAllowedInUserForm()` on save —
the whole role set is rejected because one entry (`ROLE_USER`) isn't in the allowlist, and the
user gets a generic 403 "Error" that looks like an access-control problem, not a validation one.
Confirmed live: a real edit-form save reproducibly 403'd until fixed.

Filter through the same canonical-role mapping the legacy page itself used before sending roles
to the frontend:
```php
$optionKeyByCanon = [];
foreach (UserManager::getAllowedRoleOptionsForUserForm() as $optKey => $label) {
    $optionKeyByCanon[api_normalize_role_code((string) $optKey)] = (string) $optKey;
}
$selected = [];
foreach (array_map('api_normalize_role_code', $user->getRoles()) as $canon) {
    if (isset($optionKeyByCanon[$canon])) {
        $selected[] = $optionKeyByCanon[$canon];
    }
}
```
Never send `$user->getRoles()` directly into a data endpoint's `roles` field when the frontend
round-trips it back into the same multiselect on save.

### Prod cache must be rebuilt after adding a *new* controller class, not just after editing one

Extends the constructor-change gotcha above: on a box running `APP_ENV=prod` (check `.env`
before assuming `dev`), a **brand-new** `#[Route]`-attributed controller class is invisible to
the compiled router until `cache:clear --env=prod` (+`cache:warmup` +`chmod -R 777 var/cache/prod`
if `claude` and `www-data` both need to write there) runs — hitting the new route in the
meantime doesn't 404 or 500, it silently falls through to whatever catch-all route matches next
(e.g. `IndexController`'s `/admin/{vueRouting}` SPA-shell route), returning HTTP 200 with the
wrong body (the SPA's HTML shell instead of the controller's JSON). This is easy to
misdiagnose as a frontend bug (the JS looks like it "didn't get" the expected fields) when the
real cause is a stale prod route cache. Symptom to watch for: a GET that should return JSON
instead returns an HTML document starting with `<!DOCTYPE html>`.

### Many-to-many "assign X to Y" migrations — dual-list pattern

Legacy admin tools that assign many-to-many relations (users↔URLs, courses↔URLs, groups↔sessions, etc.) typically ship as **two separate pages per relation**: an "edit" page (dual listbox scoped to one parent, with a legacy single-vs-multiple AJAX-search toggle) and an "add" page (bulk cross-assign many children × many parents at once). When migrating this shape to Vue:

- **Drop the single/multiple AJAX-search toggle.** A single searchable dual-listbox (client-side keyword filter over an already-loaded list) covers both legacy modes with less UI. See `assets/vue/components/accessurl/AccessUrlDualList.vue` for the reference implementation — props are generic (`available`/`assigned` arrays of `{id, label}}`, events `add`/`remove`/`add-all`/`remove-all`), so the parent view owns the actual entity shape and mapping.
- **Keep the bulk cross-assign screen as a second tab**, not a separate page — it is a genuinely different capability (many×many at once vs. one-parent-at-a-time), not just a UI variant. See `assets/vue/components/accessurl/AccessUrlBulkAssign.vue` (two `BaseMultiSelect`s + add/remove buttons + `useConfirmation`) used inside a PrimeVue 4 `Tabs`/`TabList`/`Tab`/`TabPanels`/`TabPanel` (imported directly from `primevue/tabs` etc. — no `Base*` wrapper exists for tabs in this project).
- **Reuse exact legacy button/confirm-dialog strings as props**, not generic ones — the per-domain wording ("Add users to selected URLs" vs "Add courses to selected URLs") already exists in `messages.en_US.po`; inventing a generic label means adding brand-new translation keys for no reason.
- Consolidate N legacy page-pairs into fewer Vue pages with tabs where it reduces file count without losing functionality (e.g. 9 legacy files → 5 Vue pages for the access-URL admin tools), but confirm the consolidation plan with the user first when it's a non-trivial restructuring — see `AccessUrlManage.vue` / `AccessUrlUsers.vue` / `AccessUrlCourses.vue` / `AccessUrlUserGroups.vue` / `AccessUrlCourseCategories.vue` as the reference set.
- When a shared entity's `#[ApiResource]` uses one `security:` string for all operations but the legacy pages you're migrating enforced a **stricter** role for the mutating ones (e.g. legacy required `ROLE_GLOBAL_ADMIN` to delete, but the resource only required `ROLE_ADMIN`), split into an explicit `operations:` array so `Get`/`GetCollection` keep the looser resource-level default (other read consumers may depend on it) while `Post`/`Put`/`Patch`/`Delete` get an operation-level `security:` override matching the legacy check. Flag this to the user before changing it — it's a shared entity, not a file scoped to the page being migrated.

### Base components (forms, tables, buttons, dialogs)

**Authoritative reference: the `use-base-components` skill** (`.claude/skills/use-base-components/SKILL.md`).
It auto-invokes whenever you create or edit a Vue interface and documents every `Base*`
component's API, the native-element → `Base*` mapping, the label-translation rule, and which
components are globally registered (so you don't import them). When building UI, follow it.

Quick essentials that apply everywhere:
- Tables → `BaseTable` (wraps PrimeVue `DataTable`; `Column` is global, no import needed).
- Buttons → `<BaseButton>` instead of plain `<button>`. Props: `type`, `icon` (MDI name without
  `mdi-`), `only-icon` + `size="small"` for icon-only row actions.
- Every non-global component used in the template **must** be imported in `<script setup>` —
  a missing import does NOT fail the build, only warns at runtime.

CRUD color convention (`type` prop):
- Create/add/save/import → green → `type="success"`
- Read/view/export/list → blue → `type="primary"`
- Update/edit/configure/move → orange → `type="secondary"`
- Delete/disable/remove → red → `type="danger"`
- Cancel/dismiss → gray → `type="plain"`
- Buttons are for actions only — never style a non-action link as a button.

Table row action convention:
- Edit: `type="secondary-text"`, `icon="pencil"`, `only-icon`, `size="small"`
- Delete: `type="danger-text"`, `icon="delete"`, `only-icon`, `size="small"`
- Never put `ch-tool-icon` on icons inside a button — the icon inherits the button's text colour automatically.

### Spacing

8-point grid (multiples of 8px). Fine adjustments of 4/6/12px are acceptable.

### Icons (MDI)

Always use `<BaseIcon>` instead of a plain `<icon>` element unless there is a clear reason not to. For standalone decorative icons outside of buttons, prefer `<BaseIcon icon="{name}" />` over the raw `<span class="mdi mdi-{name} ch-tool-icon" />` pattern. The `ch-tool-icon` class applies blue colouring — use it **only outside of buttons**. Never add it to icons inside `<button>` or `BaseButton` — the icon inherits the button's own text colour.

Common icon names:
- Edit: `mdi-pencil`, Delete: `mdi-delete`, Add: `mdi-plus-box`, Search: `mdi-magnify`
- Copy: `mdi-text-box-plus`, Configure: `mdi-hammer-wrench`, Info: `mdi-information`
- Subscribe users: `mdi-account-multiple-plus`, Add courses: `mdi-book-open-page-variant`
- Export CSV: `mdi-file-delimited-outline`, Export PDF: `mdi-file-pdf-box`
- Visible/invisible: `mdi-eye` / `mdi-eye-off`
- Active/inactive: `mdi-toggle-switch` / `mdi-toggle-switch-off`

### Status badges

- Planned/info: `bg-blue-100 text-blue-700`
- Active/success: `bg-green-100 text-green-700`
- Finished/neutral: `bg-gray-100 text-gray-700`
- Error/cancelled: `bg-red-100 text-red-700`

### Delete confirmations

Always use the `useConfirmation` composable (`assets/vue/composables/useConfirmation.js`) instead of the native `confirm()` dialog. It wraps PrimeVue's confirm service and is already i18n-aware.

```js
import { useConfirmation } from "../../composables/useConfirmation"
const { requireConfirmation } = useConfirmation()

function confirmDelete(item) {
  requireConfirmation({
    message: t("Are you sure you want to delete this item?"),
    accept: () => performDelete(item),
  })
}
```

The `title` defaults to `t("Confirmation")` and `message` defaults to `t("Please confirm your choice")` if omitted. The `reject` callback is optional.

### Breadcrumbs

Every new Vue page must have a breadcrumb. The breadcrumb is built automatically by `assets/vue/components/Breadcrumb.vue` from the route tree — no code is needed inside the view itself. What is required:

1. Set `meta: { showBreadcrumb: true, breadcrumb: "Page title" }` on the route entry in the relevant router file (e.g. `assets/vue/router/admin.js`).
2. If the page lives under a new top-level path (not `/admin/*`), add the path prefix to the `whitelist` array in `buildManualBreadcrumbIfNeeded` inside `Breadcrumb.vue`, and add a corresponding `if` block that pushes an "Administration" crumb (linked to `AdminIndex`) followed by the page label. See the `/admin/*` case as a reference.

Admin pages should always show: **Administration** (linked) / **Page title** (plain text).

### Forms

Use `Base*` form components, not native `<input>`/`<select>`/`<textarea>` — see the
`use-base-components` skill for the mapping and each component's API. Group fields with
`flex gap-4 items-end`. Only fall back to a native element when a third-party slot requires one
(e.g. multi-value checkbox arrays bound to a `v-model` array).

### SPA navigation — prefer router-links over plain links

When creating or editing Vue interfaces, always prefer client-side routing over plain `<a href>` to avoid full-page reloads:

- **`<BaseButton :route="{ name: 'RouteName', params: { id } }">`** for button navigation within the SPA.
- **`<router-link :to="{ name: 'RouteName', params: { id } }">`** for non-button link text.
- **`router.push({ name: 'RouteName' })`** in script logic (after async operations, etc.).
- Only use **`:to-url`** on `BaseButton` or plain `<a href>` when the target is **outside the SPA** (e.g., a Twig controller, a file download, a legacy PHP page that hasn't been migrated yet).

### PHP performance — session locking

Whenever a controller or script no longer needs to read or write session data, call `session_write_close()` immediately to release the session file lock. Holding the lock blocks concurrent requests from the same browser (e.g. parallel API calls from `Promise.all` in Vue).

```php
// As soon as session reads/writes are done:
session_write_close();
```

This applies to all legacy PHP scripts and any Symfony controller that accesses the session early and then performs slow work (DB queries, file I/O, external HTTP calls).


### Migrating a legacy PHP page to Vue

See `.claude/commands/legacy-to-vue.md` for the step-by-step checklist, invokable as `/legacy-to-vue` in Claude Code.

### Contextual roles in `#[ApiResource]` security expressions

Course/session/group membership is exposed to API Platform `security:` expressions as six contextual roles, all defined in `ResourceNodeVoter`:

| Role                                  | Granted when the user is…                                              |
|---------------------------------------|------------------------------------------------------------------------|
| `ROLE_CURRENT_COURSE_STUDENT`         | subscribed to the current course (`course_rel_user`)                   |
| `ROLE_CURRENT_COURSE_TEACHER`         | a teacher of the current course                                        |
| `ROLE_CURRENT_COURSE_SESSION_STUDENT` | a student of the current course inside the current session             |
| `ROLE_CURRENT_COURSE_SESSION_TEACHER` | a general coach of the session or a course coach in the current course |
| `ROLE_CURRENT_COURSE_GROUP_STUDENT`   | a member of the current group                                          |
| `ROLE_CURRENT_COURSE_GROUP_TEACHER`   | a tutor of the current group, or the teacher of its parent course      |

These roles **only exist for the current request**. They are computed and published in two places by `CourseContextRoleListener` (`src/CoreBundle/EventListener/CourseContextRoleListener.php`, priority 5) **after** `CidReqListener` (priority 6) resolves `cid`/`sid`/`gid`:

1. `User::$temporaryRoles` — visible to `Security::getUser()->getRoles()` and `ResourceNodeVoter::hasContextRole()`.
2. The security token's `getRoleNames()` — visible to `is_granted()` and the `RoleHierarchyVoter`.

The relationship logic lives in `CourseAccessResolver` (`src/CoreBundle/Security/CourseAccessResolver.php`), a pure service consumed by the listener. **Voters must never call `$user->addRole(ROLE_CURRENT_COURSE_*)`** — that pattern was removed in #8486 because Voter side-effects break Symfony's contract (non-deterministic order, short-circuit evaluation, etc.).

#### When the contextual-role model applies (scope)

The contextual-role model governs API operations that act **inside a course tool** — the request carries `cid`/`sid`/`gid` and the resource is course-scoped content. It is **not** a blanket rule for every entity:

- **`CourseBundle` `#[ApiResource]` entities** — applies to **all except `CCalendarEvent`**, which is authorized by its dedicated `CCalendarEventVoter` (creator / collective-subscription ownership, not course context).
- **`CoreBundle` `#[ApiResource]` entities** — **review case by case.** Many are not course-scoped (users, messages, social posts, user relations, sessions) and are governed by their own dedicated voters; only apply the contextual-role patterns to entities that genuinely operate inside a course tool.
- Any resource with a **dedicated voter** (e.g. `CCalendarEventVoter`, `UsergroupVoter`, `SessionVoter`) keeps that voter as the authority — do not bolt contextual-role expressions on top of it.

#### Role hierarchy

`config/packages/security.yaml` declares one-way implications **within each axis**:

```
COURSE_TEACHER ─implies─> COURSE_STUDENT
SESSION_TEACHER ─implies─> SESSION_STUDENT
GROUP_TEACHER ─implies─> GROUP_STUDENT
```

There is **no implication between axes**. A base-course teacher does NOT automatically get `SESSION_STUDENT`. Admins get all three `_TEACHER` variants via `ROLE_ADMIN`, which transitively grants all six.

#### Canonical `security:` patterns

Use the matrix below when writing or migrating an `#[ApiResource]` operation that receives `cid` (and optionally `sid`/`gid`) as **query parameters** (not body — `CidReqListener` does not parse bodies):

```php
// Get / Put / Patch / Delete on a single resource that extends AbstractResource:
// prefer object-level checks — they are more granular than role-level.
new Get(security: "is_granted('VIEW', object.resourceNode)"),
new Put(security: "is_granted('EDIT', object.resourceNode)"),
new Delete(security: "is_granted('DELETE', object.resourceNode)"),

// GetCollection / download / search — any member of the course or session:
security: "is_granted('ROLE_CURRENT_COURSE_STUDENT')
       or is_granted('ROLE_CURRENT_COURSE_SESSION_STUDENT')"

// Post / batch write — only teachers of the current course or session:
security: "is_granted('ROLE_CURRENT_COURSE_TEACHER')
       or is_granted('ROLE_CURRENT_COURSE_SESSION_TEACHER')"
```

`CDocument` (`src/CourseBundle/Entity/CDocument.php`) is the canonical reference — see lines 50-300 for examples of every pattern above.

#### Caveats

- **Body-only `cid` doesn't work.** `CidReqListener` reads `Request::get('cid')`, which sees query params and route attributes but not request bodies. Endpoints that pass `cid` only inside the JSON body need either an extra `cid` query param (preferred) or post-security validation in a `StateProcessor`.
- **`Post` (create) can't use object-level checks.** On create the `resourceNode` does not exist yet, so `is_granted('CREATE', object)` / `object.resourceNode` fails closed. Gate creation with the contextual teacher roles through an operation-own `security:` (not `securityPostDenormalize`), which also avoids inheriting a resource-level `security:` that may omit `SESSION_TEACHER` and would otherwise block session teachers. `CToolIntro` is the reference.
- **Output format negotiation runs before security.** Endpoints with binary `outputFormats` (`zip`, `bin`) reject the request with 406 if the `Accept` header is `application/ld+json`. Regression tests must send `Accept: application/zip` (or the matching MIME type) to reach the security gate.
- **The skill `/migrate-contextual-roles <Entity>`** automates this migration for a single entity, including Vue-caller compatibility checks and lint/test runs.

### Student view and "may this user edit here"

**The student view is one platform-wide session state, never a request parameter.** It lives in the
`studentview` session key, and exactly two places write it: `GET /toggle_student_view`
(`IndexController`, role-checked) and `LegacyListener`, which interprets an `isStudentView` query
parameter **only on non-API requests**. That guard matters: the `api` firewall shares the main
session, so without it a plain `GET /api/...?isStudentView=true` would switch the whole browsing
session from any origin, with no role check. No `#[ApiResource]` operation declares the parameter and
no provider reads it — if you find yourself adding it back, you are re-opening that hole.

Readers use `StudentViewHelper::isActive()`. There is no per-course variant: 1.11.x has no
equivalent, and the observable behaviour is global.

**Editing rights go through `IsAllowedToEditHelper::check()`** (`src/CoreBundle/Helpers/`), the port
of `api_is_allowed_to_edit()`. It answers admin and session-admin rights, the course teacher, the
session coach with `allow_coach_to_edit_course_session`, `Session::READ_ONLY`,
`session_courses_read_only_mode`, and the student view — so a provider that calls it needs none of
that itself. Pass `course:`/`session:` when you already resolved them.

Two rules that are easy to get wrong:

- **Never route a read gate through it.** `check()` returns `false` in the student view, but a
  teacher must still *see* a tool they cannot currently change, and must still reach an unpublished
  resource to preview it. Use `UserHelper::isTeacherOfCurrentCourse()` (the role alone) for those, or
  `check(checkStudentView: false)` when the caller wants the raw permission — the exercise runtime is
  the reference.
- **It deliberately deviates from 1.11 inside a session.** The legacy function discarded the course
  teacher there and returned only the coach term, which was safe because entering a session demoted
  the base-course teacher; `CourseAccessResolver` does not. Reproducing it would deny every
  base-course teacher in every tool. `IsAllowedToEditHelperTest` pins this.

**Tool-specific rules compose on top, in an injectable helper — not in a trait that takes services
as arguments.** `SurveyHelper`, `WikiHelper`, `CourseDescriptionHelper` and `CourseProgressHelper`
are the pattern: they inject what they need and add only what the shared helper cannot know
(`survey.extend_rights_for_coach_on_survey`, the wiki's group tutor and DRH terms, per-tool course
settings), gating the whole expression once by the student view. Where a tool's rule turned out to be
pure delegation (Gradebook, Forum, exercise authoring) no helper exists at all: call `check()`
directly.

Out of scope on purpose: `AnnouncementAccessHelperTrait`, whose coach rule uses
`announcement.allow_coach_to_edit_announcements` rather than the generic session setting, and which
also grants students through a course setting — routing it through `check()` would swap one
administrator setting for another silently.

**Frontend:** the state is read from `platformConfig.isStudentViewActive`, and any view rendering
server-computed permissions must refetch on the toggle via the `useStudentViewRefresh` composable
(`assets/vue/composables/`). Never put `isStudentView` in an API call or a Vue route query. The one
exception is navigation into the learning path runtime, which signals intent in the URL and turns it
into an explicit `/toggle_student_view` call: a lesson embeds content from other tools, so the state
has to be in the session where each of them reads it.

### Securing a per-user owned `#[ApiResource]` (Voter + Extension + Processor)

For **CoreBundle** resources that are **not** course-scoped but are **owned by one user** (e.g. `PushSubscription`, personal tokens, per-user settings rows), do **not** try to express ownership with `security: "... and object.getUser() == user"`. That expression returns **403** (leaks the row's existence), silently blocks admins unless you also `or is_granted('ROLE_ADMIN')`, and duplicates the rule across operations. Each concern has its own idiomatic tool — **an API Platform Voter acts on a single item only**, so split the work three ways:

| Concern                  | Operations                         | Tool                                                                                | Why                                                                                                         |
|--------------------------|------------------------------------|-------------------------------------------------------------------------------------|-------------------------------------------------------------------------------------------------------------|
| Per-object authorization | `Get` / `Put` / `Patch` / `Delete` | **Voter** (`is_granted('VIEW'\|'EDIT'\|'DELETE', object)`)                          | Object exists; voter is reusable from non-API code too                                                      |
| Collection scoping       | `GetCollection`                    | **`QueryCollectionExtensionInterface`** in `src/CoreBundle/DataProvider/Extension/` | A voter can't filter a collection; an extension adds a `WHERE` so foreign rows are never selected           |
| Create / mass-assignment | `Post`                             | **Processor** forcing `$data->setUser($currentUser)`                                | On create no object exists yet, so no voter can run — forcing the owner is the only mass-assignment defense |

**Voter** — standard Symfony voter on the entity, e.g. returns `$subscription->getUser() === $user || $security->isGranted('ROLE_ADMIN')`. Reference dedicated voters already in the repo: `CCalendarEventVoter`, `UsergroupVoter`, `SessionVoter`. Wire it on the item operations with `security: "is_granted('DELETE', object)"`.

**Collection extension** — a `QueryCollectionExtensionInterface` in `src/CoreBundle/DataProvider/Extension/` that adds the ownership `WHERE` on the root alias, with an admin bypass. API Platform runs every registered collection extension (including the built-in Filter / Order / Pagination) automatically, so you only add your `WHERE` — no need to re-apply those extensions by hand. Canonical reference: `SessionRelUserExtension` (`src/CoreBundle/DataProvider/Extension/SessionRelUserExtension.php`):

```php
final class XExtension implements QueryCollectionExtensionInterface
{
    public function __construct(private readonly Security $security) {}

    public function applyToCollection(QueryBuilder $qb, QueryNameGeneratorInterface $g, string $resourceClass, ?Operation $op = null, array $ctx = []): void
    {
        if (X::class !== $resourceClass || $this->security->isGranted('ROLE_ADMIN')) {
            return;
        }
        $user = $this->security->getUser() ?? throw new AccessDeniedException();
        $alias = $qb->getRootAliases()[0];
        $qb->andWhere("$alias.user = :current_user")->setParameter('current_user', $user->getId());
    }
}
```

**Processor** — forces ownership on create (and can also re-validate on delete). Branch on `$operation instanceof DeleteOperationInterface`; inject the inner services with `#[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]` / `...remove_processor`.

**When to fall back to an all-in-one State Provider instead of a Voter+Extension:** only when the collection can't be a plain `WHERE` (custom/aggregated data source — see `StickyCourseStateProvider`), or when you must return **404 instead of the voter's 403** for foreign items (no existence disclosure). The merged `PushSubscription` fix used a single `PushSubscriptionStateProvider` + `PushSubscriptionStateProcessor` for the latter reason; for the typical case prefer the Voter + Extension + Processor split above.

**Cross-cutting rules learned here:**
- **Current user:** `UserHelper::getCurrent(): ?User` (inject `UserHelper`), never `$security->getUser()` — except inside a `QueryCollectionExtension`, where `$this->security->getUser()` is the established idiom (see `SessionRelUserExtension`).
- **Role checks:** `$security->isGranted('ROLE_ADMIN')` (respects the hierarchy), never `$user->isAdmin()` / `hasRole()`.
- **Serialization:** owner relation → **read-only** group (mass-assignment defense); secrets (tokens, keys) → **write-only** group so they are never echoed back.
- **No `services.yaml` wiring needed:** classes implementing `VoterInterface` / `QueryCollectionExtensionInterface` / `ProcessorInterface` are auto-tagged via `autoconfigure`; inner API Platform services are pulled in with `#[Autowire(service: ...)]`.
- **Regression test gotcha:** the test DB host in `.env.test` may not be reachable from the PHP container — override `DATABASE_HOST`/`DATABASE_PASSWORD` env vars on the `php bin/phpunit` call. To test admin access over a JWT Bearer token, create the admin with `createUser('name', '', '', 'ROLE_ADMIN')` (it registers the `UserAuthSource::PLATFORM` row that `UserAuthSourceListener` requires); the fixture `admin/admin` account may lack it in a fresh test DB.

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

## Project Overview

Chamilo LMS 2.0 — an open-source e-learning platform built on **Symfony 6.4** (PHP 8.2/8.3) with a **Vue 3** frontend. Uses **API Platform 3.0** for REST/GraphQL APIs, **Doctrine ORM** for persistence, and **Webpack Encore** for asset compilation.

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

### Behat (browser automation tests)

Behat feature files live in `tests/behat/features/`. They are organised by domain (e.g. `hr/`, `admin/`). The shared step definitions are in `tests/behat/features/bootstrap/FeatureContext.php`.

Run a specific feature file:
```bash
vendor/bin/behat tests/behat/features/createUsers.feature
```

Run all features:
```bash
vendor/bin/behat --suite=default tests/behat/features/
```

**When to write Behat tests — mandatory rule:**
Every new feature and every new interface added to an existing feature **must** be accompanied by a Behat feature file (or additions to an existing one) that covers all user interactions: create, read, edit, and delete at minimum.

- Place feature files in `tests/behat/features/<domain>/` mirroring the view directory structure.
- Form inputs in Vue dialogs/forms **must** have a `name` attribute so Behat can target them with `I fill in "name" with "value"` and `I select "option" from "name"`.
- If an entity or page is accessible to more than one role, **run all scenarios once per role that has access**. Do not test only as admin if HR users or other roles can also interact with the feature. For access-restricted pages (e.g. admin-only), add a scenario verifying that non-privileged roles are denied access (redirected or do not see the management UI).
- Login steps available: `I am a platform administrator`, `I am an HR user` (hr/HrHrHr11+), `I am an HR manager` (ptook), `I am a student`, `I am a teacher`. Add a new named step to `FeatureContext.php` whenever a new test user with non-standard credentials is needed.
- Each feature file must be self-contained: it creates all data it needs and deletes it at the end, leaving the database in the same state it found it.


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
- **CidReq**: Course/session context is tracked via `cidReq` store — holds current course ID, session ID, and group ID passed as query parameters
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
- `setParameter()` in QueryBuilder **must** include an explicit type (3rd arg) for non-scalars: `Types::INTEGER` for entity IDs, `Types::DATETIME_MUTABLE` for DateTime, `ArrayParameterType::INTEGER` for int arrays.
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

### Doctrine / DQL gotchas

- **DQL uses entity property names, not column names.** Always read the entity file before writing a query. Example trap: `SkillRelGradebook` has property `$gradeBookCategory` (capital B), not `gradebookCategory`.
- **Do not order by SELECT aliases** in DQL (`orderBy('myAlias', 'DESC')` is unreliable). Use the full expression: `orderBy('COUNT(sru.id)', 'DESC')`.
- **`USER_SOFT_DELETED = -2`** (defined in `public/main/inc/lib/api.lib.php`). The value `-1` is `INACTIVE_AUTOMATIC` — a different state. The User entity constants are `SOFT_DELETED = -2`, `INACTIVE_AUTOMATIC = -1`, `INACTIVE = 0`, `ACTIVE = 1`.
- DQL has no `UNION`. Replace with two separate queries merged in PHP via `array_merge`.

### Legacy link locations

When replacing a legacy PHP page, search these locations for old links:
- `src/CoreBundle/Controller/Admin/IndexBlocksController.php` — admin panel block links
- `public/main/admin/index.php` — legacy admin panel
- `public/main/template/default/` — legacy Twig templates
- `assets/vue/components/` — Vue components linking to legacy pages (e.g., `social/MySkillsCard.vue`)
- `public/main/inc/ajax/model.ajax.php` — jqGrid AJAX allowlists; remove the action from both the allowlist arrays and the `case` blocks

### Tables in Vue pages

Use `BaseTable` (`assets/vue/components/basecomponents/BaseTable.vue`) which wraps PrimeVue `DataTable`. `Column` is globally registered in `main.js` — no import needed. Example:
```vue
<BaseTable :values="rows" :is-loading="isLoading">
  <Column field="firstname" :header="t('First name')" sortable />
</BaseTable>
```
### Buttons in Vue pages

Always use `<BaseButton>` instead of a plain `<button>` element unless there is a clear reason to use a native button (e.g. inside a third-party component that requires one). Import it from `../../components/basecomponents/BaseButton.vue`. Key props: `type` (success / primary / secondary / danger / plain), `icon` (MDI icon name without `mdi-` prefix), `only-icon` + `size="small"` for icon-only table row actions.

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

Standard HTML `<input>`, `<select>` with Tailwind: `border border-gray-300 rounded px-3 py-1.5 text-sm`. Group elements with `flex gap-4 items-end`.

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

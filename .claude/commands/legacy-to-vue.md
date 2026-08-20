Migrate the legacy PHP page `$ARGUMENTS` to a Vue SPA page following the Chamilo migration pattern. Work through each step below in order, reading the relevant files before making changes.

## Step 1 — Understand the legacy page

Read `$ARGUMENTS` and answer:
- What data does it display or manipulate?
- Does it use jqGrid (`Display::grid_js`)? SortableTable? Plain HTML?
- What AJAX endpoint does it call (usually `model.ajax.php?a=...`)?
- Who can access it (admin only, any logged-in user, etc.)?
- Where is it linked from (run a grep for the filename across `src/`, `public/main/`, `assets/`)?

## Step 2 — Create the Symfony data endpoint

Create a controller in `src/CoreBundle/Controller/` (or `src/CoreBundle/Controller/Admin/` if admin-only).

### Functional rules
- Route must end with `-data` to avoid conflicting with the Vue SPA route. Example: SPA at `/skill/ranking` → data at `/skill/ranking-data`.
- Use `EntityManagerInterface` and Doctrine QueryBuilder / DQL — no raw SQL or `Connection` unless there is no ORM mapping for the tables involved.
- DQL uses **entity property names**, not column names. Always read the entity file first.
- Do not order by SELECT aliases in DQL; use the full expression.
- `USER_SOFT_DELETED = -2` (not -1). User entity constants: `SOFT_DELETED=-2`, `INACTIVE_AUTOMATIC=-1`, `INACTIVE=0`, `ACTIVE=1`.
- Return `$this->json(...)` with clean data — no HTML, no legacy `Display::` output.
- Use `#[IsGranted('IS_AUTHENTICATED_FULLY')]` for any logged-in user, or `#[IsGranted('ROLE_ADMIN')]` for admin-only.

### PHP code style rules (ECS — Symfony + PSR-12 + PhpCsFixer)

The project enforces code style via `vendor/bin/ecs check`. Write code that passes from the start:

- **`declare(strict_types=1);`** is mandatory at the top of every PHP file.
- **Yoda conditions**: comparisons must put the literal on the left — `'all' === $listType`, not `$listType === 'all'`.
- **No string interpolation with `{}`**: use concatenation instead — `'%'.$keyword.'%'`, not `"%{$keyword}%"`. Concatenation has **no spaces** around the `.` operator.
- **Short array syntax**: `[]` not `array()`.
- **Trailing commas** in multiline arrays, argument lists, and `match` arms.
- **No useless else** — use early returns instead.
- **No useless return** at the end of void methods.
- **No empty phpdoc** blocks. No superfluous `@param`/`@return` tags that duplicate the type hint.
- **Void return types** — declare `void` on methods that return nothing.
- **Import classes globally** — use `use` statements, never inline `\Fully\Qualified\ClassName` in code (except in string references like DQL entity names).
- **Ordered class elements** — constants, then properties, then constructor, then public methods, then protected, then private.
- **`(int)` not `intval()`** — use modern type casting.
- **No `!` with a space** — write `!$foo`, not `! $foo`.

### Psalm static analysis rules

The project runs `vendor/bin/psalm` (level 7, with Doctrine and Symfony plugins). Write code that passes from the start:

- **`setParameter()` must include an explicit type** (3rd argument) for non-scalar values. Use `Types::INTEGER` for entity IDs (pass `$user->getId()`, not the entity object), `Types::DATETIME_MUTABLE` for DateTime objects, and `ArrayParameterType::INTEGER` for integer arrays. Import `Doctrine\DBAL\Types\Types` and `Doctrine\DBAL\ArrayParameterType`.
- **All methods must have return types.** All parameters must have type hints.
- **Avoid possibly-null access** — check for null before accessing object properties or methods.
- **No unused variables** — remove or prefix with `$_` if intentionally ignored.

### Post-write verification

After writing the controller, run both checks and fix any issues before proceeding:
```bash
vendor/bin/ecs check src/CoreBundle/Controller/Admin/YourController.php
vendor/bin/psalm --show-info=false src/CoreBundle/Controller/Admin/YourController.php
```

## Step 3 — Register the SPA route in IndexController

Add a `#[Route('/your/path', name: 'your_route_name')]` attribute to the `index()` method in `src/CoreBundle/Controller/IndexController.php`.

Without this, navigating directly to the URL returns JSON from the data endpoint instead of the Vue app shell.

Note: if the new route is under a prefix that already has a catch-all (e.g., `/admin/{vueRouting}`), a new explicit route may not be needed — verify first.

## Step 4 — Add the Vue router entry

Find the relevant domain router file in `assets/vue/router/` (e.g., `skill.js`, `social.js`). Add a child route:
```js
{
  name: 'YourRouteName',
  path: 'your-path',
  meta: { requiresAuth: true, showBreadcrumb: true },
  component: () => import('../views/your-domain/YourComponent.vue'),
}
```
If no domain router exists yet, create one and import it in `assets/vue/router/index.js`.

## Step 5 — Create the Vue view

Create `assets/vue/views/<domain>/YourComponent.vue`.

### General rules
- Use `<script setup>` with `useI18n`.
- Use `BaseTable` + `Column` for tabular data. `Column` is globally registered — no import needed.
- Fetch data with `baseService.get('/your/path-data')`.
- Use Tailwind classes exclusively; avoid inline styles and custom CSS.

### Design guide rules (from https://github.com/chamilo/chamilo-lms/wiki/Graphical-design-guide)

**Spacing:** Follow the 8-point grid system. Use multiples of 8px for spacing (Tailwind: `gap-2` = 8px, `p-4` = 16px, `mb-8` = 32px, etc.). Fine adjustments of 4px/6px/12px are acceptable when necessary.

**Buttons:** Follow the CRUD color convention:
- **Create** actions (add, import, save): green/success → `btn btn--primary` or Tailwind `bg-green-*`
- **Read** actions (export, view, list): blue/primary → `btn btn--primary`
- **Update** actions (edit, move, configure): orange/secondary → `btn btn--secondary`
- **Delete** actions (delete, disable): red/error → `btn btn--danger`
- **Cancel/dismiss**: gray → `btn btn--plain`
- Buttons are for **actions only** — never style a non-action link as a button.

**Icons:** Use Material Design Icons (MDI) via `<span class="mdi mdi-{name} ch-tool-icon" />` in Vue templates.
- Standard actions: `ch-tool-icon` class (primary color)
- Disabled states: `ch-tool-icon-disabled` class (grayed out)
- Large/hero icons: `ch-tool-icon-gradient` class (gradient)
- Icon-only buttons: `ch-tool-icon-button` class
- Follow the canonical icon names from the design guide:
  - Edit: `mdi-pencil`, Delete: `mdi-delete`, Add: `mdi-plus-box`, Search: `mdi-magnify`
  - Copy: `mdi-text-box-plus`, Configure: `mdi-hammer-wrench`, Info: `mdi-information`
  - Subscribe users: `mdi-account-multiple-plus`, Add courses: `mdi-book-open-page-variant`
  - Export CSV: `mdi-file-delimited-outline`, Export PDF: `mdi-file-pdf-box`
  - Visible/invisible: `mdi-eye` / `mdi-eye-off`
  - Active/inactive: `mdi-toggle-switch` / `mdi-toggle-switch-off`
  - Success/error: `mdi-check-circle` / `mdi-alert-circle`

**Status badges / colored indicators:** Use Tailwind utility classes for status colors:
- Planned/info: `bg-blue-100 text-blue-700`
- Active/success: `bg-green-100 text-green-700`
- Finished/neutral: `bg-gray-100 text-gray-700`
- Error/cancelled: `bg-red-100 text-red-700`

**Tables:** Use `BaseTable` (`components/basecomponents/BaseTable.vue`) wrapping PrimeVue `DataTable`. Key props: `:values`, `:total-items`, `:is-loading`, `:lazy`, `@page`, `@sort`. `Column` is globally registered.

**Forms:** Use standard HTML `<input>`, `<select>` with Tailwind classes like `border border-gray-300 rounded px-3 py-1.5 text-sm`. Group form elements with `flex gap-4 items-end`.

## Step 6 — Translations

Check which string keys the Vue component uses. For each key:
1. Verify it exists in `assets/locales/en_US.json`. If not, add it.
2. Verify it exists in `translations/messages.en_US.po`. If not, add both the `msgid` and `msgstr` lines there **and** in `translations/messages.pot` (with empty `msgstr ""`).
3. Do not add a key to `en_US.json` that is not already in `messages.en_US.po` — the JSON files are generated from the PO files via `php bin/console chamilo:update_vue_translations`.

## Step 7 — Update old links

Search for references to the old PHP file path:
```bash
grep -rn "old_page.php" src/ public/main/ assets/
```
Update each hit:
- `src/CoreBundle/Controller/Admin/IndexBlocksController.php` — admin panel blocks
- `public/main/admin/index.php` — legacy admin panel
- `public/main/template/default/` — legacy Twig templates
- `assets/vue/components/` — Vue components

## Step 8 — Verify legacy link parameters are supported

After updating all links in Step 7, search for every reference that passes **query parameters** to the new Vue route:
```bash
grep -rn "/new/vue-route?" src/ public/main/ assets/
```

For each parametrized link found, verify the Vue component handles the parameter on mount. Common patterns to check:

- **Filter/tab params** (e.g., `?list_type=active`, `?id_category=5`): The `onMounted` hook must read `route.query` and initialize the corresponding reactive state (active tab, filter value, etc.).
- **Action params** (e.g., `?action=copy&idChecked=3`, `?action=delete&id=1,2`): If legacy pages redirect to the list page with an `action` + ID to trigger a server-side operation, the Vue component must detect these on mount, prompt for confirmation, call the new action endpoint, then reload the list.
- **Search params** (e.g., `?keyword=foo`): Must be read into the search field and applied to the initial data fetch.
- **Return-to params** (e.g., `?page=/new/vue-route` passed to *other* legacy pages): Verify those legacy pages can redirect back to an absolute URL — some only handle relative paths.

If a parameter is found that the Vue component does not support, add the handling code before proceeding.

## Step 9 — Clean up the AJAX handler (if applicable)

If the legacy page used `model.ajax.php`, find the action name (e.g., `get_user_skill_ranking`). **Before removing**, verify the action is not shared by other legacy pages (grep for the action name across `public/main/`). If shared, leave it in place.

If the action is only used by the migrated page, remove it from:
- The user/admin allowlist arrays near the top of the file
- The `case` block in the count section
- The `case` block in the data section
- Any second allowlist array further down the file

Verify with: `grep -n "action_name" public/main/inc/ajax/model.ajax.php`

## Step 10 — Platform settings audit

The legacy page may behave differently based on platform settings. These must be carried over to the new implementation.

### Collect settings

1. Search the legacy file being migrated for **every** call to `api_get_configuration_value()`, `api_get_setting()`, and `api_get_settings()` — grep the whole file, don't rely on skimming while reading top to bottom; a setting referenced once near the top (deciding what the form renders) often reappears again, separately, deep in the submit handler. For each call, record: the **exact string argument, verbatim, including any category prefix** (`registration.user_hide_never_expire_option`, not a "simplified" `user_hide_never_expire_option` — see the gotcha below), any **condition it's combined with** (e.g. `'true' === api_get_setting(...) && !api_is_platform_admin()` — the role check is part of the setting's effective behavior, not a detail to drop), and **which phase of the file it governs** (what the form renders/defaults to, vs. what the submit handler validates or overwrites).
2. Also check the **1.11.x branch** version of the same file for additional setting calls that may have been lost during the 2.0 migration. Most files that were in `main/` (or subdirectories) in 1.11.x are now in `public/main/` (or subdirectories) in the current branch. If the `1.11.x` branch exists locally, read the file from there (`git show 1.11.x:main/path/to/file.php`). Otherwise, fetch it from `https://raw.githubusercontent.com/chamilo/chamilo-lms/1.11.x/main/path/to/file.php`.
3. Compile a single list of unique setting variable names with a short description of their effect, and **for each one, name every distinct place in the file it's referenced** (not just the first hit) — this list is what the post-migration verification pass below checks off against.

**Two real bugs, found migrating a real page, that this checklist exists to prevent:**
- **Dropping the category prefix.** `api_get_setting()` requires the exact `category.name` string for most settings — `SettingsManager::validateSetting()` only resolves a bare (no-dot) name automatically for the handful of settings explicitly registered that way (e.g. `login_is_email`, `limit_session_admin_role`). Writing `api_get_setting('user_hide_never_expire_option')` instead of the legacy file's own `api_get_setting('registration.user_hide_never_expire_option')` throws `InvalidArgumentException: Parameter must be in format "category.name"` at runtime — a bug that a code re-read won't catch, only an actual request will. **Copy the exact string, never "simplify" it** — see CLAUDE.md's "Porting `api_get_setting()` calls verbatim" for the full explanation.
- **Porting the rendering-time behavior but missing the submit-time enforcement.** A setting can control the same restriction in two separate places: once to decide what the form renders/defaults to, and again, independently, in the submit handler, which can **unconditionally override the submitted value** regardless of what the client sent — precisely because the corresponding form field wasn't even rendered, so the legacy author didn't trust the client for it. `user_add.php`'s `registration.user_hide_never_expire_option` is the concrete example: when it's on (for anyone but a platform admin), the "Never expires" radio isn't rendered at all, and the submit handler forces `radio_expiration_date = '1'` no matter what was submitted. Porting only "hide the radio, default the field" and treating the rest as an ordinary client-supplied value reopens the restriction: a tampered request can explicitly claim "never expires" and get it, which the legacy page never allowed. **For every setting that restricts or forces something (as opposed to one that only changes a UI default), find and port the submit-handler-side enforcement, not just the rendering-time behavior.**

### Verify and implement

After the main feature migration is complete (Steps 2–9), but **before** code quality and security checks, process each setting:

1. Check if the setting still exists. Current settings can be found in `src/CoreBundle/DataFixtures/SettingsCurrentFixtures.php`, which mirrors the `settings` table. Search for the variable name there. You can also search in `src/CoreBundle/Migrations/` and `config/` for additional context.
2. **If the setting no longer exists**: skip it, but print a note in the terminal: `"[Settings audit] Skipped 'setting_name' — no longer exists in settings table."` so the user is informed.
3. **If the setting still exists**: explain to the user what the setting does and how you plan to implement it (e.g., conditionally hide a button, pass it from the controller to the Vue component via the data endpoint JSON). **Wait for user confirmation** before implementing.

### Post-migration: verify every setting was actually ported correctly

Do this **after** the feature migration is otherwise complete (Steps 2–9), as a dedicated pass over the list from "Collect settings" above — not folded into the general OWASP/code-quality pass, and not satisfied by re-reading the new code and confirming it "looks right." A setting that looks correctly wired can still be wrong: a dropped category prefix throws at runtime; a restriction ported as a mere default can be bypassed by a client that sends an explicit value.

For **each** setting in the list:

1. Write or extend a PHPUnit functional test that toggles it with `$settingsManager->updateSetting('category.name', 'value')` (inject `SettingsManager` from the container) and drives the real endpoint — not a unit test of an isolated method, and not just a read of the controller. Assert the actual resulting behavior (a value stored, an error message returned, a field present/absent in the response), not merely "no exception was thrown."
2. Test **both** sides of the setting where it has two: the value that changes behavior, and the default/off value, so a regression on either side is caught.
3. For any setting that **restricts or forces** behavior (as opposed to one that only changes a UI default or a suggested value): write a test that submits the request as if a client were deliberately trying to bypass the restriction (e.g., explicitly sending the value the legacy page's own UI never let the user submit). Assert the restriction still holds. This is the only way to catch the "ported as a default instead of an override" class of bug — a happy-path test alone will not exercise it.
4. Cross off each setting only once its own test(s) pass against the real migrated code (run them — don't mark a setting done from reasoning alone).
5. Report the final status to the user as a list — one line per setting: ported and tested / doesn't apply to the new architecture (say why) / skipped because it no longer exists in `SettingsCurrentFixtures.php`. Don't summarize this as a single "settings were handled" statement; each setting's fate should be individually visible, since that's what lets the user catch a gap you missed.

### Check for required database fields

Some settings depend on specific database columns or tables that may not exist in the current schema. The file `1.11.x:main/install/configuration.dist.php` often documents these dependencies (the settings themselves have moved to the `settings` table, but the field requirements were documented there). For each setting you plan to implement:

1. Read `git show 1.11.x:main/install/configuration.dist.php` (or fetch from GitHub) and search for the setting variable name to find any field/table dependencies noted in comments or surrounding code.
2. Verify those fields exist in the current entity or database schema (check the Doctrine entity or run `grep` for the field name in `src/CoreBundle/Entity/`).
3. **If a required field is missing**, this is a potential blocker — do not proceed with that setting. Instead, report it to the user and ask how to handle it before continuing.

This step ensures the migration does not silently drop platform-configurable behavior.

## Step 11 — OWASP security review

Review **every** new and modified file (controller, Vue component, updated legacy links) for common vulnerabilities. This step must be re-run after any subsequent change request related to this migration (e.g., adding features, fixing bugs, updating links) — not only on the initial pass.

### Checklist

- **CSRF on state-changing endpoints.** Any POST/PUT/DELETE controller that performs destructive or sensitive actions (delete, copy, anonymize, restore, status toggle, etc.) must validate a CSRF token. Use `$this->isCsrfTokenValid('intent_name', $token)` in the controller. Generate the token via `CsrfTokenManagerInterface::getToken('intent_name')`, return it in the data endpoint JSON, and include it as a hidden `_token` field in the Vue form submission.
- **Broken access control.** Verify that `#[IsGranted(...)]` on the controller matches the legacy page's access checks. If the legacy page allowed both admins and session admins, use the `Expression` form. Verify that **every** destructive action inside the controller re-checks the role (e.g., session admins should not be able to delete sessions they don't manage just because they can reach the endpoint). For non-admin roles, filter actionable entity IDs to only those the current user is authorized to manage.
- **SQL injection.** Never interpolate user input into DQL/SQL strings. Always use bound parameters (`:paramName` + `setParameter()`). The QueryBuilder already does this — verify no raw concatenation slipped in. Sort field values must use an allowlist mapping, never be passed directly into `orderBy()`.
- **XSS.** Vue's template syntax (`{{ }}`) auto-escapes by default. Verify no `v-html` is used with user-supplied data. If linking to legacy PHP pages with query params built from data, ensure values are not attacker-controlled HTML. Check that dynamic `:href` bindings only interpolate integer IDs or known-safe strings.
- **Open redirects.** If a controller builds a redirect URL using user-supplied or database values (e.g., a username), always `urlencode()` those values before embedding them in the URL. Verify that the Vue component does not use `window.location.href` with unsanitized query param values.
- **Mass parameter manipulation.** Verify that array parameters from the client (e.g., `sessionIds[]`) are cast to safe types (`array_map('intval', ...)`) before use. Verify that a non-admin user cannot supply IDs of entities they do not own/manage.

## Step 12 — Handle the legacy file

**Always ask the user** whether to delete the legacy file or leave a deprecation stub. In most cases (especially during RC phases), the user will prefer keeping a stub to avoid breaking external links or bookmarks.

If the user wants a stub, replace the file contents with:
```php
<?php
// Deprecated file left here because contents removed after 2.0 RC2. Should be removed before the next major version.
```

If the user explicitly asks to delete it:
```bash
rm $ARGUMENTS
```

Then do a final grep to confirm no remaining references:
```bash
grep -rn "$(basename $ARGUMENTS)" src/ public/main/ assets/
```

Also update CLAUDE.md if any new discovered patterns emerged during the migration.

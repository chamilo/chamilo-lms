Restore the legacy Chamilo 1.11.x feature described below to the current master (2.0) branch.
Arguments: `$ARGUMENTS` — a URL or file path to the entry page of the feature in the 1.11.x branch,
followed (after a space or newline) by a natural-language description of the feature or set of features
to restore. The description may be a copy-paste from a user guide, a technical spec, or a free-form
summary of what to bring back.

Work through each step below in order. **Read files before modifying them.** Present findings and plans
to the user and wait for confirmation before writing code that creates entities, migrations, or API
resources. Do not write speculative abstractions — implement only what the description requires.

---

## Step 1 — Parse arguments and locate legacy source files

1. Split `$ARGUMENTS` into:
   - **entry_path**: the URL path or file path of the main entry page (e.g. `main/social/skills.php`
     or `https://raw.githubusercontent.com/chamilo/chamilo-lms/1.11.x/main/social/skills.php`)
   - **feature_description**: everything after the first whitespace / newline.

2. If entry_path is a local file path (not a URL), check whether the `1.11.x` branch exists locally:
   ```bash
   git branch -a | grep 1.11.x
   ```
   If found, read the entry file with: `git show 1.11.x:<entry_path>`
   If not found locally, fetch from:
   `https://raw.githubusercontent.com/chamilo/chamilo-lms/1.11.x/<entry_path>`

3. Identify **all** related legacy files referenced by the entry page (requires, includes, AJAX calls,
   jqGrid endpoints, Smarty templates, etc.). Fetch or `git show` each one. Build a complete file list
   before proceeding — partial understanding leads to missed functionality.

4. From the entry page and the feature description, answer:
   - What is the feature's primary purpose?
   - Who can access it (admin only, any logged-in user, course member, session coach, etc.)?
   - What database tables does it read/write? (Look for `Database::get_main_table()`, raw SQL,
     `api_get_course_dbname_prefix()`, etc.)
   - What external libraries or services does it depend on? (jQuery plugins, mail, cron, etc.)
   - Does it have sub-pages, modals, or multi-step workflows?
   - Does it depend on platform settings (`api_get_configuration_value`, `api_get_setting`)?
   - Does it use file storage or generate downloadable files?

---

## Step 2 — Map legacy tables to current Doctrine entities

For every database table identified in Step 1:

1. Search for an existing Doctrine entity that maps to it:
   ```bash
   grep -rn "table: '<table_name>'" src/CoreBundle/Entity/ src/CourseBundle/Entity/
   grep -rn "name: '<table_name>'" src/CoreBundle/Entity/ src/CourseBundle/Entity/
   ```

2. Categorise each table as one of:
   - **A** — Already mapped to a current entity (note the entity class and file path).
   - **B** — Table exists in the legacy schema but has no current entity — needs a new entity.
   - **C** — Table no longer exists and the data model must be redesigned.

3. For category-B tables, fetch the legacy table schema from the 1.11.x install scripts:
   ```
   git show 1.11.x:main/install/db_schema.sql | grep -A 30 'CREATE TABLE <table>'
   ```
   or from `https://raw.githubusercontent.com/chamilo/chamilo-lms/1.11.x/main/install/db_schema.sql`

4. For category-C tables, describe to the user what data was stored and propose a new data model
   using current Chamilo entities or a new Doctrine entity. **Wait for user confirmation** before
   designing the new schema.

5. Present the full mapping table (legacy table → current entity / planned entity) to the user
   before writing any entity code.

---

## Step 3 — Design the entity layer (for new or redesigned tables)

For each new Doctrine entity needed (category B or C from Step 2):

### Entity rules
- Namespace: `Chamilo\CoreBundle\Entity` (or `Chamilo\CourseBundle\Entity` for course-scoped data).
- File: `src/CoreBundle/Entity/{EntityName}.php` (or `src/CourseBundle/Entity/`).
- Use PHP 8.1 ORM attributes (`#[ORM\Entity]`, `#[ORM\Table]`, `#[ORM\Column]`).
- Add `#[ORM\HasLifecycleCallbacks]` only if timestamps are needed; use `TimestampableEntity` trait.
- Foreign keys to `User`, `Course`, `Session` must use `#[ORM\ManyToOne]` with the correct
  target entity class and join column — never store raw integer IDs unless there is no entity.
- For soft-deletable entities, use `#[ORM\Column(type: 'boolean', options: ['default' => false])]`
  for an `active` or `deleted` flag.
- All properties must have type hints and getters/setters.
- Property order: `id` → foreign keys → scalar columns → nullable columns → timestamps.
- `declare(strict_types=1);` at the top of every file.

### API Platform resource block

Add an `#[ApiResource]` attribute to every new entity. Apply security at the operation level — do
**not** expose operations that the feature does not require, and **never** leave endpoints open to
unauthenticated users or to all authenticated users without proper consideration.

Use this decision tree before writing the `#[ApiResource]` block:

| Who should access this data?                  | Security attribute to use |
|-----------------------------------------------|---------------------------|
| Unauthenticated (truly public)                | No restriction — confirm with user first |
| Any logged-in user (all roles)                | `"is_granted('IS_AUTHENTICATED_FULLY')"` |
| Admins only                                   | `"is_granted('ROLE_ADMIN')"` |
| The owner/creator of the resource only        | `"is_granted('ROLE_USER') and object.getCreator() == user"` |
| Course members (teachers + students)          | use `CourseVoter` / `CourseSubscriptionChecker` |
| Session coaches or course teachers            | use appropriate voter |

**When in doubt, default to `ROLE_ADMIN` and ask the user to confirm the correct access level
before widening access.** It is always safer to restrict first and loosen later.

Example minimal block (adjust operations to what the feature actually needs):
```php
#[ApiResource(
    operations: [
        new GetCollection(security: "is_granted('ROLE_ADMIN')"),
        new Get(security: "is_granted('ROLE_ADMIN')"),
        new Post(security: "is_granted('ROLE_ADMIN')"),
        new Put(security: "is_granted('ROLE_ADMIN')"),
        new Delete(security: "is_granted('ROLE_ADMIN')"),
    ],
    normalizationContext: ['groups' => ['{entity_name}:read']],
    denormalizationContext: ['groups' => ['{entity_name}:write']],
)]
```

Add `#[Groups(['{entity_name}:read'])]` / `#[Groups(['{entity_name}:write'])]` to each property.
**Do not expose sensitive columns** (passwords, tokens, raw IDs) in any group — omit the group
attribute on those properties entirely.

### Repository

Create `src/CoreBundle/Repository/{EntityName}Repository.php`.

- Extend `ServiceEntityRepository`.
- Add `#[AsEntityIdConverter(EntityName::class)]` if the entity is an API Platform resource.
- Type all method parameters and return types.
- Follow the same PHP code style rules as controllers (Step 5).
- Do not add catch-all `findAll()` public methods unless the feature requires them — keep the
  repository surface minimal.

---

## Step 4 — Write the database migration

For every new or modified entity, create a Doctrine migration:

```bash
php bin/console doctrine:migrations:diff --no-interaction
```

Review the generated SQL in `src/CoreBundle/Migrations/Schema/V200/` before committing. Verify:
- Table names match the entity `#[ORM\Table]` attribute.
- Foreign key constraints reference the correct tables.
- Column types match the legacy schema for existing data (especially `varchar` lengths, `text` vs
  `longtext`, and nullable vs not-null).
- If migrating existing data from a legacy table to a new schema, add `INSERT INTO … SELECT …`
  statements in the `up()` method **and** a rollback in `down()`.

After reviewing, run the migration on the dev database:
```bash
php bin/console doctrine:migrations:migrate --no-interaction
```

If the migration fails, diagnose the error, fix the entity, regenerate, and retry. Do **not** hand-edit
the generated migration file unless column types or default values need explicit correction.

---

## Step 5 — Create the Symfony controller(s)

Create controllers in `src/CoreBundle/Controller/` (or `.../Admin/` for admin-only features).

### Functional rules
- Data endpoints must end in `-data` to avoid clashing with Vue SPA routes (e.g. SPA at
  `/my-feature` → data at `/my-feature-data`).
- Use `EntityManagerInterface` and Doctrine QueryBuilder / DQL — no raw SQL or `Connection` unless
  there is no ORM mapping for the involved tables.
- DQL uses **entity property names**, not column names. Always read the entity file first.
- Do **not** order by SELECT aliases in DQL; use the full expression.
- `USER_SOFT_DELETED = -2` (not -1). User entity constants: `SOFT_DELETED=-2`,
  `INACTIVE_AUTOMATIC=-1`, `INACTIVE=0`, `ACTIVE=1`.
- Return `$this->json(...)` — no HTML, no legacy `Display::` output.
- `#[IsGranted('IS_AUTHENTICATED_FULLY')]` for any logged-in user, `#[IsGranted('ROLE_ADMIN')]`
  for admin-only. When the legacy code had mixed access control (admins + course coaches, etc.),
  reproduce the same access logic via Symfony voters or manual permission checks.

### PHP code style rules (ECS — Symfony + PSR-12 + PhpCsFixer)
- `declare(strict_types=1);` at the top of every file.
- Yoda conditions: `'all' === $listType`, not `$listType === 'all'`.
- No string interpolation with `{}` — use concatenation with **no spaces** around `.`:
  `'%'.$keyword.'%'`, not `"%{$keyword}%"`.
- Short array syntax `[]`, trailing commas in multiline constructs.
- No useless `else` — use early returns. No useless `return` at end of void methods.
- No empty phpdoc. No `@param`/`@return` tags that duplicate type hints.
- `void` return type on methods that return nothing.
- Import classes via `use` — never inline `\Fully\Qualified\ClassName` in code bodies.
- Ordered class elements: constants → properties → constructor → public → protected → private.
- `(int)` not `intval()`, `(string)` not `strval()`.

### Psalm static analysis rules
- `setParameter()` must include an explicit type (3rd arg) for non-scalar values:
  `Types::INTEGER` for entity IDs, `Types::DATETIME_MUTABLE` for DateTime,
  `ArrayParameterType::INTEGER` for int arrays.
- All methods must have return types. All parameters must have type hints.
- Check for possibly-null access before using object properties or calling methods.

### Post-write verification
Run both checks and fix all issues before proceeding to the next step:
```bash
vendor/bin/ecs check src/CoreBundle/Controller/Path/To/YourController.php
vendor/bin/psalm --show-info=false src/CoreBundle/Controller/Path/To/YourController.php
```

---

## Step 6 — Register the SPA route(s) in IndexController

For each page-level URL that the Vue SPA will handle, add a `#[Route(...)]` attribute to the
`index()` method in `src/CoreBundle/Controller/IndexController.php`.

Without this, navigating directly to the URL returns the data endpoint JSON instead of the Vue shell.

Check whether the feature falls under an existing catch-all prefix (e.g. `/admin/{vueRouting}`)
before adding a new explicit route.

---

## Step 7 — Add the Vue router entry/entries

Find the relevant domain router file in `assets/vue/router/` (e.g. `skill.js`, `social.js`,
`admin.js`). Add a child route for each SPA page:
```js
{
  name: 'YourFeatureRouteName',
  path: 'your-path',
  meta: { requiresAuth: true, showBreadcrumb: true },
  component: () => import('../views/your-domain/YourFeatureView.vue'),
}
```
If no domain router exists yet, create one and import it in `assets/vue/router/index.js`.

---

## Step 8 — Create the Vue view(s)

Create `assets/vue/views/<domain>/YourFeatureView.vue` (one file per logical page or workflow step).

### General rules
- `<script setup>` with `useI18n`.
- `BaseTable` + `Column` for tabular data (`Column` is globally registered — no import needed).
- Fetch data with `baseService.get('/your/path-data')` or `useService` composable.
- State-changing operations (create, update, delete) call `baseService.post/put/delete` and handle
  loading/error states explicitly.
- Tailwind classes only — no inline styles, no custom CSS unless unavoidable.

### Design guide rules
**Spacing:** 8-point grid (multiples of 8px). Fine adjustments of 4/6/12px are acceptable.

**Buttons — CRUD color convention:**
- Create/add/save/import → green → `btn btn--success`
- Read/view/export/list → blue → `btn btn--primary`
- Update/edit/configure/move → orange → `btn btn--secondary`
- Delete/disable/remove → red → `btn btn--danger`
- Cancel/dismiss → gray → `btn btn--plain`
- Buttons are for actions only — never style a non-action link as a button.

**Icons (MDI via `<span class="mdi mdi-{name} ch-tool-icon" />`):**
- Edit: `mdi-pencil`, Delete: `mdi-delete`, Add: `mdi-plus-box`, Search: `mdi-magnify`
- Copy: `mdi-text-box-plus`, Configure: `mdi-hammer-wrench`, Info: `mdi-information`
- Subscribe users: `mdi-account-multiple-plus`, Add courses: `mdi-book-open-page-variant`
- Export CSV: `mdi-file-delimited-outline`, Export PDF: `mdi-file-pdf-box`
- Visible/invisible: `mdi-eye` / `mdi-eye-off`
- Active/inactive: `mdi-toggle-switch` / `mdi-toggle-switch-off`

**Status badges:**
- Planned/info: `bg-blue-100 text-blue-700`
- Active/success: `bg-green-100 text-green-700`
- Finished/neutral: `bg-gray-100 text-gray-700`
- Error/cancelled: `bg-red-100 text-red-700`

**Tables:** `BaseTable` wrapping PrimeVue `DataTable`. Key props: `:values`, `:total-items`,
`:is-loading`, `:lazy`, `@page`, `@sort`.

**Forms:** Standard HTML `<input>`, `<select>` with Tailwind: `border border-gray-300 rounded px-3 py-1.5 text-sm`. Group elements with `flex gap-4 items-end`.

---

## Step 9 — Dependency mapping

The 1.11.x branch used many libraries that are no longer present in 2.0. For each library or
dependency identified in Step 1:

| Legacy dependency | 2.0 equivalent / action |
|---|---|
| jqGrid | `BaseTable` (PrimeVue DataTable) |
| SortableTable | `BaseTable` |
| jQuery UI widgets | PrimeVue components or Tailwind CSS |
| Smarty templates | Vue components |
| PHPMailer direct usage | Symfony Mailer service |
| `Database::get_main_table()` / raw SQL | Doctrine ORM / QueryBuilder |
| `api_get_setting()` / `api_get_configuration_value()` | `SettingsManager` service |
| `Security::remove_XSS()` | `htmlspecialchars()` or Twig/Vue auto-escaping |
| Old file-upload helpers | `UploadedFile` + Chamilo's resource node system |
| `Display::grid_js()` | `BaseTable` |
| `Tracking::*` / `Event::*` logging | Chamilo event system or custom logging |
| `CourseManager::*` | Repository / service methods |

For any dependency not in this table, investigate whether a Symfony service or Composer package
covers the same need. If no equivalent exists, describe the gap to the user and ask how to proceed.

---

## Step 10 — Platform settings audit

1. Collect all calls to `api_get_configuration_value()`, `api_get_setting()`, and `api_get_settings()`
   from every legacy file fetched in Step 1. Record the variable name and its behavioral effect.

2. Also check `1.11.x:main/install/configuration.dist.php` (or the GitHub equivalent) for documented
   field dependencies for each setting.

3. For each setting found:
   a. Search `src/CoreBundle/DataFixtures/SettingsCurrentFixtures.php` and
      `src/CoreBundle/Migrations/` for the variable name.
   b. If the setting **no longer exists**: print
      `[Settings audit] Skipped '<name>' — no longer in settings table.`
   c. If the setting **still exists**: explain to the user what it controls and how you plan to
      implement it in the 2.0 stack. **Wait for user confirmation** before implementing.

4. For each setting that requires a database column or table: verify that column exists in the
   current entity. If it is missing, flag it as a blocker and ask the user how to proceed.

---

## Step 11 — Translations

For every user-visible string in new or updated Vue components and controllers:

1. Verify the key exists in `assets/locales/en_US.json`. If not, add it.
2. Verify the key exists in `translations/messages.en_US.po` **and** `translations/messages.pot`.
   If not, add `msgid` + `msgstr` to both files (`msgstr ""` in `.pot`, actual translation in `.po`).
3. Do not add a key to `en_US.json` that is not already in `messages.en_US.po`.
4. Vue `{0}` / `{1}` placeholders are stored as `%s` / `%d` in `.po` files — the translation
   command converts them automatically.
5. Regenerate the locale JSON:
   ```bash
   php bin/console chamilo:update_vue_translations
   ```

---

## Step 12 — Update old links

Search for references to each legacy PHP file identified in Step 1:
```bash
grep -rn "legacy_file.php" src/ public/main/ assets/
```
Update every hit:
- `src/CoreBundle/Controller/Admin/IndexBlocksController.php` — admin panel block links
- `public/main/admin/index.php` — legacy admin panel
- `public/main/template/default/` — legacy Twig templates
- `assets/vue/components/` — Vue components linking to legacy pages
- `public/main/inc/ajax/model.ajax.php` — jqGrid AJAX allowlists; remove the action from all
  allowlist arrays and `case` blocks

---

## Step 13 — OWASP security review

Review **every** new and modified file (entities, repositories, controllers, migrations, Vue
components, updated legacy links) for common vulnerabilities. Re-run this step after any subsequent
change request that adds features, fixes bugs, or updates links.

### Checklist

- **CSRF on state-changing endpoints.** Every POST/PUT/DELETE controller action that performs
  destructive or sensitive operations must validate a CSRF token:
  `$this->isCsrfTokenValid('intent_name', $token)`. Generate tokens via `CsrfTokenManagerInterface`,
  return them in the data endpoint JSON, and include them as a hidden `_token` field in the Vue
  form submission.

- **API Platform endpoint access control.** Re-verify every `#[ApiResource]` operation against the
  actual access requirements from Step 3. Ask the user to confirm any `GetCollection` or `Get`
  operation that is not restricted to `ROLE_ADMIN` before leaving it open to broader roles.

- **Broken access control.** Verify that `#[IsGranted(...)]` on every controller matches the
  legacy page's access checks. For resource-scoped access (e.g., "only the session coach of *this*
  session"), verify the permission check is done per-resource inside the action, not only at the
  route level. For non-admin roles, filter actionable entity IDs to those the current user owns.

- **SQL injection.** Never interpolate user input into DQL/SQL strings. Always use bound parameters
  (`:paramName` + `setParameter()`). Sort field values must use an allowlist map, never be passed
  directly to `orderBy()`.

- **XSS.** Vue `{{ }}` auto-escapes; verify no `v-html` is used with user-supplied data. Check
  that dynamic `:href` bindings only interpolate integer IDs or known-safe strings.

- **Open redirects.** If a controller builds a redirect URL from user-supplied or database values,
  `urlencode()` those values. Verify the Vue component does not use `window.location.href` with
  unsanitized query param values.

- **Mass parameter manipulation.** Cast array params from the client to safe types before use
  (`array_map('intval', ...)`). Verify non-admin users cannot supply IDs of entities they do not own.

- **Sensitive data exposure via API.** Verify that API Platform group annotations exclude sensitive
  fields (passwords, raw tokens, internal flags). Verify that `GetCollection` endpoints apply
  server-side filtering so a user cannot retrieve another user's private records.

---

## Step 14 — Code quality final pass

Run ECS and Psalm on every PHP file created or modified during this task, fix all reported issues,
and re-run until both tools report zero errors:

```bash
vendor/bin/ecs check src/CoreBundle/Entity/YourEntity.php \
                     src/CoreBundle/Repository/YourEntityRepository.php \
                     src/CoreBundle/Controller/YourController.php

vendor/bin/psalm --show-info=false \
                 src/CoreBundle/Entity/YourEntity.php \
                 src/CoreBundle/Repository/YourEntityRepository.php \
                 src/CoreBundle/Controller/YourController.php
```

Common issues to watch for:
- `QueryBuilderSetParameter`: explicit type required (3rd arg).
- Missing return types or parameter type hints.
- Import ordering, Yoda conditions, trailing commas.
- Unused variables or unreachable code.

---

## Step 15 — Handle the legacy files

**Always ask the user** whether to delete each legacy PHP file or leave a deprecation stub.
During RC phases the default is to keep a stub to avoid breaking bookmarks or external links.

Stub content:
```php
<?php
// Deprecated file left here because contents removed after 2.0 RC2. Should be removed before the next major version.
```

After any deletion or stub replacement, run a final grep to confirm no dangling references remain:
```bash
grep -rn "legacy_file.php" src/ public/main/ assets/
```

---

## Step 16 — Deployment checklist

List the steps the user must run after reviewing and deploying the changes to the target environment:

1. `composer dump-autoload` — rebuild the classmap if new PHP classes were added.
2. `php bin/console doctrine:migrations:migrate --no-interaction` — apply new migrations.
3. `php bin/console cache:clear` — rebuild the Symfony container (required after new entities).
4. `yarn build` — recompile Vue assets if any JS/Vue files were added or changed.
5. `php bin/console chamilo:update_vue_translations` — regenerate locale JSON if translations changed.

---

## Step 17 — Summary

Provide a concise summary of all changes made:

- **Entities created** — class name, table name, API Platform operations and their security levels.
- **Repositories created** — class name, notable query methods.
- **Migrations** — migration file name and what schema changes it applies.
- **Controllers** — file path, routes, access control applied.
- **Vue views / router entries** — file paths and route names.
- **Translation keys added** — list of new keys.
- **Legacy files stubbed or deleted** — file paths.
- **Settings implemented or skipped** — with reason for each skipped one.
- **Known limitations or follow-up items** — anything that could not be fully restored and why.

Also update `CLAUDE.md` (project root) if any new discovered patterns emerged during this migration
that should be remembered for future work.

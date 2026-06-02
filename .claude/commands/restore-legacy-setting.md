Restore implementation of the platform setting `$ARGUMENTS` in the current branch. The argument can be `category.variable` (e.g., `session.hide_search_form_in_session_list`) or just `variable` (e.g., `hide_search_form_in_session_list`). Work through each step below in order.

## Step 1 — Identify the setting

1. Parse `$ARGUMENTS`: if it contains a `.`, split into `category` and `variable`. Otherwise, treat the whole string as `variable`.
2. Search `src/CoreBundle/DataFixtures/SettingsCurrentFixtures.php` for the variable name to confirm the setting exists in the current branch and note its category, title, and default value.
3. If the setting is **not found** in `SettingsCurrentFixtures.php`, also search `src/CoreBundle/Migrations/` for it — it may have been added via a migration rather than a fixture. If still not found, inform the user that this setting does not exist in the current settings table and stop.

## Step 2 — Understand what the setting does

### Current branch

1. Grep the entire codebase for the variable name:
```bash
grep -rn "setting_variable_name" src/ public/main/ assets/ config/
```
2. For each hit, note **where** it is used (controller, legacy PHP page, Vue component, config file) and **what** it does (hides a UI element, changes a query, enables a feature, etc.).

### 1.11.x branch

3. The setting may have had broader usage in the 1.11.x branch. Check the 1.11.x version of files that use it (files in `main/` in 1.11.x correspond to `public/main/` in the current branch). If the `1.11.x` branch exists locally:
```bash
git show 1.11.x:main/path/to/file.php
```
Otherwise, fetch from `https://raw.githubusercontent.com/chamilo/chamilo-lms/1.11.x/main/path/to/file.php`.

4. Also search `1.11.x:main/install/configuration.dist.php` for the variable name — this file often documents the setting's purpose, default value, and any database field dependencies.

5. Compile a summary: what the setting controls, its possible values, and the user-facing behavior change when toggled.

## Step 3 — Check for required database fields

Some settings depend on specific database columns or entity properties.

1. From the 1.11.x research (especially `configuration.dist.php`), identify any database fields the setting depends on.
2. Verify those fields exist in the current Doctrine entities (`src/CoreBundle/Entity/`) or database schema.
3. **If a required field is missing**, this is a blocker. Report it to the user and ask how to proceed before continuing.

## Step 4 — Check current implementation status

Determine whether the setting is already fully implemented:

1. If the setting is read in a **Symfony controller** and its value influences the response (e.g., conditionally includes data, hides an action), it is likely implemented on the backend.
2. If the setting value is passed to a **Vue component** (via the data endpoint JSON or a global config) and the component conditionally renders based on it, it is likely implemented on the frontend.
3. If the setting is **only** referenced in legacy PHP files under `public/main/` but not in any Symfony controller or Vue component that replaced those files, it is **not implemented** in the new stack.

Report your findings. If the setting is already fully implemented, inform the user and stop. If partially or not implemented, proceed to the next step.

## Step 5 — Plan the implementation

Present the implementation plan to the user **before writing any code**. Include:

- **What the setting does** (from Step 2).
- **Where the setting needs to be read** — typically in a Symfony controller that serves data to a Vue component. Use `api_get_setting('category.variable')` in legacy code, or the `SettingsManager` service in Symfony controllers.
- **How the setting value reaches the frontend** — usually as a field in the data endpoint's JSON response (e.g., `'showSearchForm' => 'true' === $settingValue`).
- **What changes in the Vue component** — typically a `v-if` or `v-show` conditional, a default value override, or a column/button toggle.
- **Any edge cases** — e.g., the setting may need to work differently for admins vs. regular users, or may interact with other settings.

**Wait for user confirmation** before proceeding with implementation.

## Step 6 — Implement

### Backend (if applicable)

Read the setting in the relevant Symfony controller. Follow these conventions:

- Use `SettingsManager::getSetting('category.variable')` or inject the setting via the service container.
- Pass the resolved boolean/value in the JSON response of the data endpoint.
- Follow all PHP code style rules (see CLAUDE.md): `declare(strict_types=1)`, Yoda conditions, no-space concatenation, trailing commas, ordered imports, explicit `setParameter()` types, etc.

### Frontend (if applicable)

- Read the setting value from the data endpoint response.
- Use `v-if` / `v-show` to conditionally render UI elements.
- Follow Vue conventions: `<script setup>`, Tailwind classes, design guide (button CRUD colors, MDI icons with `ch-tool-icon`).

### Translations

If the implementation introduces new user-facing strings:
1. Verify each key exists in `translations/messages.en_US.po` and `translations/messages.pot`.
2. Add to `assets/locales/en_US.json` only if the key already exists in the `.po` files.

## Step 7 — Code quality checks

Run both checks on every modified PHP file and fix any issues:

```bash
vendor/bin/ecs check src/CoreBundle/Controller/Path/To/ModifiedFile.php
vendor/bin/psalm --show-info=false src/CoreBundle/Controller/Path/To/ModifiedFile.php
```

Common issues to watch for:
- `QueryBuilderSetParameter`: explicit type required (3rd arg) — `Types::INTEGER`, `Types::DATETIME_MUTABLE`, `ArrayParameterType::INTEGER`.
- Import ordering, Yoda conditions, trailing commas.
- Missing return types or parameter type hints.

Both tools must pass with zero errors before proceeding.

## Step 8 — Security review

Review every new or modified file for:

- **SQL injection**: no raw interpolation in DQL/SQL. Always use bound parameters.
- **XSS**: no `v-html` with user data. Dynamic `:href` bindings only interpolate safe values (integer IDs, known constants).
- **Broken access control**: if the setting enables/disables an action, ensure the backend enforces it too — do not rely solely on hiding a frontend button.
- **CSRF**: if the setting adds or modifies a state-changing action, validate a CSRF token.

## Step 9 — Summary

Print a summary of all changes made:
- Files modified/created
- How the setting is now read and applied
- Any caveats or follow-up items

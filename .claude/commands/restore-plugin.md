Migrate the legacy Chamilo 1.x plugin `$ARGUMENTS` to work with Chamilo 2.0. Work through each step below in order, reading the relevant files before making changes.

## Step 1 — Audit the plugin

Read all files in `public/plugin/$ARGUMENTS/` and answer:
- What is the plugin class name and where is it defined?
- What settings does it register (constructor `$parameters` array)?
- Does it create database tables? If so, what tables and columns?
- Does it have AJAX endpoints (files in `ajax/` directory)?
- What templates does it use (`.tpl` or `.html.twig` files)?
- What language files exist (`lang/` directory)?
- What plugin regions does it target?
- Does it hook into events (check for `EventSubscriber` or event-related code)?
- Who can access it (admin only, any logged-in user, course users)?
- Are there any resources bundled (CSS, JS libraries, images)?

Also check if `composer.json` classmaps cover `public/plugin/` (it should — this is standard).

**Important:** After creating or renaming any PHP class under `public/plugin/`, `composer dump-autoload` must be run so the classmap discovers the new files. After creating new `Entity/` directories, `php bin/console cache:clear` must also be run so the `PluginEntityPass` compiler pass registers the new entity metadata with Doctrine. Without both of these steps, the plugin class and its entities will not be autoloadable.

## Step 2 — Rename the plugin class

The 2.0 convention is `{Name}Plugin extends Plugin` in `src/{Name}Plugin.php`.

### Rules
- Rename `src/{old_name}_plugin.class.php` → `src/{Name}Plugin.php` (or whatever the current filename is).
- Rename the class from `{OldName} extends Plugin` to `{Name}Plugin extends Plugin`.
- Update `create()` return type to `static`.
- Add `declare(strict_types=1);` at the top.
- Delete the old class file after creating the new one.

### Update references
Update all files that reference the old class name:
- `plugin.php` — `{Name}Plugin::create()->get_info()`
- `install.php` — `{Name}Plugin::create()->install()`
- `uninstall.php` — `{Name}Plugin::create()->uninstall()`
- `index.php` — `{Name}Plugin::create()`
- `config.php` — if it references the class
- Any AJAX files in `ajax/`
- Any controller files in `src/Controller/`

## Step 3 — Fix config.php

Replace any broken bootstrap path with the 2.0 pattern:

```php
<?php

declare(strict_types=1);

$course_plugin = '{PluginName}';

require_once __DIR__.'/../../main/inc/global.inc.php';
```

Remove any `define('TABLE_*', ...)` constants — these will be handled by Doctrine entities.

## Step 4 — Create Doctrine entities

For each database table the plugin creates (found in `install()` or `installDatabase()` raw SQL):

### Rules
- Create entity in `public/plugin/{Name}/src/Entity/{EntityName}.php`
- Namespace: `Chamilo\PluginBundle\{Name}\Entity`
- Use PHP 8.1 attributes (`#[ORM\Entity]`, `#[ORM\Table]`, `#[ORM\Column]`)
- Table name must match the existing table (e.g., `plugin_tour_log`)
- Use `TimestampableTypedEntity` trait if the table has created_at/updated_at columns
- For foreign keys to core entities (User, Course, Session), use `#[ORM\ManyToOne]` with the correct target entity and join column
- For simple user references where a full relation is not needed, `#[ORM\Column(type: 'integer')]` for the user ID is acceptable
- Add proper getters/setters for all properties
- The `PluginEntityPass` compiler pass auto-discovers entities in `Entity/` directories under `public/plugin/` — no manual registration needed

### Reference pattern (from ExerciseMonitoring)
```php
namespace Chamilo\PluginBundle\{Name}\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'plugin_{name}_{table}')]
class {EntityName}
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    // ... columns matching the old CREATE TABLE SQL
}
```

## Step 5 — Update install/uninstall

Replace raw SQL table creation with Doctrine SchemaTool.

**Important prerequisite:** `composer dump-autoload` and `php bin/console cache:clear` must have been run after creating the entity files (Step 4). Without this, `$em->getClassMetadata()` will fail because Doctrine does not know about the entity class yet.

### Install pattern (from TopLinks)
```php
public function install(): void
{
    $em = Database::getManager();
    $schemaManager = $em->getConnection()->createSchemaManager();

    if ($schemaManager->tablesExist(['plugin_{name}_{table}'])) {
        return;
    }

    $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($em);
    $schemaTool->createSchema([
        $em->getClassMetadata(\Chamilo\PluginBundle\{Name}\Entity\{Entity}::class),
    ]);
}
```

### Uninstall pattern
```php
public function uninstall(): void
{
    $em = Database::getManager();

    $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($em);
    $schemaTool->dropSchema([
        $em->getClassMetadata(\Chamilo\PluginBundle\{Name}\Entity\{Entity}::class),
    ]);
}
```

## Step 6 — Modernize AJAX endpoints

For each file in `ajax/`:

### Rules
- Add `declare(strict_types=1);` at the top
- Replace `require_once __DIR__.'/../../../main/inc/global.inc.php'` with `require_once __DIR__.'/../config.php'`
- Replace `api_is_anonymous()` checks with proper access control
- Replace raw SQL (`Database::select()`, `Database::insert()`, `Database::query()`) with Doctrine entity manager operations
- Use `json_encode()` with proper `Content-Type: application/json` header for responses
- Validate and sanitize all input parameters (`$_GET`, `$_POST`)

### Optional: Convert to Symfony controllers
For more complex plugins, AJAX endpoints can be converted to proper controller classes in `src/Controller/`:
```php
namespace Chamilo\PluginBundle\{Name}\Controller;

class {Name}Controller
{
    public function __invoke(): Response
    {
        // Controller logic
    }
}
```
These are instantiated manually in the PHP bootstrap file (not via Symfony routing). See `ExerciseFocused/admin.php` for the pattern.

## Step 7 — Migrate templates

### Smarty `.tpl` → Twig `.html.twig`
- Rename template files from `.tpl` to `.html.twig`
- Convert Smarty syntax to Twig:
  - `{$variable}` → `{{ variable }}`
  - `{if condition}` → `{% if condition %}`
  - `{foreach}` → `{% for %}`
  - `{assign var=x value=y}` → `{% set x = y %}`
- Update `plugin.php` to reference the new template filename
- Template variables are passed via `$_template` array in `index.php`

### Critical: Twig variable namespacing

`getAllPluginContentsByRegion()` in `plugin.lib.php` passes template variables **namespaced under the plugin name**:
```php
echo Container::getTwig()->render("$plugin_name/$pluginTemplate", [$plugin_name => $_template]);
```
This means if your plugin is called `Tour`, all `$_template` variables set in `index.php` are accessible in Twig under the `Tour.` prefix:
- `$_template['show_tour'] = true;` → `{{ Tour.show_tour }}` in Twig
- `$_template['my_data']` → `{{ Tour.my_data }}` in Twig
- `{% if Tour.show_tour is defined and Tour.show_tour %}` — not `{% if show_tour %}`

**Forgetting this prefix is the most common reason a plugin template renders as empty.** The `PluginRegionController` skips blocks with empty HTML, so the Vue SPA receives an empty `blocks[]` array and nothing appears.

### Twig filter/function equivalents
- `{{ 'Key' | get_lang }}` — translate a core platform string
- `{{ 'Key' | get_plugin_lang('{Name}Plugin') }}` — translate a plugin-specific string (note: the argument is the **class name**, e.g., `'TourPlugin'`, not the directory name)
- `{{ url('route_name') }}` — generate a URL

### For plugins injecting JavaScript
If the template outputs `<script>` tags, these will be executed by the `usePluginRegion` composable in the Vue SPA context. The composable re-executes inline scripts after injecting the HTML block. Keep this in mind:
- Use `document.querySelector()` instead of jQuery selectors when possible
- Access the current route via `window.location.pathname` or `window.location.hash`
- Any external JS libraries must be loaded dynamically (the old `$.getScript()` pattern still works, or use `document.createElement('script')`)

## Step 8 — Update language strings

- Review `lang/english.php` and update any outdated references (e.g., "Chamilo 1.9.x" → "Chamilo")
- Add new keys if the template was restructured
- Update translations in other language files (`french.php`, `spanish.php`, etc.)
- Plugin language strings use `$strings['Key']` format and are accessed via `$plugin->get_lang('Key')` in PHP or `{{ 'Key' | get_plugin_lang('PluginName') }}` in Twig

## Step 9 — Update index.php and plugin.php

### index.php
This is the main entry point loaded by the plugin region system. It:
1. Instantiates the plugin
2. Checks if the plugin is enabled / conditions are met
3. Sets `$_template` variables that the Twig template will use
4. Is called by `AppPlugin::loadRegion()` (legacy) and `PluginRegionController` (Vue SPA)

Update to use the new class name and modern patterns.

### Plugin regions in the Vue SPA

Currently, **only `content_bottom`** is rendered in the Vue SPA (`App.vue` contains `<PluginBlockRenderer region="content_bottom" />`). Other regions (e.g., `footer_center`, `header_right`) are only rendered in legacy pages.

If the plugin needs to be visible in the Vue SPA, it **must** be assigned to `content_bottom` in the admin panel. If the plugin injects UI into a different part of the page (e.g., the topbar), use `content_bottom` as the region for loading, then use JavaScript in the template to relocate the UI element to the desired DOM location (e.g., `document.querySelector('.app-topbar__items').appendChild(btn)`).

### plugin.php
Must reference:
- The new class: `$plugin_info = {Name}Plugin::create()->get_info();`
- The new template: `$plugin_info['templates'] = ['views/{template}.html.twig'];`

## Step 10 — Clean up bundled libraries

If the plugin bundles JavaScript/CSS libraries in its directory:
- Check if the library is already available in the project (search `package.json`, `node_modules/`)
- If available via npm, prefer using the npm version (import in the template or load from `build/` output)
- If not available and still needed, keep the bundled version
- Remove any font files that duplicate system fonts (e.g., Lato is already in the project's CSS)

## Step 11 — PHP code style check

Run the code style checker on all modified PHP files:
```bash
vendor/bin/ecs check public/plugin/{Name}/src/ public/plugin/{Name}/config.php public/plugin/{Name}/plugin.php public/plugin/{Name}/install.php public/plugin/{Name}/uninstall.php public/plugin/{Name}/index.php public/plugin/{Name}/ajax/
```

Fix any issues reported. Common fixes:
- `declare(strict_types=1);` at the top of every file
- Yoda conditions: `'value' === $var`
- No spaces around `.` concatenation
- Blank line before `exit;` and `return;`
- `static $result;` not `static $result = null;`

## Step 12 — OWASP security review

Review every modified file for common vulnerabilities:

- **CSRF on state-changing endpoints.** POST endpoints that write data should validate a token or at minimum check `api_is_anonymous()` and user permissions.
- **SQL injection.** Verify no raw user input is interpolated into queries. All Doctrine operations use parameterized queries automatically. For any remaining `Database::query()` calls, ensure parameters are properly escaped.
- **XSS.** Twig auto-escapes `{{ }}` by default. Verify no `|raw` filter is used with user-supplied data. Check that AJAX endpoints returning JSON don't include unescaped HTML.
- **Access control.** Verify that admin-only functionality checks `api_protect_admin_script()` or equivalent. Verify that user-specific data is filtered by the current user's ID.
- **File inclusion.** Verify that no user input is used in `require`/`include` paths.

## Step 13 — Test and verify

1. Verify the plugin can be enabled from the admin panel (Plugin settings page)
2. Verify install creates the database tables
3. Verify the plugin renders in its configured region
4. Verify AJAX endpoints work (check browser network tab)
5. Verify uninstall drops the tables cleanly

If testing is not possible in the current environment, list the manual test steps for the user.

### Deployment checklist
After deploying new/modified plugin files to the server:
1. `composer dump-autoload` — registers new PHP classes in the classmap
2. `php bin/console cache:clear` — rebuilds the Symfony container (required for new entities)
3. In the admin panel → Plugins: install/enable the plugin and assign it to a region

### Debugging a plugin that doesn't render in the Vue SPA
If the plugin is enabled and assigned to `content_bottom` but nothing appears:

1. **Check the API response.** Open browser DevTools → Network tab, look for `/plugin-regions/content_bottom`. If `blocks` is `[]`, the issue is server-side.
2. **Check the Twig template produces output.** The most common cause of empty blocks is forgetting the Twig variable namespace prefix (see Step 7). Variables are under `{PluginName}.variable`, not just `variable`.
3. **Check the DOM.** Run `document.querySelector('[data-region="content_bottom"]')?.innerHTML` in the console. If `undefined`, the blocks array is empty and the `v-if` hides the container.
4. **Check scripts execute.** The `usePluginRegion` composable re-creates `<script>` tags from the injected HTML to force browser execution. If scripts still don't run, check for JS errors in the console.

## Step 14 — Summary

Provide a summary of all changes made:
- Files created
- Files modified
- Files deleted
- Database tables affected
- Any breaking changes or migration notes
- Remaining TODO items (if any functionality could not be migrated)

# MigrationMoodlePlugin

Allow migrating course contents and user progress from a Moodle platform.

> This plugin is a migration tool. Run it only on a controlled environment and after a full database/files backup.

## What this plugin does

MigrationMoodle reads data from an external Moodle database and Moodle `moodledata` directory, then loads courses, users, learning paths, quizzes, SCORM data and progress into Chamilo.

The plugin creates two tracking tables:

- `plugin_migrationmoodle_task`
- `plugin_migrationmoodle_item`

It also creates the user extra field `moodle_password`, used by the login subscriber to validate migrated Moodle password hashes when the plugin is enabled and the field is present for the user.

## Configuration

Configure the plugin from the Chamilo plugin administration page.

Required for migration tasks:

- Moodle DB host
- Moodle DB user
- Moodle DB name

Optional but required for file-related tasks:

- Moodledata path

Other settings:

- User filter: imports only users whose username starts with this prefix.
- URL ID: access URL where courses, users and sessions are assigned. Defaults to `1`.

The plugin activation status is controlled from the Chamilo plugin list. There is no additional `active` setting inside the plugin configuration.

## Running tasks from the browser

Open the plugin administration page:

```text
/plugin/MigrationMoodle/admin.php
```

Tasks are shown in dependency order. A task becomes clickable only when its parent task has been completed.

Task links include a Chamilo security token and ask for confirmation before execution.

## Running tasks from CLI

```bash
php public/plugin/MigrationMoodle/run_cli.php
```

The CLI runner stops if the plugin is disabled or the required Moodle database configuration is missing.

## Notes

- Always test on a copy of the target platform first.
- Run tasks in order.
- Do not run the same task manually twice unless you have reviewed the data already imported.
- Check if an index exists on `c_lp_item_view.status` in Chamilo DB to optimize `UserScormProgressLoader`.
- Some tasks require a Moodle DB with MySQL 8 or MariaDB 10.2.2.

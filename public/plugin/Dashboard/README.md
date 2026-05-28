# Dashboard plugin for Chamilo 2

This plugin exposes a compact administration dashboard for Chamilo 2.

## What changed

The original `Dashboard` directory only contained legacy `block_*` classes and `.info` files. Those blocks were designed for the old dashboard controller and depend on a legacy `Block` base class that is not present in the current Chamilo 2 stack.

This version adds a real Chamilo 2 plugin wrapper:

- `plugin.php`
- `src/DashboardPlugin.php`
- `admin.php`
- `install.php`
- `uninstall.php`
- `lang/en_US.php`
- `lang/es.php`

The legacy block files are kept as historical reference, but they are not automatically loaded.

## Access

Only platform administrators can access:

```text
/plugin/Dashboard/admin.php
```

The plugin is also marked as an admin plugin, so Chamilo can expose the admin URL through the plugin administration interface.

## Data shown

The dashboard uses read-only counts from current Chamilo 2 tables:

- `user`
- `course`
- `session`
- `c_lp`
- `c_quiz`
- `resource_file`
- `asset`

If a table does not exist in a customized installation, the count falls back to `0` and an error is written to the PHP error log with the `[Dashboard]` prefix.

## No database changes

The plugin does not create or modify database tables.

## Legacy blocks

The old `block_*` files are not removed, but they should not be considered Chamilo 2-ready. They rely on old dashboard integration patterns, old graph rendering assumptions, and a missing legacy `Block` class.

A future migration could convert selected blocks to a Symfony data endpoint plus a Vue view, but that should be done incrementally and only for blocks that are still required.


## Legacy block files

The `block_*` directories are kept for historical reference only. They are not loaded by the Chamilo 2 admin page because they depend on the old dashboard block architecture.


## v4 UI update

The admin page includes lightweight chart panels rendered with existing Chamilo/Tailwind utility classes. No external JavaScript charting dependency is added. The plugin remains read-only and uses database counts only.

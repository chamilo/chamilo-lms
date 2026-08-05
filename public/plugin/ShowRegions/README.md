# Show regions plugin for Chamilo 2

Show regions is a small diagnostic plugin that renders a visible marker in each plugin region where it is assigned.

## Purpose

Use this plugin to identify where Chamilo 2 plugin regions are rendered in the current layout.

The marker is shown only to platform administrators. Regular users do not see anything.

## How to use

1. Install and enable the plugin from `Administration > Plugins`.
2. Open `Administration > Settings > Regions`.
3. Assign `Show regions` to one or more regions.
4. Save the settings.
5. Browse the platform as a platform administrator.

Each enabled region displays a small yellow marker with the technical region name.

## Recommended usage

Enable it only while developing or debugging plugin layouts, then remove the assigned regions when finished.

## Security notes

- The output is restricted to platform administrators.
- The region name and request path are escaped before rendering.
- The plugin does not write database records beyond the standard Chamilo plugin region assignment.
- The plugin does not create tables, entities, Vue components or Symfony controllers.

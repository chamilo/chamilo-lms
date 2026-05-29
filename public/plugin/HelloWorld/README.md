# HelloWorld plugin for Chamilo 2

This is a minimal example plugin for Chamilo 2.

## Purpose

The plugin renders a small translated greeting in any plugin region where it is assigned. It is useful as a simple reference for:

- `plugin.php` metadata.
- A small `Plugin` subclass.
- Plugin settings.
- Region rendering through `index.php`.

## Recommended regions

In the Vue SPA, Chamilo 2 currently mounts these regions globally:

- `content_bottom`
- `pre_footer`

For visible UI, prefer `content_bottom`.

## Configuration

The plugin has one setting:

- `show_type`: selects which greeting is rendered.

Available values:

- Hello world
- Hello
- Hi

## Notes

This plugin does not create database tables, course tools, Vue components or Symfony controllers.

# ExtraMenuFromWebservice for Chamilo 2

This plugin adds an optional extra menu to the Chamilo 2 sidebar.

## Chamilo 2 approach

The old plugin injected raw HTML, CSS and JavaScript returned by a remote webservice. That is not aligned with the Chamilo 2 UI, because the main navigation is rendered by Vue.

This version uses this flow:

1. The plugin lifecycle keeps `display.show_tabs.menu.extra_menu_from_webservice` synchronized.
2. When the plugin is installed, the key is added as `false`.
3. When the plugin is enabled, the key is set to `true`.
4. When the plugin is disabled, the key is set to `false`.
5. When the plugin is uninstalled, the key is removed.
6. The Vue sidebar loads `/plugin/ExtraMenuFromWebservice/menu.php` only when the menu entry is enabled.
7. `menu.php` checks the current Chamilo user and plugin status.
8. The plugin calls the configured API Platform compatible endpoint.
9. The response is normalized to safe menu items.
10. Vue renders the menu using Chamilo/Tailwind classes.

The plugin does not render arbitrary remote HTML/CSS/JS.

## Sidebar visibility setting

The plugin uses this key in `display.show_tabs`:

```json
{
  "menu": {
    "extra_menu_from_webservice": true
  }
}
```

The key is managed automatically by the plugin lifecycle:

- install: add the key as `false`
- enable: set the key to `true`
- disable: set the key to `false`
- uninstall: remove the key


## Configuration

Configure the plugin from the Chamilo plugin settings page.

Recommended settings:

- **API menu URL**: API Platform endpoint returning menu items.
- **Static bearer token**: optional token used as `Authorization: Bearer ...`.
- **Authentication URL, email and password**: optional alternative to obtain a token dynamically.
- **Menu request mode**: keep `API Platform query parameters` for the new flow.
- **Menu cache TTL**: default 300 seconds.
- **HTTP request timeout**: default 3 seconds.

Legacy settings `normal_menu_url` and `mobile_menu_url` are kept only as backward-compatible fallback when `api_menu_url` is empty.

## Expected API response

The endpoint can return a plain list:

```json
{
  "items": [
    {
      "title": "Help center",
      "url": "https://example.com/help",
      "icon": "mdi-help-circle",
      "target": "_blank"
    },
    {
      "title": "Internal page",
      "url": "/page/example",
      "icon": "mdi-link"
    }
  ]
}
```

It can also return an API Platform collection:

```json
{
  "@context": "/api/contexts/MenuItem",
  "@type": "hydra:Collection",
  "hydra:member": [
    {
      "title": "Help center",
      "url": "https://example.com/help",
      "icon": "mdi-help-circle",
      "target": "_blank"
    }
  ],
  "hydra:totalItems": 1
}
```

Supported item fields:

- `title`, `label` or `name`
- `url`, `href` or `path`
- `icon` using MDI names, for example `mdi-help-circle`
- `target`, only `_self` and `_blank` are accepted
- `children`, optional nested items
- `roles`, optional list using values like `ROLE_ADMIN`, `ROLE_TEACHER`, `ROLE_STUDENT`, `ROLE_USER`

## Security

The plugin intentionally rejects:

- raw HTML from the webservice
- remote CSS injection
- remote JavaScript injection
- unsupported URL schemes such as `javascript:`
- unsupported icon class names

Remote requests have a short timeout and menu data is cached per user session.


## Current access URL synchronization

The plugin updates `display.show_tabs` through the SettingsManager configured with the current access URL.
This is required in multi-URL installations and also avoids writing the sidebar key into a non-visible settings scope.

## Local test menu

This repository includes a static test file that can be used to verify the sidebar rendering without an external webservice:

```text
http://chamilo2.local/plugin/ExtraMenuFromWebservice/test-menu.json
```

For a local test, configure the plugin with:

- **API menu URL**: `http://chamilo2.local/plugin/ExtraMenuFromWebservice/test-menu.json`
- **Menu request mode**: `API Platform query parameters`
- **Menu cache TTL**: `0` while testing, or clear the session after changing the menu URL
- **HTTP request timeout**: `3`

Then verify:

1. The plugin is installed and enabled.
2. `display.show_tabs` contains `"extra_menu_from_webservice": true` under `menu`.
3. Open `/plugin/ExtraMenuFromWebservice/menu.php`.
4. It should return `enabled: true` and a non-empty `items` array.
5. Rebuild Vue assets if the sidebar changes were just applied.

The remote service can return PrimeVue-like MenuItem fields such as `label`, `url`, `target`, `icon` and nested `children`. The plugin normalizes them before Vue renders the sidebar.

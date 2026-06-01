# ExtraMenuFromWebservice for Chamilo 2

This plugin adds an optional extra menu to the Chamilo 2 topbar.

## Chamilo 2 approach

The old plugin injected raw HTML, CSS and JavaScript returned by a remote webservice. That is not aligned with the Chamilo 2 UI, because the main navigation is rendered by Vue.

This version uses this flow:

1. The Vue topbar component loads `/plugin/ExtraMenuFromWebservice/menu.php`.
2. `menu.php` checks the current Chamilo user and plugin status.
3. The plugin calls the configured API Platform compatible endpoint.
4. The response is normalized to safe menu items.
5. Vue renders the menu using Chamilo/Tailwind classes.

The plugin does not render arbitrary remote HTML/CSS/JS.

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

# ExtAuthChamiloLogoutButtonBehaviour for Chamilo 2

This plugin customizes the logout action for users authenticated through an external provider.

Chamilo 2 renders the user menu and sidebar with Vue, so the old jQuery template that modified `#logout_button` is no longer used. This version exposes a local JSON endpoint and the Vue logout entries read that endpoint before rendering or executing the logout action.

## Behavior

The plugin can:

- change the logout URL used by the topbar and sidebar logout actions;
- show an alert before redirecting;
- disable the logout action by setting the URL to `#`;
- apply the behavior only to users whose authentication source is not the local/platform source.

## Settings

- `behavior_enabled`: enables this plugin behavior.
  The old `enabled` setting key is still read as a fallback for backward compatibility.
- `apply_only_to_external_auth`: applies the behavior only to external-authenticated users.
- `logout_url`: destination used when the user clicks Sign out. Use `/logout` for normal Chamilo logout, an absolute `https://` URL for a trusted SSO logout endpoint, or `#` to disable navigation.
- `tooltip_text`: optional title text used in the sidebar logout link.
- `show_alert`: shows a browser alert before redirecting.
- `alert_text`: text displayed in the alert.

## Security notes

The endpoint only returns a normalized URL. It accepts:

- relative URLs starting with `/`;
- absolute `http://` or `https://` URLs;
- `#` for disabled navigation.

Other schemes such as `javascript:` are rejected and replaced with `/logout`.

## Files touched outside the plugin

Chamilo 2 renders logout links in Vue, so two Vue files are updated:

- `assets/vue/composables/useTopbarLoggedIn.js`
- `assets/vue/components/layout/Sidebar.vue`

No database migration is required.

## Compatibility note

The behavior switch uses `behavior_enabled` instead of `enabled` to avoid ambiguity with the plugin installation/enabled state managed by Chamilo.

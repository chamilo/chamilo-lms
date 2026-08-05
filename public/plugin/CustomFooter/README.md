# CustomFooter plugin

## Purpose

CustomFooter adds configurable HTML content to the platform footer area in Chamilo 2.x.

The plugin renders a two-column block inside the global `pre_footer` plugin region:

- Left footer content
- Right footer content

This keeps the historical left/right footer behavior while avoiding conflicts with other plugins that may also use footer regions.

## Configuration

1. Install and enable `CustomFooter`.
2. Go to:

   `/main/admin/configure_plugin.php?plugin=CustomFooter`

3. Configure:
   - Footer left
   - Footer right

Both fields are WYSIWYG HTML fields.

Example left content:

```html
<p><strong>Custom footer left</strong></p>
<p>This content is displayed in the left footer column.</p>
```

Example right content:

```html
<p><strong>Custom footer right</strong></p>
<p>This content is displayed in the right footer column.</p>
```

## Region assignment

1. Go to:

   `/main/admin/settings.php?category=Regions`

2. Assign `CustomFooter` to:

   `Before footer (pre_footer)`

3. Save settings.

## Expected behavior

- The configured footer content appears before the default platform footer.
- The content is split into two columns.
- The plugin does not render anything if it is disabled.
- The plugin does not render anything if both footer fields are empty.
- HTML is sanitized with `Security::remove_XSS()` before rendering.

## Compatibility notes

The plugin keeps fallback support for old legacy settings:

- `customfooter_footer_left`
- `customfooter_footer_right`

This allows existing installations to keep showing older configured content when the new access URL plugin settings are empty.

## Security

- Configured HTML is sanitized before rendering.
- The plugin does not expose public write actions.
- Installation and uninstallation do not create or drop custom database tables.
- No secret values or API keys are used.

## Testing checklist

1. Enable the plugin.
2. Configure left and right footer HTML.
3. Assign the plugin to `pre_footer`.
4. Open a normal platform page.
5. Confirm the footer appears once.
6. Clear one field and confirm only the other column appears.
7. Disable the plugin and confirm the footer disappears.
8. Test basic XSS input:

```html
<script>alert('xss')</script>
<p><strong>Safe footer text</strong></p>
```

Expected result: the script must not execute.

Maintenance Mode plugin
=======================

This Chamilo 2 version uses a plugin EventSubscriber.

The plugin is fully self-contained. It does not require server rewrite rules and
does not create a standalone public maintenance page.

The plugin is controlled directly from the plugin list:

- Enabled: maintenance mode is active.
- Disabled: maintenance mode is inactive.

The subscriber lives in:

`public/plugin/MaintenanceMode/src/EventSubscriber/MaintenanceModeEventSubscriber.php`

Because the current plugin compiler pass loads subscriber classes by their file
basename, the subscriber intentionally has no namespace, matching the pattern
used by other working plugins.

The subscriber returns an HTTP 503 maintenance page for non-administrators and
keeps administration, login/logout and static asset routes available so the
plugin can be disabled again.

The maintenance response is intentionally self-contained and does not depend on
Chamilo assets, Twig, translations, sessions or legacy helpers. This keeps the
response safe during early `kernel.request` handling.

Limit: a Symfony subscriber can only intercept requests handled by the Symfony
kernel. Direct legacy PHP files served by Apache outside Symfony are not covered
by this mechanism.

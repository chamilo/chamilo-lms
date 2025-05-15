Redirection plugin
===

Chamilo plugin for the redirection of specific users after they login and
redirect from the index.php to the selected URL.

1. Requires the addition of the following in configuration.php:

```
$_configuration['plugin_redirection_enabled'] = true;
```

This setting is defined in configuration.php rather than in settings_current to reduce the
load, as it is used on every login.

2. Add the plugin in the "menu administration" region.

@TODO Check the load difference for *just* checking it in settings_current rather than building the plugin object

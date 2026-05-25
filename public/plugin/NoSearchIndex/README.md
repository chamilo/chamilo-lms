# NoSearchIndex

This Chamilo 2 version is intentionally simple.

When the plugin is active from **Administration > Plugins**, Chamilo renders this tag inside the global HTML `<head>`:

```html
<meta name="robots" content="noindex, nofollow, noarchive" data-plugin="NoSearchIndex">
```

The plugin does not edit `robots.txt`, does not register dynamic plugin subscribers, and does not change web server configuration.

This only affects HTML pages rendered by Chamilo. Static files served directly by Apache/Nginx are not modified by the plugin.

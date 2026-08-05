User Remote Service Plugin
==========================

This plugin exposes signed user-specific links to external services.

Flow
----

1. Enable the plugin from the plugin list.
2. Configure the plugin settings:
   - Salt: required to generate signed hashes.
   - Hide links from navigation menu: optional.
3. Open the plugin administration page and create remote services with HTTP/HTTPS URLs.
4. Authenticated users can open the service through:
   - iframe.php?serviceId=ID
   - redirect.php?serviceId=ID

Generated parameters
--------------------

Iframe URLs receive:

- username
- hash

Redirect URLs receive:

- uid
- hash

The remote service can verify the hash with:

```php
password_verify($salt.$userId, $hash)
```

Security notes
--------------

- Only authenticated Chamilo users can open service links.
- Service URLs must use HTTP or HTTPS.
- Empty or invalid service IDs are rejected.
- The plugin does not create or update Chamilo users.
- The plugin only signs the current authenticated user and redirects/embeds the configured service.

# The OAuth2 Plugin
Allows authentication with a generic OAuth2 provider.

This plugin adds an extra field to users :
- `oauth2_id`, to store each users' OAuth2 identifier.

> This plugin uses the [`league/oauth2-client`](https://oauth2-client.thephpleague.com/) package.

### To configure the OAuth2 server
The OAuth2 server administrator must give you an OAuth2 client identifier and secret and enter this callback URL :
`https://{CHAMILO_URL}/plugin/oauth2/src/callback.php`.

### To configure this plugin
* Enable it
* Fill in the setting parameters (read the help messages)
* assign a region. Preferably `login_bottom`.

Also, you can configure the external login to work with the classic Chamilo form login.
Adding this line in `configuration.php` file.
```php
$extAuthSource["oauth2"]["login"] = $_configuration['root_sys']."main/auth/external_login/login.oauth2.php";
```

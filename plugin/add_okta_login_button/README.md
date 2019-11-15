Add Okta login button plugin
===

This plugin adds a button to allow users to log into Chamilo through their Okta account.

To display this button on your portal, you have to:
 
* enable the Okta authentication setting and configure it
* add the Client ID and the Client Secret provided by Okta inside the app/config/auth.conf.php file
* set the following line in your app/config/configuration.php
```
$_configuration['okta_auth'] = 1;
```

This plugin has been developed to be added to the login_top or login_bottom region in Chamilo, but you can put it in whichever region you want.

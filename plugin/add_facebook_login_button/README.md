Add Facebook login button plugin
===

This plugin adds a button to allow users to log into Chamilo through their Facebook account.

To display this button on your portal, you have to:
 
* enable the Facebook authentication setting and configure it
* enable and configure Facebook authentication on your Chamilo platform: go to Administration > Configuration settings > Facebook
* add the App ID and the Secret Key provided by Facebook inside the app/config/auth.conf.php file
* set the following line in your app/config/configuration.php
```
$_configuration['facebook_auth'] = 1;
```

This plugin has been developed to be added to the login_top or login_bottom region in Chamilo, but you can put it in whichever region you want.

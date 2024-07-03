# The Azure Active Directory Plugin

This plugin allows users to authenticate (with OAuth2) through Microsoft's Azure Active Directory.
This will modify the login form to either substitute the default login form or add another option to connect through
Azure.
An option allows you to automatically provision/create users in Chamilo from their account on Azure if they don't exist
in Chamilo yet.

This plugin adds two extra fields for users:

* `organisationemail`, the email registered in Azure Active Directory for each user (under _Email_ in the _Contact info_ section).
* `azure_id`, to save the internal ID for each user in Azure (which is also the prefix before the _@_ sign in the _User Principal Name_).

### Prerequisites
This plugin will *not* work if you do not use HTTPS. 
Make sure your portal is in HTTPS before you configure this plugin.

### To configure Azure Active Directory
* Create and configure an application in your Azure panel (Azure Active Directory -> Applications registration -> New registration)).
* In the _Authentication_ section, set an _Reply URL_ (or _Redirect URIs_) of `https://{CHAMILO_URL}/plugin/azure_active_directory/src/callback.php`.
* In the _Front-channel logout URL_, use `https://{CHAMILO_URL}/index.php?logout=logout`.
* Leave the rest of the _Authentication_ section unchanged.
* In _Certificates & secrets_, create a secret string (or application password). Keep the _Value_ field at hand. If you don't copy it somewhere at this point, it will later be hidden, so take a copy, seriously!
* Make sure you actually have users.

### To configure this plugin
* _Enable_: You can enable the plugin once everything is configured correctly. Disabling it will return to the normal Chamilo login procedure.
* _Application ID_: Enter the _Application (client) ID_ assigned to your app when you created it in your Azure Active Directory interface, under _App registrations_.
* _Application secret_: Enter the client secret _value_ created in _Certificate & secrets_ above.
* _Block name_: (Optional) The name to show above the login button.
* _Force logout button_: (Optional) Add a button to force logout from Azure.
* _Management login_: (Optional) Disable the chamilo login and enable an alternative login page for users.   
   You will need copy the `/plugin/azure_active_directory/layout/login_form.tpl` file to `/main/template/overrides/layout/` directory.
* _Name for the management login_: A name for the manager login. By default, it is set to "Management Login".
* _Automated provisioning_: Enable if you want users to be created automatically in Chamilo (as students) when they don't exist yet.
* Assign a region in which the login option will appear. Preferably `login_bottom`.

### Enable through the normal login form
You can configure the external login procedure to work with the classic Chamilo form login.
To do it, make sure users have _azure_ in their auth_source field, then add this line in `configuration.php` file
```php
$extAuthSource["azure"]["login"] = $_configuration['root_sys']."main/auth/external_login/login.azure.php";
```

### Dependencies
> This plugin uses the [`thenetworg/oauth2-azure`](https://github.com/TheNetworg/oauth2-azure) package.

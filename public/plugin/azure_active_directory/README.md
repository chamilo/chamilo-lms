# The Azure Active Directory Plugin
Allow authentication (with OAuth2) with Microsoft's Azure Active Directory.

This plugin add two extra fields for users:
- `organisationemail`, the email registered in Azure Active Directory for each user.
- `azure_id`, to save the internal ID for each user in Azure.

> This plugin use the [`thenetworg/oauth2-azure`](https://github.com/TheNetworg/oauth2-azure) package.

### To configure Azure Active Directory
* Create and configure an application.
* In _Authentication_ section, set an _Reply URL_ with `https://{CHAMILO_URL}/plugin/azure_active_directory/src/callback.php`.
* In _Certificates & secrets_, create a secret string (or application password). And keep copied.

### To configure this plugin
* _Enable_
* _Application ID_: Enter the Application Id assinged to your app by the Azure portal.
* _Application secret_: Enter the client secret created.
* _Block name_: (Optional) The name to show above the login button.
* _Force logout button_: (Optional) Add a button to force logout from Azure.
* _Management login_: (Optional) Disable the chamilo login and enable an alternative login page for users.   
   You will need copy the `/plugin/azure_active_directory/layout/login_form.tpl` file to `/main/template/overrides/layout/` directory.
* _Name for the management login_: A name for the management login. By default is "Management Login".
* And assign a region. Preferably `login_bottom`.

Also, you can configure the external login to work with the classic Chamilo form login.
Adding this line in `configuration.php` file.
```php
$extAuthSource["azure"]["login"] = $_configuration['root_sys']."main/auth/external_login/login.azure.php";
```

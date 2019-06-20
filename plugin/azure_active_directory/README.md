# The Azure Active Directory Plugin
Allow authentication (with OAuth2) with Microsoft's Azure Active Directory.

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
* And assign a region. Preferably `login_bottom`.

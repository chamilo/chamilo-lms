# The Azure Active Directory Plugin
Allow authentication with Microsoft's Azure Active Directory

### To configure Azure Active Directory
* [Create an Azure AD B2C tenant](https://azure.microsoft.com/en-us/documentation/articles/active-directory-b2c-get-started/)
* [Register your application](https://azure.microsoft.com/en-us/documentation/articles/active-directory-b2c-app-registration/)
* [Configure Facebook, Google+, Microsoft account, Amazon, and LinkedIn accounts for use in your consumer-facing applications](https://azure.microsoft.com/en-us/documentation/articles/active-directory-b2c-overview/#how-to-articles)

### To configure this plugin
* Enable
* Application ID: Enter the Application Id assinged to your app by the Azure portal, e.g. 580e250c-8f26-49d0-bee8-1c078add1609
* Tenant: Enter the name of your B2C directory, e.g. contoso.onmicrosoft.com
* Sign up policy: Enter your sign up policy name, e.g. b2c_1_sign_up
* Sign in policy: Enter your sign in policy name, e.g. b2c_1_sign_in
* Block name: (Optional) The name to show above the buttons

And assign a region. Preferably `login_bottom`

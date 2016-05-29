<h1 class="page-header">The Azure Active Directory Plugin</h1>
<p>Allow authentication with Microsoft's Azure Active Directory</p>
<h3>To configure Azure Active Directory</h3>
<ul>
    <li>
        <a href="https://azure.microsoft.com/en-us/documentation/articles/active-directory-b2c-get-started/">
            Create an Azure AD B2C tenant
        </a>
    </li>
    <li>
        <a href="https://azure.microsoft.com/en-us/documentation/articles/active-directory-b2c-app-registration/">
            Register your application
        </a>
    </li>
    <li>
        <a href="https://azure.microsoft.com/en-us/documentation/articles/active-directory-b2c-overview/#how-to-articles">
            Configure Facebook, Google+, Microsoft account, Amazon, and LinkedIn accounts for use in your consumer-facing applications
        </a>
    </li>
</ul>
<h3>To configure this plugin</h3>
<ul>
    <li>Enable</li>
    <li>Application ID: Enter the Application Id assinged to your app by the Azure portal, e.g. 580e250c-8f26-49d0-bee8-1c078add1609</li>
    <li>Tenant: Enter the name of your B2C directory, e.g. contoso.onmicrosoft.com</li>
    <li>Sign up policy: Enter your sign up policy name, e.g. b2c_1_sign_up</li>
    <li>Sign in policy: Enter your sign in policy name, e.g. b2c_1_sign_in</li>
    <li>Block name: (Optional) The name to show above the buttons</li>
</ul>
<p>And assign a region. Preferably <code>login_bottom</code></p>

Keycloak
==============

1. Enable the plugin.
2. Create a new settings.php file here plugin/keycloak/settings.php you can find an example here:
plugin/keycloak/settings.dist.php

3. Edit the settings.php file with your Keycloak settings:

<pre>
'idp' => array(
    'entityId' => 'http://localhost:8080/auth/realms/master',
    'singleSignOnService' => array (
        'url' => 'http://localhost:8080/auth/realms/master/protocol/saml',
    ),
    'singleLogoutService' => array (
        'url' => 'http://localhost:8080/auth/realms/master/protocol/saml',
    ),
    'x509cert' => 'xxx',
)
</pre>

4. Configure your keycloak server with the following settings:

* Create a client with "client id" value:
 http://www.example.org/plugin/keycloak/metadata.php
 
* Valid redirect URIs: * 
* Client Protocol: saml
* Logout service redirect Binding URL:  http://www.example.org/plugin/keycloak/start.php?sls


Change the client scope roles to "Single role attribute".

- Client Scopes-> role_list -> Mappers -> role list -> "Single Role Attribute" = true

Add user mappers for "Firstname" "LastName" and "Email" so Chamilo can get those values.

Clients -> (select the client previously created) -> mappers -> create

Name: Email
Mapper Type = User Property. 
User Attribute: Email
Friendly Name: Email
SAML Attribute Name: Email
SAML Attribute: Basic 

Repeat the process for the 3 attributes. 

Create a demo user in keycloak

Try to login using the keycloak new button in Chamilo. 
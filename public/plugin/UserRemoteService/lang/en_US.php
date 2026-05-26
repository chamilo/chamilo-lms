<?php
/* For licensing terms, see /license.txt */

$strings['plugin_title'] = 'User Remote Services';
$strings['plugin_comment'] = 'Appends site-specific iframe-targeted user-identifying links to the menu bar.';

$strings['salt'] = 'Salt';
$strings['salt_help'] =
'Secret character string, used to generate the <em>hash</em> URL parameter. The longest, the best.
<br/>Remote user services can check the generated URL authenticity with the following PHP expression :
<br/><code class="php">password_verify($salt.$userId, $hash)</code>
<br/>Where
<br/><code>$salt</code> is this input value,
<br/><code>$userId</code> is the number of the user referenced by the <em>username</em> URL parameter value and
<br/><code>$hash</code> contains the <em>hash</em> URL parameter value.';
$strings['hide_link_from_navigation_menu'] = 'hide links from the menu';

// Please keep alphabetically sorted
$strings['CreateService'] = 'Add service to menu bar';
$strings['DeleteServices'] = 'Remove services from menu bar';
$strings['ServicesToDelete'] = 'Services to remove from menu bar';
$strings['ServiceTitle'] = 'Service title';
$strings['ServiceURL'] = 'Service web site location (URL)';
$strings['RedirectAccessURL'] = "URL to use in Chamilo to redirect user to the service (URL)";
$strings['Actions'] = 'Actions';
$strings['AddRemoteService'] = 'Add remote service';
$strings['CurrentServices'] = 'Current services';
$strings['DeleteService'] = 'Delete service';
$strings['InvalidSecurityToken'] = 'Invalid security token.';
$strings['InvalidServiceTitle'] = 'Please enter a service title.';
$strings['InvalidServiceUrl'] = 'Please enter a valid HTTP or HTTPS URL.';
$strings['MissingSaltWarning'] = 'Configure a salt before exposing remote service links. The salt is required to generate signed user URLs.';
$strings['NoServicesConfigured'] = 'No remote services have been configured yet.';
$strings['OpenInIframe'] = 'Open in iframe';
$strings['OpenRedirect'] = 'Open redirect URL';
$strings['RemoteServicesDescription'] = 'Manage external services that receive signed user URLs from Chamilo. Only authenticated users can open these links.';
$strings['ServiceCreated'] = 'The remote service has been created.';
$strings['ServiceDeleted'] = 'The remote service has been deleted.';
$strings['ServiceManagement'] = 'Remote service management';
$strings['ServiceUnavailable'] = 'This remote service is not available. Check that the plugin is enabled, the salt is configured and the URL is valid.';

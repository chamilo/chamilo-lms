<?php
/* For licensing terms, see /license.txt */

$strings['plugin_title'] = 'Gebruikers externe services';
$strings['plugin_comment'] = 'Voegt site-specifieke iframe-gerichte gebruikersidentificerende links toe aan de menubalk.';

$strings['salt'] = 'Zout';
$strings['salt_help'] = 'Geheim tekenreeks, gebruikt om de <em>hash</em> URL-parameter te genereren. Hoe langer, hoe beter.
<br/>Externe gebruikersservices kunnen de authenticiteit van de gegenereerde URL controleren met de volgende PHP-expressie:
<br/><code class="php">password_verify($salt.$userId, $hash)</code>
<br/>Waarbij
<br/><code>$salt</code> deze invoerwaarde is,
<br/><code>$userId</code> het nummer van de gebruiker is die wordt aangeduid door de <em>username</em> URL-parameterwaarde en
<br/><code>$hash</code> de <em>hash</em> URL-parameterwaarde bevat.';
$strings['hide_link_from_navigation_menu'] = 'verberg links van het menu';

// Please keep alphabetically sorted
$strings['CreateService'] = 'Service toevoegen aan menubalk';
$strings['DeleteServices'] = 'Services verwijderen uit menubalk';
$strings['ServicesToDelete'] = 'Services om te verwijderen uit menubalk';
$strings['ServiceTitle'] = 'Service titel';
$strings['ServiceURL'] = 'Service website locatie (URL)';
$strings['RedirectAccessURL'] = 'URL om in Chamilo te gebruiken om de gebruiker door te sturen naar de service (URL)';
$strings['Actions'] = 'Acties';
$strings['AddRemoteService'] = 'Externe service toevoegen';
$strings['CurrentServices'] = 'Huidige services';
$strings['DeleteService'] = 'Service verwijderen';
$strings['InvalidSecurityToken'] = 'Ongeldig beveiligingstoken.';
$strings['InvalidServiceTitle'] = 'Voer een servicetitel in.';
$strings['InvalidServiceUrl'] = 'Voer een geldige HTTP- of HTTPS-URL in.';
$strings['MissingSaltWarning'] = "Configureer een salt voordat u externe servicelinks beschikbaar stelt. De salt is vereist om ondertekende gebruikers-URL's te genereren.";
$strings['NoServicesConfigured'] = 'Er zijn nog geen externe services geconfigureerd.';
$strings['OpenInIframe'] = 'Openen in iframe';
$strings['OpenRedirect'] = 'Open redirect-URL';
$strings['RemoteServicesDescription'] = "Beheer externe services die ondertekende gebruikers-URL's van Chamilo ontvangen. Alleen geauthenticeerde gebruikers kunnen deze links openen.";
$strings['ServiceCreated'] = 'De externe service is aangemaakt.';
$strings['ServiceDeleted'] = 'De externe service is verwijderd.';
$strings['ServiceManagement'] = 'Beheer van externe services';
$strings['ServiceUnavailable'] = 'Deze externe service is niet beschikbaar. Controleer of de plugin is ingeschakeld, de salt is geconfigureerd en de URL geldig is.';

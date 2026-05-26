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

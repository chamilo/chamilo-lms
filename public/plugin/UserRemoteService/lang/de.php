<?php
/* For licensing terms, see /license.txt */

$strings['plugin_title'] = 'Benutzer-Fernzugriffe';
$strings['plugin_comment'] = 'Fügt benutzerspezifische iframe-zielende Identifikationslinks zur Menüleiste hinzu.';

$strings['salt'] = 'Salt';
$strings['salt_help'] = 'Geheimer Zeichensatz, verwendet zur Erzeugung des <em>hash</em> URL-Parameters. Je länger, desto besser.
<br/>Remote-Benutzerdienste können die Authentizität der generierten URL mit folgendem PHP-Ausdruck überprüfen:
<br/><code class="php">password_verify($salt.$userId, $hash)</code>
<br/>Wobei
<br/><code>$salt</code> dieser Eingabewert ist,
<br/><code>$userId</code> die Nummer des durch den <em>username</em> URL-Parameterwert referenzierten Benutzers und
<br/><code>$hash</code> den <em>hash</em> URL-Parameterwert enthält.';
$strings['hide_link_from_navigation_menu'] = 'Links aus dem Menü ausblenden';

// Please keep alphabetically sorted
$strings['CreateService'] = 'Dienst zur Menüleiste hinzufügen';
$strings['DeleteServices'] = 'Dienste aus der Menüleiste entfernen';
$strings['ServicesToDelete'] = 'Aus der Menüleiste zu entfernende Dienste';
$strings['ServiceTitle'] = 'Diensttitel';
$strings['ServiceURL'] = 'Website-Standort des Diensts (URL)';
$strings['RedirectAccessURL'] = 'In Chamilo zur Weiterleitung des Benutzers zum Dienst zu verwendende URL (URL)';

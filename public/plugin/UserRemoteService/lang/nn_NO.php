<?php
/* For licensing terms, see /license.txt */

$strings['plugin_title'] = 'Brukarens eksterne tenester';
$strings['plugin_comment'] = 'Legg til nettstadsspesifikke iframe-målretta brukaridentifiserande lenker i menylinja.';

$strings['salt'] = 'Salt';
$strings['salt_help'] = 'Hemmelig teiknstreng, brukt for å generere <em>hash</em> URL-parameteren. Lengst, best.
<br/>Eksterne brukar-tenester kan kontrollere den genererte URL-sinnehalda med dette PHP-uttrykket:
<br/><code class="php">password_verify($salt.$userId, $hash)</code>
<br/>Der
<br/><code>$salt</code> er denne inndata-verdien,
<br/><code>$userId</code> er tallet på brukaren som vert referert av <em>username</em> URL-parameterverdien, og
<br/><code>$hash</code> inneheld <em>hash</em> URL-parameterverdien.';
$strings['hide_link_from_navigation_menu'] = 'skjul lenker frå menyen';

// Please keep alphabetically sorted
$strings['CreateService'] = 'Legg teneste til menylinja';
$strings['DeleteServices'] = 'Fjern tenester frå menylinja';
$strings['ServicesToDelete'] = 'Tenester å fjerne frå menylinja';
$strings['ServiceTitle'] = 'Tittel på teneste';
$strings['ServiceURL'] = 'Nettstadplassering for teneste (URL)';
$strings['RedirectAccessURL'] = 'URL å bruke i Chamilo for å sende brukar til tenesta (URL)';

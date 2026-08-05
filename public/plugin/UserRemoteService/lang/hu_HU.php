<?php
/* For licensing terms, see /license.txt */

$strings['plugin_title'] = 'Felhasználói távoli szolgáltatások';
$strings['plugin_comment'] = 'Hozzáad oldal-specifikus iframe-célzott felhasználóazonosító hivatkozásokat a menüsorhoz.';

$strings['salt'] = 'Só';
$strings['salt_help'] = 'Titkos karakterlánc, amelyet a <em>hash</em> URL paraméter előállításához használnak. Minél hosszabb, annál jobb.
<br/>A távoli felhasználói szolgáltatások a következő PHP kifejezéssel ellenőrizhetik a generált URL hitelességét:
<br/><code class="php">password_verify($salt.$userId, $hash)</code>
<br/>Ahol
<br/><code>$salt</code> ez a beviteli érték,
<br/><code>$userId</code> a <em>username</em> URL paraméter értékére hivatkozó felhasználó száma,
<br/><code>$hash</code> pedig a <em>hash</em> URL paraméter értékét tartalmazza.';
$strings['hide_link_from_navigation_menu'] = 'hivatkozások elrejtése a menüből';

// Please keep alphabetically sorted
$strings['CreateService'] = 'Szolgáltatás hozzáadása a menüsorhoz';
$strings['DeleteServices'] = 'Szolgáltatások eltávolítása a menüsorból';
$strings['ServicesToDelete'] = 'Eltávolítandó szolgáltatások a menüsorból';
$strings['ServiceTitle'] = 'Szolgáltatás címe';
$strings['ServiceURL'] = 'Szolgáltatás webhelye (URL)';
$strings['RedirectAccessURL'] = 'A Chamilo-ban használandó URL a felhasználó átirányításához a szolgáltatáshoz (URL)';
$strings['Actions'] = 'Műveletek';
$strings['AddRemoteService'] = 'Távoli szolgáltatás hozzáadása';
$strings['CurrentServices'] = 'Jelenlegi szolgáltatások';
$strings['DeleteService'] = 'Szolgáltatás törlése';
$strings['InvalidSecurityToken'] = 'Érvénytelen biztonsági token.';
$strings['InvalidServiceTitle'] = 'Kérjük, adjon meg egy szolgáltatás címet.';
$strings['InvalidServiceUrl'] = 'Kérjük, adjon meg egy érvényes HTTP vagy HTTPS URL-t.';
$strings['MissingSaltWarning'] = 'Konfiguráljon egy salt értéket a távoli szolgáltatás linkek közzététele előtt. A salt szükséges az aláírt felhasználói URL-ek generálásához.';
$strings['NoServicesConfigured'] = 'Még nincs konfigurálva távoli szolgáltatás.';
$strings['OpenInIframe'] = 'Megnyitás iframe-ben';
$strings['OpenRedirect'] = 'Átirányító URL megnyitása';
$strings['RemoteServicesDescription'] = 'Külső szolgáltatások kezelése, amelyek aláírt felhasználói URL-eket kapnak a Chamilo rendszerből. Csak hitelesített felhasználók nyithatják meg ezeket a linkeket.';
$strings['ServiceCreated'] = 'A távoli szolgáltatás létrejött.';
$strings['ServiceDeleted'] = 'A távoli szolgáltatás törölve lett.';
$strings['ServiceManagement'] = 'Távoli szolgáltatások kezelése';
$strings['ServiceUnavailable'] = 'Ez a távoli szolgáltatás nem érhető el. Ellenőrizze, hogy a bővítmény engedélyezve van-e, a salt konfigurálva van-e és az URL érvényes-e.';

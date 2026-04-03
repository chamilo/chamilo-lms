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

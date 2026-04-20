<?php
/* For licensing terms, see /license.txt */

$strings['plugin_title'] = 'Servicii Remote Utilizator';
$strings['plugin_comment'] = 'Adaugă link-uri specifice site-ului, țintite iframe, de identificare a utilizatorului, în bara de meniu.';

$strings['salt'] = 'Salt';
$strings['salt_help'] = 'Șir de caractere secret, utilizat pentru a genera parametrul URL <em>hash</em>. Cu cât mai lung, cu atât mai bine.
<br/>Serviciile remote pentru utilizatori pot verifica autenticitatea URL-ului generat cu expresia PHP următoare:
<br/><code class="php">password_verify($salt.$userId, $hash)</code>
<br/>Unde
<br/><code>$salt</code> este această valoare de intrare,
<br/><code>$userId</code> este numărul utilizatorului referit de valoarea parametrului URL <em>username</em> și
<br/><code>$hash</code> conține valoarea parametrului URL <em>hash</em>.';
$strings['hide_link_from_navigation_menu'] = 'ascunde link-urile din meniu';

// Please keep alphabetically sorted
$strings['CreateService'] = 'Adaugă serviciu în bara de meniu';
$strings['DeleteServices'] = 'Elimină serviciile din bara de meniu';
$strings['ServicesToDelete'] = 'Servicii de eliminat din bara de meniu';
$strings['ServiceTitle'] = 'Titlu serviciu';
$strings['ServiceURL'] = 'Locație site web serviciu (URL)';
$strings['RedirectAccessURL'] = 'URL de utilizat în Chamilo pentru a redirecționa utilizatorul către serviciu (URL)';

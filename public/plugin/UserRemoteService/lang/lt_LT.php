<?php
/* For licensing terms, see /license.txt */

$strings['plugin_title'] = 'Vartotojo nuotolinės paslaugos';
$strings['plugin_comment'] = 'Prideda svetainės specifines iframe nukreipiančias vartotoją identifikavusias nuorodas į meniu juostą.';

$strings['salt'] = 'Druska';
$strings['salt_help'] = 'Slaptas simbolių eilutė, naudojama <em>hash</em> URL parametro generavimui. Kuo ilgesnė, tuo geriau.
<br/>Nuotolinės vartotojo paslaugos gali patikrinti sugeneruotos URL autentiškumą su šia PHP išraiška:
<br/><code class="php">password_verify($salt.$userId, $hash)</code>
<br/>Kur
<br/><code>$salt</code> yra šios įvesties reikšmė,
<br/><code>$userId</code> yra vartotojo, nurodyto <em>username</em> URL parametro reikšmės, numeris ir
<br/><code>$hash</code> yra <em>hash</em> URL parametro reikšmė.';
$strings['hide_link_from_navigation_menu'] = 'slėpti nuorodas iš meniu';

// Please keep alphabetically sorted
$strings['CreateService'] = 'Pridėti paslaugą prie meniu juostos';
$strings['DeleteServices'] = 'Pašalinti paslaugas iš meniu juostos';
$strings['ServicesToDelete'] = 'Pašalinamos paslaugos iš meniu juostos';
$strings['ServiceTitle'] = 'Paslaugos pavadinimas';
$strings['ServiceURL'] = 'Paslaugos svetainės vieta (URL)';
$strings['RedirectAccessURL'] = 'URL, naudojamas Chamilo nukreipti vartotoją į paslaugą (URL)';

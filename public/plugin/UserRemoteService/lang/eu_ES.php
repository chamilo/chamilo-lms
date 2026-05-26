<?php
/* For licensing terms, see /license.txt */

$strings['plugin_title'] = 'Erabiltzaile Zerbitzu Urrunak';
$strings['plugin_comment'] = 'Menuko barrara guneari dagozkion iframe-helburuko erabiltzailea identifikatzen dituzten estekak gehitzen ditu.';

$strings['salt'] = 'Gatz';
$strings['salt_help'] = 'Karaktere-kate sekretua, <em>hash</em> URL parametroa sortzeko erabiltzen da. Zenbat eta luzeagoa, orduan eta hobeto.
<br/>Erabiltzaile zerbitzu urrunek sortutako URL egiaztapen hau egiteko PHP adierazpen hau erabili dezakete:
<br/><code class="php">password_verify($salt.$userId, $hash)</code>
<br/>Non
<br/><code>$salt</code> sarrera balio hau den,
<br/><code>$userId</code> <em>username</em> URL parametroaren balioak aipatzen duen erabiltzailearen zenbakia eta
<br/><code>$hash</code> <em>hash</em> URL parametroaren balioa duena.';
$strings['hide_link_from_navigation_menu'] = 'estekak ezkutatu menutik';

// Please keep alphabetically sorted
$strings['CreateService'] = 'Zerbitzua gehitu menu barrara';
$strings['DeleteServices'] = 'Kendu zerbitzuak menu barratik';
$strings['ServicesToDelete'] = 'Menu barratik kentzeko zerbitzuak';
$strings['ServiceTitle'] = 'Zerbitzuaren izenburua';
$strings['ServiceURL'] = 'Zerbitzuaren webgune kokapena (URL)';
$strings['RedirectAccessURL'] = 'Chamilon erabiltzailea zerbitzura birbideratzeko erabiltzeko URL (URL)';

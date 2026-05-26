<?php
/* For licensing terms, see /license.txt */

$strings['plugin_title'] = 'Vzdialené služby používateľa';
$strings['plugin_comment'] = 'Pridá do panela ponuky odkazy iframe zamerané na identifikáciu používateľa špecifické pre stránku.';

$strings['salt'] = 'Soľ';
$strings['salt_help'] = 'Tajný reťazec znakov používaný na generovanie parametru URL <em>hash</em>. Čím dlhší, tým lepší.
<br/>Vzdialené služby používateľa môžu overiť autentickosť generovaného URL pomocou nasledujúceho výrazu PHP:
<br/><code class="php">password_verify($salt.$userId, $hash)</code>
<br/>Kde
<br/><code>$salt</code> je táto vstupná hodnota,
<br/><code>$userId</code> je číslo používateľa odkazovaného hodnotou parametra URL <em>username</em> a
<br/><code>$hash</code> obsahuje hodnotu parametra URL <em>hash</em>.';
$strings['hide_link_from_navigation_menu'] = 'skryť odkazy z ponuky';

// Please keep alphabetically sorted
$strings['CreateService'] = 'Pridať službu do panela ponuky';
$strings['DeleteServices'] = 'Odstrániť služby z panela ponuky';
$strings['ServicesToDelete'] = 'Služby na odstránenie z panela ponuky';
$strings['ServiceTitle'] = 'Názov služby';
$strings['ServiceURL'] = 'Umiestnenie webovej stránky služby (URL)';
$strings['RedirectAccessURL'] = 'URL na použitie v Chamilo na presmerovanie používateľa na službu (URL)';

<?php
/* For licensing terms, see /license.txt */

$strings['plugin_title'] = 'Uzanto Remote Services';
$strings['plugin_comment'] = 'Aldonas retejo-specifajn iframe-celitajn uzanto-identigajn ligilojn al la menubaro.';

$strings['salt'] = 'Salto';
$strings['salt_help'] = 'Sekreta signŝnuro, uzata por generi la <em>hash</em> URL-parametron. Ju pli longa, des pli bona.
<br/>Remote uzanto-servoj povas kontroli la aŭtentikecon de la generita URL per la jena PHP-esprimo :
<br/><code class="php">password_verify($salt.$userId, $hash)</code>
<br/> kie
<br/><code>$salt</code> estas ĉi tiu eniga valoro,
<br/><code>$userId</code> estas la numero de la uzanto referencita de la valoro de la <em>username</em> URL-parametro kaj
<br/><code>$hash</code> enhavas la valoron de la <em>hash</em> URL-parametro.';
$strings['hide_link_from_navigation_menu'] = 'kaŝi ligilojn el la menuo';

// Please keep alphabetically sorted
$strings['CreateService'] = 'Aldoni servon al la menubaro';
$strings['DeleteServices'] = 'Forigi servojn el la menubaro';
$strings['ServicesToDelete'] = 'Servoj forigitaj el la menubaro';
$strings['ServiceTitle'] = 'Titolo de la servo';
$strings['ServiceURL'] = 'Retreteja loko de la servo (URL)';
$strings['RedirectAccessURL'] = 'URL uzenda en Chamilo por redirigi la uzanton al la servo (URL)';

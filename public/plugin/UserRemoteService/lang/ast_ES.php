<?php
/* For licensing terms, see /license.txt */

$strings['plugin_title'] = "Servicios Remotos d'Usuariu";
$strings['plugin_comment'] = "Amesta enllaces d'identificación d'usuariu direutivos a iframe específicos del sitiu na barra de menú.";

$strings['salt'] = 'Saltu';
$strings['salt_help'] = "Cadena de carauteres secreta, usa pa xenerar el parámetru d'URL <em>hash</em>. Cantu más llinu, meyor.\n<br/>Los servicios remotos d'usuariu pueden verificar l'autenticidá de l'URL xenerada colesiguiente expresón PHP:\n<br/><code class=\"php\">password_verify(\$salt.\$userId, \$hash)</code>\n<br/>Onde\n<br/><code>\$salt</code> ye esti valor d'entamada,\n<br/><code>\$userId</code> ye el númberu de l'usuariu referenciáu pol valor del parámetru d'URL <em>username</em> y\n<br/><code>\$hash</code> contién el valor del parámetru d'URL <em>hash</em>.";
$strings['hide_link_from_navigation_menu'] = 'anubrir enllaces del menú';

// Please keep alphabetically sorted
$strings['CreateService'] = 'Amestar serviciu na barra de menú';
$strings['DeleteServices'] = 'Retirar servicios de la barra de menú';
$strings['ServicesToDelete'] = 'Servicios pa retirar de la barra de menú';
$strings['ServiceTitle'] = 'Títulu del serviciu';
$strings['ServiceURL'] = 'Llocalización del sitiu web del serviciu (URL)';
$strings['RedirectAccessURL'] = "URL pa usar en Chamilo pa redirixir l'usuariu al serviciu (URL)";

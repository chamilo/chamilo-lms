<?php
/* For licensing terms, see /license.txt */

$strings['plugin_title'] = "Serveis remots d'usuari";
$strings['plugin_comment'] = "Afegeix enllaços d'identificació d'usuari específics del lloc, dirigits a iframe, a la barra de menú.";

$strings['salt'] = 'Sal';
$strings['salt_help'] = "Cadena de caràcters secreta, utilitzada per generar el paràmetre d'URL <em>hash</em>. Més llarga, millor.\n<br/>Els serveis remots d'usuari poden verificar l'autenticitat de l'URL generat amb l'expressió PHP següent:\n<br/><code class=\"php\">password_verify(\$salt.\$userId, \$hash)</code>\n<br/>On\n<br/><code>\$salt</code> és aquest valor d'entrada,\n<br/><code>\$userId</code> és el número de l'usuari referenciat pel valor del paràmetre d'URL <em>username</em> i\n<br/><code>\$hash</code> conté el valor del paràmetre d'URL <em>hash</em>.";
$strings['hide_link_from_navigation_menu'] = 'amagar enllaços del menú';

// Please keep alphabetically sorted
$strings['CreateService'] = 'Afegir servei a la barra de menú';
$strings['DeleteServices'] = 'Eliminar serveis de la barra de menú';
$strings['ServicesToDelete'] = 'Serveis a eliminar de la barra de menú';
$strings['ServiceTitle'] = 'Títol del servei';
$strings['ServiceURL'] = 'Ubicació del lloc web del servei (URL)';
$strings['RedirectAccessURL'] = "URL a utilitzar a Chamilo per redirigir l'usuari al servei (URL)";

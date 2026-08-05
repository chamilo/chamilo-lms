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
$strings['Actions'] = 'Accions';
$strings['AddRemoteService'] = 'Afegeix un servei remot';
$strings['CurrentServices'] = 'Serveis actuals';
$strings['DeleteService'] = 'Suprimeix el servei';
$strings['InvalidSecurityToken'] = 'Token de seguretat no vàlid.';
$strings['InvalidServiceTitle'] = 'Introduïu un títol per al servei.';
$strings['InvalidServiceUrl'] = 'Introduïu un URL HTTP o HTTPS vàlid.';
$strings['MissingSaltWarning'] = "Configureu un salt abans d'exposar enllaços de serveis remots. El salt és necessari per generar URL d'usuari signades.";
$strings['NoServicesConfigured'] = "Encara no s'ha configurat cap servei remot.";
$strings['OpenInIframe'] = 'Obre en iframe';
$strings['OpenRedirect'] = "Obre l'URL de redirecció";
$strings['RemoteServicesDescription'] = "Gestioneu serveis externs que reben URL d'usuari signades des de Chamilo. Només els usuaris autenticats poden obrir aquests enllaços.";
$strings['ServiceCreated'] = "El servei remot s'ha creat.";
$strings['ServiceDeleted'] = "El servei remot s'ha suprimit.";
$strings['ServiceManagement'] = 'Gestió de serveis remots';
$strings['ServiceUnavailable'] = "Aquest servei remot no està disponible. Verifiqueu que el connector estigui activat, que el salt estigui configurat i que l'URL sigui vàlid.";

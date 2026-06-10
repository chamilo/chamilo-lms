<?php
/* For licensing terms, see /license.txt */

$strings['plugin_title'] = 'Servizos remotos de usuario';
$strings['plugin_comment'] = 'Engade ligazóns específicas do sitio dirixidas a iframe para identificar o usuario á barra de menú.';

$strings['salt'] = 'Sal';
$strings['salt_help'] = 'Cadena de caracteres secreta, usada para xerar o parámetro de URL <em>hash</em>. Canto máis longa, mellor.
<br/>Os servizos remotos de usuario poden comprobar a autenticidade da URL xerada coa seguinte expresión PHP:
<br/><code class="php">password_verify($salt.$userId, $hash)</code>
<br/>Onde
<br/><code>$salt</code> é este valor de entrada,
<br/><code>$userId</code> é o número do usuario referenciado polo valor do parámetro de URL <em>username</em> e
<br/><code>$hash</code> contén o valor do parámetro de URL <em>hash</em>.';
$strings['hide_link_from_navigation_menu'] = 'agochar ligazóns do menú';

// Please keep alphabetically sorted
$strings['CreateService'] = 'Engadir servizo á barra de menú';
$strings['DeleteServices'] = 'Eliminar servizos da barra de menú';
$strings['ServicesToDelete'] = 'Servizos a eliminar da barra de menú';
$strings['ServiceTitle'] = 'Título do servizo';
$strings['ServiceURL'] = 'Localización do sitio web do servizo (URL)';
$strings['RedirectAccessURL'] = 'URL a usar en Chamilo para redirixir o usuario ao servizo (URL)';
$strings['Actions'] = 'Accións';
$strings['AddRemoteService'] = 'Engadir servizo remoto';
$strings['CurrentServices'] = 'Servizos actuais';
$strings['DeleteService'] = 'Eliminar servizo';
$strings['InvalidSecurityToken'] = 'Token de seguridade non válido.';
$strings['InvalidServiceTitle'] = 'Por favor, introduza un título para o servizo.';
$strings['InvalidServiceUrl'] = 'Por favor, introduza un URL HTTP ou HTTPS válido.';
$strings['MissingSaltWarning'] = 'Configure un salt antes de expoñer ligazóns a servizos remotos. O salt é necesario para xerar URLs de usuario firmadas.';
$strings['NoServicesConfigured'] = 'Aínda non se configuraron servizos remotos.';
$strings['OpenInIframe'] = 'Abrir en iframe';
$strings['OpenRedirect'] = 'Abrir URL de redirección';
$strings['RemoteServicesDescription'] = 'Xestionar servizos externos que reciben URLs de usuario firmadas desde Chamilo. Só os usuarios autenticados poden abrir estas ligazóns.';
$strings['ServiceCreated'] = 'O servizo remoto foi creado.';
$strings['ServiceDeleted'] = 'O servizo remoto foi eliminado.';
$strings['ServiceManagement'] = 'Xestión de servizos remotos';
$strings['ServiceUnavailable'] = 'Este servizo remoto non está dispoñible. Comprobe que o plugin está activado, que o salt está configurado e que o URL é válido.';

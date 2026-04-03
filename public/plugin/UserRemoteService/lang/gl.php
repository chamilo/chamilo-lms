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

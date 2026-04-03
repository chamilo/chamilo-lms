<?php
/* For licensing terms, see /license.txt */

$strings['plugin_title'] = 'Servicios remotos de usuario';
$strings['plugin_comment'] = 'Añade enlaces específicos del sitio con iframe dirigido a la identificación del usuario en la barra de menús.';

$strings['salt'] = 'Sal';
$strings['salt_help'] = 'Cadena de caracteres secreta, utilizada para generar el parámetro de URL <em>hash</em>. Cuanto más larga, mejor.
<br/>Los servicios remotos de usuario pueden verificar la autenticidad de la URL generada con la siguiente expresión PHP:
<br/><code class="php">password_verify($salt.$userId, $hash)</code>
<br/>Donde
<br/><code>$salt</code> es este valor de entrada,
<br/><code>$userId</code> es el número del usuario referenciado por el valor del parámetro de URL <em>username</em> y
<br/><code>$hash</code> contiene el valor del parámetro de URL <em>hash</em>.';
$strings['hide_link_from_navigation_menu'] = 'ocultar enlaces del menú';

// Please keep alphabetically sorted
$strings['CreateService'] = 'Añadir servicio a la barra de menús';
$strings['DeleteServices'] = 'Eliminar servicios de la barra de menús';
$strings['ServicesToDelete'] = 'Servicios a eliminar de la barra de menús';
$strings['ServiceTitle'] = 'Título del servicio';
$strings['ServiceURL'] = 'Ubicación del sitio web del servicio (URL)';
$strings['RedirectAccessURL'] = 'URL a usar en Chamilo para redirigir al usuario al servicio (URL)';

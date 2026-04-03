<?php
/* For licensing terms, see /license.txt */

$strings['plugin_title'] = 'Serviços Remotos de Usuário';
$strings['plugin_comment'] = 'Adiciona links específicos do site, direcionados a iframe e identificadores de usuário, à barra de menu.';

$strings['salt'] = 'Salt';
$strings['salt_help'] = 'Cadeia de caracteres secreta, usada para gerar o parâmetro de URL <em>hash</em>. Quanto mais longa, melhor.
<br/>Os serviços remotos de usuário podem verificar a autenticidade da URL gerada com a seguinte expressão PHP:
<br/><code class="php">password_verify($salt.$userId, $hash)</code>
<br/>Onde
<br/><code>$salt</code> é este valor de entrada,
<br/><code>$userId</code> é o número do usuário referenciado pelo valor do parâmetro de URL <em>username</em> e
<br/><code>$hash</code> contém o valor do parâmetro de URL <em>hash</em>.';
$strings['hide_link_from_navigation_menu'] = 'ocultar links do menu';

// Please keep alphabetically sorted
$strings['CreateService'] = 'Adicionar serviço à barra de menu';
$strings['DeleteServices'] = 'Remover serviços da barra de menu';
$strings['ServicesToDelete'] = 'Serviços a remover da barra de menu';
$strings['ServiceTitle'] = 'Título do serviço';
$strings['ServiceURL'] = 'Localização do site do serviço (URL)';
$strings['RedirectAccessURL'] = 'URL a usar no Chamilo para redirecionar o usuário ao serviço (URL)';

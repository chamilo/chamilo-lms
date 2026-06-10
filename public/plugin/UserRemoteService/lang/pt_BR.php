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
$strings['Actions'] = 'Ações';
$strings['AddRemoteService'] = 'Adicionar serviço remoto';
$strings['CurrentServices'] = 'Serviços atuais';
$strings['DeleteService'] = 'Excluir serviço';
$strings['InvalidSecurityToken'] = 'Token de segurança inválido.';
$strings['InvalidServiceTitle'] = 'Por favor, insira um título para o serviço.';
$strings['InvalidServiceUrl'] = 'Por favor, insira uma URL HTTP ou HTTPS válida.';
$strings['MissingSaltWarning'] = 'Configure um salt antes de expor links de serviços remotos. O salt é necessário para gerar URLs de usuário assinadas.';
$strings['NoServicesConfigured'] = 'Nenhum serviço remoto foi configurado ainda.';
$strings['OpenInIframe'] = 'Abrir em iframe';
$strings['OpenRedirect'] = 'Abrir URL de redirecionamento';
$strings['RemoteServicesDescription'] = 'Gerencie serviços externos que recebem URLs de usuário assinadas do Chamilo. Apenas usuários autenticados podem abrir esses links.';
$strings['ServiceCreated'] = 'O serviço remoto foi criado.';
$strings['ServiceDeleted'] = 'O serviço remoto foi excluído.';
$strings['ServiceManagement'] = 'Gerenciamento de serviços remotos';
$strings['ServiceUnavailable'] = 'Este serviço remoto não está disponível. Verifique se o plugin está habilitado, o salt está configurado e a URL é válida.';

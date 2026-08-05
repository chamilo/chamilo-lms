<?php
/* For licensing terms, see /license.txt */

$strings['plugin_title'] = 'Serviços Remotos de Utilizador';
$strings['plugin_comment'] = 'Adiciona ligações iframe específicas do site direcionadas ao utilizador à barra de menu.';

$strings['salt'] = 'Salt';
$strings['salt_help'] = 'Cadeia de caracteres secreta, usada para gerar o parâmetro de URL <em>hash</em>. Quanto mais longa, melhor.
<br/>Os serviços remotos de utilizador podem verificar a autenticidade da URL gerada com a seguinte expressão PHP:
<br/><code class="php">password_verify($salt.$userId, $hash)</code>
<br/>Onde
<br/><code>$salt</code> é este valor de entrada,
<br/><code>$userId</code> é o número do utilizador referenciado pelo valor do parâmetro de URL <em>username</em> e
<br/><code>$hash</code> contém o valor do parâmetro de URL <em>hash</em>.';
$strings['hide_link_from_navigation_menu'] = 'ocultar ligações do menu';

// Please keep alphabetically sorted
$strings['CreateService'] = 'Adicionar serviço à barra de menu';
$strings['DeleteServices'] = 'Remover serviços da barra de menu';
$strings['ServicesToDelete'] = 'Serviços a remover da barra de menu';
$strings['ServiceTitle'] = 'Título do serviço';
$strings['ServiceURL'] = 'Localização do site web do serviço (URL)';
$strings['RedirectAccessURL'] = 'URL a usar no Chamilo para redirecionar o utilizador para o serviço (URL)';
$strings['Actions'] = 'Ações';
$strings['AddRemoteService'] = 'Adicionar serviço remoto';
$strings['CurrentServices'] = 'Serviços atuais';
$strings['DeleteService'] = 'Eliminar serviço';
$strings['InvalidSecurityToken'] = 'Token de segurança inválido.';
$strings['InvalidServiceTitle'] = 'Por favor, introduza um título para o serviço.';
$strings['InvalidServiceUrl'] = 'Por favor, introduza um URL HTTP ou HTTPS válido.';
$strings['MissingSaltWarning'] = 'Configure um salt antes de expor ligações de serviços remotos. O salt é necessário para gerar URLs de utilizador assinadas.';
$strings['NoServicesConfigured'] = 'Ainda não foram configurados serviços remotos.';
$strings['OpenInIframe'] = 'Abrir em iframe';
$strings['OpenRedirect'] = 'Abrir URL de redirecionamento';
$strings['RemoteServicesDescription'] = 'Gerir serviços externos que recebem URLs de utilizador assinadas do Chamilo. Apenas utilizadores autenticados podem abrir estas ligações.';
$strings['ServiceCreated'] = 'O serviço remoto foi criado.';
$strings['ServiceDeleted'] = 'O serviço remoto foi eliminado.';
$strings['ServiceManagement'] = 'Gestão de serviços remotos';
$strings['ServiceUnavailable'] = 'Este serviço remoto não está disponível. Verifique se o plugin está ativado, se o salt está configurado e se o URL é válido.';

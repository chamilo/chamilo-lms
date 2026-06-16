<?php
/* For licensing terms, see /license.txt */

$strings['plugin_title'] = 'Удалённые сервисы пользователя';
$strings['plugin_comment'] = 'Добавляет в панель меню ссылки iframe, специфичные для сайта, идентифицирующие пользователя.';

$strings['salt'] = 'Соль';
$strings['salt_help'] = 'Секретная строка символов, используемая для генерации параметра URL <em>hash</em>. Чем длиннее, тем лучше.
<br/>Удалённые сервисы пользователя могут проверить подлинность сгенерированного URL с помощью следующего выражения PHP:
<br/><code class="php">password_verify($salt.$userId, $hash)</code>
<br/>Где
<br/><code>$salt</code> — это введённое значение,
<br/><code>$userId</code> — номер пользователя, на который ссылается значение параметра URL <em>username</em>, и
<br/><code>$hash</code> содержит значение параметра URL <em>hash</em>.';
$strings['hide_link_from_navigation_menu'] = 'скрыть ссылки из меню';

// Please keep alphabetically sorted
$strings['CreateService'] = 'Добавить сервис в панель меню';
$strings['DeleteServices'] = 'Удалить сервисы из панели меню';
$strings['ServicesToDelete'] = 'Сервисы для удаления из панели меню';
$strings['ServiceTitle'] = 'Название сервиса';
$strings['ServiceURL'] = 'Расположение веб-сайта сервиса (URL)';
$strings['RedirectAccessURL'] = 'URL для использования в Chamilo для перенаправления пользователя на сервис (URL)';
$strings['Actions'] = 'Действия';
$strings['AddRemoteService'] = 'Добавить удалённый сервис';
$strings['CurrentServices'] = 'Текущие сервисы';
$strings['DeleteService'] = 'Удалить сервис';
$strings['InvalidSecurityToken'] = 'Неверный токен безопасности.';
$strings['InvalidServiceTitle'] = 'Пожалуйста, введите название сервиса.';
$strings['InvalidServiceUrl'] = 'Пожалуйста, введите корректный URL HTTP или HTTPS.';
$strings['MissingSaltWarning'] = 'Настройте соль перед публикацией ссылок на удалённые сервисы. Соль необходима для генерации подписанных URL пользователей.';
$strings['NoServicesConfigured'] = 'Удалённые сервисы ещё не настроены.';
$strings['OpenInIframe'] = 'Открыть в iframe';
$strings['OpenRedirect'] = 'Открыть URL перенаправления';
$strings['RemoteServicesDescription'] = 'Управление внешними сервисами, получающими подписанные URL пользователей от Chamilo. Только аутентифицированные пользователи могут открывать эти ссылки.';
$strings['ServiceCreated'] = 'Удалённый сервис создан.';
$strings['ServiceDeleted'] = 'Удалённый сервис удалён.';
$strings['ServiceManagement'] = 'Управление удалёнными сервисами';
$strings['ServiceUnavailable'] = 'Этот удалённый сервис недоступен. Проверьте, что плагин включён, соль настроена и URL корректен.';

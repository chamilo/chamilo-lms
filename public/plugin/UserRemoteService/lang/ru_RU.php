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

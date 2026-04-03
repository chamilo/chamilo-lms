<?php
/* For licensing terms, see /license.txt */

$strings['plugin_title'] = 'Віддалені служби користувача';
$strings['plugin_comment'] = 'Додає до панелі меню посилання, специфічні для сайту, з iframe-ціллю для ідентифікації користувача.';

$strings['salt'] = 'Сіль';
$strings['salt_help'] = 'Секретний рядок символів, що використовується для генерації параметра URL <em>hash</em>. Чим довший, тим кращий.
<br/>Віддалені служби користувача можуть перевірити автентичність згенерованого URL за допомогою такого виразу PHP:
<br/><code class="php">password_verify($salt.$userId, $hash)</code>
<br/>Де
<br/><code>$salt</code> — це введене значення,
<br/><code>$userId</code> — номер користувача, на який посилається значення параметра URL <em>username</em>,
<br/><code>$hash</code> містить значення параметра URL <em>hash</em>.';
$strings['hide_link_from_navigation_menu'] = 'приховати посилання з меню';

// Please keep alphabetically sorted
$strings['CreateService'] = 'Додати службу до панелі меню';
$strings['DeleteServices'] = 'Видалити служби з панелі меню';
$strings['ServicesToDelete'] = 'Служби для видалення з панелі меню';
$strings['ServiceTitle'] = 'Назва служби';
$strings['ServiceURL'] = 'Розташування вебсайту служби (URL)';
$strings['RedirectAccessURL'] = 'URL для використання в Chamilo для перенаправлення користувача до служби (URL)';

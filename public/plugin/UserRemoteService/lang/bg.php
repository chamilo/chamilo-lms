<?php
/* For licensing terms, see /license.txt */

$strings['plugin_title'] = 'Услуги за отдалечени потребители';
$strings['plugin_comment'] = 'Добавя към лентата с менюта iframe-целеви връзки, специфични за сайта, за идентифициране на потребителя.';

$strings['salt'] = 'Сол';
$strings['salt_help'] = 'Таен низ от символи, използван за генериране на параметъра <em>hash</em> в URL. Колкото по-дълъг, толкова по-добре.
<br/>Услугите за отдалечени потребители могат да проверят автентичността на генерирания URL с следното PHP изражение:
<br/><code class="php">password_verify($salt.$userId, $hash)</code>
<br/>Където
<br/><code>$salt</code> е тази въведена стойност,
<br/><code>$userId</code> е номерът на потребителя, посочен от стойността на параметъра <em>username</em> в URL и
<br/><code>$hash</code> съдържа стойността на параметъра <em>hash</em> в URL.';
$strings['hide_link_from_navigation_menu'] = 'скрий връзките от менюто';

// Please keep alphabetically sorted
$strings['CreateService'] = 'Добави услуга към лентата с менюта';
$strings['DeleteServices'] = 'Премахни услугите от лентата с менюта';
$strings['ServicesToDelete'] = 'Услуги за премахване от лентата с менюта';
$strings['ServiceTitle'] = 'Заглавие на услугата';
$strings['ServiceURL'] = 'Местоположение на уебсайта на услугата (URL)';
$strings['RedirectAccessURL'] = 'URL за използване в Chamilo за пренасочване на потребителя към услугата (URL)';

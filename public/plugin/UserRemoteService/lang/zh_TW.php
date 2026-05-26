<?php
/* For licensing terms, see /license.txt */

$strings['plugin_title'] = '使用者遠端服務';
$strings['plugin_comment'] = '在選單列中附加網站專屬的 iframe 目標使用者識別連結。';

$strings['salt'] = '鹽值';
$strings['salt_help'] = '秘密字串，用來產生 <em>hash</em> URL 參數。越長越好。
<br/>遠端使用者服務可以使用以下 PHP 運算式檢查產生的 URL 真實性：
<br/><code class="php">password_verify($salt.$userId, $hash)</code>
<br/>其中
<br/><code>$salt</code> 是此輸入值，
<br/><code>$userId</code> 是 <em>username</em> URL 參數值所參照使用者的編號，且
<br/><code>$hash</code> 包含 <em>hash</em> URL 參數值。';
$strings['hide_link_from_navigation_menu'] = '從選單隱藏連結';

// Please keep alphabetically sorted
$strings['CreateService'] = '將服務新增至選單列';
$strings['DeleteServices'] = '從選單列移除服務';
$strings['ServicesToDelete'] = '從選單列移除的服務';
$strings['ServiceTitle'] = '服務標題';
$strings['ServiceURL'] = '服務網站位置 (URL)';
$strings['RedirectAccessURL'] = '用於 Chamilo 將使用者重新導向至服務的 URL (URL)';

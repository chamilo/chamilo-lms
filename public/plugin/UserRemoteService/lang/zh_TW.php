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
$strings['Actions'] = '動作';
$strings['AddRemoteService'] = '新增遠端服務';
$strings['CurrentServices'] = '目前服務';
$strings['DeleteService'] = '刪除服務';
$strings['InvalidSecurityToken'] = '無效的安全權杖。';
$strings['InvalidServiceTitle'] = '請輸入服務標題。';
$strings['InvalidServiceUrl'] = '請輸入有效的 HTTP 或 HTTPS URL。';
$strings['MissingSaltWarning'] = '在公開遠端服務連結前請先設定 salt。salt 是用來產生已簽署使用者 URL 所必需的。';
$strings['NoServicesConfigured'] = '尚未設定任何遠端服務。';
$strings['OpenInIframe'] = '在 iframe 中開啟';
$strings['OpenRedirect'] = '開啟重新導向 URL';
$strings['RemoteServicesDescription'] = '管理接收來自 Chamilo 已簽署使用者 URL 的外部服務。只有已驗證的使用者才能開啟這些連結。';
$strings['ServiceCreated'] = '遠端服務已建立。';
$strings['ServiceDeleted'] = '遠端服務已刪除。';
$strings['ServiceManagement'] = '遠端服務管理';
$strings['ServiceUnavailable'] = '此遠端服務無法使用。請確認外掛已啟用、salt 已設定且 URL 有效。';

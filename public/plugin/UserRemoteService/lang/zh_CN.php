<?php
/* For licensing terms, see /license.txt */

$strings['plugin_title'] = '用户远程服务';
$strings['plugin_comment'] = '在菜单栏中附加站点特定的 iframe 目标用户识别链接。';

$strings['salt'] = 'Salt';
$strings['salt_help'] = '密钥字符字符串，用于生成<em>hash</em> URL 参数。越长越好。
<br/>远程用户服务可以使用以下 PHP 表达式检查生成的 URL 真实性：
<br/><code class="php">password_verify($salt.$userId, $hash)</code>
<br/>其中
<br/><code>$salt</code> 是此输入值，
<br/><code>$userId</code> 是<em>username</em> URL 参数值引用的用户编号，
<br/><code>$hash</code> 包含<em>hash</em> URL 参数值。';
$strings['hide_link_from_navigation_menu'] = '从菜单中隐藏链接';

// Please keep alphabetically sorted
$strings['CreateService'] = '将服务添加到菜单栏';
$strings['DeleteServices'] = '从菜单栏移除服务';
$strings['ServicesToDelete'] = '从菜单栏移除的服务';
$strings['ServiceTitle'] = '服务标题';
$strings['ServiceURL'] = '服务网站位置 (URL)';
$strings['RedirectAccessURL'] = '在 Chamilo 中用于将用户重定向到服务的 URL (URL)';
$strings['Actions'] = '操作';
$strings['AddRemoteService'] = '添加远程服务';
$strings['CurrentServices'] = '当前服务';
$strings['DeleteService'] = '删除服务';
$strings['InvalidSecurityToken'] = '无效的安全令牌。';
$strings['InvalidServiceTitle'] = '请输入服务标题。';
$strings['InvalidServiceUrl'] = '请输入有效的 HTTP 或 HTTPS URL。';
$strings['MissingSaltWarning'] = '在公开远程服务链接前请先配置 salt。生成签名的用户 URL 需要使用 salt。';
$strings['NoServicesConfigured'] = '尚未配置任何远程服务。';
$strings['OpenInIframe'] = '在 iframe 中打开';
$strings['OpenRedirect'] = '打开重定向 URL';
$strings['RemoteServicesDescription'] = '管理接收来自 Chamilo 签名用户 URL 的外部服务。只有已认证用户才能打开这些链接。';
$strings['ServiceCreated'] = '远程服务已创建。';
$strings['ServiceDeleted'] = '远程服务已删除。';
$strings['ServiceManagement'] = '远程服务管理';
$strings['ServiceUnavailable'] = '此远程服务不可用。请检查插件是否已启用、salt 是否已配置以及 URL 是否有效。';

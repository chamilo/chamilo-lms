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

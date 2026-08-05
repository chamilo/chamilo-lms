<?php

$strings['plugin_title'] = '来自 Web 服务的外菜单';
$strings['plugin_comment'] = '添加通过 Web 服务获取的菜单';
$strings['api_menu_url'] = 'API 菜单 URL';
$strings['api_menu_url_help'] = '返回菜单项 JSON 的 API Platform 端点。会自动添加 email、userId、locale 和 mobile 查询参数。';
$strings['authentication_url'] = '认证 URL';
$strings['authentication_url_help'] = '用于获取 bearer token 的可选端点。';
$strings['authentication_email'] = '用于认证的电子邮件';
$strings['authentication_password'] = '用于认证的密码';
$strings['api_bearer_token'] = '静态 bearer token';
$strings['api_bearer_token_help'] = '可选。如果已定义，则使用此 token 而不是调用认证 URL。';
$strings['normal_menu_url'] = '普通菜单 Web 服务 URL';
$strings['normal_menu_url_help'] = 'API 菜单 URL 为空时的向后兼容回退';
$strings['mobile_menu_url'] = '移动菜单 Web 服务 URL';
$strings['mobile_menu_url_help'] = 'API 菜单 URL 为空时的向后兼容回退';
$strings['menu_request_mode'] = '菜单请求模式';
$strings['api_platform_query'] = 'API Platform 查询参数';
$strings['legacy_email_path'] = '旧版邮箱路径';
$strings['session_timeout'] = '会话超时令牌，以秒为单位';
$strings['session_timeout_help'] = '如果未指定时间，将使用 86400 秒。';
$strings['cache_ttl'] = '菜单缓存 TTL（秒）';
$strings['cache_ttl_help'] = '默认值：300 秒。设为 0 则禁用菜单缓存。';
$strings['request_timeout'] = 'HTTP 请求超时（秒）';
$strings['request_timeout_help'] = '默认值：3 秒。';
$strings['MenuTitle'] = '额外菜单';
$strings['list_css_imports'] = "要导入的 CSS URL 列表，以 ';' 分隔";
$strings['list_fonts_imports'] = "要导入的字体 URL 列表，以 ';' 分隔";

<?php

$strings['plugin_title'] = 'Extra menu from webservice';
$strings['plugin_comment'] = 'Adds a Vue topbar menu obtained from an API Platform compatible webservice.';
$strings['api_menu_url'] = 'API menu URL';
$strings['api_menu_url_help'] = 'API Platform endpoint returning menu items as JSON. Query parameters email, userId, locale and mobile are added automatically.';
$strings['authentication_url'] = 'Authentication URL';
$strings['authentication_url_help'] = 'Optional endpoint used to obtain a bearer token.';
$strings['authentication_email'] = 'Authentication email';
$strings['authentication_password'] = 'Authentication password';
$strings['api_bearer_token'] = 'Static bearer token';
$strings['api_bearer_token_help'] = 'Optional. If defined, this token is used instead of calling the authentication URL.';
$strings['normal_menu_url'] = 'Legacy desktop menu URL';
$strings['normal_menu_url_help'] = 'Backward-compatible fallback when API menu URL is empty.';
$strings['mobile_menu_url'] = 'Legacy mobile menu URL';
$strings['mobile_menu_url_help'] = 'Backward-compatible fallback when API menu URL is empty.';
$strings['menu_request_mode'] = 'Menu request mode';
$strings['api_platform_query'] = 'API Platform query parameters';
$strings['legacy_email_path'] = 'Legacy email path';
$strings['session_timeout'] = 'Token session timeout in seconds';
$strings['session_timeout_help'] = 'Default value: 86400 seconds.';
$strings['cache_ttl'] = 'Menu cache TTL in seconds';
$strings['cache_ttl_help'] = 'Default value: 300 seconds. Set 0 to disable menu cache.';
$strings['request_timeout'] = 'HTTP request timeout in seconds';
$strings['request_timeout_help'] = 'Default value: 3 seconds.';
$strings['MenuTitle'] = 'Extra menu';

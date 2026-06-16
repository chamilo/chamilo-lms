<?php

$strings['plugin_title'] = '來自 Web 服務的額外選單';
$strings['plugin_comment'] = '新增由 Web 服務取得的選單';
$strings['api_menu_url'] = 'API 選單 URL';
$strings['api_menu_url_help'] = 'API Platform 端點，以 JSON 格式回傳選單項目。會自動附加 email、userId、locale 與 mobile 等查詢參數。';
$strings['authentication_url'] = '驗證 URL';
$strings['authentication_url_help'] = '用來取得 bearer token 的選用端點。';
$strings['authentication_email'] = '用於驗證的電子郵件';
$strings['authentication_password'] = '用於驗證的密碼';
$strings['api_bearer_token'] = '靜態 bearer token';
$strings['api_bearer_token_help'] = '選用。若有設定此 token，則不會呼叫驗證 URL。';
$strings['normal_menu_url'] = '一般選單 Web 服務的 URL';
$strings['normal_menu_url_help'] = '當 API 選單 URL 為空時的相容性後援';
$strings['mobile_menu_url'] = '行動裝置選單 Web 服務的 URL';
$strings['mobile_menu_url_help'] = '當 API 選單 URL 為空時的相容性後援';
$strings['menu_request_mode'] = '選單請求模式';
$strings['api_platform_query'] = 'API Platform 查詢參數';
$strings['legacy_email_path'] = '舊版 email 路徑';
$strings['session_timeout'] = '工作階段逾時的 Token，以秒為單位';
$strings['session_timeout_help'] = '若未指定時間，將使用 86400 秒。';
$strings['cache_ttl'] = '選單快取 TTL（秒）';
$strings['cache_ttl_help'] = '預設值：300 秒。設定 0 則停用選單快取。';
$strings['request_timeout'] = 'HTTP 請求逾時（秒）';
$strings['request_timeout_help'] = '預設值：3 秒。';
$strings['MenuTitle'] = '額外選單';
$strings['list_css_imports'] = "要匯入的 CSS URL 清單，以 ';' 分隔";
$strings['list_fonts_imports'] = "要匯入的字型 URL 清單，以 ';' 分隔";

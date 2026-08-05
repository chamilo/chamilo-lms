<?php

$strings['plugin_title'] = 'Menú extra desde webservice';
$strings['plugin_comment'] = 'Añade un menú Vue en la barra superior obtenido desde un webservice compatible con API Platform.';
$strings['api_menu_url'] = 'URL API del menú';
$strings['api_menu_url_help'] = 'Endpoint API Platform que devuelve los ítems del menú en JSON. Se agregan automáticamente los parámetros email, userId, locale y mobile.';
$strings['authentication_url'] = 'URL de autenticación';
$strings['authentication_url_help'] = 'Endpoint opcional usado para obtener un token bearer.';
$strings['authentication_email'] = 'Email de autenticación';
$strings['authentication_password'] = 'Contraseña de autenticación';
$strings['api_bearer_token'] = 'Token bearer estático';
$strings['api_bearer_token_help'] = 'Opcional. Si se define, este token se usa en lugar de llamar a la URL de autenticación.';
$strings['normal_menu_url'] = 'URL legacy del menú de escritorio';
$strings['normal_menu_url_help'] = 'Fallback de compatibilidad cuando la URL API del menú está vacía.';
$strings['mobile_menu_url'] = 'URL legacy del menú móvil';
$strings['mobile_menu_url_help'] = 'Fallback de compatibilidad cuando la URL API del menú está vacía.';
$strings['menu_request_mode'] = 'Modo de petición del menú';
$strings['api_platform_query'] = 'Parámetros query de API Platform';
$strings['legacy_email_path'] = 'Ruta legacy con email';
$strings['session_timeout'] = 'Tiempo de vida del token en sesión, en segundos';
$strings['session_timeout_help'] = 'Valor por defecto: 86400 segundos.';
$strings['cache_ttl'] = 'TTL de caché del menú, en segundos';
$strings['cache_ttl_help'] = 'Valor por defecto: 300 segundos. Usa 0 para desactivar la caché del menú.';
$strings['request_timeout'] = 'Timeout HTTP en segundos';
$strings['request_timeout_help'] = 'Valor por defecto: 3 segundos.';
$strings['MenuTitle'] = 'Menú extra';

<?php
/**
 * This file allows a Chamilo portal admin to authorize access from specific
 * IPs or ranges of IPs.
 */
/**
 * Check no direct access to file using a constant defined in the calling script.
 */
if (!defined('WS_ERROR_SECRET_KEY')) {
    exit();
}
/**
 * Define here the IPs or ranges that will be authorized to access the
 * webservice. When this is in place, the security key check will be made on
 * the string given here in $ws_auth_ip, and not anymore on
 * $_SERVER['REMOTE_ADDR'], but $_SERVER['REMOTE_ADDR'] will still be checked
 * against the IP or range provided. It doesn't support IPv6 yet.
 * If $ws_auth_ip is not defined, this file will be ignored. If $ws_auth_ip *is*
 * defined, then the only security key expected from the client is the
 * $_configuration['security_key'] encrypted through SHA1.
 *
 * @example
 * <pre>
 * $ws_auth_ip = '192.168.1.1/22';
 * $ws_auth_ip = '192.168.1.5';
 * $ws_auth_ip = '192.168.1.5,192.168.1.9';
 * </pre>
 */

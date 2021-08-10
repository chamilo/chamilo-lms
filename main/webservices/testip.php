<?php
/* For licensing terms, see /license.txt */
/**
 *  @package chamilo.webservices
 */
$ip = '';
if (!empty($_SERVER['REMOTE_ADDR'])) {
    $ip = trim($_SERVER['REMOTE_ADDR']);
}
if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    if (filter_var($_SERVER['HTTP_X_FORWARDED_FOR'], FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6) == $_SERVER['HTTP_X_FORWARDED_FOR']) {
        list($ip1, $ip2) = preg_split('/,/', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($ip1);
    }
}
if (!empty($ip) && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6)) {
    echo htmlentities($ip);
}

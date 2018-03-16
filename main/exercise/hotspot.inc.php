<?php
/* For licensing terms, see /license.txt */
/**
 *	Hotspot languae conversion.
 *
 *	@package chamilo.exercise
 */
/**
 * Code.
 */
session_cache_limiter('none');

$language_file = 'hotspot';

require_once __DIR__.'/../inc/global.inc.php';

header('Content-Type: text/html; charset=UTF-8');

$file = file(api_get_path(SYS_LANG_PATH).'english/hotspot.inc.php');

foreach ($file as &$value) {
    $variable = explode('=', $value, 2);
    if (count($variable) > 1) {
        $variable = substr(trim($variable[0]), 1);
        $variable = '&'.$variable.'='.api_utf8_encode(get_lang($variable)).' ';
        echo $variable;
    }
}

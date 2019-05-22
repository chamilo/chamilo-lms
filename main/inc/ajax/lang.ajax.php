<?php
/* For licensing terms, see /license.txt */

/**
 * Responses to AJAX calls.
 */
require_once __DIR__.'/../global.inc.php';

api_protect_course_script(true);

$action = $_REQUEST['a'];

switch ($action) {
    case 'translate_html':
        header('Content-type: application/x-javascript');

        echo api_get_language_translate_html();
        break;
    default:
        echo '';
}
exit;

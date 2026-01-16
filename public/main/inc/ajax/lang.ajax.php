<?php
/* For licensing terms, see /license.txt */

/**
 * Responses to AJAX calls.
 */
// Avoid auto-closing the session in global.inc.php because of api_is_platform_admin() call
const KEEP_SESSION_OPEN = true;
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

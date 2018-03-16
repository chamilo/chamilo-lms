<?php
/* For licensing terms, see /license.txt */

/**
 * Responses to AJAX calls.
 */
require_once __DIR__.'/../global.inc.php';

api_protect_course_script(true);

$action = $_REQUEST['a'];

switch ($action) {
    case 'check_url':
        if (api_is_allowed_to_edit(null, true)) {
            $url = $_REQUEST['url'];
            $result = \Link::checkUrl($url);

            if ($result) {
                echo Display::return_icon(
                    'check-circle.png',
                    get_lang('Ok'),
                    null,
                    ICON_SIZE_TINY
                );
            } else {
                echo Display::return_icon(
                    'closed-circle.png',
                    get_lang('Wrong'),
                    null,
                    ICON_SIZE_TINY
                );
            }
        }
        break;
    default:
        echo '';
}
exit;

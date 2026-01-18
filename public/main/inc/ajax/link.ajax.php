<?php
/* For licensing terms, see /license.txt */

/**
 * Responses to AJAX calls.
 */

use Chamilo\CoreBundle\Enums\StateIcon;

require_once __DIR__.'/../global.inc.php';

api_protect_course_script(true);
$isAllowedToEdit = api_is_allowed_to_edit(null, true);
// Close the session as we don't need it any further
session_write_close();

$action = $_REQUEST['a'];

switch ($action) {
    case 'check_url':
        if ($isAllowedToEdit) {
            $url = $_REQUEST['url'];
            $result = \Link::checkUrl($url);

            if ($result) {
                echo Display::getMdiIcon(
                    StateIcon::COMPLETE,
                    'ch-tool-icon',
                    null,
                    ICON_SIZE_TINY,
                    get_lang('Validate')
                );
            } else {
                echo Display::getMdiIcon(
                    StateIcon::WARNING,
                    'ch-tool-icon',
                    null,
                    ICON_SIZE_TINY,
                    get_lang('Wrong')
                );
            }
        }
        break;
    default:
        echo '';
}
exit;

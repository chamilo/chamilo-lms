<?php
/* For licensing terms, see /license.txt */

/**
 * Responses to AJAX calls
 */

require_once '../global.inc.php';

$action = isset($_REQUEST['a']) ? $_REQUEST['a'] : null;
$isAllowedToEdit = api_is_allowed_to_edit();
$courseInfo = api_get_course_info();

switch ($action) {
    case 'delete_item':
        if ($isAllowedToEdit) {
            if (empty($_REQUEST['id'])) {
                return false;
            }

            if (api_get_session_id() != 0 && api_is_allowed_to_session_edit(false, true) == false) {
                return false;
            }

            $list = explode(',', $_REQUEST['id']);
            foreach ($list as $itemId) {
                if (!api_is_course_coach() || api_is_element_in_the_session(TOOL_ANNOUNCEMENT, $itemId)) {
                    AnnouncementManager::delete_announcement($courseInfo, $itemId);
                }
            }
        }
        break;
    default:
        echo '';
        break;
}
exit;

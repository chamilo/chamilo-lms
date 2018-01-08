<?php
/* For licensing terms, see /license.txt */

/**
 * Responses to AJAX calls
 */

require_once __DIR__.'/../global.inc.php';

$action = isset($_REQUEST['a']) ? $_REQUEST['a'] : null;

$isAllowedToEdit = api_is_allowed_to_edit();
$courseInfo = api_get_course_info();
$groupId = api_get_group_id();
$sessionId = api_get_session_id();

$isTutor = false;
if (!empty($groupId)) {
    $groupInfo = GroupManager::get_group_properties($groupId);
    $isTutor = GroupManager::is_tutor_of_group(api_get_user_id(), $groupInfo);
    if ($isTutor) {
        $isAllowedToEdit = true;
    }
}

switch ($action) {
    case 'delete_item':
        if ($isAllowedToEdit) {
            if (empty($_REQUEST['id'])) {
                return false;
            }
            if (!empty($sessionId) && api_is_allowed_to_session_edit(false, true) == false && empty($groupId)) {
                return false;
            }

            $list = explode(',', $_REQUEST['id']);
            foreach ($list as $itemId) {
                if (!api_is_session_general_coach() || api_is_element_in_the_session(TOOL_ANNOUNCEMENT, $itemId)) {
                    $result = AnnouncementManager::get_by_id(
                        api_get_course_int_id(),
                        $itemId
                    );
                    if (!empty($result)) {
                        $delete = true;
                        if (!empty($groupId) && $isTutor) {
                            if ($groupId != $result['to_group_id']) {
                                $delete = false;
                            }
                        }
                        if ($delete) {
                            AnnouncementManager::delete_announcement($courseInfo, $itemId);
                        }
                    }
                }
            }
        }
        break;
    default:
        echo '';
        break;
}
exit;

<?php
/* For licensing terms, see /license.txt */
/**
 * Responses to AJAX calls.
 */
require_once __DIR__.'/../global.inc.php';

$action = isset($_REQUEST['a']) ? $_REQUEST['a'] : '';
$isAllowedToEdit = api_is_allowed_to_edit();

switch ($action) {
    case 'delete_user_in_usergroup':
        if ($isAllowedToEdit) {
            $userGroup = new UserGroup();
            $userId = isset($_REQUEST['id']) ? $_REQUEST['id'] : 0;
            $userIdList = explode(',', $userId);
            $groupId = isset($_REQUEST['group_id']) ? $_REQUEST['group_id'] : 0;
            foreach ($userIdList as $userId) {
                $userGroup->delete_user_rel_group($userId, $groupId);
            }
        }
        break;
    default:
        echo '';
        break;
}
exit;

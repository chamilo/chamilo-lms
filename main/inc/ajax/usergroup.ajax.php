<?php

/* For licensing terms, see /license.txt */

/**
 * Responses to AJAX calls.
 */
require_once __DIR__.'/../global.inc.php';

$action = isset($_REQUEST['a']) ? $_REQUEST['a'] : '';
$isAllowedToEdit = api_is_allowed_to_edit();

switch ($action) {
    case 'get_class_by_keyword':
        $keyword = isset($_REQUEST['q']) ? $_REQUEST['q'] : '';
        $allow = api_is_platform_admin() || api_is_session_admin();

        if ($allow && !empty($keyword)) {
            $userGroup = new UserGroup();
            $where = ['where' => ['name like ?' => "%$keyword%"], 'order' => 'name '];
            $items = [];
            $list = $userGroup->get_all($where);
            foreach ($list as $class) {
                $items[] = [
                    'id' => $class['id'],
                    'text' => $class['name'],
                ];
            }
            echo json_encode(['items' => $items]);
        }
        break;
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

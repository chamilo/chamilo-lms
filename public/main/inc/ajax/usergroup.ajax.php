<?php

/* For licensing terms, see /license.txt */

/**
 * Responses to AJAX calls.
 */

use Chamilo\CoreBundle\Framework\Container;

require_once __DIR__.'/../global.inc.php';

$action = isset($_REQUEST['a']) ? $_REQUEST['a'] : '';
$isAllowedToEdit = api_is_allowed_to_edit();

switch ($action) {
    case 'get_users_by_group_course':
        $groupId = (int) $_POST['group_id'];
        $sessionId = (int) $_POST['session_id'];
        if ($groupId) {
            $users = Container::getUsergroupRepository()->getUsersByGroup($groupId, true);
            if (!empty($sessionId)) {
                // Close the session as we don't need it any further
                session_write_close();
                $filtered = [];
                foreach ($users as $user) {
                    $filtered[] = [
                        'id' => $user['id'],
                        'name' => api_get_person_name($user['firstname'], $user['lastname']),
                    ];
                }
            } else {
                $courseCode = $_POST['course_code'];
                $courseUsers = CourseManager::get_user_list_from_course_code($courseCode, 0);
                $courseUserIds = array_column($courseUsers, 'user_id');
                // Close the session as we don't need it any further
                session_write_close();

                $filtered = [];
                foreach ($users as $user) {
                    if (in_array($user['id'], $courseUserIds)) {
                        $filtered[] = [
                            'id' => $user['id'],
                            'name' => api_get_person_name($user['firstname'], $user['lastname']),
                        ];
                    }
                }
            }

            echo json_encode($filtered);
        }
        exit;
    case 'get_class_by_keyword':
        $keyword = isset($_REQUEST['q']) ? $_REQUEST['q'] : '';
        if (api_is_platform_admin() && !empty($keyword)) {
            // Close the session as we don't need it any further
            session_write_close();
            $userGroup = new UserGroupModel();
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
            // Close the session as we don't need it any further
            session_write_close();
            $userGroup = new UserGroupModel();
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

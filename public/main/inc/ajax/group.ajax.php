<?php

/* For licensing terms, see /license.txt */

require_once __DIR__.'/../global.inc.php';

$action = $_REQUEST['a'] ?? '';
$isAllowedToEdit = api_is_allowed_to_edit();

switch ($action) {
    case 'search':
        if ($isAllowedToEdit) {
            $groups = GroupManager::get_group_list(null, api_get_course_entity(), null, 0, false, $_REQUEST['q']);
            $list = [];
            foreach ($groups as $group) {
                $list[] = [
                    'id' => $group['iid'],
                    'text' => $group['name'],
                ];
            }
            echo json_encode(['items' => $list]);
        }
        break;
    default:
        break;
}
exit;

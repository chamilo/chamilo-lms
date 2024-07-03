<?php

/* For licensing terms, see /license.txt */

/**
 * Responses to AJAX calls.
 */

use Symfony\Component\HttpFoundation\Request as HttpRequest;

require_once __DIR__.'/../global.inc.php';

$httpRequest = HttpRequest::createFromGlobals();

$action = $httpRequest->query->has('a') ? $httpRequest->query->get('a') : $httpRequest->request->get('a');

$isAllowedToEdit = api_is_allowed_to_edit();

switch ($action) {
    case 'get_class_by_keyword':
        $keyword = $httpRequest->query->has('q') ? $httpRequest->query->get('q') : $httpRequest->request->get('q');
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
            $userId = $httpRequest->query->has('id')
                ? $httpRequest->query->getInt('id')
                : $httpRequest->request->getInt('id');
            $userIdList = explode(',', $userId);
            $groupId = $httpRequest->query->has('group_id')
                ? $httpRequest->query->getInt('group_id')
                : $httpRequest->request->getInt('group_id');
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

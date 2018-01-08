<?php
/* For licensing terms, see /license.txt */

/**
 * Responses to AJAX calls
 */
require_once __DIR__.'/../global.inc.php';

$action = $_REQUEST['a'];

switch ($action) {
    case 'get_user_sessions':
        if (api_is_platform_admin() || api_is_session_admin()) {
            $user_id = intval($_POST['user_id']);
            $list_sessions = SessionManager::get_sessions_by_user($user_id, true);
            if (!empty($list_sessions)) {
                foreach ($list_sessions as $session_item) {
                    echo $session_item['session_name'].'<br />';
                }
            } else {
                echo get_lang('NoSessionsForThisUser');
            }
            unset($list_sessions);
        }
        break;
    case 'order':
        api_protect_admin_script();
        $allowOrder = api_get_configuration_value('session_list_order');
        if ($allowOrder) {
            $order = isset($_GET['order']) ? $_GET['order'] : [];
            $order = json_decode($order);
            if (!empty($order)) {
                $table = Database::get_main_table(TABLE_MAIN_SESSION);
                foreach ($order as $data) {
                    if (isset($data->order) && isset($data->id)) {
                        $orderId = (int)$data->order;
                        $sessionId = (int)$data->id;
                        $sql = "UPDATE $table SET position = $orderId WHERE id = $sessionId ";
                        Database::query($sql);
                    }
                }
            }
        }
        break;
    case 'search_session':
        if (api_is_platform_admin()) {
            $sessions = SessionManager::get_sessions_list(
                [
                    's.name' => [
                        'operator' => 'LIKE',
                        'value' => "%".$_REQUEST['q']."%"
                    ]
                ]
            );

            $list = [
                'items' => []
            ];

            if (empty($sessions)) {
                echo json_encode([]);
                break;
            }

            foreach ($sessions as $session) {
                $list['items'][] = [
                    'id' => $session['id'],
                    'text' => $session['name']
                ];
            }

            echo json_encode($list);
        }
        break;
    case 'search_session_all':
        if (api_is_platform_admin()) {
            $results = SessionManager::get_sessions_list(
                [
                    's.name' => ['operator' => 'like', 'value' => "%".$_REQUEST['q']."%"],
                    'c.id' => ['operator' => '=', 'value' => $_REQUEST['course_id']]
                ]
            );
            $results2 = [];
            if (!empty($results)) {
                foreach ($results as $item) {
                    $item2 = [];
                    foreach ($item as $id => $internal) {
                        if ($id == 'id') {
                            $item2[$id] = $internal;
                        }
                        if ($id == 'name') {
                            $item2['text'] = $internal;
                        }
                    }
                    $results2[] = $item2;
                }
                $results2[] = ['T', 'text' => 'TODOS', 'id' => 'T'];
                echo json_encode($results2);
            } else {
                echo json_encode([['T', 'text' => 'TODOS', 'id' => 'T']]);
            }
        }
        break;
    case 'search_session_by_course':
        if (api_is_platform_admin()) {
            $results = SessionManager::get_sessions_list(
                [
                    's.name' => ['operator' => 'like', 'value' => "%".$_REQUEST['q']."%"],
                    'c.id' => ['operator' => '=', 'value' => $_REQUEST['course_id']]
                ]
            );
            $json = [
                'items' => [
                    ['id' => 'T', 'text' => get_lang('All')]
                ]
            ];
            if (!empty($results)) {
                foreach ($results as $item) {
                    $item2 = [];
                    foreach ($item as $id => $internal) {
                        if ($id == 'id') {
                            $item2[$id] = $internal;
                        }
                        if ($id == 'name') {
                            $item2['text'] = $internal;
                        }
                    }
                    $json['items'][] = $item2;
                }
            }

            echo json_encode($json);
        }
        break;
    case 'session_info':
        $sessionId = isset($_GET['session_id']) ? $_GET['session_id'] : '';
        $sessionInfo = api_get_session_info($sessionId);

        $extraFieldValues = new ExtraFieldValue('session');
        $extraField = new ExtraField('session');
        $values = $extraFieldValues->getAllValuesByItem($sessionId);
        $load = isset($_GET['load_empty_extra_fields']) ? true : false;

        if ($load) {
            $allExtraFields = $extraField->get_all();
            $valueList = array_column($values, 'id');
            foreach ($allExtraFields as $extra) {
                if (!in_array($extra['id'], $valueList)) {
                    $values[] = [
                        'id' => $extra['id'],
                        'variable' => $extra['variable'],
                        'value' => '',
                        'field_type' => $extra['field_type'],
                    ];
                }
            }
        }

        $sessionInfo['extra_fields'] = $values;

        if (!empty($sessionInfo)) {
            echo json_encode($sessionInfo);
        }
        break;
    case 'get_description':
        if (isset($_GET['session'])) {
            $sessionInfo = api_get_session_info($_GET['session']);
            echo '<h2>'.$sessionInfo['name'].'</h2>';
            echo '<div class="home-course-intro"><div class="page-course"><div class="page-course-intro">';
            echo $sessionInfo['show_description'] == 1 ? $sessionInfo['description'] : get_lang('None');
            echo '</div></div></div>';
        }
        break;
    case 'search_general_coach':
        header('Content-Type: application/json');

        if (api_is_anonymous()) {
            echo '';
            break;
        }

        $list = [
            'items' => []
        ];

        $entityManager = Database::getManager();
        $usersRepo = $entityManager->getRepository('ChamiloUserBundle:User');
        $users = $usersRepo->searchUsersByStatus($_GET['q'], COURSEMANAGER);

        foreach ($users as $user) {
            $list['items'][] = [
                'id' => $user->getId(),
                'text' => $user->getCompleteName()
            ];
        }

        echo json_encode($list);
        break;
    case 'get_courses_inside_session':
        $userId = api_get_user_id();
        $isAdmin = api_is_platform_admin();
        if ($isAdmin) {
            $sessionList = SessionManager::get_sessions_list();
            $sessionIdList = array_column($sessionList, 'id');
        } else {
            $sessionList = SessionManager::get_sessions_by_user($userId);
            $sessionIdList = array_column($sessionList, 'session_id');
        }

        $sessionId = isset($_GET['session_id']) ? (int) $_GET['session_id'] : 0;
        $courseList = [];
        if (empty($sessionId)) {
            $preCourseList = CourseManager::get_courses_list_by_user_id(
                $userId,
                false,
                true
            );
            $courseList = array_column($preCourseList, 'real_id');
        } else {
            if ($isAdmin) {
                $courseList = SessionManager::getCoursesInSession($sessionId);
            } else {
                if (in_array($sessionId, $sessionIdList)) {
                    $courseList = SessionManager::getCoursesInSession($sessionId);
                }
            }
        }

        $courseListToSelect = [];
        if (!empty($courseList)) {
            // Course List
            foreach ($courseList as $courseId) {
                $courseInfo = api_get_course_info_by_id($courseId);
                $courseListToSelect[] = [
                    'id' => $courseInfo['real_id'],
                    'name' => $courseInfo['title']
                ];
            }
        }

        echo json_encode($courseListToSelect);
        break;
    default:
        echo '';
}
exit;

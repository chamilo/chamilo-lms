<?php
/* For licensing terms, see /license.txt */

/**
 * Responses to AJAX calls
 */
require_once '../global.inc.php';

$action = $_REQUEST['a'];

switch ($action) {
    case 'get_user_sessions':
        if (api_is_platform_admin()) {
            $user_id = intval($_POST['user_id']);
            $list_sessions = SessionManager::get_sessions_by_user($user_id, true);
            if (!empty($list_sessions)) {
                foreach ($list_sessions as $session_item) {
                    echo $session_item['session_name'] . '<br />';
                }
            } else {
                echo get_lang('NoSessionsForThisUser');
            }
            unset($list_sessions);
        }
        break;
    case 'search_session':
        if (api_is_platform_admin()) {
            $sessions = SessionManager::get_sessions_list(
                [
                    's.name' => [
                        'operator' => 'LIKE',
                        'value' => "%" . $_REQUEST['q'] . "%"
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
                array(
                    's.name' => array('operator' => 'like', 'value' => "%".$_REQUEST['q']."%"),
                    'c.id' => array('operator' => '=', 'value' => $_REQUEST['course_id'])
                )
            );
            $results2 = array();
            if (!empty($results)) {
                foreach ($results as $item) {
                    $item2 = array();
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
                $results2[] = array('T', 'text' => 'TODOS', 'id' => 'T');
                echo json_encode($results2);
            } else {
                echo json_encode(array(array('T', 'text' => 'TODOS', 'id' => 'T')));
            }
        }
        break;
    case 'search_session_by_course':
        if (api_is_platform_admin()) {
            $results = SessionManager::get_sessions_list(
                array(
                    's.name' => array('operator' => 'like', 'value' => "%".$_REQUEST['q']."%"),
                    'c.id' => array('operator' => '=', 'value' => $_REQUEST['course_id'])
                )
            );
            $json = [
                'items' => [
                    ['id' => 'T', 'text' => get_lang('All')]
                ]
            ];
            if (!empty($results)) {
                foreach ($results as $item) {
                    $item2 = array();
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
    default:
        echo '';
}
exit;

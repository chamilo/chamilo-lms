<?php
/* For licensing terms, see /license.txt */
/**
 * Responses to AJAX calls
 */

$language_file = array('learnpath', 'courses', 'index','tracking','exercice', 'admin');

require_once '../global.inc.php';

$action = $_REQUEST['a'];

switch ($action) {
    case 'get_user_sessions':
        if (api_is_platform_admin()) {
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
    case 'search_session':
        if (api_is_platform_admin()) {
            $results = SessionManager::get_sessions_list(
                array('s.name' => array('operator' => 'LIKE', 'value' => "%".$_REQUEST['q']."%"))
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
                echo json_encode($results2);
            } else {
                echo json_encode(array());
            }
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
    case 'my_session_statistics':
        if (empty($_GET['session_id'])) {
            api_not_allowed();
        }

        $courseCode = !empty($_GET['course']) ? $_GET['course'] : null;
        $sessionId = api_get_session_id();

        // Getting all sessions where I'm subscribed
        $newSessionList = UserManager::get_personal_session_course_list(api_get_user_id());

        $mySessionList = array();

        if (!empty($newSessionList)) {
            foreach ($newSessionList as $item) {
                if (!isset($item['id_session'])) {
                    continue;
                }

                $mySessionList[] = $item['id_session'];
            }
        }

        // If the requested session does not exist in my list we stop the script
        if (!api_is_platform_admin()) {
            if (!in_array($sessionId, $mySessionList)) {
                api_not_allowed(true);
            }
        }

        $reportingTab = Tracking::show_user_progress(api_get_user_id(), $sessionId, null, false, false, true);

        if (empty($reportingTab)) {
            echo Display::return_message(get_lang('NoDataAvailable'), 'warning');
            exit;
        }

        echo $reportingTab . '<br>' . Tracking::show_course_detail(api_get_user_id(), $courseCode, $sessionId);
        break;
    default:
        echo '';
}
exit;

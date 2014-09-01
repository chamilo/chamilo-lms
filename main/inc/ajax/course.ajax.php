<?php
/* For licensing terms, see /license.txt */
/**
 * Responses to AJAX calls
 */

$language_file[] = 'admin';
require_once '../global.inc.php';

$action = $_REQUEST['a'];
$user_id = api_get_user_id();

switch ($action) {
    case 'add_course_vote':
        $course_id = intval($_REQUEST['course_id']);
        $star      = intval($_REQUEST['star']);

        if (!api_is_anonymous()) {
            CourseManager::add_course_vote($user_id, $star, $course_id, 0);
        }
        $point_info = CourseManager::get_course_ranking($course_id, 0);
        $ajax_url = api_get_path(WEB_AJAX_PATH).'course.ajax.php?a=add_course_vote';
        $rating = Display::return_rating_system('star_'.$course_id, $ajax_url.'&amp;course_id='.$course_id, $point_info, false);
        echo $rating;

        break;
    case 'get_user_courses':
        if (api_is_platform_admin()) {
            $user_id = intval($_POST['user_id']);
            $list_course_all_info = CourseManager::get_courses_list_by_user_id($user_id, false);
            if (!empty($list_course_all_info)) {
                foreach ($list_course_all_info as $course_item) {
                    $course_info = api_get_course_info($course_item['code']);
                    echo $course_info['title'].'<br />';
                }
            } else {
                echo get_lang('UserHasNoCourse');
            }
        }
        break;
    case 'search_category':
        require_once api_get_path(LIBRARY_PATH).'course_category.lib.php';
        if (api_is_platform_admin() || api_is_allowed_to_create_course()) {
            $results = searchCategoryByKeyword($_REQUEST['q']);
            if (!empty($results)) {
                foreach ($results as &$item) {
                    $item['id'] = $item['code'];
                }
                echo json_encode($results);
            } else {
                echo json_encode(array());
            }
        }
        break;
    case 'search_course':
        if (api_is_platform_admin()) {
            if (!empty($_GET['session_id']) && intval($_GET['session_id'])) {
                //if session is defined, lets find only courses of this session
                $courseList = SessionManager::get_course_list_by_session_id(
                    $_GET['session_id'],
                    $_GET['q']
                );
            } else {
                //if session is not defined lets search all courses STARTING with $_GET['q']
                //TODO change this function to search not only courses STARTING with $_GET['q']
                $courseList = CourseManager::get_courses_list(
                    0, //offset
                    0, //howMany
                    1, //$orderby = 1
                    'ASC',
                    -1,  //visibility
                    $_GET['q'],
                    null, //$urlId
                    true //AlsoSearchCode
                );
            }

            $results = array();

            require_once api_get_path(LIBRARY_PATH).'course_category.lib.php';

            if (!empty($courseList)) {

                foreach ($courseList as $courseInfo) {
                    $title = $courseInfo['title'];

                    if (!empty($courseInfo['category_code'])) {
                        $parents = getParentsToString($courseInfo['category_code']);
                        $title = $parents.$courseInfo['title'];
                    }

                    $results[] = array(
                        'id' => $courseInfo['id'],
                        'text' => $title
                        );
                }
                echo json_encode($results);
            } else {
                echo json_encode(array());
            }
        }
        break;
    case 'search_course_by_session':
        if (api_is_platform_admin()) {
            $results = SessionManager::get_course_list_by_session_id($_GET['session_id'], $_GET['q']);

            //$results = SessionManager::get_sessions_list(array('s.name LIKE' => "%".$_REQUEST['q']."%"));
            $results2 = array();
            if (!empty($results)) {
                foreach ($results as $item) {
                    $item2 = array();
                    foreach ($item as $id => $internal) {
                        if ($id == 'id') {
                            $item2[$id] = $internal;
                        }
                        if ($id == 'title') {
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
    case 'search_course_by_session_all':
        if (api_is_platform_admin()) {
            if ($_GET['session_id'] == 'TODOS' || $_GET['session_id'] == 'T') {
                $_GET['session_id'] = '%';
            }

            $results = SessionManager::get_course_list_by_session_id_like(
                $_GET['session_id'],
                $_GET['q']
            );
            $results2 = array();
            if (!empty($results)) {
                foreach ($results as $item) {
                    $item2 = array();
                    foreach ($item as $id => $internal) {
                        if ($id == 'id') {
                            $item2[$id] = $internal;
                        }
                        if ($id == 'title') {
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
    case 'search_user_by_course':
        if (api_is_platform_admin()) {
            $user                   = Database :: get_main_table(TABLE_MAIN_USER);
            $session_course_user    = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE_USER);

            $course = api_get_course_info_by_id($_GET['course_id']);

            $sql = "SELECT u.user_id as id, u.username, u.lastname, u.firstname
                    FROM $user u
                    INNER JOIN $session_course_user r ON u.user_id = r.id_user
                    WHERE id_session = %d AND course_code =  '%s'
                    AND (u.firstname LIKE '%s' OR u.username LIKE '%s' OR u.lastname LIKE '%s')";
            $needle = '%' . $_GET['q'] . '%';
            $sql_query = sprintf($sql, $_GET['session_id'], $course['code'], $needle, $needle, $needle);

            $result = Database::query($sql_query);
            while ($user = Database::fetch_assoc($result)) {
                $data[] = array('id' => $user['id'], 'text' => $user['username'] . ' (' . $user['firstname'] . ' ' . $user['lastname'] . ')');

            }
            if (!empty($data)) {
                echo json_encode($data);
            } else {
                echo json_encode(array());
            }
        }
        break;
    case 'search_exercise_by_course':
        if (api_is_platform_admin()) {
            $course = api_get_course_info_by_id($_GET['course_id']);
            require_once api_get_path(SYS_CODE_PATH).'exercice/exercise.lib.php';
            $session_id = (!empty($_GET['session_id'])) ?  intval($_GET['session_id']) : 0 ;
            $exercises = get_all_exercises($course, $session_id, false, $_GET['q'], true, 3);

            foreach ($exercises as $exercise) {
                $data[] = array('id' => $exercise['id'], 'text' => html_entity_decode($exercise['title']) );
            }
            if (!empty($data)) {
                $data[] = array('id' => 'T', 'text' => 'TODOS');
                echo json_encode($data);
            } else {
                echo json_encode(array(array('id' => 'T', 'text' => 'TODOS')));
            }
        }
        break;
    case 'search_survey_by_course':
        if (api_is_platform_admin()) {
            $survey = Database :: get_course_table(TABLE_SURVEY);

            $sql = "SELECT survey_id as id, title, anonymous
                    FROM $survey
                    WHERE
                      c_id = %d AND
                      session_id = %d AND
                      title LIKE '%s'";

            $sql_query = sprintf(
                $sql,
                intval($_GET['course_id']),
                intval($_GET['session_id']),
                '%' . Database::escape_string($_GET['q']).'%'
            );
            $result = Database::query($sql_query);
            while ($survey = Database::fetch_assoc($result)) {
                $survey['title'] .= ($survey['anonymous'] == 1) ? ' (' . get_lang('Anonymous') . ')' : '';
                $data[] = array(
                    'id' => $survey['id'],
                    'text' => strip_tags(html_entity_decode($survey['title']))
                );
            }
            if (!empty($data)) {
                echo json_encode($data);
            } else {
                echo json_encode(array());
            }
        }
        break;
    default:
        echo '';
}
exit;

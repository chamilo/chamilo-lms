<?php
/* For licensing terms, see /license.txt */
/**
 * Responses to AJAX calls
 */
require_once __DIR__.'/../global.inc.php';

$action = $_REQUEST['a'];
$user_id = api_get_user_id();

switch ($action) {
    case 'add_course_vote':
        $course_id = intval($_REQUEST['course_id']);
        $star = intval($_REQUEST['star']);

        if (!api_is_anonymous()) {
            CourseManager::add_course_vote($user_id, $star, $course_id, 0);
        }
        $point_info = CourseManager::get_course_ranking($course_id, 0);
        $ajax_url = api_get_path(WEB_AJAX_PATH).'course.ajax.php?a=add_course_vote';
        $rating = Display::return_rating_system(
            'star_'.$course_id,
            $ajax_url.'&amp;course_id='.$course_id,
            $point_info,
            false
        );
        echo $rating;
        break;
    case 'get_course_image':
        $courseInfo = api_get_course_info($_REQUEST['code']);
        $image = isset($_REQUEST['image']) && in_array($_REQUEST['image'], ['course_image_large_source', 'course_image_source']) ? $_REQUEST['image'] : '';
        if ($courseInfo && $image) {
            DocumentManager::file_send_for_download($courseInfo[$image]);
        }
        break;
    case 'get_user_courses':
        if (api_is_platform_admin() || api_is_session_admin()) {
            $user_id = intval($_POST['user_id']);
            $list = CourseManager::get_courses_list_by_user_id(
                $user_id,
                false
            );
            if (!empty($list)) {
                foreach ($list as $course) {
                    $courseInfo = api_get_course_info_by_id($course['real_id']);
                    echo $courseInfo['title'].'<br />';
                }
            } else {
                echo get_lang('UserHasNoCourse');
            }
        }
        break;
    case 'search_category':
        if (api_is_platform_admin() || api_is_allowed_to_create_course()) {
            $categories = CourseCategory::searchCategoryByKeyword($_REQUEST['q']);

            if (empty($categories)) {
                echo json_encode([]);
                break;
            }

            $list = [];
            foreach ($categories as $item) {
                $list['items'][] = [
                    'id' => $item['code'],
                    'text' => '('.$item['code'].') '.strip_tags($item['name'])
                ];
            }

            echo json_encode($list);
        }
        break;
    case 'search_course':
        if (api_is_teacher()) {
            if (!empty($_GET['session_id']) && intval($_GET['session_id'])) {
                //if session is defined, lets find only courses of this session
                $courseList = SessionManager::get_course_list_by_session_id(
                    $_GET['session_id'],
                    $_GET['q']
                );
            } else {
                //if session is not defined lets search all courses STARTING with $_GET['q']
                //TODO change this function to search not only courses STARTING with $_GET['q']
                if (api_is_platform_admin()) {
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
                } elseif (api_is_teacher()) {
                    $courseList = CourseManager::get_course_list_of_user_as_course_admin(api_get_user_id(), $_GET['q']);
                }
            }

            $results = array();

            if (empty($courseList)) {
                echo json_encode([]);
                break;
            }

            foreach ($courseList as $course) {
                $title = $course['title'];

                if (!empty($course['category_code'])) {
                    $parents = CourseCategory::getParentsToString($course['category_code']);
                    $title = $parents . $course['title'];
                }

                $results['items'][] = array(
                    'id' => $course['id'],
                    'text' => $title
                );
            }

            echo json_encode($results);
        }
        break;
    case 'search_course_by_session':
        if (api_is_platform_admin()) {
            $results = SessionManager::get_course_list_by_session_id($_GET['session_id'], $_GET['q']);
            $results2 = array();
            if (is_array($results) && !empty($results)) {
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
            $results2 = ['items' => []];
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
                    $results2['items'][] = $item2;
                }
            }

            echo json_encode($results2);
        }
        break;
    case 'search_user_by_course':
        if (api_is_platform_admin()) {
            $user = Database::get_main_table(TABLE_MAIN_USER);
            $session_course_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
            $course = api_get_course_info_by_id($_GET['course_id']);

            $json = [
                'items' => []
            ];

            $sql = "SELECT u.user_id as id, u.username, u.lastname, u.firstname
                    FROM $user u
                    INNER JOIN $session_course_user r 
                    ON u.user_id = r.user_id
                    WHERE session_id = %d AND c_id =  '%s'
                    AND (u.firstname LIKE '%s' OR u.username LIKE '%s' OR u.lastname LIKE '%s')";
            $needle = '%' . $_GET['q'] . '%';
            $sql_query = sprintf($sql, $_GET['session_id'], $course['real_id'], $needle, $needle, $needle);

            $result = Database::query($sql_query);
            while ($user = Database::fetch_assoc($result)) {
                $userCompleteName = api_get_person_name($user['firstname'], $user['lastname']);

                $json['items'][] = [
                    'id' => $user['id'],
                    'text' => "{$user['username']} ($userCompleteName)"
                ];
            }

            echo json_encode($json);
        }
        break;
    case 'search_exercise_by_course':
        if (api_is_platform_admin()) {
            $course = api_get_course_info_by_id($_GET['course_id']);
            $session_id = (!empty($_GET['session_id'])) ?  intval($_GET['session_id']) : 0 ;
            $exercises = ExerciseLib::get_all_exercises(
                $course,
                $session_id,
                false,
                $_GET['q'],
                true,
                3
            );

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
            $survey = Database::get_course_table(TABLE_SURVEY);

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
    case 'display_sessions_courses':
        $sessionId = intval($_GET['session']);
        $userTable = Database::get_main_table(TABLE_MAIN_USER);
        $coursesData = SessionManager::get_course_list_by_session_id($sessionId);

        $courses = array();

        foreach ($coursesData as $courseId => $course) {
            $coachData = SessionManager::getCoachesByCourseSession($sessionId, $courseId);
            $coachName = '';
            if (!empty($coachData)) {
                $userResult = Database::select('lastname,firstname', $userTable, array(
                    'where' => array(
                        'user_id = ?' => $coachData[0]
                    )
                ), 'first');

                $coachName = api_get_person_name($userResult['firstname'], $userResult['lastname']);
            }

            $courses[] = array(
                'id' => $courseId,
                'name' => $course['title'],
                'coachName' => $coachName,
            );
        }

        echo json_encode($courses);
        break;
    default:
        echo '';
}
exit;

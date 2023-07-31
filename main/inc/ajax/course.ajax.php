<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Component\Utils\ChamiloApi;

/**
 * Responses to AJAX calls.
 */
require_once __DIR__.'/../global.inc.php';

$action = $_REQUEST['a'];
$user_id = api_get_user_id();

switch ($action) {
    case 'add_course_vote':
        $course_id = (int) $_REQUEST['course_id'];
        $star = (int) $_REQUEST['star'];

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
        $courseId = ChamiloApi::getCourseIdByDirectory($_REQUEST['code']);
        $courseInfo = api_get_course_info_by_id($courseId);
        $image = isset($_REQUEST['image']) && in_array($_REQUEST['image'], ['course_image_large_source', 'course_image_source', 'course_email_image_large_source', 'course_email_image_source']) ? $_REQUEST['image'] : '';
        if ($courseInfo && $image) {
            // Arbitrarily set a cache of 10' for the course image to
            // avoid hammering the server with otherwise unfrequently
            // changed images that can have some weight
            $now = time() + 600; //time must be in GMT anyway
            $headers = [
              'Expires' => gmdate('D, d M Y H:i:s ', $now).'GMT',
              'Cache-Control' => 'max-age=600',
            ];
            DocumentManager::file_send_for_download($courseInfo[$image], null, null, null, $headers);
        }
        break;
    case 'get_user_courses':
        // Only search my courses
        if (api_is_platform_admin() || api_is_session_admin()) {
            $userId = (int) $_REQUEST['user_id'];
            $list = CourseManager::get_courses_list_by_user_id(
                $userId,
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
    case 'get_my_courses_and_sessions':
        // Search my courses and sessions allowed for admin, session admin, teachers
        $currentCourseId = api_get_course_int_id();
        $currentSessionId = api_get_session_id();
        if (api_is_platform_admin() || api_is_session_admin() || api_is_allowed_to_edit()) {
            $list = CourseManager::get_courses_list_by_user_id(
                api_get_user_id(),
                true,
                false,
                false,
                [],
                true,
                true
            );

            if (empty($list)) {
                echo json_encode([]);
                break;
            }

            $courseList = [];
            if (!empty($list)) {
                foreach ($list as $course) {
                    $courseInfo = api_get_course_info_by_id($course['real_id']);
                    $sessionId = 0;
                    if (isset($course['session_id']) && !empty($course['session_id'])) {
                        $sessionId = $course['session_id'];
                    }

                    $sessionName = '';
                    if (isset($course['session_name']) && !empty($course['session_name'])) {
                        $sessionName = ' ('.$course['session_name'].')';
                    }

                    // Skip current course/course session
                    if ($currentCourseId == $courseInfo['real_id'] && $sessionId == $currentSessionId) {
                        continue;
                    }

                    $courseList['items'][] = [
                        'id' => $courseInfo['real_id'].'_'.$sessionId,
                        'text' => $courseInfo['title'].$sessionName,
                    ];
                }

                echo json_encode($courseList);
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

            $categoryToAvoid = '';
            if (!api_is_platform_admin()) {
                $categoryToAvoid = api_get_configuration_value('course_category_code_to_use_as_model');
            }

            $list = [];
            foreach ($categories as $item) {
                $categoryCode = $item['code'];
                if (!empty($categoryToAvoid) && $categoryToAvoid == $categoryCode) {
                    continue;
                }

                $list['items'][] = [
                    'id' => $categoryCode,
                    'text' => '('.$categoryCode.') '.strip_tags($item['name']),
                ];
            }

            echo json_encode($list);
        }
        break;
    case 'search_course':
        if (api_is_teacher() || api_is_platform_admin()) {
            if (isset($_GET['session_id']) && !empty($_GET['session_id'])) {
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
                        0,
                        0,
                        'title',
                        'ASC',
                        -1,
                        $_GET['q'],
                        null,
                        true
                    );
                } elseif (api_is_teacher()) {
                    $courseList = CourseManager::get_course_list_of_user_as_course_admin(api_get_user_id(), $_GET['q']);
                    $category = api_get_configuration_value('course_category_code_to_use_as_model');
                    if (!empty($category)) {
                        $alreadyAdded = [];
                        if (!empty($courseList)) {
                            $alreadyAdded = array_column($courseList, 'id');
                        }
                        $coursesInCategory = CourseCategory::getCoursesInCategory($category, $_GET['q']);
                        foreach ($coursesInCategory as $course) {
                            if (!in_array($course['id'], $alreadyAdded)) {
                                $courseList[] = $course;
                            }
                        }
                    }
                }
            }

            $results = [];
            if (empty($courseList)) {
                echo json_encode([]);
                break;
            }

            foreach ($courseList as $course) {
                $title = $course['title'];
                if (!empty($course['category_code'])) {
                    $parents = CourseCategory::getParentsToString($course['category_code']);
                    $title = $parents.$course['title'];
                }

                $results['items'][] = [
                    'id' => $course['id'],
                    'text' => $title,
                ];
            }

            echo json_encode($results);
        }
        break;
    case 'search_course_by_session':
        if (api_is_platform_admin()) {
            $results = SessionManager::get_course_list_by_session_id($_GET['session_id'], $_GET['q']);
            $results2 = [];
            if (is_array($results) && !empty($results)) {
                foreach ($results as $item) {
                    $item2 = [];
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
                echo json_encode([]);
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
                    $item2 = [];
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
        $sessionId = $_GET['session_id'];
        $course = api_get_course_info_by_id($_GET['course_id']);

        $isPlatformAdmin = api_is_platform_admin();
        $userIsSubscribedInCourse = CourseManager::is_user_subscribed_in_course(
            api_get_user_id(),
            $course['code'],
            !empty($sessionId),
            $sessionId
        );

        if ($isPlatformAdmin || $userIsSubscribedInCourse) {
            $json = [
                'items' => [],
            ];

            $keyword = Database::escape_string($_GET['q']);
            $status = 0;
            if (empty($sessionId)) {
                $status = STUDENT;
            }

            $userList = CourseManager::get_user_list_from_course_code(
                $course['code'],
                $sessionId,
                null,
                null,
                $status,
                false,
                false,
                false,
                [],
                [],
                [],
                true,
                [],
                $_GET['q']
            );

            foreach ($userList as $user) {
                $userCompleteName = api_get_person_name($user['firstname'], $user['lastname']);

                $json['items'][] = [
                    'id' => $user['user_id'],
                    'text' => "{$user['username']} ($userCompleteName)",
                    'avatarUrl' => UserManager::getUserPicture($user['id']),
                    'username' => $user['username'],
                    'completeName' => $userCompleteName,
                ];
            }

            echo json_encode($json);
        }
        break;
    case 'search_exercise_by_course':
        if (api_is_platform_admin()) {
            $course = api_get_course_info_by_id($_GET['course_id']);
            $session_id = (!empty($_GET['session_id'])) ? (int) $_GET['session_id'] : 0;
            $exercises = ExerciseLib::get_all_exercises(
                $course,
                $session_id,
                false,
                $_GET['q'],
                true,
                3
            );

            foreach ($exercises as $exercise) {
                $data[] = ['id' => $exercise['iid'], 'text' => html_entity_decode($exercise['title'])];
            }
            if (!empty($data)) {
                $data[] = ['id' => 'T', 'text' => 'TODOS'];
                echo json_encode($data);
            } else {
                echo json_encode([['id' => 'T', 'text' => 'TODOS']]);
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
                (int) $_GET['course_id'],
                (int) $_GET['session_id'],
                '%'.Database::escape_string($_GET['q']).'%'
            );
            $result = Database::query($sql_query);
            while ($survey = Database::fetch_assoc($result)) {
                $survey['title'] .= ($survey['anonymous'] == 1) ? ' ('.get_lang('Anonymous').')' : '';
                $data[] = [
                    'id' => $survey['id'],
                    'text' => strip_tags(html_entity_decode($survey['title'])),
                ];
            }
            if (!empty($data)) {
                echo json_encode($data);
            } else {
                echo json_encode([]);
            }
        }
        break;
    case 'display_sessions_courses':
        $sessionId = (int) $_GET['session'];
        $userTable = Database::get_main_table(TABLE_MAIN_USER);
        $coursesData = SessionManager::get_course_list_by_session_id($sessionId);

        $courses = [];

        foreach ($coursesData as $courseId => $course) {
            $coachData = SessionManager::getCoachesByCourseSession($sessionId, $courseId);
            $coachName = '';
            if (!empty($coachData)) {
                $userResult = Database::select('lastname,firstname', $userTable, [
                    'where' => [
                        'user_id = ?' => $coachData[0],
                    ],
                ], 'first');

                $coachName = api_get_person_name($userResult['firstname'], $userResult['lastname']);
            }

            $courses[] = [
                'id' => $courseId,
                'name' => $course['title'],
                'coachName' => $coachName,
            ];
        }

        echo json_encode($courses);
        break;
    case 'course_logout':
        $logoutInfo = [
            'uid' => api_get_user_id(),
            'cid' => api_get_course_int_id(),
            'sid' => api_get_session_id(),
        ];

        $logInfo = [
            'tool' => 'close-window',
            'tool_id' => 0,
            'tool_id_detail' => 0,
            'action' => 'exit',
        ];
        Event::registerLog($logInfo);

        $result = (int) Event::courseLogout($logoutInfo);
        echo $result;
        break;
    default:
        echo '';
}
exit;

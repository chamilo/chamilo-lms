<?php
/* For licensing terms, see /license.txt */
/**
 * Courses reporting.
 */
ob_start();
$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_TRACKING;

$sessionId = isset($_GET['session_id']) ? intval($_GET['session_id']) : null;

api_block_anonymous_users();

$allowToTrack = api_is_platform_admin(true, true) || api_is_teacher();

if (!$allowToTrack) {
    api_not_allowed(true);
}

$interbreadcrumb[] = ["url" => "index.php", "name" => get_lang('MySpace')];

if (isset($_GET["id_session"]) && $_GET["id_session"] != "") {
    $interbreadcrumb[] = ["url" => "session.php", "name" => get_lang('Sessions')];
}

if (isset($_GET["user_id"]) && $_GET["user_id"] != "" && isset($_GET["type"]) && $_GET["type"] == "coach") {
    $interbreadcrumb[] = ["url" => "coaches.php", "name" => get_lang('Tutors')];
}

if (isset($_GET["user_id"]) && $_GET["user_id"] != "" && isset($_GET["type"]) && $_GET["type"] == "student") {
    $interbreadcrumb[] = ["url" => "student.php", "name" => get_lang('Students')];
}

if (isset($_GET["user_id"]) && $_GET["user_id"] != "" && !isset($_GET["type"])) {
    $interbreadcrumb[] = ["url" => "teachers.php", "name" => get_lang('Teachers')];
}

function count_courses()
{
    global $nb_courses;

    return $nb_courses;
}

// Checking if the current coach is the admin coach
$showImportIcon = false;
if (api_get_setting('add_users_by_coach') == 'true') {
    if (!api_is_platform_admin()) {
        $isGeneralCoach = SessionManager::user_is_general_coach(
            api_get_user_id(),
            $sessionId
        );
        if ($isGeneralCoach) {
            $showImportIcon = true;
        }
    }
}

Display::display_header(get_lang('Courses'));
$user_id = 0;
$a_courses = [];
$menu_items = [];
if (api_is_platform_admin(true, true)) {
    $title = '';
    if (empty($sessionId)) {
        if (isset($_GET['user_id'])) {
            $user_id = intval($_GET['user_id']);
            $user_info = api_get_user_info($user_id);
            $title = get_lang('AssignedCoursesTo').' '.api_get_person_name($user_info['firstname'], $user_info['lastname']);
            $courses = CourseManager::get_course_list_of_user_as_course_admin($user_id);
        } else {
            $title = get_lang('YourCourseList');
            $courses = CourseManager::get_courses_followed_by_drh(api_get_user_id());
        }
    } else {
        $session_name = api_get_session_name($sessionId);
        $title = $session_name.' : '.get_lang('CourseListInSession');
        $courses = Tracking::get_courses_list_from_session($sessionId);
    }

    $a_courses = array_keys($courses);

    if (!api_is_session_admin()) {
        $menu_items[] = Display::url(
            Display::return_icon('statistics.png', get_lang('MyStats'), '', ICON_SIZE_MEDIUM),
            api_get_path(WEB_CODE_PATH)."auth/my_progress.php"
        );
        $menu_items[] = Display::url(
            Display::return_icon('user.png', get_lang('Students'), [], ICON_SIZE_MEDIUM),
            "index.php?view=drh_students&amp;display=yourstudents"
        );
        $menu_items[] = Display::url(
            Display::return_icon('teacher.png', get_lang('Trainers'), [], ICON_SIZE_MEDIUM),
            'teachers.php'
        );
        $menu_items[] = Display::url(
            Display::return_icon('course_na.png', get_lang('Courses'), [], ICON_SIZE_MEDIUM),
            '#'
        );
        $menu_items[] = Display::url(
            Display::return_icon('session.png', get_lang('Sessions'), [], ICON_SIZE_MEDIUM),
            api_get_path(WEB_CODE_PATH).'mySpace/session.php'
        );

        $menu_items[] = Display::url(
            get_lang('QuestionStats'),
            api_get_path(WEB_CODE_PATH).'mySpace/question_stats_global.php'
        );

        $menu_items[] = Display::url(
            get_lang('QuestionStatsDetailedReport'),
            api_get_path(WEB_CODE_PATH).'mySpace/question_stats_global_detail.php'
        );

        if (api_can_login_as($user_id)) {
            $link = '<a
                href="'.api_get_path(WEB_CODE_PATH).'admin/user_list.php?action=login_as&amp;user_id='.$user_id.'&amp;sec_token='.Security::get_existing_token().'">'.
                Display::return_icon('login_as.png', get_lang('LoginAs'), null, ICON_SIZE_MEDIUM).'</a>&nbsp;&nbsp;';
            $menu_items[] = $link;
        }
    }

    $actionsLeft = $actionsRight = '';
    $nb_menu_items = count($menu_items);
    if ($nb_menu_items > 1) {
        foreach ($menu_items as $key => $item) {
            $actionsLeft .= $item;
        }
    }

    if (count($a_courses) > 0) {
        $actionsRight .= Display::url(
            Display::return_icon('printer.png', get_lang('Print'), [], 32),
            'javascript: void(0);',
            ['onclick' => 'javascript: window.print();']
        );
    }

    $toolbar = Display::toolbarAction('toolbar-course', [$actionsLeft, $actionsRight]);
    echo $toolbar;
    echo Display::page_header($title);
}

if ($showImportIcon) {
    echo "<div align=\"right\">";
    echo '<a href="user_import.php?id_session='.$sessionId.'&action=export&amp;type=xml">'.
            Display::return_icon('excel.gif', get_lang('ImportUserListXMLCSV')).'&nbsp;'.get_lang('ImportUserListXMLCSV').'</a>';
    echo "</div><br />";
}

/**
 * @return int
 */
function get_count_courses()
{
    $userId = api_get_user_id();
    $sessionId = isset($_GET['session_id']) ? intval($_GET['session_id']) : null;
    $keyword = isset($_GET['keyword']) ? $_GET['keyword'] : null;
    $drhLoaded = false;

    if (api_is_drh()) {
        if (api_drh_can_access_all_session_content()) {
            if (empty($sessionId)) {
                $count = SessionManager::getAllCoursesFollowedByUser(
                    $userId,
                    null,
                    null,
                    null,
                    null,
                    null,
                    true,
                    $keyword
                );
            } else {
                $count = SessionManager::getCourseCountBySessionId(
                    $sessionId,
                    $keyword
                );
            }
            $drhLoaded = true;
        }
    }

    if ($drhLoaded == false) {
        $isGeneralCoach = SessionManager::user_is_general_coach(
            api_get_user_id(),
            $sessionId
        );

        if ($isGeneralCoach) {
            $courseList = SessionManager::getCoursesInSession($sessionId);
            $count = count($courseList);
        } else {
            $count = CourseManager::getCoursesFollowedByUser(
                $userId,
                COURSEMANAGER,
                null,
                null,
                null,
                null,
                true,
                $keyword,
                $sessionId
            );
        }
    }

    return $count;
}

/**
 * @param $from
 * @param $limit
 * @param $column
 * @param $direction
 *
 * @return array
 */
function get_courses($from, $limit, $column, $direction)
{
    $userId = api_get_user_id();
    $sessionId = isset($_GET['session_id']) ? intval($_GET['session_id']) : 0;
    $keyword = isset($_GET['keyword']) ? $_GET['keyword'] : null;
    $follow = isset($_GET['follow']) ? true : false;
    $drhLoaded = false;
    if (api_is_drh()) {
        if (api_drh_can_access_all_session_content()) {
            $courses = SessionManager::getAllCoursesFollowedByUser(
                $userId,
                $sessionId,
                $from,
                $limit,
                $column,
                $direction,
                false,
                $keyword
            );
            $drhLoaded = true;
        }
    }

    if ($drhLoaded == false) {
        $isGeneralCoach = SessionManager::user_is_general_coach(
            api_get_user_id(),
            $sessionId
        );

        // General coach can see all reports
        if ($isGeneralCoach) {
            $courseList = SessionManager::getCoursesInSession($sessionId);
            $courses = [];
            if (!empty($courseList)) {
                foreach ($courseList as $courseId) {
                    $courses[] = api_get_course_info_by_id($courseId);
                }
            }
        } else {
            $courses = CourseManager::getCoursesFollowedByUser(
                $userId,
                COURSEMANAGER,
                $from,
                $limit,
                $column,
                $direction,
                false,
                $keyword,
                $sessionId,
                $follow
            );
        }
    }

    $courseList = [];
    if (!empty($courses)) {
        foreach ($courses as $data) {
            $courseCode = $data['code'];
            $courseInfo = api_get_course_info($courseCode);
            if (empty($sessionId)) {
                $userList = CourseManager::get_user_list_from_course_code($data['code']);
            } else {
                $userList = CourseManager::get_user_list_from_course_code(
                    $data['code'],
                    $sessionId,
                    null,
                    null,
                    0
                );
            }

            $userIdList = [];
            if (!empty($userList)) {
                foreach ($userList as $user) {
                    $userIdList[] = $user['user_id'];
                }
            }

            $messagesInCourse = 0;
            $assignmentsInCourse = 0;
            $avgTimeSpentInCourse = 0;
            $avgProgressInCourse = 0;
            $countStudents = 0;
            $avgScoreInCourse = 0;

            if (count($userIdList) > 0) {
                $countStudents = count($userIdList);
                // tracking data
                $avgProgressInCourse = Tracking::get_avg_student_progress($userIdList, $courseCode, [], $sessionId);
                $avgScoreInCourse = Tracking::get_avg_student_score($userIdList, $courseCode, [], $sessionId);
                $avgTimeSpentInCourse = Tracking::get_time_spent_on_the_course($userIdList, $courseInfo['real_id'], $sessionId);
                $messagesInCourse = Tracking::count_student_messages($userIdList, $courseCode, $sessionId);
                $assignmentsInCourse = Tracking::count_student_assignments($userIdList, $courseCode, $sessionId);
                $avgTimeSpentInCourse = api_time_to_hms($avgTimeSpentInCourse / $countStudents);
                $avgProgressInCourse = round($avgProgressInCourse / $countStudents, 2);

                if (is_numeric($avgScoreInCourse)) {
                    $avgScoreInCourse = round($avgScoreInCourse / $countStudents, 2).'%';
                }
            }

            $thematic = new Thematic();
            $tematic_advance = $thematic->get_total_average_of_thematic_advances($courseCode, $sessionId);
            $tematicAdvanceProgress = '-';
            if (!empty($tematic_advance)) {
                $tematicAdvanceProgress = '<a title="'.get_lang('GoToThematicAdvance').'" href="'.api_get_path(WEB_CODE_PATH).'course_progress/index.php?cidReq='.$courseCode.'&id_session='.$sessionId.'">'.
                    $tematic_advance.'%</a>';
            }

            $courseIcon = '<a href="'.api_get_path(WEB_CODE_PATH).'tracking/courseLog.php?cidReq='.$courseCode.'&id_session='.$sessionId.'">
                        '.Display::return_icon('2rightarrow.png', get_lang('Details')).'
                      </a>';
            $title = Display::url(
                $data['title'],
                $courseInfo['course_public_url'].'?id_session='.$sessionId
            );

            $attendanceLink = Display::url(
                Display::return_icon('attendance_list.png', get_lang('Attendance'), [], ICON_SIZE_MEDIUM),
                api_get_path(WEB_CODE_PATH).'attendance/index.php?cidReq='.$courseCode.'&id_session='.$sessionId.'&action=calendar_logins'
            );

            $courseList[] = [
                $title,
                $countStudents,
                is_null($avgTimeSpentInCourse) ? '-' : $avgTimeSpentInCourse,
                $tematicAdvanceProgress,
                is_null($avgProgressInCourse) ? '-' : $avgProgressInCourse.'%',
                is_null($avgScoreInCourse) ? '-' : $avgScoreInCourse,
                is_null($messagesInCourse) ? '-' : $messagesInCourse,
                is_null($assignmentsInCourse) ? '-' : $assignmentsInCourse,
                $attendanceLink,
                $courseIcon,
            ];
        }
    }

    return $courseList;
}

$table = new SortableTable(
    'tracking_course',
    'get_count_courses',
    'get_courses',
    1,
    10
);

$table->set_header(0, get_lang('CourseTitle'), false);
$table->set_header(1, get_lang('NbStudents'), false);
$table->set_header(2, get_lang('TimeSpentInTheCourse').Display::return_icon('info.png', get_lang('TimeOfActiveByTraining'), ['align' => 'absmiddle', 'hspace' => '3px']), false);
$table->set_header(3, get_lang('ThematicAdvance'), false);
$table->set_header(4, get_lang('AvgStudentsProgress').Display::return_icon('info.png', get_lang('AvgAllUsersInAllCourses'), ['align' => 'absmiddle', 'hspace' => '3px']), false);
$table->set_header(5, get_lang('AvgCourseScore').Display::return_icon('info.png', get_lang('AvgAllUsersInAllCourses'), ['align' => 'absmiddle', 'hspace' => '3px']), false);
$table->set_header(6, get_lang('AvgMessages'), false);
$table->set_header(7, get_lang('AvgAssignments'), false);
$table->set_header(8, get_lang('Attendances'), false);
$table->set_header(9, get_lang('Details'), false);

$form = new FormValidator('search_course', 'get', api_get_path(WEB_CODE_PATH).'mySpace/course.php');
$form->addElement('text', 'keyword', get_lang('Keyword'));
$form->addButtonSearch(get_lang('Search'));
$form->addElement('hidden', 'session_id', $sessionId);

$keyword = isset($_GET['keyword']) ? Security::remove_XSS($_GET['keyword']) : null;

$params = [
    'session_id' => $sessionId,
    'keyword' => $keyword,
];
$table->set_additional_parameters($params);

$form->setDefaults($params);
$form->display();
$table->display();

Display::display_footer();

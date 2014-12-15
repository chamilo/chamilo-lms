<?php
/* For licensing terms, see /license.txt */

/**
 * With this tool you can easily adjust non critical configuration settings.
 * Non critical means that changing them will not result in a broken campus.
 *
 * @author Patrick Cool
 * @author Julio Montoya - Multiple URL site
 * @package chamilo.admin
 */
/* INIT SECTION */

// Language files that need to be included.
if (isset($_GET['category']) && $_GET['category'] == 'Templates') {
    $language_file = array('admin', 'document');
} else if (isset($_GET['category']) && $_GET['category'] == 'Gradebook') {
    $language_file = array('admin', 'gradebook');
} else {
    $language_file = array('admin', 'document');
}
$language_file[] = 'tracking';

// Resetting the course id.
$cidReset = true;

// Including some necessary library files.
require_once '../inc/global.inc.php';

// Setting the section (for the tabs).
$this_section = SECTION_PLATFORM_ADMIN;

$interbreadcrumb[] = array("url" => 'index.php', "name" => get_lang('PlatformAdmin'));

$toolName = get_lang('TeacherTimeReport');

// Access restrictions.
api_protect_admin_script();

$startDate = new DateTime(api_get_datetime());
$startDate->modify('first day of this month');

$limitDate = new DateTime(api_get_datetime());

$selectedCourse = isset($_POST['course']) ? $_POST['course'] : null;
$selectedSession = isset($_POST['session']) ? $_POST['session'] : 0;
$selectedTeacher = isset($_POST['teacher']) ? $_POST['teacher'] : 0;
$selectedFrom = isset($_POST['from']) && !empty($_POST['from']) ? $_POST['from'] : $startDate->format('Y-m-d');
$selectedUntil = isset($_POST['from']) && !empty($_POST['until']) ? $_POST['until'] : $limitDate->format('Y-m-d');

$courseList = CourseManager::get_courses_list(0, 0, 'title');
$sessionsList = SessionManager::get_sessions_list(array(), array('name'));

$teacherList = SessionManager::getAllCourseCoaches();

foreach ($courseCoaches as &$coach) {
    $coach['totalTime'] = SessionManager::getTotalUserTimeInPlatform($coach['id']);
}

$htmlHeadXtra[] = '
<script src="' . api_get_path(WEB_LIBRARY_PATH) . 'javascript/daterange/moment.min.js"></script>
<link rel="stylesheet" href="' . api_get_path(WEB_LIBRARY_PATH) . 'javascript/daterange/daterangepicker-bs2.css">
<script src="' . api_get_path(WEB_LIBRARY_PATH) . 'javascript/daterange/daterangepicker.js"></script>';

$withFilter = false;

$rows = array();

if (!empty($selectedCourse)) {
    $withFilter = true;

    $course = api_get_course_info($selectedCourse);

    $sessionsByCourse = SessionManager::get_session_by_course($selectedCourse);

    foreach ($sessionsByCourse as $session) {
        $coaches = CourseManager::get_coachs_from_course($session['id'], $selectedCourse);

        foreach ($coaches as $coach) {
            $totalTime = SessionManager::getUserTimeInCourse(
                $coach['user_id'],
                $selectedCourse,
                $session['id'],
                $selectedFrom,
                $selectedUntil
            );

            $rows[] = array(
                'session' => array(
                    'id' => $session['id'],
                    'name' => $session['name']
                ),
                'course' => array(
                    'id' => $course['real_id'],
                    'name' => $course['title']
                ),
                'coach' => array(
                    'userId' => $coach['user_id'],
                    'lastname' => $coach['lastname'],
                    'firstname' => $coach['firstname'],
                    'username' => $coach['username'],
                    'completeName' => api_get_person_name($coach['firstname'], $coach['lastname'])
                ),
                'totalTime' => $totalTime
            );
        }
    }
}

if (!empty($selectedSession)) {
    $withFilter = true;

    $session = api_get_session_info($selectedSession);

    $courses = SessionManager::get_course_list_by_session_id($selectedSession);

    foreach ($courses as $course) {
        $coaches = CourseManager::get_coachs_from_course($selectedSession, $course['code']);

        foreach ($coaches as $coach) {
            $totalTime = SessionManager::getUserTimeInCourse(
                $coach['user_id'],
                $course['code'],
                $selectedSession,
                $selectedFrom,
                $selectedUntil
            );

            $rows[] = array(
                'session' => array(
                    'id' => $session['id'],
                    'name' => $session['name']
                ),
                'course' => array(
                    'id' => $course['real_id'],
                    'name' => $course['title']
                ),
                'coach' => array(
                    'userId' => $coach['user_id'],
                    'lastname' => $coach['lastname'],
                    'firstname' => $coach['firstname'],
                    'username' => $coach['username'],
                    'completeName' => api_get_person_name($coach['firstname'], $coach['lastname'])
                ),
                'totalTime' => $totalTime
            );
        }
    }
}

if (!empty($selectedTeacher)) {
    $withFilter = true;

    $coach = api_get_user_info($selectedTeacher);

    $courses = SessionManager::getCoursesListByCourseCoach($selectedTeacher);

    foreach ($courses as $course) {
        $session = api_get_session_info($course['id_session']);

        $courseInfo = api_get_course_info($course['course_code']);

        $totalTime = SessionManager::getUserTimeInCourse(
            $selectedTeacher,
            $course['course_code'],
            $session['id'],
            $selectedFrom,
            $selectedUntil
        );

        $rows[] = array(
            'session' => array(
                'id' => $session['id'],
                'name' => $session['name']
            ),
            'course' => array(
                'id' => $courseInfo['real_id'],
                'name' => $courseInfo['title']
            ),
            'coach' => array(
                'userId' => $coach['user_id'],
                'lastname' => $coach['lastname'],
                'firstname' => $coach['firstname'],
                'username' => $coach['username'],
                'completeName' => $coach['complete_name']
            ),
            'totalTime' => $totalTime
        );
    }
}

// view
//hack for daterangepicker
$startDate->modify('+1 day');
$limitDate->modify('+1 day');

$tpl = new Template($toolName);
$tpl->assign('filterStartDate', $startDate->format('Y-m-d'));
$tpl->assign('filterEndDate', $limitDate->format('Y-m-d'));
$tpl->assign('filterMaxDate', $limitDate->format('Y-m-d'));

$tpl->assign('selectedCourse', $selectedCourse);
$tpl->assign('selectedSession', $selectedSession);
$tpl->assign('selectedTeacher', $selectedTeacher);
$tpl->assign('selectedFrom', $selectedFrom);
$tpl->assign('selectedUntil', $selectedUntil);

$tpl->assign('withFilter', $withFilter);

$tpl->assign('courses', $courseList);
$tpl->assign('sessions', $sessionsList);
$tpl->assign('courseCoaches', $teacherList);

$tpl->assign('rows', $rows);

$contentTemplate = $tpl->get_template('admin/teacher_time_report.tpl');

$tpl->display($contentTemplate);

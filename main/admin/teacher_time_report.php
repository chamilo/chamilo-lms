<?php
/* For licensing terms, see /license.txt */

/**
 * Generate a teacher time report in platform or sessions/courses
 *
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
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

$startDate = new DateTime(api_get_local_time());
$startDate->modify('first day of this month');

$limitDate = new DateTime(api_get_local_time());

$selectedCourse = isset($_REQUEST['course']) ? $_REQUEST['course'] : null;
$selectedSession = isset($_REQUEST['session']) ? $_REQUEST['session'] : 0;
$selectedTeacher = isset($_REQUEST['teacher']) ? $_REQUEST['teacher'] : 0;
$selectedFrom = isset($_REQUEST['from']) && !empty($_REQUEST['from']) ? $_REQUEST['from'] : $startDate->format('Y-m-d');
$selectedUntil = isset($_REQUEST['from']) && !empty($_REQUEST['until']) ? $_REQUEST['until'] : $limitDate->format('Y-m-d');

$courseList = CourseManager::get_courses_list(0, 0, 'title');
$sessionsList = SessionManager::get_sessions_list(array(), array('name'));

$teacherList = UserManager::getTeachersList();

$htmlHeadXtra[] = '
<script src="' . api_get_path(WEB_LIBRARY_PATH) . 'javascript/daterange/moment.min.js"></script>
<link rel="stylesheet" href="' . api_get_path(WEB_LIBRARY_PATH) . 'javascript/daterange/daterangepicker-bs2.css">
<script src="' . api_get_path(WEB_LIBRARY_PATH) . 'javascript/daterange/daterangepicker.js"></script>';

$withFilter = false;

$reportTitle = get_lang('TimeReportIncludingAllCoursesAndSessionsByTeacher');
$reportSubTitle = sprintf(get_lang('TimeSpentBetweenXAndY'), $selectedFrom, $selectedUntil);

$timeReport = new TeacherTimeReport();

if (!empty($selectedCourse)) {
    $withFilter = true;

    $course = api_get_course_info($selectedCourse);

    $reportTitle = sprintf(get_lang('TimeReportForCourseX'), $course['title']);

    $teachers = CourseManager::get_teacher_list_from_course_code($selectedCourse);

    foreach ($teachers as $teacher) {
        $totalTime = UserManager::getExpendedTimeInCourses(
            $teacher['user_id'],
            $selectedCourse,
            0,
            $selectedFrom,
            $selectedUntil
        );
        $formatedTime = api_format_time($totalTime);

        $timeReport->data[] = array(
            'session' => null,
            'course' => array(
                'id' => $course['real_id'],
                'name' => $course['title']
            ),
            'coach' => array(
                'userId' => $teacher['user_id'],
                'lastname' => $teacher['lastname'],
                'firstname' => $teacher['firstname'],
                'username' => $teacher['username'],
                'completeName' => api_get_person_name($teacher['firstname'], $teacher['lastname'])
            ),
            'totalTime' => $formatedTime
        );
    }

    $sessionsByCourse = SessionManager::get_session_by_course($selectedCourse);

    foreach ($sessionsByCourse as $session) {
        $coaches = CourseManager::get_coachs_from_course($session['id'], $selectedCourse);

        if ($coaches) {
            foreach ($coaches as $coach) {
                $totalTime = UserManager::getExpendedTimeInCourses(
                    $coach['user_id'],
                    $selectedCourse,
                    $session['id'],
                    $selectedFrom,
                    $selectedUntil
                );
                $formatedTime = api_format_time($totalTime);

                $timeReport->data[] = array(
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
                    'totalTime' => $formatedTime
                );
            }
        }
    }
}

if (!empty($selectedSession)) {
    $withFilter = true;

    $session = api_get_session_info($selectedSession);
    $sessionData = array(
        'id' => $session['id'],
        'name' => $session['name']
    );

    $reportTitle = sprintf(get_lang('TimeReportForSessionX'), $session['name']);

    $courses = SessionManager::get_course_list_by_session_id($selectedSession);

    foreach ($courses as $course) {
        $courseData = array(
            'id' => $course['id'],
            'name' => $course['title']
        );

        $coaches = CourseManager::get_coachs_from_course($selectedSession, $course['code']);

        if ($coaches) {
            foreach ($coaches as $coach) {
                $totalTime = UserManager::getExpendedTimeInCourses(
                    $coach['user_id'],
                    $course['code'],
                    $selectedSession,
                    $selectedFrom,
                    $selectedUntil
                );
                $formatedTime = api_format_time($totalTime);

                $timeReport->data[] = array(
                    'session' => $sessionData,
                    'course' => $courseData,
                    'coach' => array(
                        'userId' => $coach['user_id'],
                        'lastname' => $coach['lastname'],
                        'firstname' => $coach['firstname'],
                        'username' => $coach['username'],
                        'completeName' => api_get_person_name($coach['firstname'], $coach['lastname'])
                    ),
                    'totalTime' => $formatedTime
                );
            }
        }
    }
}

if (!empty($selectedTeacher)) {
    $withFilter = true;

    $teacher = api_get_user_info();

    $teacherData = array(
        'userId' => $teacher['user_id'],
        'lastname' => $teacher['lastname'],
        'firstname' => $teacher['firstname'],
        'username' => $teacher['username'],
        'completeName' => $teacher['complete_name']
    );

    $reportTitle = sprintf(get_lang('TimeReportForTeacherX'), $teacher['complete_name']);

    $courses = CourseManager::get_courses_list_by_user_id($selectedTeacher, false);

    if (!empty($courses)) {
        foreach ($courses as $course) {
            $courseInfo = api_get_course_info($course['code']);

            $totalTime = UserManager::getExpendedTimeInCourses(
                $selectedTeacher,
                $course['code'],
                0,
                $selectedFrom,
                $selectedUntil
            );
            $formatedTime = api_format_time($totalTime);

            $timeReport->data[] = array(
                'session' => null,
                'course' => array(
                    'id' => $courseInfo['real_id'],
                    'name' => $courseInfo['title']
                ),
                'coach' => $teacherData,
                'totalTime' => $formatedTime
            );
        }
    }

    $coursesInSession = SessionManager::getCoursesListByCourseCoach($selectedTeacher);

    foreach ($coursesInSession as $course) {
        $session = api_get_session_info($course['id_session']);
        $sessionData = array(
            'id' => $session['id'],
            'name' => $session['name']
        );

        $courseInfo = api_get_course_info($course['course_code']);

        $totalTime = UserManager::getExpendedTimeInCourses(
            $selectedTeacher,
            $course['course_code'],
            $session['id'],
            $selectedFrom,
            $selectedUntil
        );
        $formatedTime = api_format_time($totalTime);

        $timeReport->data[] = array(
            'session' => $sessionData,
            'course' => array(
                'id' => $courseInfo['real_id'],
                'name' => $courseInfo['title']
            ),
            'coach' => $teacherData,
            'totalTime' => $formatedTime
        );
    }
}

if (empty($selectedCourse) && empty($selectedSession) && empty($selectedTeacher)) {
    foreach ($teacherList as &$teacher) {
        $timeReport->data[] = array(
            'coach' => array(
                'username' => $teacher['username'],
                'completeName' => $teacher['completeName'],
            ),
            'totalTime' => SessionManager::getTotalUserTimeInPlatform($teacher['user_id'], $selectedFrom, $selectedUntil)
        );
    }
}

$timeReport->sortData($withFilter);

if (isset($_GET['export'])) {
    $dataToExport = $timeReport->prepareDataToExport($withFilter);

    $fileName = get_lang('TeacherTimeReport') . ' ' . api_get_local_time();

    switch ($_GET['export']) {
        case 'pdf':
            $params = array(
                'add_signatures' => false,
                'filename' => $fileName,
                'pdf_title' => "$reportTitle - $reportSubTitle",
                'pdf_description' => get_lang('TeacherTimeReport'),
                'format' => 'A4-L',
                'orientation' => 'L'
            );

            $pdfContent = Export::convert_array_to_html($dataToExport);

            Export::export_html_to_pdf($pdfContent, $params);
            break;
        case 'xls':
            array_unshift($dataToExport, array(
                $reportTitle
            ), array(
                $reportSubTitle
            ), array());

            Export::export_table_xls_html($dataToExport, $fileName);
            break;
    }

    die;
}

// view
//hack for daterangepicker
$startDate->modify('+1 day');
$limitDate->modify('+1 day');

$tpl = new Template($toolName);
$tpl->assign('reportTitle', $reportTitle);
$tpl->assign('reportSubTitle', $reportSubTitle);

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

$tpl->assign('rows', $timeReport->data);

$contentTemplate = $tpl->get_template('admin/teacher_time_report.tpl');

$tpl->display($contentTemplate);

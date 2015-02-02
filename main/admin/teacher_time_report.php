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
require_once api_get_path(LIBRARY_PATH) . 'TeacherTimeReport.php';

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

$reportTitle = 'TimeReportIncludingAllCoursesAndSessionsByTeacher';
$reportSubTitle = sprintf(get_lang('TimeSpentBetweenXAndY'), $selectedFrom, $selectedUntil);

$timeReport = new TeacherTimeReport();

if (!empty($selectedCourse)) {
    $withFilter = true;

    $course = api_get_course_info($selectedCourse);

    $reportTitle = sprintf(get_lang('TimeReportByCourseX'), $course['title']);

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
                'totalTime' => $totalTime
            );
        }
    }
}

if (!empty($selectedSession)) {
    $withFilter = true;

    $session = api_get_session_info($selectedSession);

    $reportTitle = sprintf(get_lang('TimeReportBySessionX'), $session['name']);

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

            $timeReport->data[] = array(
                'session' => array(
                    'id' => $session['id'],
                    'name' => $session['name']
                ),
                'course' => array(
                    'id' => $course['id'],
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

    $reportTitle = sprintf(get_lang('TimeReportByTeacherX'), $coach['complete_name']);

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

        $timeReport->data[] = array(
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
    require_once api_get_path(LIBRARY_PATH) . 'export.lib.inc.php';

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

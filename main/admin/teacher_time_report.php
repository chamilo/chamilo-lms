<?php
/* For licensing terms, see /license.txt */

/**
 * Generate a teacher time report in platform or sessions/courses
 *
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 * @package chamilo.admin
 */

// Resetting the course id.
$cidReset = true;

// Including some necessary library files.
require_once __DIR__.'/../inc/global.inc.php';

// Setting the section (for the tabs).
$this_section = SECTION_PLATFORM_ADMIN;

$interbreadcrumb[] = array("url" => 'index.php', "name" => get_lang('PlatformAdmin'));

$toolName = get_lang('TeacherTimeReport');

// Access restrictions.
api_protect_admin_script();

$form = new FormValidator('teacher_time_report');

$startDate = new DateTime(api_get_local_time());
$startDate->modify('first day of this month');

$limitDate = new DateTime(api_get_local_time());

$selectedCourse = null;
$selectedSession = 0;
$selectedTeacher = 0;
$selectedFrom = $startDate->format('Y-m-d');
$selectedUntil = $limitDate->format('Y-m-d');

if ($form->validate()) {
    $formValues = $form->getSubmitValues();

    $selectedCourse = $formValues['course'];
    $selectedSession = $formValues['session'];
    $selectedTeacher = $formValues['teacher'];

    if (!empty($formValues['from'])) {
        $selectedFrom = $formValues['from'];
    }

    if (!empty($formValues['until'])) {
        $selectedUntil = $formValues['until'];
    }
}

$optionsCourses = [0 => get_lang('None')];
$optionsSessions = [0 => get_lang('None')];
$optionsTeachers = [0 => get_lang('None')];

$courseList = CourseManager::get_courses_list(0, 0, 'title');
$sessionsList = SessionManager::get_sessions_list(array(), array('name'));

$teacherList = UserManager::getTeachersList();

foreach ($courseList as $courseItem) {
    $optionsCourses[$courseItem['code']] = $courseItem['title'];
}

foreach ($sessionsList as $sessionItem) {
    $optionsSessions[$sessionItem['id']] = $sessionItem['name'];
}

foreach ($teacherList as $teacherItem) {
    $optionsTeachers[$teacherItem['user_id']] = $teacherItem['completeName'];
}

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
        $totalTime = UserManager::getTimeSpentInCourses(
            $teacher['user_id'],
            $course['real_id'],
            0,
            $selectedFrom,
            $selectedUntil
        );
        $formattedTime = api_format_time($totalTime);

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
            'totalTime' => $formattedTime
        );
    }

    $sessionsByCourse = SessionManager::get_session_by_course($course['real_id']);

    foreach ($sessionsByCourse as $session) {
        $coaches = CourseManager::get_coachs_from_course($session['id'], $course['real_id']);

        if (!empty($coaches)) {
            foreach ($coaches as $coach) {
                $totalTime = UserManager::getTimeSpentInCourses(
                    $coach['user_id'],
                    $course['real_id'],
                    $session['id'],
                    $selectedFrom,
                    $selectedUntil
                );
                $formattedTime = api_format_time($totalTime);

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
                    'totalTime' => $formattedTime
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

        $coaches = CourseManager::get_coachs_from_course($selectedSession, $course['id']);

        if (!empty($coaches)) {
            foreach ($coaches as $coach) {
                $totalTime = UserManager::getTimeSpentInCourses(
                    $coach['user_id'],
                    $course['id'],
                    $selectedSession,
                    $selectedFrom,
                    $selectedUntil
                );
                $formattedTime = api_format_time($totalTime);

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
                    'totalTime' => $formattedTime
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
            $courseInfo = api_get_course_info_by_id($course['real_id']);

            $totalTime = UserManager::getTimeSpentInCourses(
                $selectedTeacher,
                $course['real_id'],
                0,
                $selectedFrom,
                $selectedUntil
            );
            $formattedTime = api_format_time($totalTime);

            $timeReport->data[] = array(
                'session' => null,
                'course' => array(
                    'id' => $courseInfo['real_id'],
                    'name' => $courseInfo['title']
                ),
                'coach' => $teacherData,
                'totalTime' => $formattedTime
            );
        }
    }

    $coursesInSession = SessionManager::getCoursesListByCourseCoach($selectedTeacher);

    foreach ($coursesInSession as $userCourseSubscription) {
        $course = $userCourseSubscription->getCourse();
        $session = $userCourseSubscription->getSession();

        $totalTime = UserManager::getTimeSpentInCourses(
            $selectedTeacher,
            $course->getId(),
            $session->getId(),
            $selectedFrom,
            $selectedUntil
        );
        $formattedTime = api_format_time($totalTime);

        $timeReport->data[] = array(
            'session' => [
                'id' => $session->getId(),
                'name' => $session->getName()
            ],
            'course' => array(
                'id' => $course->getId(),
                'name' => $course->getTitle()
            ),
            'coach' => $teacherData,
            'totalTime' => $formattedTime
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
            'totalTime' => SessionManager::getTotalUserTimeInPlatform(
                $teacher['user_id'],
                $selectedFrom,
                $selectedUntil
            )
        );
    }
}

$timeReport->sortData($withFilter);

if (isset($_GET['export'])) {
    $dataToExport = $timeReport->prepareDataToExport($withFilter);

    $fileName = get_lang('TeacherTimeReport').' '.api_get_local_time();

    switch ($_GET['export']) {
        case 'pdf':
            $params = array(
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

$form->addSelect(
    'course',
    get_lang('Course'),
    $optionsCourses,
    ['id' => 'courses']
);
$form->addSelect(
    'session',
    get_lang('Session'),
    $optionsSessions,
    ['id' => 'session']
);
$form->addSelect(
    'teacher',
    get_lang('Teacher'),
    $optionsTeachers,
    ['id' => 'teacher']
);
$form->addDateRangePicker(
    'daterange',
    get_lang('Date'),
    false,
    [
        'id' => 'daterange',
        'maxDate' => $limitDate->format('Y-m-d'),
        'format' => 'YYYY-MM-DD',
        'timePicker' => 'false',
        'value' => "$selectedFrom / $selectedUntil"
    ]
);
$form->addButtonFilter(get_lang('Filter'));
$form->addHidden('from', '');
$form->addHidden('until', '');
$form->setDefaults([
    'course' => $selectedCourse,
    'session' => $selectedSession,
    'teacher' => $selectedTeacher,
    'date_range' => "$selectedFrom / $selectedUntil",
    'from' => $selectedFrom,
    'until' => $selectedUntil
]);

$tpl = new Template($toolName);
$tpl->assign('report_title', $reportTitle);
$tpl->assign('report_sub_title', $reportSubTitle);
$tpl->assign('selected_course', $selectedCourse);
$tpl->assign('selected_session', $selectedSession);
$tpl->assign('selected_teacher', $selectedTeacher);
$tpl->assign('selected_from', $selectedFrom);
$tpl->assign('selected_until', $selectedUntil);
$tpl->assign('with_filter', $withFilter);
$tpl->assign('courses', $courseList);
$tpl->assign('sessions', $sessionsList);
//$tpl->assign('courseCoaches', $teacherList);
$tpl->assign('form', $form->returnForm());
$tpl->assign('rows', $timeReport->data);

$templateName = $tpl->get_template('admin/teacher_time_report.tpl');

$contentTemplate = $tpl->fetch($templateName);
$tpl->assign('content', $contentTemplate);
$tpl->display_one_col_template();

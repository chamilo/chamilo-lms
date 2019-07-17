<?php
/* For licensing terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';

api_block_anonymous_users();

// Access restrictions.
$is_allowedToTrack = api_is_platform_admin(true, true) ||
    api_is_teacher() || api_is_course_tutor();

if (!$is_allowedToTrack) {
    api_not_allowed(true);
    exit;
}

// the section (for the tabs)
$this_section = SECTION_TRACKING;
$quote_simple = "'";

$userId = isset($_REQUEST['user_id']) ? (int) $_REQUEST['user_id'] : 0;
$userInfo = api_get_user_info($userId);
if (empty($userInfo)) {
    api_not_allowed(true);
}

$sessions = SessionManager::getSessionsFollowedByUser($userId,
    null,
        null,
        null,
        false,
        false,
        false,
        'ORDER BY s.access_end_date'
);

$startDate = '';
$endDate = '';
if (!empty($sessions)) {
    foreach ($sessions as $session) {
        $startDate = api_get_local_time(
            $session['access_start_date'],
            null,
            null,
            true,
            false
        );
        $endDate = api_get_local_time(
            $session['access_end_date'],
            null,
            null,
            true,
            false
        );
    }
}

$form = new FormValidator(
    'myform',
    'get',
    api_get_self().'?user_id='.$userId,
    null,
    ['id' => 'myform']
);
$form->addElement('text', 'from', get_lang('From'), ['id' => 'date_from']);
$form->addElement('text', 'to', get_lang('Until'), ['id' => 'date_to']);
/*$form->addElement(
    'select',
    'type',
    get_lang('Type'),
    ['day' => get_lang('Day'), 'month' => get_lang('Month')],
    ['id' => 'type']
);*/
$form->addElement('hidden', 'user_id', $userId);
$form->addRule('from', get_lang('ThisFieldIsRequired'), 'required');
$form->addRule('to', get_lang('ThisFieldIsRequired'), 'required');
$form->addButtonSearch(get_lang('Search'));

if ($form->validate()) {
    $values = $form->getSubmitValues();
    $from = $values['from'];
    $to = $values['to'];
    $sessionCategories = UserManager::get_sessions_by_category($userId, false);

    $sessionCourseList = [];
    $report = [];
    foreach ($sessionCategories as $category) {
        foreach ($category['sessions'] as $session) {
            $sessionId = $session['session_id'];
            $courseList = $session['courses'];
            foreach ($courseList as $course) {
                $courseInfo = api_get_course_info_by_id($course['real_id']);
                $result = MySpace::get_connections_to_course_by_date(
                    $userId,
                    $course,
                    $sessionId,
                    $from,
                    $to
                );

                foreach ($result as $item) {
                    $record = [
                        $courseInfo['name'],
                        $session['session_name'],
                        api_get_local_time($item['login']),
                        api_get_local_time($item['logout']),
                        api_format_time($item['duration'], 'js'),
                    ];
                    $report[] = $record;
                }
            }
        }
    }

    $courses = CourseManager::returnCourses($userId);
    $courses = array_merge($courses['in_category'], $courses['not_category']);

    foreach ($courses as $course) {
        $result = MySpace::get_connections_to_course_by_date(
            $userId,
            $course,
            0,
            $from,
            $to
        );

        foreach ($result as $item) {
            $record = [
                $courseInfo['name'],
                '',
                api_get_local_time($item['login']),
                api_get_local_time($item['logout']),
                api_format_time($item['duration'], 'js'),
            ];
            $report[] = $record;
        }
    }

    $table = new HTML_Table(['class' => 'data_table']);
    $headers = [
        get_lang('Course'),
        get_lang('Session'),
        get_lang('StartDate'),
        get_lang('EndDate'),
        get_lang('Duration'),
    ];
    $row = 0;
    $column = 0;
    foreach ($headers as $header) {
        $table->setHeaderContents($row, $column, $header);
        $column++;
    }
    $row++;
    foreach ($report as $record) {
        $column = 0;
        foreach ($record as $item) {
            $table->setCellContents($row, $column++, $item);
        }
        $row++;
    }

    $tpl = new Template('', false, false, false, true, false, false);
    $tpl->assign('title', get_lang('AttestationOfAttendance'));
    $tpl->assign('student', $userInfo['complete_name']);
    $tpl->assign('table_progress', $table->toHtml());
    $content = $tpl->fetch($tpl->get_template('my_space/pdf_export_student.tpl'));
    $params = [
        'pdf_title' => get_lang('Resume'),
        //'session_info' => $sessionInfo,
        'course_info' => '',
        'pdf_date' => '',
        'student_info' => $userInfo,
        'show_grade_generated_date' => true,
        'show_real_course_teachers' => false,
        'show_teacher_as_myself' => false,
        'orientation' => 'P',
    ];

    $pdf = new PDF('A4', $params['orientation'], $params);

    $pdf->setBackground($tpl->theme);
    @$pdf->content_to_pdf(
        $content,
        '',
        '',
        null,
        'D',
        false,
        null,
        false,
        true,
        false
    );
    exit;
}


$interbreadcrumb[] = ['url' => '#', 'name' => get_lang('AccessDetails')];

Display::display_header('');
$userInfo = api_get_user_info($userId);
$result_to_print = '';
echo Display::page_header(get_lang('DetailsStudentInCourse'));
echo Display::page_subheader(
    get_lang('User').': '.$userInfo['complete_name']
);

$form->setDefaults(['from' => $startDate, 'to' => $endDate]);
$form->display();
Display::display_footer();

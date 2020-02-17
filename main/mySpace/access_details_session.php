<?php
/* For licensing terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';

api_block_anonymous_users();

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

/**
 * @param string $dateTime
 * @param bool   $showTime
 *
 * @return string
 */
function customDate($dateTime, $showTime = false)
{
    $format = 'd/m/Y';
    if ($showTime) {
        $format = 'd/m/Y H:i:s';
    }
    $dateTime = api_get_local_time(
        $dateTime,
        null,
        null,
        true,
        false,
        true,
        $format
    );

    return $dateTime;
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
        $startDate = customDate($session['access_start_date']);
        $endDate = customDate($session['access_end_date']);
    }
}

$form = new FormValidator(
    'myform',
    'get',
    api_get_self().'?user_id='.$userId,
    null,
    ['id' => 'myform']
);
$form->addElement('text', 'from', get_lang('From'));
$form->addElement('text', 'to', get_lang('Until'));
$form->addElement('hidden', 'user_id', $userId);
$form->addRule('from', get_lang('ThisFieldIsRequired'), 'required');
$form->addRule('from', get_lang('ThisFieldIsRequired').' dd/mm/yyyy', 'callback', 'validateDate');
$form->addRule('to', get_lang('ThisFieldIsRequired'), 'required');
$form->addRule('to', get_lang('ThisFieldIsRequired').' dd/mm/yyyy', 'callback', 'validateDate');
$form->addButtonSearch(get_lang('GenerateReport'));

/**
 * @param string $value
 *
 * @return bool
 */
function validateDate($value)
{
    $value = DateTime::createFromFormat('d/m/Y', $value);

    if ($value === false) {
        return false;
    }

    return true;
}

if ($form->validate()) {
    $values = $form->getSubmitValues();
    $from = $values['from'];
    $to = $values['to'];

    $from = DateTime::createFromFormat('d/m/Y', $from);
    $to = DateTime::createFromFormat('d/m/Y', $to);

    $from = api_get_utc_datetime($from->format('Y-m-d'));
    $to = api_get_utc_datetime($to->format('Y-m-d'));

    $sessionCategories = UserManager::get_sessions_by_category($userId, false);
    $report = [];
    $minLogin = 0;
    $maxLogin = 0;
    $totalDuration = 0;

    foreach ($sessionCategories as $category) {
        foreach ($category['sessions'] as $session) {
            $sessionId = $session['session_id'];
            $courseList = $session['courses'];
            foreach ($courseList as $course) {
                $courseInfo = api_get_course_info_by_id($course['real_id']);
                $result = MySpace::get_connections_to_course_by_date(
                    $userId,
                    $courseInfo,
                    $sessionId,
                    $from,
                    $to
                );

                $partialMinLogin = 0;
                $partialMaxLogin = 0;
                $partialDuration = 0;

                foreach ($result as $item) {
                    $record = [
                        customDate($item['login'], true),
                        customDate($item['logout'], true),
                        api_format_time($item['duration'], 'js'),
                    ];

                    $totalDuration += $item['duration'];

                    if (empty($minLogin)) {
                        $minLogin = api_strtotime($item['login'], 'UTC');
                    }
                    if ($minLogin > api_strtotime($item['login'], 'UTC')) {
                        $minLogin = api_strtotime($item['login'], 'UTC');
                    }
                    if (api_strtotime($item['logout']) > $maxLogin) {
                        $maxLogin = api_strtotime($item['logout'], 'UTC');
                    }

                    // Partials
                    $partialDuration += $item['duration'];
                    if (empty($partialMinLogin)) {
                        $partialMinLogin = api_strtotime($item['login'], 'UTC');
                    }
                    if ($partialMinLogin > api_strtotime($item['login'], 'UTC')) {
                        $partialMinLogin = api_strtotime($item['login'], 'UTC');
                    }
                    if (api_strtotime($item['logout'], 'UTC') > $partialMaxLogin) {
                        $partialMaxLogin = api_strtotime($item['logout'], 'UTC');
                    }

                    $report[$sessionId]['courses'][$course['real_id']][] = $record;
                    $report[$sessionId]['name'][$course['real_id']] = $courseInfo['title'].'&nbsp; ('.$session['session_name'].')';
                }

                if (!empty($result)) {
                    $record = [
                        customDate($partialMinLogin, true),
                        customDate($partialMaxLogin, true),
                        api_format_time($partialDuration, 'js'),
                    ];
                    $report[$sessionId]['courses'][$course['real_id']][] = $record;
                    $report[$sessionId]['name'][$course['real_id']] = $courseInfo['title'].'&nbsp; ('.$session['session_name'].')';
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

        $partialMinLogin = 0;
        $partialMaxLogin = 0;
        $partialDuration = 0;

        foreach ($result as $item) {
            $record = [
                customDate($item['login'], true),
                customDate($item['logout'], true),
                api_format_time($item['duration'], 'js'),
            ];
            $report[0]['courses'][$course['course_id']][] = $record;
            $report[0]['name'][$course['course_id']] = $course['title'];

            $totalDuration += $item['duration'];

            if (empty($minLogin)) {
                $minLogin = api_strtotime($item['login'], 'UTC');
            }
            if ($minLogin > api_strtotime($item['login'], 'UTC')) {
                $minLogin = api_strtotime($item['login'], 'UTC');
            }
            if (api_strtotime($item['logout'], 'UTC') > $maxLogin) {
                $maxLogin = api_strtotime($item['logout'], 'UTC');
            }

            // Partials
            $partialDuration += $item['duration'];
            if (empty($partialMinLogin)) {
                $partialMinLogin = api_strtotime($item['login'], 'UTC');
            }
            if ($partialMinLogin > api_strtotime($item['login'], 'UTC')) {
                $partialMinLogin = api_strtotime($item['login'], 'UTC');
            }
            if (api_strtotime($item['logout'], 'UTC') > $partialMaxLogin) {
                $partialMaxLogin = api_strtotime($item['logout'], 'UTC');
            }
        }

        if (!empty($result)) {
            $record = [
                customDate($partialMinLogin, true),
                customDate($partialMaxLogin, true),
                api_format_time($partialDuration, 'js'),
            ];

            $report[0]['courses'][$course['course_id']][] = $record;
            $report[0]['name'][$course['course_id']] = $course['title'];
        }
    }

    $table = new HTML_Table(['class' => 'data_table_pdf']);
    $headers = [
        get_lang('MinStartDate'),
        get_lang('MaxEndDate'),
        get_lang('TotalDuration'),
    ];
    $row = 0;
    $column = 0;
    foreach ($headers as $header) {
        $table->setHeaderContents($row, $column, $header);
        $column++;
    }
    $row++;
    $column = 0;
    $table->setCellContents($row, $column++, customDate($minLogin));

    $table->setCellContents($row, $column++, customDate($maxLogin));
    $table->setRowAttributes($row, ['style' => 'font-weight:bold']);

    $table->setCellContents($row, $column++, api_format_time($totalDuration, 'js'));
    $totalTable = Display::page_subheader3(sprintf(get_lang('ExtractionFromX'), api_get_local_time()));
    $totalTable .= $table->toHtml();

    $courseSessionTable = '';
    $courseSessionTableData = [];
    foreach ($report as $sessionId => $data) {
        foreach ($data['courses'] as $courseId => $courseData) {
            $courseSessionTable .= Display::page_subheader3($data['name'][$courseId]);
            $table = new HTML_Table(['class' => 'data_table']);
            $headers = [
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
            $countData = count($courseData);
            foreach ($courseData as $record) {
                $column = 0;
                foreach ($record as $item) {
                    $table->setCellContents($row, $column++, $item);
                    if ($row == $countData) {
                        $courseSessionTableData[$data['name'][$courseId]] = $item;
                        $table->setRowAttributes($row, ['style' => 'font-weight:bold']);
                    }
                }
                $row++;
            }
            $courseSessionTable .= $table->toHtml();
        }
    }

    $table = new HTML_Table(['class' => 'data_table']);
    $headers = [
        get_lang('Course'),
        get_lang('TotalDuration'),
    ];
    $row = 0;
    $column = 0;
    foreach ($headers as $header) {
        $table->setHeaderContents($row, $column, $header);
        $column++;
    }
    $row++;
    foreach ($courseSessionTableData as $name => $duration) {
        $column = 0;
        $table->setCellContents($row, $column++, $name);
        $table->setCellContents($row, $column++, $duration);
        $row++;
    }
    $totalCourseSessionTable = $table->toHtml();

    $tpl = new Template('', false, false, false, true, false, false);
    $tpl->assign('title', get_lang('RealisationCertificate'));
    $tpl->assign('student', $userInfo['complete_name']);
    $tpl->assign('table_progress', $totalTable.$totalCourseSessionTable.'<pagebreak>'.$courseSessionTable);

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

    @$pdf = new PDF('A4', $params['orientation'], $params);

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
echo Display::page_header(get_lang('DetailsStudentInCourse'));
echo Display::page_subheader(
    get_lang('User').': '.$userInfo['complete_name']
);

$form->setDefaults(['from' => $startDate, 'to' => $endDate]);
$form->display();
Display::display_footer();

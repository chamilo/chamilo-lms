<?php

/* For licensing terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';

api_block_anonymous_users();

$is_allowedToTrack = api_is_platform_admin(true, true) ||
    api_is_teacher() || api_is_course_tutor() || api_is_student_boss();

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

$sessions = SessionManager::getSessionsFollowedByUser(
    $userId,
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
$form->addElement('text', 'from', get_lang('From'), ['placeholder' => get_lang('DateFormatddmmyyyy')]);
$form->addElement('text', 'to', get_lang('Until'), ['placeholder' => get_lang('DateFormatddmmyyyy')]);
$form->addHidden('user_id', $userId);
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

function getReport($userId, $from, $to, $addTime = false)
{
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

    if ($addTime) {
        $fromFirst = api_get_local_time($from.' 00:00:00');
        $toEnd = api_get_local_time($from.' 23:59:59');

        $from = api_get_utc_datetime($fromFirst);
        $to = api_get_utc_datetime($toEnd);
    }

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

    $table = new HTML_Table(['class' => 'table table-hover table-striped data_table']);
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

    $first = $table->toHtml();

    $courseSessionTable = '';
    $courseSessionTableData = [];
    $iconCourse = Display::return_icon('course.png', null, [], ICON_SIZE_SMALL);
    foreach ($report as $sessionId => $data) {
        foreach ($data['courses'] as $courseId => $courseData) {
            if (empty($courseData)) {
                continue;
            }
            $courseSessionTable .= '<div class="data-title">'.Display::page_subheader3(
                    $iconCourse.$data['name'][$courseId]
                ).'</div>';
            $table = new HTML_Table(['class' => 'table table-hover table-striped data_table']);
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
    $totalCourseSessionTable = '';
    if ($courseSessionTableData) {
        $table = new HTML_Table(['class' => 'table table-hover table-striped data_table']);
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
    }

    $result = [];
    $result['first'] = $first;
    $result['second'] = $courseSessionTable;
    $result['third'] = $totalCourseSessionTable;
    $result['total'] = $totalDuration;

    return $result;
}

if ($form->validate()) {
    $values = $form->getSubmitValues();
    $from = $values['from'];
    $to = $values['to'];

    $from = DateTime::createFromFormat('d/m/Y', $from);
    $to = DateTime::createFromFormat('d/m/Y', $to);

    $from = api_get_utc_datetime($from->format('Y-m-d'));
    $to = api_get_utc_datetime($to->format('Y-m-d'));
    $title = Display::page_subheader3(sprintf(get_lang('ExtractionFromX'), api_get_local_time()));
    $result = getReport($userId, $from, $to);

    $first = $result['first'];
    $courseSessionTable = $result['second'];
    $totalCourseSessionTable = $result['third'];

    $tpl = new Template('', false, false, false, true, false, false);
    $tpl->assign('title', get_lang('RealisationCertificate'));
    $tpl->assign('student', $userInfo['complete_name']);
    $tpl->assign('table_progress', $title.$first.$totalCourseSessionTable.'<pagebreak>'.$courseSessionTable);

    $content = $tpl->fetch($tpl->get_template('my_space/pdf_export_student.tpl'));

    $params = [
        'pdf_title' => get_lang('Resume'),
        'course_info' => '',
        'pdf_date' => '',
        'student_info' => $userInfo,
        'show_grade_generated_date' => true,
        'show_real_course_teachers' => false,
        'show_teacher_as_myself' => false,
        'orientation' => 'P',
    ];

    $pdfName = api_strtoupper($userInfo['lastname'].'_'.$userInfo['firstname']).'_'.api_get_local_time();
    @$pdf = new PDF('A4', $params['orientation'], $params);
    @$pdf->setBackground($tpl->theme);
    @$pdf->content_to_pdf(
        $content,
        '',
        $pdfName,
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
$userInfo = api_get_user_info($userId);

$form->setDefaults(['from' => $startDate, 'to' => $endDate]);

$formByDay = new FormValidator(
    'by_day',
    'get',
    api_get_self().'?user_id='.$userId,
    null,
    ['id' => 'by_day']
);
$formByDay->addElement('text', 'from', get_lang('From'), ['placeholder' => get_lang('DateFormatddmmyyyy')]);
$formByDay->addElement('text', 'to', get_lang('Until'), ['placeholder' => get_lang('DateFormatddmmyyyy')]);
$formByDay->addCheckBox('reduced', null, get_lang('ReducedReport'));
$formByDay->addHidden('user_id', $userId);
$formByDay->addRule('from', get_lang('ThisFieldIsRequired'), 'required');
$formByDay->addRule('from', get_lang('ThisFieldIsRequired').' dd/mm/yyyy', 'callback', 'validateDate');
$formByDay->addRule('to', get_lang('ThisFieldIsRequired'), 'required');
$formByDay->addRule('to', get_lang('ThisFieldIsRequired').' dd/mm/yyyy', 'callback', 'validateDate');
$formByDay->addButtonSearch(get_lang('GenerateReport'));

if ($formByDay->validate()) {
    $from = $formByDay->getSubmitValue('from');
    $to = $formByDay->getSubmitValue('to');
    $reduced = !empty($formByDay->getSubmitValue('reduced'));

    $fromObject = DateTime::createFromFormat('d/m/Y', $from);
    $toObject = DateTime::createFromFormat('d/m/Y', $to);

    $from = api_get_utc_datetime($fromObject->format('Y-m-d').' 00:00:00');
    $to = api_get_utc_datetime($toObject->format('Y-m-d').' 23:59:59');

    $list = Tracking::get_time_spent_on_the_platform($userId, 'wide', $from, $to, true);
    $newList = [];
    foreach ($list as $item) {
        $key = substr($item['login_date'], 0, 10);

        $dateLogout = substr($item['logout_date'], 0, 10);
        if ($dateLogout > $key) {
            $itemLogoutOriginal = $item['logout_date'];
            $fromItemObject = DateTime::createFromFormat('Y-m-d H:i:s', $item['login_date'], new DateTimeZone('UTC'));
            $toItemObject = DateTime::createFromFormat('Y-m-d H:i:s', $item['logout_date'], new DateTimeZone('UTC'));
            $item['logout_date'] = api_get_utc_datetime($key.' 23:59:59');

            $period = new DatePeriod(
                $fromItemObject,
                new DateInterval('P1D'),
                $toItemObject
            );

            $counter = 1;
            $itemKey = null;
            foreach ($period as $value) {
                $dateToCheck = api_get_utc_datetime($value->format('Y-m-d').' 00:00:01');
                $end = api_get_utc_datetime($value->format('Y-m-d').' 23:59:59');
                if ($counter === 1) {
                    $dateToCheck = $item['login_date'];
                }
                $itemKey = substr($value->format('Y-m-d'), 0, 10);

                if (isset($newList[$itemKey])) {
                    if ($newList[$itemKey]['login_date']) {
                        $dateToCheck = $newList[$itemKey]['login_date'];
                    }
                }

                $newList[$itemKey] = [
                    'login_date' => $dateToCheck,
                    'logout_date' => $end,
                    'diff' => 0,
                ];

                $counter++;
            }

            if (!empty($itemKey) && isset($newList[$itemKey])) {
                if (
                    substr(api_get_local_time($newList[$itemKey]['login_date']), 0, 10) ===
                    substr(api_get_local_time($itemLogoutOriginal), 0, 10)
                ) {
                    $newList[$itemKey]['logout_date'] = $itemLogoutOriginal;
                }
            }
        }

        if (!isset($newList[$key])) {
            $newList[$key] = [
                'login_date' => $item['login_date'],
                'logout_date' => $item['logout_date'],
                'diff' => 0,
            ];
        } else {
            $newList[$key] = [
                'login_date' => $newList[$key]['login_date'],
                'logout_date' => $item['logout_date'],
                'diff' => 0,
            ];
        }
    }

    if (!empty($newList)) {
        foreach ($newList as &$item) {
            $item['diff'] = api_strtotime($item['logout_date']) - api_strtotime($item['login_date']);
        }
    }

    $period = new DatePeriod(
        $fromObject,
        new DateInterval('P1D'),
        $toObject
    );

    $tableList = '';
    foreach ($period as $value) {
        $dateToCheck = $value->format('Y-m-d');
        $data = isset($newList[$dateToCheck]) ? $newList[$dateToCheck] : [];

        if (empty($data)) {
            continue;
        }

        $table = new HTML_Table(['class' => ' table_print']);
        $headers = [
            get_lang('FirstLogin'),
            get_lang('LastConnection'),
            get_lang('Total'),
        ];

        $row = 0;
        $column = 0;
        foreach ($headers as $header) {
            $table->setHeaderContents($row, $column, $header);
            $column++;
        }

        $row = 1;
        $column = 0;
        $table->setCellContents($row, $column++, customDate($data['login_date'], true));
        $table->setCellContents($row, $column++, customDate($data['logout_date'], true));
        $table->setCellContents($row, $column, api_format_time($data['diff'], 'js'));

        $result = getReport($userId, $dateToCheck, $dateToCheck, true);
        $first = $result['first'];
        $courseSessionTable = $result['second'];
        $totalCourseSessionTable = $result['third'];
        $total = $result['total'];
        $iconCalendar = Display::return_icon('calendar.png', null, [], ICON_SIZE_SMALL);
        $tableList .= '<div class="date-calendar">'.Display::page_subheader2(
                $iconCalendar.get_lang('Date').': '.$dateToCheck
            ).'</div>';
        $tableList .= $table->toHtml();
        if (!$reduced && !empty($total)) {
            $diff = get_lang('NotInCourse').' '.api_format_time($data['diff'] - $total, 'js');
            $tableList .= $courseSessionTable;
            $tableList .= $totalCourseSessionTable;
            $tableList .= '<div style="text-align: center;">'.Display::page_subheader3($diff).'</div>';
        }
    }

    $tpl = new Template('', false, false, false, true, false, false);
    $tpl->assign('title', get_lang('RealisationCertificate'));
    $tpl->assign('student', $userInfo['complete_name']);
    $totalTable = Display::page_subheader3(sprintf(get_lang('ExtractionFromX'), api_get_local_time()));
    $tpl->assign('table_progress', $totalTable.$tableList);

    $content = $tpl->fetch($tpl->get_template('my_space/pdf_export_student.tpl'));

    $params = [
        'pdf_title' => get_lang('Resume'),
        'course_info' => '',
        'pdf_date' => '',
        'student_info' => $userInfo,
        'show_grade_generated_date' => true,
        'show_real_course_teachers' => false,
        'show_teacher_as_myself' => false,
        'orientation' => 'P',
    ];
    $pdfName = api_strtoupper($userInfo['lastname'].'_'.$userInfo['firstname']).'_'.api_get_local_time();
    @$pdf = new PDF('A4', $params['orientation'], $params);
    @$pdf->setBackground($tpl->theme, true);
    @$pdf->content_to_pdf(
        $content,
        '',
        $pdfName,
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

$formByDay->setDefaults(['from' => $startDate, 'to' => $endDate]);

Display::display_header('');
echo Display::page_header(get_lang('CertificateOfAchievement'), get_lang('CertificateOfAchievementHelp'));
echo Display::page_subheader(
    get_lang('User').': '.$userInfo['complete_name']
);

echo Display::tabs(
    [get_lang('CertificateOfAchievement'), get_lang('CertificateOfAchievementByDay')],
    [$form->returnForm(), $formByDay->returnForm()]
);

Display::display_footer();

<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Enums\ToolIcon;

require_once __DIR__.'/../inc/global.inc.php';

api_block_anonymous_users();

$isAllowedToTrack = api_is_platform_admin(true, true) ||
    api_is_teacher() ||
    api_is_course_tutor();

if (!$isAllowedToTrack) {
    api_not_allowed(true);
    exit;
}

// The section (for the tabs)
$this_section = SECTION_TRACKING;

$userId = isset($_REQUEST['user_id']) ? (int) $_REQUEST['user_id'] : 0;
$userInfo = api_get_user_info($userId);

if (empty($userInfo)) {
    api_not_allowed(true);
    exit;
}

$summaryTabLabel = 'Activity summary report';
$dailyTabLabel = 'Daily activity report';
$summaryButtonLabel = 'Generate summary PDF';
$dailyButtonLabel = 'Generate daily PDF';
$summaryDescription = 'Generate a PDF with the overall first connection, last connection and time spent by course/session in the selected range.';
$dailyDescription = 'Generate a day-by-day PDF. Enable the reduced report option to keep only the daily totals.';
$pageTitle = 'Learner activity reports';

$origin = isset($_REQUEST['origin'])
    ? Security::remove_XSS((string) $_REQUEST['origin'])
    : 'tracking_course';

$cid = isset($_REQUEST['cid']) ? (int) $_REQUEST['cid'] : 0;
$courseCode = isset($_REQUEST['course']) ? Security::remove_XSS((string) $_REQUEST['course']) : '';
$sid = isset($_REQUEST['sid']) ? (int) $_REQUEST['sid'] : 0;

$commonReportParams = [
    'user_id' => $userId,
    'cid' => $cid,
    'course' => $courseCode,
    'origin' => $origin,
    'sid' => $sid,
];

$summaryFormAction = api_get_self().'?'.http_build_query(
        array_merge($commonReportParams, ['tab' => 'summary'])
    );

$dailyFormAction = api_get_self().'?'.http_build_query(
        array_merge($commonReportParams, ['tab' => 'daily'])
    );

$backUrl = api_get_path(WEB_CODE_PATH).'my_space/myStudents.php?details=true&student='.$userId;
if (!empty($cid)) {
    $backUrl .= '&cid='.$cid;
}
if (!empty($courseCode)) {
    $backUrl .= '&course='.urlencode($courseCode);
}
if (!empty($origin)) {
    $backUrl .= '&origin='.urlencode($origin);
}
$backUrl .= '&sid='.$sid;

$defaultTab = isset($_REQUEST['tab']) && 'daily' === $_REQUEST['tab'] ? 'daily' : 'summary';

/**
 * Convert a date/datetime to local format.
 *
 * @param string|int $dateTime
 * @param bool       $showTime
 *
 * @return string
 */
function customDate($dateTime, $showTime = false)
{
    if (empty($dateTime)) {
        return '';
    }

    $format = 'd/m/Y';
    if ($showTime) {
        $format = 'd/m/Y H:i:s';
    }

    return api_get_local_time(
        $dateTime,
        null,
        null,
        true,
        false,
        true,
        $format
    );
}

/**
 * Validate a dd/mm/YYYY date.
 */
function validateDate($value)
{
    if (empty($value)) {
        return false;
    }

    $date = DateTime::createFromFormat('d/m/Y', $value);

    return false !== $date && $date->format('d/m/Y') === $value;
}

/**
 * Apply a shared Tailwind-based renderer to a legacy FormValidator form.
 */
function applyActivityReportFormRenderer(FormValidator $form): void
{
    $renderer = $form->defaultRenderer();

    $renderer->setFormTemplate(
        '<form{attributes}>
            <div class="space-y-4">
                {content}
            </div>
            {hidden}
        </form>'
    );

    $renderer->setElementTemplate(
        '
        <div class="mb-5">
            <!-- BEGIN required -->
                <label {label-for} class="mb-2 block text-sm font-medium text-gray-700">
                    <span class="text-red-600">*</span> {label}
                </label>
            <!-- END required -->

            <!-- BEGIN label -->
                <!-- BEGIN required -->
                <!-- END required -->
            <!-- END label -->

            <!-- BEGIN label -->
            <!-- END label -->

            <div class="activity-report-field">
                {element}
            </div>

            <!-- BEGIN error -->
                <div class="mt-2 text-sm text-red-600">{error}</div>
            <!-- END error -->

            <!-- BEGIN label_2 -->
                <div class="mt-1 text-sm text-gray-500">{label_2}</div>
            <!-- END label_2 -->
        </div>'
    );

    $renderer->setRequiredNoteTemplate(
        '<div class="mt-4 text-sm text-gray-500">{requiredNote}</div>'
    );

    $form->setRequiredNote(
        '<span class="text-red-600">*</span> '.get_lang('Required field')
    );
}

/**
 * Render a small PDF-ready summary table.
 */
function renderSimpleSummaryTable(array $headers, array $rows): string
{
    $html = '<table border="1" cellpadding="6" cellspacing="0" width="100%">';
    $html .= '<thead><tr>';

    foreach ($headers as $header) {
        $html .= '<th style="font-weight:bold; background-color:#f3f4f6;">'.$header.'</th>';
    }

    $html .= '</tr></thead><tbody>';

    foreach ($rows as $row) {
        $html .= '<tr>';
        foreach ($row as $cell) {
            $html .= '<td>'.$cell.'</td>';
        }
        $html .= '</tr>';
    }

    $html .= '</tbody></table>';

    return $html;
}

/**
 * Build the activity report content.
 */
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
                if (empty($courseInfo)) {
                    continue;
                }

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
                    if (api_strtotime($item['logout'], 'UTC') > $maxLogin) {
                        $maxLogin = api_strtotime($item['logout'], 'UTC');
                    }

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
                    $report[$sessionId]['name'][$course['real_id']] = $courseInfo['title'].' ('.$session['session_name'].')';
                }

                if (!empty($result)) {
                    $record = [
                        customDate($partialMinLogin, true),
                        customDate($partialMaxLogin, true),
                        api_format_time($partialDuration, 'js'),
                    ];

                    $report[$sessionId]['courses'][$course['real_id']][] = $record;
                    $report[$sessionId]['name'][$course['real_id']] = $courseInfo['title'].' ('.$session['session_name'].')';
                }
            }
        }
    }

    $courses = [];
    $coursesList = CourseManager::get_courses_list_by_user_id($userId, false, true);

    if (!empty($coursesList) && is_array($coursesList)) {
        foreach ($coursesList as $courseItem) {
            $courseCodeItem = $courseItem['code'] ?? null;
            if (empty($courseCodeItem)) {
                continue;
            }

            $courseInfoItem = api_get_course_info($courseCodeItem);
            if (empty($courseInfoItem) || empty($courseInfoItem['real_id'])) {
                continue;
            }

            $courses[] = [
                'course_id' => (int) $courseInfoItem['real_id'],
                'real_id' => (int) $courseInfoItem['real_id'],
                'code' => $courseCodeItem,
                'title' => $courseInfoItem['title'] ?? $courseCodeItem,
            ];
        }
    }

    if ($addTime) {
        $fromFirst = api_get_local_time($from.' 00:00:00');
        $toEnd = api_get_local_time($to.' 23:59:59');

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

    $first = renderSimpleSummaryTable(
        [
            get_lang('First connection'),
            get_lang('Last connection'),
            get_lang('Total time spent'),
        ],
        [[
            customDate($minLogin),
            customDate($maxLogin),
            api_format_time($totalDuration, 'js'),
        ]]
    );

    $courseSessionTable = '';
    $courseSessionTableData = [];
    $iconCourse = Display::getMdiIcon(ToolIcon::COURSE, 'ch-tool-icon', null, ICON_SIZE_SMALL);

    foreach ($report as $sessionId => $data) {
        foreach ($data['courses'] as $courseId => $courseData) {
            if (empty($courseData)) {
                continue;
            }

            $courseSessionTable .= '<div class="data-title">'.Display::page_subheader3(
                    $iconCourse.$data['name'][$courseId]
                ).'</div>';

            $headers = [
                get_lang('Start Date'),
                get_lang('End Date'),
                get_lang('Duration'),
            ];

            $rows = [];
            $countData = count($courseData);
            $index = 0;

            foreach ($courseData as $record) {
                $rows[] = $record;
                $index++;

                if ($index === $countData) {
                    $courseSessionTableData[$data['name'][$courseId]] = $record[2];
                }
            }

            $courseSessionTable .= renderSimpleSummaryTable($headers, $rows);
        }
    }

    $totalCourseSessionTable = '';
    if (!empty($courseSessionTableData)) {
        $rows = [];
        foreach ($courseSessionTableData as $name => $duration) {
            $rows[] = [$name, $duration];
        }

        $totalCourseSessionTable = renderSimpleSummaryTable(
            [
                get_lang('Course'),
                get_lang('Total time spent'),
            ],
            $rows
        );
    }

    return [
        'first' => $first,
        'second' => $courseSessionTable,
        'third' => $totalCourseSessionTable,
        'total' => $totalDuration,
    ];
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
    $minStartTimestamp = null;
    $maxEndTimestamp = null;

    foreach ($sessions as $session) {
        if (!empty($session['access_start_date'])) {
            $currentStart = api_strtotime($session['access_start_date'], 'UTC');
            if (null === $minStartTimestamp || $currentStart < $minStartTimestamp) {
                $minStartTimestamp = $currentStart;
            }
        }

        if (!empty($session['access_end_date'])) {
            $currentEnd = api_strtotime($session['access_end_date'], 'UTC');
            if (null === $maxEndTimestamp || $currentEnd > $maxEndTimestamp) {
                $maxEndTimestamp = $currentEnd;
            }
        }
    }

    if (null !== $minStartTimestamp) {
        $startDate = customDate($minStartTimestamp);
    }

    if (null !== $maxEndTimestamp) {
        $endDate = customDate($maxEndTimestamp);
    }
}

if (empty($startDate) || empty($endDate)) {
    $today = new DateTime();
    $startDate = $today->modify('first day of this month')->format('d/m/Y');
    $today = new DateTime();
    $endDate = $today->modify('last day of this month')->format('d/m/Y');
}

$inputClass = 'block w-full rounded-xl border border-gray-30 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20';
$checkboxClass = 'h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary';

$form = new FormValidator(
    'summary_report_form',
    'get',
    $summaryFormAction,
    null,
    ['id' => 'summary_report_form', 'class' => 'w-full']
);
applyActivityReportFormRenderer($form);

$form->addHtml(
    '<div class="mb-4 rounded-xl border border-gray-10 bg-gray-20 px-4 py-3 text-sm text-sky-800">'.
    $summaryDescription.
    '</div>'
);

$form->addElement(
    'text',
    'from',
    get_lang('From'),
    [
        'id' => 'summary_from',
        'class' => $inputClass,
        'autocomplete' => 'off',
        'placeholder' => 'DD/MM/YYYY',
    ]
);

$form->addElement(
    'text',
    'to',
    get_lang('Until'),
    [
        'id' => 'summary_to',
        'class' => $inputClass,
        'autocomplete' => 'off',
        'placeholder' => 'DD/MM/YYYY',
    ]
);

$form->addHidden('user_id', $userId);
$form->addHidden('cid', $cid);
$form->addHidden('course', $courseCode);
$form->addHidden('origin', $origin);
$form->addHidden('sid', $sid);
$form->addRule('from', get_lang('Required field'), 'required');
$form->addRule('from', get_lang('Required field').' dd/mm/yyyy', 'callback', 'validateDate');
$form->addRule('to', get_lang('Required field'), 'required');
$form->addRule('to', get_lang('Required field').' dd/mm/yyyy', 'callback', 'validateDate');

$form->addHtml(
    '<div class="mt-6 flex flex-wrap items-center gap-3">
        <button
            type="submit"
            class="inline-flex items-center rounded-xl bg-primary px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:opacity-90"
        >
            '.$summaryButtonLabel.'
        </button>
    </div>'
);

$form->setDefaults([
    'from' => $startDate,
    'to' => $endDate,
]);

$formByDay = new FormValidator(
    'daily_report_form',
    'get',
    $dailyFormAction,
    null,
    ['id' => 'daily_report_form', 'class' => 'w-full']
);
applyActivityReportFormRenderer($formByDay);

$formByDay->addHtml(
    '<div class="mb-4 rounded-xl border border-gray-20 bg-gray-20 px-4 py-3 text-sm text-amber-800">'.
    $dailyDescription.
    '</div>'
);

$formByDay->addElement(
    'text',
    'from',
    get_lang('From'),
    [
        'id' => 'daily_from',
        'class' => $inputClass,
        'autocomplete' => 'off',
        'placeholder' => 'DD/MM/YYYY',
    ]
);

$formByDay->addElement(
    'text',
    'to',
    get_lang('Until'),
    [
        'id' => 'daily_to',
        'class' => $inputClass,
        'autocomplete' => 'off',
        'placeholder' => 'DD/MM/YYYY',
    ]
);

$formByDay->addCheckBox(
    'reduced',
    null,
    get_lang('Reduced report'),
    [
        'class' => $checkboxClass,
        'id' => 'daily_reduced',
    ]
);

$formByDay->addHidden('user_id', $userId);
$formByDay->addHidden('cid', $cid);
$formByDay->addHidden('course', $courseCode);
$formByDay->addHidden('origin', $origin);
$formByDay->addHidden('sid', $sid);
$formByDay->addRule('from', get_lang('Required field'), 'required');
$formByDay->addRule('from', get_lang('Required field').' dd/mm/yyyy', 'callback', 'validateDate');
$formByDay->addRule('to', get_lang('Required field'), 'required');
$formByDay->addRule('to', get_lang('Required field').' dd/mm/yyyy', 'callback', 'validateDate');

$formByDay->addHtml(
    '<div class="mt-6 flex flex-wrap items-center gap-3">
        <button
            type="submit"
            class="inline-flex items-center rounded-xl bg-primary px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:opacity-90"
        >
            '.$dailyButtonLabel.'
        </button>
    </div>'
);

$formByDay->setDefaults([
    'from' => $startDate,
    'to' => $endDate,
]);

if ($form->validate()) {
    $values = $form->getSubmitValues();
    $from = $values['from'];
    $to = $values['to'];

    $fromObject = DateTime::createFromFormat('d/m/Y', $from);
    $toObject = DateTime::createFromFormat('d/m/Y', $to);

    $from = api_get_utc_datetime($fromObject->format('Y-m-d'));
    $to = api_get_utc_datetime($toObject->format('Y-m-d'));

    $title = Display::page_subheader3(sprintf(get_lang('Extraction from %s'), api_get_local_time()));
    $result = getReport($userId, $from, $to);

    $first = $result['first'];
    $courseSessionTable = $result['second'];
    $totalCourseSessionTable = $result['third'];

    $rangeTitle = get_lang('From').': '.customDate($from).' - '.get_lang('Until').': '.customDate($to);
    $tpl = new Template('', false, false, false, true, false, false);
    $tpl->assign('title', get_lang('Certificate of achievement'));
    $tpl->assign('session_title', $rangeTitle);
    $tpl->assign('student', $userInfo['complete_name']);
    $tpl->assign('table_progress', $title.$first.$totalCourseSessionTable.'<pagebreak />'.$courseSessionTable);
    $tpl->assign('subtitle', '');
    $tpl->assign('table_course', '');

    $content = $tpl->fetch($tpl->get_template('my_space/pdf_export_student.tpl'));

    $params = [
        'pdf_title' => $summaryTabLabel,
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
        null,
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

    if (!empty($list)) {
        foreach ($list as $item) {
            $key = substr($item['login_date'], 0, 10);
            $dateLogout = substr($item['logout_date'], 0, 10);

            if ($dateLogout > $key) {
                $itemLogoutOriginal = $item['logout_date'];

                $fromItemObject = DateTime::createFromFormat(
                    'Y-m-d H:i:s',
                    $item['login_date'],
                    new DateTimeZone('UTC')
                );
                $toItemObject = DateTime::createFromFormat(
                    'Y-m-d H:i:s',
                    $item['logout_date'],
                    new DateTimeZone('UTC')
                );

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

                    if (1 === $counter) {
                        $dateToCheck = $item['login_date'];
                    }

                    $itemKey = substr($value->format('Y-m-d'), 0, 10);

                    if (isset($newList[$itemKey]) && !empty($newList[$itemKey]['login_date'])) {
                        $dateToCheck = $newList[$itemKey]['login_date'];
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
    }

    if (!empty($newList)) {
        foreach ($newList as &$item) {
            $item['diff'] = api_strtotime($item['logout_date']) - api_strtotime($item['login_date']);
        }
        unset($item);
    }

    $period = new DatePeriod(
        $fromObject,
        new DateInterval('P1D'),
        $toObject->modify('+1 day')
    );

    $tableList = '';
    foreach ($period as $value) {
        $dateToCheck = $value->format('Y-m-d');
        $data = isset($newList[$dateToCheck]) ? $newList[$dateToCheck] : [];

        if (empty($data)) {
            continue;
        }

        $dailyTable = renderSimpleSummaryTable(
            [
                get_lang('First connection'),
                get_lang('Last connection'),
                get_lang('Total'),
            ],
            [[
                customDate($data['login_date'], true),
                customDate($data['logout_date'], true),
                api_format_time($data['diff'], 'js'),
            ]]
        );

        $result = getReport($userId, $dateToCheck, $dateToCheck, true);
        $courseSessionTable = $result['second'];
        $totalCourseSessionTable = $result['third'];
        $total = $result['total'];

        $iconCalendar = Display::getMdiIcon(ToolIcon::AGENDA, 'ch-tool-icon', null, ICON_SIZE_SMALL);

        $tableList .= '<div class="date-calendar">'.Display::page_subheader2(
                $iconCalendar.get_lang('Date').': '.$dateToCheck
            ).'</div>';
        $tableList .= $dailyTable;

        if (!$reduced && !empty($total)) {
            $diff = get_lang('Outside courses').' '.api_format_time($data['diff'] - $total, 'js');
            $tableList .= $courseSessionTable;
            $tableList .= $totalCourseSessionTable;
            $tableList .= '<div style="text-align:center;">'.Display::page_subheader3($diff).'</div>';
        }
    }

    $rangeTitle = get_lang('From').': '.customDate($from).' - '.get_lang('Until').': '.customDate($to);

    $tpl = new Template('', false, false, false, true, false, false);
    $tpl->assign('title', get_lang('Certificate of achievement by day'));
    $tpl->assign('session_title', $rangeTitle);
    $tpl->assign('student', $userInfo['complete_name']);
    $totalTable = Display::page_subheader3(sprintf(get_lang('Extraction from %s'), api_get_local_time()));
    $tpl->assign('table_progress', $totalTable.$tableList);
    $tpl->assign('subtitle', '');
    $tpl->assign('table_course', '');

    $content = $tpl->fetch($tpl->get_template('my_space/pdf_export_student.tpl'));

    $params = [
        'pdf_title' => $dailyTabLabel,
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
        null,
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

$flatpickrCss = api_get_path(WEB_PATH).'build/flatpickr/flatpickr.min.css';
$flatpickrJs = api_get_path(WEB_PATH).'build/flatpickr/flatpickr.min.js';

$htmlHeadXtra[] = '<link rel="stylesheet" href="'.$flatpickrCss.'" />';
$htmlHeadXtra[] = '<script src="'.$flatpickrJs.'"></script>';
$htmlHeadXtra[] = "
<script>
document.addEventListener('DOMContentLoaded', function () {
    function activateReportTab(tabName) {
        var buttons = document.querySelectorAll('[data-report-tab]');
        var panels = document.querySelectorAll('[data-report-panel]');

        buttons.forEach(function (button) {
            var isActive = button.getAttribute('data-report-tab') === tabName;

            button.classList.toggle('bg-primary', isActive);
            button.classList.toggle('text-white', isActive);
            button.classList.toggle('shadow-sm', isActive);
            button.classList.toggle('bg-white', !isActive);
            button.classList.toggle('text-gray-700', !isActive);
            button.classList.toggle('border', !isActive);
            button.classList.toggle('border-gray-30', !isActive);
        });

        panels.forEach(function (panel) {
            if (panel.getAttribute('data-report-panel') === tabName) {
                panel.classList.remove('hidden');
            } else {
                panel.classList.add('hidden');
            }
        });
    }

    document.querySelectorAll('[data-report-tab]').forEach(function (button) {
        button.addEventListener('click', function () {
            activateReportTab(button.getAttribute('data-report-tab'));
        });
    });

    activateReportTab('".$defaultTab."');

    if (typeof flatpickr !== 'undefined') {
        ['#summary_from', '#summary_to', '#daily_from', '#daily_to'].forEach(function (selector) {
            if (document.querySelector(selector)) {
                flatpickr(selector, {
                    dateFormat: 'd/m/Y',
                    allowInput: true
                });
            }
        });
    }
});
</script>";

$interbreadcrumb[] = ['url' => '#', 'name' => get_lang('Access details')];

Display::display_header('');

echo '<div class="mb-4">';
echo '    <a
            href="'.$backUrl.'"
            class="inline-flex items-center gap-2 rounded-xl border border-gray-30 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-10 hover:text-gray-900"
        >';
echo          Display::getMdiIcon('arrow-left', 'ch-tool-icon', null, 18, get_lang('Back'));
echo '        <span>'.get_lang('Back').'</span>';
echo '    </a>';
echo '</div>';

echo Display::page_header($pageTitle);
echo Display::page_subheader(
    get_lang('User').': '.$userInfo['complete_name']
);

echo '<div class="mt-6 space-y-6">';
echo '    <div class="rounded-2xl border border-gray-30 bg-white shadow-sm">';
echo '        <div class="border-b border-gray-30 px-6 py-4">';
echo '            <h2 class="text-lg font-semibold text-gray-800">Reports</h2>';
echo '            <p class="mt-1 text-sm text-gray-500">Choose the report type and date range to generate the learner activity PDF.</p>';
echo '        </div>';
echo '        <div class="px-6 py-6">';
echo '            <div class="mb-6 flex flex-wrap gap-3">';
echo '                <button
                        type="button"
                        data-report-tab="summary"
                        class="inline-flex items-center rounded-xl px-4 py-2 text-sm font-semibold transition"
                    >'.$summaryTabLabel.'</button>';
echo '                <button
                        type="button"
                        data-report-tab="daily"
                        class="inline-flex items-center rounded-xl px-4 py-2 text-sm font-semibold transition"
                    >'.$dailyTabLabel.'</button>';
echo '            </div>';

echo '            <div data-report-panel="summary">';
echo                  $form->returnForm();
echo '            </div>';

echo '            <div data-report-panel="daily" class="hidden">';
echo                  $formByDay->returnForm();
echo '            </div>';

echo '        </div>';
echo '    </div>';
echo '</div>';

Display::display_footer();

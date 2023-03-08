<?php

/* For licensing terms, see /license.txt */

/**
 * This tool show global Statistics on general platform events.
 */
$cidReset = true;

require_once __DIR__.'/../../inc/global.inc.php';
api_protect_admin_script();

$interbreadcrumb[] = ['url' => '../index.php', 'name' => get_lang('PlatformAdmin')];

$report = $_REQUEST['report'] ?? '';
$sessionDuration = isset($_GET['session_duration']) ? (int) $_GET['session_duration'] : '';
$validated = false;
$sessionStatusAllowed = api_get_configuration_value('allow_session_status');
$invoicingMonth = isset($_GET['invoicing_month']) ? (int) $_GET['invoicing_month'] : '';
$invoicingYear = isset($_GET['invoicing_year']) ? (int) $_GET['invoicing_year'] : '';

if (
in_array(
    $report,
    ['recentlogins', 'tools', 'courses', 'coursebylanguage', 'users', 'users_active', 'session_by_date']
)
) {
    $htmlHeadXtra[] = api_get_js('chartjs/Chart.min.js');
    $htmlHeadXtra[] = api_get_asset('chartjs-plugin-labels/build/chartjs-plugin-labels.min.js');

    // Prepare variables for the JS charts
    $url = $reportName = $reportType = '';
    switch ($report) {
        case 'recentlogins':
            $url = api_get_path(WEB_CODE_PATH).'inc/ajax/statistics.ajax.php?a=recent_logins&session_duration='
                .$sessionDuration;
            $reportName = '';
            $reportType = 'line';
            $reportOptions = '';
            $htmlHeadXtra[] = Statistics::getJSChartTemplate($url, $reportType, $reportOptions);
            break;
        case 'tools':
            $url = api_get_path(WEB_CODE_PATH).'inc/ajax/statistics.ajax.php?a=tools_usage';
            $reportName = 'PlatformToolAccess';
            $reportType = 'pie';
            $reportOptions = '
                legend: {
                    position: "left"
                },
                title: {
                    text: "'.get_lang($reportName).'",
                    display: true
                },
                cutoutPercentage: 25
                ';
            $htmlHeadXtra[] = Statistics::getJSChartTemplate($url, $reportType, $reportOptions);
            break;
        case 'courses':
            $url = api_get_path(WEB_CODE_PATH).'inc/ajax/statistics.ajax.php?a=courses';
            $reportName = 'CountCours';
            $reportType = 'pie';
            $reportOptions = '
                legend: {
                    position: "left"
                },
                title: {
                    text: "'.get_lang($reportName).'",
                    display: true
                },
                cutoutPercentage: 25
                ';
            $htmlHeadXtra[] = Statistics::getJSChartTemplate($url, $reportType, $reportOptions);
            break;
        case 'coursebylanguage':
            $url = api_get_path(WEB_CODE_PATH).'inc/ajax/statistics.ajax.php?a=courses_by_language';
            $reportName = 'CountCourseByLanguage';
            $reportType = 'pie';
            $reportOptions = '
                legend: {
                    position: "left"
                },
                title: {
                    text: "'.get_lang($reportName).'",
                    display: true
                },
                cutoutPercentage: 25
                ';
            $htmlHeadXtra[] = Statistics::getJSChartTemplate($url, $reportType, $reportOptions);
            break;
        case 'users':
            $invisible = isset($_GET['count_invisible_courses']) ? intval($_GET['count_invisible_courses']) : null;
            $urlBase = api_get_path(WEB_CODE_PATH).'inc/ajax/statistics.ajax.php?';
            $url1 = $urlBase.'a=users&count_invisible='.$invisible;
            $url2 = $urlBase.'a=users_teachers&count_invisible='.$invisible;
            $url3 = $urlBase.'a=users_students&count_invisible='.$invisible;
            $reportName1 = get_lang('NumberOfUsers');
            $reportName2 = get_lang('Teachers');
            $reportName3 = get_lang('Students');
            $reportType = 'pie';
            $reportOptions = '
                legend: {
                    position: "left"
                },
                title: {
                    text: "%s",
                    display: true
                },
                cutoutPercentage: 25
                ';
            $reportOptions1 = sprintf($reportOptions, $reportName1);
            $reportOptions2 = sprintf($reportOptions, $reportName2);
            $reportOptions3 = sprintf($reportOptions, $reportName3);
            $htmlHeadXtra[] = Statistics::getJSChartTemplate(
                $url1,
                $reportType,
                $reportOptions1,
                'canvas1'
            );
            $htmlHeadXtra[] = Statistics::getJSChartTemplate(
                $url2,
                $reportType,
                $reportOptions2,
                'canvas2'
            );
            $htmlHeadXtra[] = Statistics::getJSChartTemplate(
                $url3,
                $reportType,
                $reportOptions3,
                'canvas3'
            );
            break;
        case 'users_active':
            $form = new FormValidator('users_active', 'get', api_get_self().'?report=users_active');
            $form->addDateRangePicker(
                'daterange',
                get_lang('DateRange'),
                true,
                ['format' => 'YYYY-MM-DD', 'timePicker' => 'false', 'validate_format' => 'Y-m-d']
            );

            $form->addHidden('report', 'users_active');
            $form->addButtonFilter(get_lang('Search'));

            $validated = $form->validate() || isset($_REQUEST['daterange']);

            $urlBase = api_get_path(WEB_CODE_PATH).'inc/ajax/statistics.ajax.php?';
            $dateStart = '';
            $dateEnd = '';
            if ($validated) {
                $values = $_REQUEST;
                $form->setDefaults(['daterange' => Security::remove_XSS($values['daterange'])]);
                $dateStart = Security::remove_XSS($values['daterange_start']);
                $dateEnd = Security::remove_XSS($values['daterange_end']);
            }

            $reportType = 'pie';
            $reportOptions = '
                legend: {
                    position: "left"
                },
                title: {
                    text: "%s",
                    display: true
                },
                cutoutPercentage: 25
                ';

            $reportName1 = get_lang('UsersCreatedInTheSelectedPeriod');
            $reportName2 = get_lang('UsersByStatus');
            $reportName3 = get_lang('UsersByLanguage');
            $reportName4 = get_lang('UsersByTargetLanguage');
            $reportName5 = get_lang('UsersByCareer');
            $reportName6 = get_lang('UsersByContract');
            $reportName7 = get_lang('UsersByCertificate');
            $reportName8 = get_lang('UsersByAge');

            //$url1 = $urlBase.'a=users_active&filter=active&date_start='.$dateStart.'&date_end='.$dateEnd;
            $url2 = $urlBase.'a=users_active&filter=status&date_start='.$dateStart.'&date_end='.$dateEnd;
            $url3 = $urlBase.'a=users_active&filter=language&date_start='.$dateStart.'&date_end='.$dateEnd;
            $url4 = $urlBase.'a=users_active&filter=language_cible&date_start='.$dateStart.'&date_end='.$dateEnd;
            $url5 = $urlBase.'a=users_active&filter=career&date_start='.$dateStart.'&date_end='.$dateEnd;
            $url6 = $urlBase.'a=users_active&filter=contract&date_start='.$dateStart.'&date_end='.$dateEnd;
            $url7 = $urlBase.'a=users_active&filter=certificate&date_start='.$dateStart.'&date_end='.$dateEnd;
            $url8 = $urlBase.'a=users_active&filter=age&date_start='.$dateStart.'&date_end='.$dateEnd;

            $reportOptions1 = sprintf($reportOptions, $reportName1);
            $reportOptions2 = sprintf($reportOptions, $reportName2);
            $reportOptions3 = sprintf($reportOptions, $reportName3);
            $reportOptions4 = sprintf($reportOptions, $reportName4);
            $reportOptions5 = sprintf($reportOptions, $reportName5);
            $reportOptions6 = sprintf($reportOptions, $reportName6);
            $reportOptions7 = sprintf($reportOptions, $reportName7);
            $reportOptions8 = sprintf($reportOptions, $reportName8);

            break;
        case 'session_by_date':
            $form = new FormValidator('session_by_date', 'get');
            $form->addDateRangePicker(
                'range',
                get_lang('DateRange'),
                true,
                ['format' => 'YYYY-MM-DD', 'timePicker' => 'false', 'validate_format' => 'Y-m-d']
            );

            if ($sessionStatusAllowed) {
                $options = SessionManager::getStatusList();
                $form->addSelect('status_id', get_lang('SessionStatus'), $options, ['placeholder' => get_lang('All')]);
            }

            $form->addHidden('report', 'session_by_date');
            $form->addButtonSearch(get_lang('Search'));

            $validated = $form->validate() || isset($_REQUEST['range']);
            if ($validated) {
                $values = $form->getSubmitValues();
                $urlBase = api_get_path(WEB_CODE_PATH).'inc/ajax/statistics.ajax.php?';
                $dateStart = null;
                $dateEnd = null;

                if (isset($values['range_start'])) {
                    $dateStart = Security::remove_XSS($values['range_start']);
                }
                if (isset($values['range_end'])) {
                    $dateEnd = Security::remove_XSS($values['range_end']);
                }

                if (isset($_REQUEST['range_start'])) {
                    $dateStart = Security::remove_XSS($_REQUEST['range_start']);
                }

                if (isset($_REQUEST['range_end'])) {
                    $dateEnd = Security::remove_XSS($_REQUEST['range_end']);
                }

                $statusId = isset($_REQUEST['status_id']) ? (int) $_REQUEST['status_id'] : 0;

                $conditions = "&date_start=$dateStart&date_end=$dateEnd&status=$statusId";

                $url1 = $urlBase.'a=session_by_date&filter=category'.$conditions;
                $url2 = $urlBase.'a=session_by_date&filter=language'.$conditions;
                $url3 = $urlBase.'a=session_by_date&filter=status'.$conditions;
                $url4 = $urlBase.'a=session_by_date&filter=course_in_session'.$conditions;

                $reportName1 = get_lang('SessionsPerCategory');
                $reportName2 = get_lang('SessionsPerLanguage');
                $reportName3 = get_lang('SessionsPerStatus');
                $reportName4 = get_lang('CourseInSession');

                $reportType = 'pie';
                $reportOptions = '
                    legend: {
                        position: "left"
                    },
                    title: {
                        text: "%s",
                        display: true
                    },
                    cutoutPercentage: 25
                ';
                $reportOptions1 = sprintf($reportOptions, $reportName1);
                $reportOptions2 = sprintf($reportOptions, $reportName2);
                $reportOptions3 = sprintf($reportOptions, $reportName3);

                $htmlHeadXtra[] = Statistics::getJSChartTemplate(
                    $url1,
                    $reportType,
                    $reportOptions1,
                    'canvas1'
                );
                $htmlHeadXtra[] = Statistics::getJSChartTemplate(
                    $url2,
                    $reportType,
                    $reportOptions2,
                    'canvas2'
                );

                if ($sessionStatusAllowed) {
                    $htmlHeadXtra[] = Statistics::getJSChartTemplate(
                        $url3,
                        $reportType,
                        $reportOptions3,
                        'canvas3'
                    );
                }

                $reportOptions = '
                    legend: {
                        position: "left"
                    },
                    title: {
                        text: "'.$reportName4.'",
                        display: true
                    },
                    responsive: true,
                    animation: {
                      animateScale: true,
                      animateRotate: true
                    },
                    cutoutPercentage: 25,
                    tooltips: {
                        callbacks: {
                            label: function(tooltipItem, data) {
                                var dataset = data.datasets[tooltipItem.datasetIndex];
                                var total = dataset.data.reduce(function(previousValue, currentValue, currentIndex, array) {
                                    return previousValue + currentValue;
                                });

                                var label = data.labels[tooltipItem.datasetIndex];
                                var currentValue = dataset.data[tooltipItem.index];
                                var percentage = Math.floor(((currentValue/total) * 100)+0.5);

                                return label + " " + percentage + "%";
                            }
                        }
                    }
                ';

                $htmlHeadXtra[] = Statistics::getJSChartTemplate(
                    $url4,
                    $reportType,
                    $reportOptions,
                    'canvas4'
                );
            }
            break;
    }
}

if ('user_session' === $report) {
    $htmlHeadXtra[] = api_get_jqgrid_js();
}

if (isset($_GET['export'])) {
    ob_start();
}

$tool_name = get_lang('Statistics');
$tools = [
    get_lang('Courses') => [
        'report=courses' => get_lang('CountCours'),
        'report=tools' => get_lang('PlatformToolAccess'),
        'report=courselastvisit' => get_lang('LastAccess'),
        'report=coursebylanguage' => get_lang('CountCourseByLanguage'),
    ],
    get_lang('Users') => [
        'report=users' => get_lang('CountUsers'),
        'report=recentlogins' => get_lang('Logins'),
        'report=logins&amp;type=month' => get_lang('Logins').' ('.get_lang('PeriodMonth').')',
        'report=logins&amp;type=day' => get_lang('Logins').' ('.get_lang('PeriodDay').')',
        'report=logins&amp;type=hour' => get_lang('Logins').' ('.get_lang('PeriodHour').')',
        'report=pictures' => get_lang('CountUsers').' ('.get_lang('UserPicture').')',
        'report=logins_by_date' => get_lang('LoginsByDate'),
        'report=no_login_users' => get_lang('StatsUsersDidNotLoginInLastPeriods'),
        'report=zombies' => get_lang('Zombies'),
        'report=users_active' => get_lang('UserStats'),
        'report=users_online' => get_lang('UsersOnline'),
        'report=invoicing' => get_lang('InvoicingByAccessUrl'),
        'report=duplicated_users' => get_lang('DuplicatedUsers'),
    ],
    get_lang('System') => [
        'report=activities' => get_lang('ImportantActivities'),
        'report=user_session' => get_lang('PortalUserSessionStats'),
        'report=courses_usage' => get_lang('CoursesUsage'),
        'report=quarterly_report' => get_lang('QuarterlyReport'),
    ],
    get_lang('Social') => [
        'report=messagereceived' => get_lang('MessagesReceived'),
        'report=messagesent' => get_lang('MessagesSent'),
        'report=friends' => get_lang('CountFriends'),
    ],
    get_lang('Session') => [
        'report=session_by_date' => get_lang('SessionsByDate'),
    ],
];

if ('true' === api_get_plugin_setting('lti_provider', 'enabled')) {
    $tools[get_lang('Users')]['report=lti_tool_lp'] = get_lang('LearningPathLTI');
}

$course_categories = Statistics::getCourseCategories();
$content = '';

switch ($report) {
    case 'courses_usage':
        $form = new FormValidator('courses_usage', 'get');
        $nextElement = 0;
        $currentPage = 0;
        $start = 0;
        $pagged = 10;
        $coursesList = [];
        $op = [];
        $today = new DateTime();
        $reportPost = $_POST['report'] ?? null;
        $endDate = $today->format('Y-m-d');
        $pag = 0;
        $fechas = [
            'day' => $today->setTimestamp(strtotime('-1 day'))->format('Y-m-d'),
            'week' => $today->setTimestamp(strtotime('-1 week'))->format('Y-m-d'),
            'month' => $today->setTimestamp(strtotime('-1 month'))->format('Y-m-d'),
            '6month' => $today->setTimestamp(strtotime('-6 month'))->format('Y-m-d'),
            'year' => $today->setTimestamp(strtotime('-1 year'))->format('Y-m-d'),
            '2year' => $today->setTimestamp(strtotime('-2 year'))->format('Y-m-d'),
            'total' => null,
        ];
        $courses = CourseManager::get_course_list();
        $coursesTotal = count($courses);
        if (0 < $coursesTotal) {
            $start = isset($_GET['start']) ? (int) ($_GET['start']) : 0;
        }

        $start = abs($start);

        $termina = ($start + $pagged) < $pagged ? $pagged : $start + $pagged;
        foreach ($courses as $course) {
            $courseId = $course['id'];
            $sessions = 0;
            $courseTotal = 0;
            $visit = 0;
            $indexCourseList = count($coursesList);
            $item = [];
            if ($indexCourseList >= $start && $indexCourseList < $termina) {
                foreach ($fechas as $index => $date) {
                    $startDate = $date;
                    $courseTotal = count(CourseManager::getAccessCourse(
                        $courseId,
                        0,
                        0,
                        $startDate,
                        $endDate
                    ));
                    $sessions = count(CourseManager::getAccessCourse(
                        $courseId,
                        1,
                        0,
                        $startDate,
                        $endDate
                    ));
                    $visit = count(CourseManager::getAccessCourse(
                        $courseId,
                        3,
                        0,
                        $startDate,
                        $endDate
                    ));
                    $temp = [
                        'start' => $startDate,
                        'course' => $visit,
                        'course_id' => $course['id'],
                        'session' => $sessions,
                        'count' => $sessions + $courseTotal,
                    ];
                    $item[$index] = $temp;
                }
                $coursesList[$indexCourseList] = [
                    $course['title'],
                    $item['day']['count'],
                    $item['week']['count'],
                    $item['month']['count'],
                    $item['6month']['count'],
                    $item['year']['count'],
                    $item['2year']['count'],
                    $courseTotal,
                    $sessions,
                    $item['total']['count'],
                ];
                if (0 == $nextElement) {
                    $nextElement = $indexCourseList;
                }
                $op[] = $coursesList[$indexCourseList];
            } else {
                $coursesList[$indexCourseList] = null;
            }
            if ($indexCourseList >= ($termina)) {
                break;
            }

            if (1 == count($coursesList) % $pagged) {
                $currentPage++;
            }
        }
        $headerName = [
            [get_lang('Course'), false],
            [get_lang('Today'), false],
            [get_lang('ThisWeek'), false],
            [get_lang('ThisMonth'), false],
            ["6 ".get_lang('MinMonths'), false],
            ["1 ".get_lang('Year'), false],
            ["2 ".get_lang('Years'), false],
            [get_lang('NumAccess')." (".get_lang('Course').")", false],
            [get_lang('NumAccess')." (".get_lang('Session').")", false],
            [get_lang('AbsoluteTotal')." (".get_lang('Visited').")", false],
        ];
        $query_vars = [];
        $query_vars['start'] = $nextElement;
        $query_vars['report'] = 'courses_usage';
        $paging_options = [];
        $paging_options['per_page'] = $pagged;
        $nextCourseIndex = ($start + $pagged);
        $previousCourseIndex = ($start - 10) < 0 ? 0 : ($start - 10);

        $pag = (int) ($coursesTotal / $pagged);
        if ($pag < ($coursesTotal / $pagged)) {
            $pag++;
        }

        $nextLink = Display::url(
            Display::return_icon(
                'action_next.png',
                get_lang('NextPage'),
                [],
                ICON_SIZE_MEDIUM
            ),
            api_get_self()."?&start=$nextCourseIndex&report=courses_usage",
            ['class' => 'btn']
        );

        $previousLink = Display::url(
            Display::return_icon(
                'action_prev.png',
                get_lang('PreviousPage'),
                [],
                ICON_SIZE_MEDIUM
            ),
            api_get_self()."?&start=$previousCourseIndex&report=courses_usage",
            ['class' => 'btn']
        );
        $table = Display::return_sortable_table(
            $headerName,
            $op,
            'ASC',
            $paging_options,
            $query_vars);

        $form->addHtml(
            $table
        );
        $html = "<div class='col-md-12 row'><div class='col-md-6'>&nbsp;</div><div class='col-md-6'>";
        $html .= ($pag > 1) ? $currentPage." / ".$pag : '';
        $html .= ($previousCourseIndex > 1) ? $previousLink : '';
        $html .= ($nextCourseIndex < $coursesTotal) ? $nextLink : '';
        $html .= '</div>';
        $form->addHtml($html);
        $content = $form->returnForm();

        break;
    case 'session_by_date':
        $sessions = [];
        if ($validated) {
            $values = $form->getSubmitValues();
            $first = DateTime::createFromFormat('Y-m-d', $dateStart);
            $second = DateTime::createFromFormat('Y-m-d', $dateEnd);
            $numberOfWeeks = 0;
            if ($first) {
                $numberOfWeeks = floor($first->diff($second)->days / 7);
            }

            $statusCondition = '';
            if (!empty($statusId)) {
                $statusCondition .= " AND status = $statusId ";
            }

            $start = Database::escape_string($dateStart);
            $end = Database::escape_string($dateEnd);

            // User count
            $tableSession = Database::get_main_table(TABLE_MAIN_SESSION);
            $sql = "SELECT * FROM $tableSession
                    WHERE
                        (display_start_date BETWEEN '$start' AND '$end' OR
                        display_end_date BETWEEN '$start' AND '$end')
                        $statusCondition
                    ";
            $result = Database::query($sql);

            $sessionCount = 0;
            $numberUsers = 0;
            while ($row = Database::fetch_array($result, 'ASSOC')) {
                $sessions[] = $row;
                $numberUsers += $row['nbr_users'];
                $sessionCount++;
            }

            $content .= Display::page_subheader2(get_lang('GeneralStats'));

            // Coach
            $sql = "SELECT count(DISTINCT(id_coach)) count FROM $tableSession
                    WHERE
                        (display_start_date BETWEEN '$start' AND '$end' OR
                        display_end_date BETWEEN '$start' AND '$end')
                        $statusCondition
                     ";
            $result = Database::query($sql);
            $row = Database::fetch_array($result);
            $uniqueCoaches = $row['count'];

            // Categories
            $sql = "SELECT count(id) count, session_category_id FROM $tableSession
                    WHERE
                        (display_start_date BETWEEN '$start' AND '$end' OR
                        display_end_date BETWEEN '$start' AND '$end')
                        $statusCondition;
                    GROUP BY session_category_id
                    ";

            $result = Database::query($sql);
            $sessionPerCategories = [];
            while ($row = Database::fetch_array($result)) {
                $sessionPerCategories[$row['session_category_id']] = $row['count'];
            }

            $sessionAverage = 0;
            $averageUser = 0;
            $averageCoach = 0;
            if (!empty($numberOfWeeks)) {
                $sessionAverage = api_number_format($sessionCount / $numberOfWeeks, 2);
            }
            if (!empty($sessionCount)) {
                $averageUser = api_number_format($numberUsers / $sessionCount, 2);
            }
            if (!empty($uniqueCoaches)) {
                $averageCoach = api_number_format($sessionCount / $uniqueCoaches, 2);
            }

            $courseSessions = [];
            if (!empty($sessions)) {
                foreach ($sessions as $session) {
                    $courseList = SessionManager::getCoursesInSession($session['id']);
                    foreach ($courseList as $courseId) {
                        if (!isset($courseSessions[$courseId])) {
                            $courseSessions[$courseId] = 0;
                        }
                        $courseSessions[$courseId]++;
                    }
                }
            }

            $table = new HTML_Table(['class' => 'table table-responsive']);
            $row = 0;
            $table->setCellContents($row, 0, get_lang('Weeks'));
            $table->setCellContents($row, 1, $numberOfWeeks);
            $row++;

            $table->setCellContents($row, 0, get_lang('SessionCount'));
            $table->setCellContents($row, 1, $sessionCount);
            $row++;

            $table->setCellContents($row, 0, get_lang('SessionsPerWeek'));
            $table->setCellContents($row, 1, $sessionAverage);
            $row++;

            $table->setCellContents($row, 0, get_lang('AverageUserPerSession'));
            $table->setCellContents($row, 1, $averageUser);
            $row++;

            $table->setCellContents($row, 0, get_lang('AverageSessionPerGeneralCoach'));
            $table->setCellContents($row, 1, $averageCoach);
            $row++;

            $content .= $table->toHtml();

            $content .= '<div class="row">';
            $content .= '<div class="col-md-4"><h4 class="page-header" id="canvas1_title"></h4><div id="canvas1_table"></div></div>';
            $content .= '<div class="col-md-4"><h4 class="page-header" id="canvas2_title"></h4><div id="canvas2_table"></div></div>';

            if ($sessionStatusAllowed) {
                $content .= '<div class="col-md-4"><h4 class="page-header" id="canvas3_title"></h4><div id="canvas3_table"></div></div>';
            }
            $content .= '</div>';

            $tableCourse = new HTML_Table(['class' => 'table table-responsive']);
            $headers = [
                get_lang('Course'),
                get_lang('CountOfSessions'),
            ];

            $row = 0;
            $column = 0;
            foreach ($headers as $header) {
                $tableCourse->setHeaderContents($row, $column, $header);
                $column++;
            }
            $row++;

            if (!empty($courseSessions)) {
                arsort($courseSessions);
                foreach ($courseSessions as $courseId => $count) {
                    $courseInfo = api_get_course_info_by_id($courseId);
                    $tableCourse->setCellContents($row, 0, $courseInfo['name']);
                    $tableCourse->setCellContents($row, 1, $count);
                    $row++;
                }
            }

            $content .= $tableCourse->toHtml();

            $content .= '<div class="row">';
            $content .= '<div class="col-md-4"><canvas id="canvas1" style="margin-bottom: 20px"></canvas></div>';
            $content .= '<div class="col-md-4"><canvas id="canvas2" style="margin-bottom: 20px"></canvas></div>';

            if ($sessionStatusAllowed) {
                $content .= '<div class="col-md-4"><canvas id="canvas3" style="margin-bottom: 20px"></canvas></div>';
            }
            $content .= '</div>';

            $content .= '<div class="row">';
            $content .= '<div class="col-md-12"><canvas id="canvas4" style="margin-bottom: 20px"></canvas></div>';
            $content .= '</div>';
        }

        $table = new HTML_Table(['class' => 'table table-responsive']);
        $headers = [
            get_lang('Name'),
            get_lang('StartDate'),
            get_lang('EndDate'),
            get_lang('Language'),
        ];
        if ($sessionStatusAllowed) {
            $headers[] = get_lang('Status');
        }
        $headers[] = get_lang('NumberOfStudents');
        $row = 0;
        $column = 0;
        foreach ($headers as $header) {
            $table->setHeaderContents($row, $column, $header);
            $column++;
        }
        $row++;

        foreach ($sessions as $session) {
            $courseList = SessionManager::getCoursesInSession($session['id']);
            $table->setCellContents($row, 0, $session['name']);
            $table->setCellContents($row, 1, api_get_local_time($session['display_start_date']));
            $table->setCellContents($row, 2, api_get_local_time($session['display_end_date']));

            // Get first language.
            $language = '';
            $courses = SessionManager::getCoursesInSession($session['id']);
            if (!empty($courses)) {
                $courseId = $courses[0];
                $courseInfo = api_get_course_info_by_id($courseId);
                $language = $courseInfo['language'];
                $language = get_lang(ucfirst(str_replace(2, '', $language)));
            }
            $table->setCellContents($row, 3, $language);

            if ($sessionStatusAllowed) {
                $table->setCellContents($row, 4, SessionManager::getStatusLabel($session['status']));
            }
            $studentsCount = SessionManager::get_users_by_session($session['id'], 0, true);
            $table->setCellContents($row, 5, $studentsCount);
            $row++;
        }

        $content .= $table->toHtml();

        if (isset($_REQUEST['action']) && 'export' === $_REQUEST['action']) {
            $data = $table->toArray();
            Export::arrayToXls($data);
            exit;
        }

        $link = '';
        if ($validated) {
            $url = api_get_self().'?report=session_by_date&action=export';
            if (!empty($values)) {
                foreach ($values as $index => $value) {
                    $url .= '&'.$index.'='.$value;
                }
            }
            $link = Display::url(
                Display::return_icon('excel.png').'&nbsp;'.get_lang('ExportAsXLS'),
                $url,
                ['class' => 'btn btn-default']
            );
        }

        $content = $form->returnForm().$content.$link;

        break;
    case 'user_session':
        $form = new FormValidator('user_session', 'get');
        $form->addDateRangePicker('range', get_lang('DateRange'));
        $form->addHidden('report', 'user_session');
        $form->addButtonSearch(get_lang('Search'));

        $date = new DateTime('now');
        $startDate = $date->format('Y-m-d').' 00:00:00';
        $endDate = $date->format('Y-m-d').' 23:59:59';
        $start = $startDate;
        $end = $endDate;

        if ($form->validate()) {
            $values = $form->getSubmitValues();
            $start = $values['range_start'];
            $end = $values['range_end'];
        }
        $content .= $form->returnForm();

        $url = api_get_path(WEB_AJAX_PATH).'statistics.ajax.php?a=get_user_session&start='.$start.'&end='.$end;
        $columns = [
            'URL',
            get_lang('Session'),
            get_lang('Course'),
            get_lang('CountUsers'),
        ];

        $columnModel = [
            [
                'name' => 'url',
                'index' => 'url',
                'width' => '120',
                'align' => 'left',
            ],
            [
                'name' => 'session',
                'index' => 'session',
                'width' => '180',
                'align' => 'left',
                'sortable' => 'false',
            ],
            [
                'name' => 'course',
                'index' => 'course',
                'width' => '100',
                'align' => 'left',
                'sortable' => 'false',
            ],
            [
                'name' => 'count',
                'index' => 'count',
                'width' => '50',
                'align' => 'left',
                'sortable' => 'false',
            ],
        ];
        $extraParams['autowidth'] = 'true'; //use the width of the parent
        $extraParams['height'] = 'auto'; //use the width of the parent
        $actionLinks = '';

        $content .= '
        <script>
            $(function() {
                '.Display::grid_js(
                'user_session_grid',
                $url,
                $columns,
                $columnModel,
                $extraParams,
                [],
                $actionLinks,
                true
            ).';

                jQuery("#user_session_grid").jqGrid("navGrid","#user_session_grid_pager",{
                    view:false,
                    edit:false,
                    add:false,
                    del:false,
                    search:false,
                    excel:true
                });

                jQuery("#user_session_grid").jqGrid("navButtonAdd","#user_session_grid_pager", {
                    caption:"",
                    onClickButton : function () {
                        jQuery("#user_session_grid").jqGrid("excelExport",{"url":"'.$url.'&export_format=xls"});
                    }
                });
            });
        </script>';

        $content .= Display::grid_html('user_session_grid');

        break;
    case 'courses':
        $content .= '<canvas class="col-md-12" id="canvas" height="300px" style="margin-bottom: 20px"></canvas>';
        // total amount of courses
        $courses = [];
        foreach ($course_categories as $code => $name) {
            $courses[$name] = Statistics::countCourses($code);
        }
        // courses for each course category
        $content .= Statistics::printStats(get_lang('CountCours'), $courses);
        break;
    case 'tools':
        $content .= '<canvas class="col-md-12" id="canvas" height="300px" style="margin-bottom: 20px"></canvas>';
        $content .= Statistics::printToolStats();
        break;
    case 'coursebylanguage':
        $content .= '<canvas class="col-md-12" id="canvas" height="300px" style="margin-bottom: 20px"></canvas>';
        $result = Statistics::printCourseByLanguageStats();
        $content .= Statistics::printStats(get_lang('CountCourseByLanguage'), $result);
        break;
    case 'courselastvisit':
        $content .= Statistics::printCourseLastVisit();
        break;
    case 'invoicing':
        if (!empty($invoicingMonth)) {
            $invoicingMonth = sprintf("%02d", $invoicingMonth);
            $currentMonth = $invoicingYear.'-'.$invoicingMonth;
            $lastMonth = date("Y-m", mktime(0, 0, 0, $invoicingMonth, 0, $invoicingYear));
        } else {
            $currentMonth = date("Y-m");
            $lastMonth = date("Y-m", strtotime('-1 month'));
            $invoicingMonth = date('m');
            $invoicingYear = date('Y');
        }
        $content .= Statistics::printInvoicingByAccessUrl($currentMonth, $lastMonth, $invoicingMonth, $invoicingYear);
        break;
    case 'users_active':
        $content = '';
        if ($validated) {
            $startDate = $values['daterange_start'];
            $endDate = $values['daterange_end'];

            $graph = '<div class="row">';
            $graph .= '<div class="col-md-4"><canvas id="canvas1" style="margin-bottom: 20px"></canvas></div>';
            $graph .= '<div class="col-md-4"><canvas id="canvas2" style="margin-bottom: 20px"></canvas></div>';
            $graph .= '<div class="col-md-4"><canvas id="canvas3" style="margin-bottom: 20px"></canvas></div>';
            $graph .= '</div>';

            $graph .= '<div class="row">';
            $graph .= '<div class="col-md-6"><canvas id="canvas4" style="margin-bottom: 20px"></canvas></div>';
            $graph .= '<div class="col-md-6"><canvas id="canvas8" style="margin-bottom: 20px"></canvas></div>';
            $graph .= '</div>';

            $graph .= '<div class="row">';
            $graph .= '<div class="col-md-6"><canvas id="canvas5" style="margin-bottom: 20px"></canvas></div>';
            $graph .= '<div class="col-md-6"><canvas id="canvas6" style="margin-bottom: 20px"></canvas></div>';
            $graph .= '</div>';

            $graph .= '<div class="row">';
            $graph .= '<div class="col-md-6"><canvas id="canvas7" style="margin-bottom: 20px"></canvas></div>';
            $graph .= '</div>';

            $conditions = [];
            $extraConditions = '';
            if (!empty($startDate) && !empty($endDate)) {
                // $extraConditions is already cleaned inside the function getUserListExtraConditions
                $extraConditions .= " AND registration_date BETWEEN '$startDate' AND '$endDate' ";
            }

            $totalCount = UserManager::getUserListExtraConditions(
                $conditions,
                [],
                false,
                false,
                null,
                $extraConditions,
                true
            );

            $pagination = 10;
            $table = new SortableTableFromArray(
                [],
                0,
                $pagination,
                'table_users_active',
                null,
                'table_users_active'
            );

            $table->actionButtons = [
                'export' => [
                    'label' => get_lang('ExportAsXLS'),
                    'icon' => Display::return_icon('excel.png'),
                ],
            ];

            $first = ($table->page_nr - 1) * $pagination;
            $limit = $table->page_nr * $pagination;

            $data = [];
            $headers = [
                get_lang('FirstName'),
                get_lang('LastName'),
                get_lang('RegistrationDate'),
                get_lang('UserNativeLanguage'),
                get_lang('LangueCible'),
                get_lang('ApprenticeshipContract'),
                get_lang('UserResidenceCountry'),
                get_lang('Career'),
                get_lang('Status'),
                get_lang('Active'),
                get_lang('Certificate'),
                get_lang('UserBirthday'),
            ];

            if (isset($_REQUEST['action_table']) && 'export' === $_REQUEST['action_table']) {
                $first = 0;
                $limit = $totalCount;
                $data[] = $headers;
            }

            if (isset($_REQUEST['table_users_active_per_page'])) {
                $limit = (int) $_REQUEST['table_users_active_per_page'];
            }

            $users = UserManager::getUserListExtraConditions(
                $conditions,
                [],
                $first,
                $limit,
                null,
                $extraConditions
            );

            $extraFieldValueUser = new ExtraFieldValue('user');
            foreach ($users as $user) {
                $userId = $user['user_id'];
                $userInfo = api_get_user_info($userId);

                $extraDataList = $extraFieldValueUser->getAllValuesByItem($userId);
                $extraFields = [];
                foreach ($extraDataList as $extraData) {
                    $extraFields[$extraData['variable']] = $extraData['value'];
                }

                $certificate = GradebookUtils::get_certificate_by_user_id(0, $userId);
                $language = $extraFields['langue_cible'] ?? '';
                //$contract = isset($extraFields['termactivated']) ? $extraFields['termactivated'] : '';
                $contract = false;
                $legalAccept = $extraFieldValueUser->get_values_by_handler_and_field_variable($userId, 'legal_accept');
                if ($legalAccept && !empty($legalAccept['value'])) {
                    list($legalId, $legalLanguageId, $legalTime) = explode(':', $legalAccept['value']);
                    if ($legalId) {
                        $contract = true;
                    }
                }

                $residence = $extraFields['terms_paysresidence'] ?? '';
                $career = $extraFields['filiere_user'] ?? '';
                $birthDate = $extraFields['terms_datedenaissance'] ?? '';

                $userLanguage = '';
                if (!empty($user['language'])) {
                    $userLanguage = get_lang(ucfirst(str_replace(2, '', $user['language'])));
                }

                $languageTarget = '';
                if (!empty($language)) {
                    $languageTarget = get_lang(ucfirst(str_replace(2, '', strtolower($language))));
                }

                $item = [];
                $item[] = $user['firstname'];
                $item[] = $user['lastname'];
                $item[] = api_get_local_time($user['registration_date']);
                $item[] = $userLanguage;
                $item[] = $languageTarget;
                $item[] = $contract ? get_lang('Yes') : get_lang('No');
                $item[] = $residence;
                $item[] = $career;
                $item[] = $userInfo['icon_status_label'];
                $item[] = 1 == $user['active'] ? get_lang('Yes') : get_lang('No');
                $item[] = $certificate ? get_lang('Yes') : get_lang('No');
                $item[] = $birthDate;
                $data[] = $item;
            }

            if (isset($_REQUEST['action_table']) && 'export' === $_REQUEST['action_table']) {
                Export::arrayToXls($data);
                exit;
            }

            $table->total_number_of_items = $totalCount;
            $table->table_data = $data;
            unset($values['submit']);
            $table->set_additional_parameters($values);
            $table->handlePagination = true;

            $row = 0;
            $column = 0;
            foreach ($headers as $header) {
                $table->set_header($column, $header, false);
                $column++;
            }

            $studentCount = UserManager::getUserListExtraConditions(
                ['status' => STUDENT],
                null,
                null,
                null,
                null,
                null,
                true
            );
            $content .= $table->return_table();

            $conditions = ['active' => 1];
            $active = UserManager::getUserListExtraConditions(
                $conditions,
                [],
                false,
                false,
                null,
                $extraConditions,
                true
            );
            $conditions = ['active' => 0];
            $noActive = UserManager::getUserListExtraConditions(
                $conditions,
                [],
                false,
                false,
                null,
                $extraConditions,
                true
            );

            $all = [
                get_lang('Active') => $active,
                get_lang('Inactive') => $noActive,
            ];

            $data = Statistics::buildJsChartData($all, $reportName1);
            $htmlHeadXtra[] = Statistics::getJSChartTemplateWithData(
                $data['chart'],
                'pie',
                $reportOptions1,
                'canvas1'
            );

            $scoreDisplay = ScoreDisplay::instance();
            $table = new HTML_Table(['class' => 'table table-hover table-striped data_table']);
            $headers = [
                get_lang('Name'),
                get_lang('Count'),
                get_lang('Percentage'),
            ];
            $row = 0;
            $column = 0;
            foreach ($headers as $header) {
                $table->setHeaderContents($row, $column, $header);
                $column++;
            }

            $row++;
            $table->setCellContents($row, 0, get_lang('Total'));
            $table->setCellContents($row, 1, $totalCount);
            $table->setCellContents($row, 2, '100 %');

            $row++;
            $total = 0;
            foreach ($all as $name => $value) {
                $total += $value;
            }
            foreach ($all as $name => $value) {
                $percentage = $scoreDisplay->display_score([$value, $total], SCORE_PERCENT);
                $table->setCellContents($row, 0, $name);
                $table->setCellContents($row, 1, $value);
                $table->setCellContents($row, 2, $percentage);
                $row++;
            }
            $extraTables = Display::page_subheader2($reportName1).$table->toHtml();

            // graph 2
            $extraFieldValueUser = new ExtraField('user');
            $extraField = $extraFieldValueUser->get_handler_field_info_by_field_variable('statusocial');

            if ($extraField) {
                $users = UserManager::getUserListExtraConditions(
                    [],
                    [],
                    false,
                    false,
                    null,
                    $extraConditions
                );

                $userIdList = array_column($users, 'user_id');
                $userIdListToString = implode("', '", $userIdList);

                $all = [];
                $total = count($users);
                $usersFound = 0;

                $extraFieldOption = new ExtraFieldOption('user');
                foreach ($extraField['options'] as $item) {
                    $value = Database::escape_string($item['option_value']);
                    $count = 0;
                    $sql = "SELECT count(id) count
                            FROM $extraFieldValueUser->table_field_values
                            WHERE
                            value = '$value' AND
                            item_id IN ('$userIdListToString') AND
                            field_id = ".$extraField['id'];
                    $query = Database::query($sql);
                    $result = Database::fetch_array($query);
                    $count = $result['count'];
                    $usersFound += $count;

                    $option = $extraFieldOption->get($item['id']);
                    $item['display_text'] = $option['display_text'];
                    $all[$item['display_text']] = $count;
                }
                $all[get_lang('N/A')] = $total - $usersFound;

                $data = Statistics::buildJsChartData($all, $reportName2);
                $htmlHeadXtra[] = Statistics::getJSChartTemplateWithData(
                    $data['chart'],
                    'pie',
                    $reportOptions2,
                    'canvas2'
                );
                $extraTables .= $data['table'];
            }

            // graph 3
            $languages = api_get_languages();
            $all = [];
            foreach ($languages['folder'] as $language) {
                $conditions = ['language' => $language];
                $key = $language;
                if ('2' === substr($language, -1)) {
                    $key = str_replace(2, '', $language);
                }

                $key = get_lang(ucfirst($key));
                if (!isset($all[$key])) {
                    $all[$key] = 0;
                }
                $all[$key] += UserManager::getUserListExtraConditions(
                    $conditions,
                    [],
                    false,
                    false,
                    null,
                    $extraConditions,
                    true
                );
            }

            $data = Statistics::buildJsChartData($all, $reportName3);
            $htmlHeadXtra[] = Statistics::getJSChartTemplateWithData(
                $data['chart'],
                'pie',
                $reportOptions3,
                'canvas3'
            );
            $extraTables .= $data['table'];

            // graph 4
            $extraFieldValueUser = new ExtraField('user');
            $extraField = $extraFieldValueUser->get_handler_field_info_by_field_variable('langue_cible');
            if ($extraField) {
                $users = UserManager::getUserListExtraConditions(
                    [],
                    [],
                    false,
                    false,
                    null,
                    $extraConditions
                );

                $userIdList = array_column($users, 'user_id');
                $userIdListToString = implode("', '", $userIdList);

                $all = [];
                $total = count($users);
                $usersFound = 0;
                foreach ($extraField['options'] as $item) {
                    $value = Database::escape_string($item['option_value']);
                    $count = 0;
                    $sql = "SELECT count(id) count
                            FROM $extraFieldValueUser->table_field_values
                            WHERE
                            value = '$value' AND
                            item_id IN ('$userIdListToString') AND
                            field_id = ".$extraField['id'];
                    $query = Database::query($sql);
                    $result = Database::fetch_array($query);
                    $count = $result['count'];
                    $usersFound += $count;

                    $item['display_text'] = get_lang(ucfirst(str_replace('2', '', strtolower($item['display_text']))));
                    $all[$item['display_text']] = $count;
                }
                $all[get_lang('N/A')] = $total - $usersFound;

                $data = Statistics::buildJsChartData($all, $reportName4);
                $htmlHeadXtra[] = Statistics::getJSChartTemplateWithData(
                    $data['chart'],
                    'pie',
                    $reportOptions4,
                    'canvas4'
                );
                $extraTables .= $data['table'];
            }

            // Graph Age
            $extraFieldValueUser = new ExtraField('user');
            $extraField = $extraFieldValueUser->get_handler_field_info_by_field_variable('terms_datedenaissance');
            if ($extraField) {
                $users = UserManager::getUserListExtraConditions(
                    [],
                    [],
                    false,
                    false,
                    null,
                    $extraConditions
                );

                $userIdList = array_column($users, 'user_id');
                $userIdListToString = implode("', '", $userIdList);

                $all = [];
                $total = count($users);

                $sql = "SELECT value
                        FROM $extraFieldValueUser->table_field_values
                        WHERE
                        item_id IN ('$userIdListToString') AND
                        field_id = ".$extraField['id'];
                $query = Database::query($sql);
                $usersFound = 0;
                $now = new DateTime();
                $all = [
                    //get_lang('N/A') => 0,
                    '16-17' => 0,
                    '18-25' => 0,
                    '26-30' => 0,
                ];

                while ($row = Database::fetch_array($query)) {
                    $usersFound++;
                    if (!empty($row['value'])) {
                        $validDate = DateTime::createFromFormat('Y-m-d', $row['value']);
                        $validDate = $validDate && $validDate->format('Y-m-d') === $row['value'];
                        if ($validDate) {
                            $date1 = new DateTime($row['value']);
                            $interval = $now->diff($date1);
                            $years = $interval->y;

                            if ($years >= 16 && $years <= 17) {
                                $all['16-17']++;
                            }
                            if ($years >= 18 && $years <= 25) {
                                $all['18-25']++;
                            }
                            if ($years >= 26 && $years <= 30) {
                                $all['26-30']++;
                            }
                        }
                    }
                }

                $data = Statistics::buildJsChartData($all, $reportName8);
                $htmlHeadXtra[] = Statistics::getJSChartTemplateWithData(
                    $data['chart'],
                    'pie',
                    $reportOptions8,
                    'canvas8'
                );
                $extraTables .= $data['table'];
            }

            // graph 5
            $extraFieldValueUser = new ExtraField('user');
            $extraField = $extraFieldValueUser->get_handler_field_info_by_field_variable('filiere_user');
            if ($extraField) {
                $all = [];
                $users = UserManager::getUserListExtraConditions(
                    [],
                    [],
                    false,
                    false,
                    null,
                    $extraConditions
                );

                $userIdList = array_column($users, 'user_id');
                $userIdListToString = implode("', '", $userIdList);
                $usersFound = 0;

                $total = count($users);
                $extraFieldOption = new ExtraFieldOption('user');
                foreach ($extraField['options'] as $item) {
                    $value = Database::escape_string($item['option_value']);
                    $count = 0;
                    $sql = "SELECT count(id) count
                            FROM $extraFieldValueUser->table_field_values
                            WHERE
                            value = '$value' AND
                            item_id IN ('$userIdListToString') AND
                            field_id = ".$extraField['id'];
                    $query = Database::query($sql);
                    $result = Database::fetch_array($query);
                    $count = $result['count'];
                    $option = $extraFieldOption->get($item['id']);
                    $item['display_text'] = $option['display_text'];
                    $all[$item['display_text']] = $count;
                    $usersFound += $count;
                }

                $all[get_lang('N/A')] = $total - $usersFound;

                $data = Statistics::buildJsChartData($all, $reportName5);
                $htmlHeadXtra[] = Statistics::getJSChartTemplateWithData(
                    $data['chart'],
                    'pie',
                    $reportOptions5,
                    'canvas5'
                );
                $extraTables .= $data['table'];
            }

            // graph 6
            $extraFieldValueUser = new ExtraField('user');
            $extraField = $extraFieldValueUser->get_handler_field_info_by_field_variable('termactivated');
            if ($extraField) {
                $users = UserManager::getUserListExtraConditions(
                    [],
                    [],
                    false,
                    false,
                    null,
                    $extraConditions
                );

                $userIdList = array_column($users, 'user_id');
                $userIdListToString = implode("', '", $userIdList);

                $all = [];
                $total = count($users);
                $sql = "SELECT count(id) count
                        FROM $extraFieldValueUser->table_field_values
                        WHERE
                        value = 1 AND
                        item_id IN ('$userIdListToString') AND
                        field_id = ".$extraField['id'];
                $query = Database::query($sql);
                $result = Database::fetch_array($query);
                $count = $result['count'];

                $all[get_lang('Yes')] = $count;
                $all[get_lang('No')] = $total - $count;

                $data = Statistics::buildJsChartData($all, $reportName6);
                $htmlHeadXtra[] = Statistics::getJSChartTemplateWithData(
                    $data['chart'],
                    'pie',
                    $reportOptions6,
                    'canvas6'
                );
                $extraTables .= $data['table'];
            }

            // Graph 7
            $extraFieldValueUser = new ExtraField('user');
            $extraField = $extraFieldValueUser->get_handler_field_info_by_field_variable('langue_cible');
            if ($extraField) {
                $users = UserManager::getUserListExtraConditions(
                    [],
                    [],
                    false,
                    false,
                    null,
                    $extraConditions
                );

                $total = count($users);
                $userIdList = array_column($users, 'user_id');
                $certificateCount = 0;
                foreach ($userIdList as $userId) {
                    $certificate = GradebookUtils::get_certificate_by_user_id(
                        0,
                        $userId
                    );

                    if (!empty($certificate)) {
                        $certificateCount++;
                    }
                }

                $all[get_lang('Yes')] = $certificateCount;
                $all[get_lang('No')] = $total - $certificateCount;

                $data = Statistics::buildJsChartData($all, $reportName7);
                $htmlHeadXtra[] = Statistics::getJSChartTemplateWithData(
                    $data['chart'],
                    'pie',
                    $reportOptions7,
                    'canvas7'
                );
                $extraTables .= $data['table'];
            }

            $header = Display::page_subheader2(get_lang('TotalNumberOfStudents').': '.$studentCount);
            $content = $header.$extraTables.$graph.$content;
        }

        $content = $form->returnForm().$content;

        break;
    case 'users_online':
        $table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
        $intervals = [3, 5, 30, 120];
        $counts = [];
        foreach ($intervals as $minutes) {
            $sql = "SELECT count(distinct(user_id))
                FROM $table WHERE
                DATE_ADD(tms, INTERVAL '$minutes' MINUTE) > UTC_TIMESTAMP()";
            $query = Database::query($sql);
            $counts[$minutes] = 0;
            if (Database::num_rows($query) > 0) {
                $row = Database::fetch_array($query);
                $counts[$minutes] = $row[0];
            }
        }
        $content = '<div class="pull-left">'.get_lang('UsersOnline').'</div>
        <div class="pull-right">'.api_get_local_time().'</div>
        <hr />
        <div class="tracking-course-summary">
            <div class="row">
                <div class="col-lg-3 col-sm-3">
                    <div class="panel panel-default tracking tracking-exercise">
                        <div class="panel-body">
                            <span class="tracking-icon">
                                <i class="fa fa-thermometer-4" aria-hidden="true"></i>
                            </span>
                            <div class="tracking-info">
                                <div class="tracking-text">'.get_lang('UsersOnline').' (3\')</div>
                                <div class="tracking-number">'.getOnlineUsersCount(3).'</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-sm-3">
                    <div class="panel panel-default tracking tracking-certificate">
                        <div class="panel-body">
                            <span class="tracking-icon">
                                <i class="fa fa-thermometer-3" aria-hidden="true"></i>
                            </span>
                            <div class="tracking-info">
                                <div class="tracking-text">'.get_lang('UsersOnline').' (5\')</div>
                                <div class="tracking-number">'.getOnlineUsersCount(5).'</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-sm-3">
                    <div class="panel panel-default tracking tracking-lessons">
                        <div class="panel-body">
                            <span class="tracking-icon">
                                <i class="fa fa-thermometer-2" aria-hidden="true"></i>
                            </span>
                            <div class="tracking-info">
                                <div class="tracking-text">'.get_lang('UsersOnline').' (30\')</div>
                                <div class="tracking-number">'.getOnlineUsersCount(30).'</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-sm-3">
                    <div class="panel panel-default tracking tracking-student">
                        <div class="panel-body">
                            <span class="tracking-icon">
                                <i class="fa fa-thermometer-1" aria-hidden="true"></i>
                            </span>
                            <div class="tracking-info">
                                <div class="tracking-text">'.get_lang('UsersOnline').' (120\')</div>
                                <div class="tracking-number">'.getOnlineUsersCount(120).'</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <div class="pull-left">'.get_lang('UsersActiveInATest').'</div>
        <hr />
        <div class="row">
            <div class="col-lg-3 col-sm-3">
                <div class="panel panel-default tracking tracking-exercise">
                    <div class="panel-body">
                        <span class="tracking-icon">
                            <i class="fa fa-thermometer-4" aria-hidden="true"></i>
                        </span>
                        <div class="tracking-info">
                            <div class="tracking-text">(3\')</div>
                            <div class="tracking-number">'.$counts[3].'</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-sm-3">
                <div class="panel panel-default tracking tracking-certificate">
                    <div class="panel-body">
                        <span class="tracking-icon">
                            <i class="fa fa-thermometer-3" aria-hidden="true"></i>
                        </span>
                        <div class="tracking-info">
                            <div class="tracking-text">(5\')</div>
                            <div class="tracking-number">'.$counts[5].'</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-sm-3">
                <div class="panel panel-default tracking tracking-lessons">
                    <div class="panel-body">
                        <span class="tracking-icon">
                            <i class="fa fa-thermometer-2" aria-hidden="true"></i>
                        </span>
                        <div class="tracking-info">
                            <div class="tracking-text">(30\')</div>
                            <div class="tracking-number">'.$counts[30].'</div>
                        </div>
                    </div>
                </div>
            </div>
             <div class="col-lg-3 col-sm-3">
                <div class="panel panel-default tracking tracking-student">
                    <div class="panel-body">
                        <span class="tracking-icon">
                            <i class="fa fa-thermometer-1" aria-hidden="true"></i>
                        </span>
                        <div class="tracking-info">
                            <div class="tracking-text">(120\')</div>
                            <div class="tracking-number">'.$counts[120].'</div>
                        </div>
                    </div>
                </div>
            </div>

        </div>';
        break;
    case 'users':
        $content .= '<div class="row">';
        $content .= '<div class="col-md-4"><canvas id="canvas1" style="margin-bottom: 20px"></canvas></div>';
        $content .= '<div class="col-md-4"><canvas id="canvas2" style="margin-bottom: 20px"></canvas></div>';
        $content .= '<div class="col-md-4"><canvas id="canvas3" style="margin-bottom: 20px"></canvas></div>';

        $content .= '</div>';
        // total amount of users
        $teachers = $students = [];
        $countInvisible = isset($_GET['count_invisible_courses']) ? intval($_GET['count_invisible_courses']) : null;
        $content .= Statistics::printStats(
            get_lang('NumberOfUsers'),
            [
                get_lang('Teachers') => Statistics::countUsers(COURSEMANAGER, null, $countInvisible),
                get_lang('Students') => Statistics::countUsers(STUDENT, null, $countInvisible),
            ]
        );
        foreach ($course_categories as $code => $name) {
            $name = str_replace(get_lang('Department'), '', $name);
            $teachers[$name] = Statistics::countUsers(COURSEMANAGER, $code, $countInvisible);
            $students[$name] = Statistics::countUsers(STUDENT, $code, $countInvisible);
        }
        // docents for each course category
        $content .= Statistics::printStats(get_lang('Teachers'), $teachers);
        // students for each course category
        $content .= Statistics::printStats(get_lang('Students'), $students);
        break;
    case 'recentlogins':
        $content .= '<h2>'.sprintf(get_lang('LastXDays'), '31').'</h2>';
        $form = new FormValidator(
            'session_time',
            'get',
            api_get_self().'?report=recentlogins&session_duration='.$sessionDuration
        );
        $sessionTimeList = ['', 5 => 5, 15 => 15, 30 => 30, 60 => 60];
        $form->addSelect('session_duration', [get_lang('SessionMinDuration'), get_lang('Minutes')], $sessionTimeList);
        $form->addButtonSend(get_lang('Filter'));
        $form->addHidden('report', 'recentlogins');
        $content .= $form->returnForm();

        $content .= '<canvas class="col-md-12" id="canvas" height="200px" style="margin-bottom: 20px"></canvas>';
        $content .= Statistics::printRecentLoginStats(false, $sessionDuration);
        $content .= Statistics::printRecentLoginStats(true, $sessionDuration);
        break;
    case 'logins':
        $content .= Statistics::printLoginStats($_GET['type']);
        break;
    case 'pictures':
        $content .= Statistics::printUserPicturesStats();
        break;
    case 'no_login_users':
        $content .= Statistics::printUsersNotLoggedInStats();
        break;
    case 'zombies':
        $content .= ZombieReport::create(['report' => 'zombies'])->display(true);
        break;
    case 'activities':
        $content .= Statistics::printActivitiesStats();
        break;
    case 'messagesent':
        $messages_sent = Statistics::getMessages('sent');
        $content .= Statistics::printStats(get_lang('MessagesSent'), $messages_sent);
        break;
    case 'messagereceived':
        $messages_received = Statistics::getMessages('received');
        $content .= Statistics::printStats(get_lang('MessagesReceived'), $messages_received);
        break;
    case 'friends':
        // total amount of friends
        $friends = Statistics::getFriends();
        $content .= Statistics::printStats(get_lang('CountFriends'), $friends);
        break;
    case 'logins_by_date':
        $content .= Statistics::printLoginsByDate();
        break;
    case 'lti_tool_lp':
        $content .= Statistics::printLtiLearningPath();
        break;
    case 'quarterly_report':

        $htmlHeadXtra[] = '<script>
                function loadReportQuarterlyUsers () {
                    $("#tracking-report-quarterly-users")
                        .html(\'<p><span class="fa fa-spinner fa-spin fa-2x" aria-hidden="true"></span></p>\')
                        .load(_p.web_ajax + "statistics.ajax.php?a=report_quarterly_users");
            }</script>';
        $htmlHeadXtra[] = '<script>
                function loadReportQuarterlyCourses () {
                    $("#tracking-report-quarterly-courses")
                        .html(\'<p><span class="fa fa-spinner fa-spin fa-2x" aria-hidden="true"></span></p>\')
                        .load(_p.web_ajax + "statistics.ajax.php?a=report_quarterly_courses");
            }</script>';

        $htmlHeadXtra[] = '<script>
                function loadReportQuarterlyHoursOfTraining () {
                    $("#tracking-report-quarterly-hours-of-training")
                        .html(\'<p><span class="fa fa-spinner fa-spin fa-2x" aria-hidden="true"></span></p>\')
                        .load(_p.web_ajax + "statistics.ajax.php?a=report_quarterly_hours_of_training");
            }</script>';

        $htmlHeadXtra[] = '<script>
                function loadReportQuarterlyCertificatesGenerated () {
                    $("#tracking-report-quarterly-number-of-certificates-generated")
                        .html(\'<p><span class="fa fa-spinner fa-spin fa-2x" aria-hidden="true"></span></p>\')
                        .load(_p.web_ajax + "statistics.ajax.php?a=report_quarterly_number_of_certificates_generated");
            }</script>';

        $htmlHeadXtra[] = '<script>
                function loadReportQuarterlySessionsByDuration () {
                    $("#tracking-report-quarterly-sessions-by-duration")
                        .html(\'<p><span class="fa fa-spinner fa-spin fa-2x" aria-hidden="true"></span></p>\')
                        .load(_p.web_ajax + "statistics.ajax.php?a=report_quarterly_sessions_by_duration");
            }</script>';

        $htmlHeadXtra[] = '<script>
                function loadReportQuarterlyCoursesAndSessions () {
                    $("#tracking-report-quarterly-courses-and-sessions")
                        .html(\'<p><span class="fa fa-spinner fa-spin fa-2x" aria-hidden="true"></span></p>\')
                        .load(_p.web_ajax + "statistics.ajax.php?a=report_quarterly_courses_and_sessions");
            }</script>';

        if (api_get_current_access_url_id() === 1) {
            $htmlHeadXtra[] = '<script>
                function loadReportQuarterlyTotalDiskUsage () {
                    $("#tracking-report-quarterly-total-disk-usage")
                        .html(\'<p><span class="fa fa-spinner fa-spin fa-2x" aria-hidden="true"></span></p>\')
                        .load(_p.web_ajax + "statistics.ajax.php?a=report_quarterly_total_disk_usage");
            }</script>';
        }

        $content .= Display::tag('H4', get_lang('ReportQuarterlyUsers'), ['style' => 'margin-bottom: 25px;']);
        $content .= Display::url(
            get_lang('Show'),
            'javascript://',
            ['onclick' => 'loadReportQuarterlyUsers();', 'class' => 'btn btn-default']
        );
        $content .= Display::div('', ['id' => 'tracking-report-quarterly-users', 'style' => 'margin: 30px;']);

        $content .= Display::tag('H4', get_lang('ReportQuarterlyCourses'), ['style' => 'margin-bottom: 25px;']);
        $content .= Display::url(
            get_lang('Show'),
            'javascript://',
            ['onclick' => 'loadReportQuarterlyCourses();', 'class' => 'btn btn-default']
        );
        $content .= Display::div('', ['id' => 'tracking-report-quarterly-courses', 'style' => 'margin: 30px;']);

        $content .= Display::tag('H4', get_lang('ReportQuarterlyHoursOfTraining'), ['style' => 'margin-bottom: 25px;']);
        $content .= Display::url(
            get_lang('Show'),
            'javascript://',
            ['onclick' => 'loadReportQuarterlyHoursOfTraining();', 'class' => 'btn btn-default']
        );
        $content .= Display::div(
            '',
            [
                'id' => 'tracking-report-quarterly-hours-of-training',
                'style' => 'margin: 30px;',
            ]
        );
        $content .= Display::tag(
            'H4',
            get_lang('ReportQuarterlyNumberOfCertificatesGenerated'),
            ['style' => 'margin-bottom: 25px;']
        );
        $content .= Display::url(
            get_lang('Show'),
            'javascript://',
            ['onclick' => 'loadReportQuarterlyCertificatesGenerated();', 'class' => 'btn btn-default']
        );
        $content .= Display::div(
            '',
            ['id' => 'tracking-report-quarterly-number-of-certificates-generated', 'style' => 'margin: 30px;']
        );
        $content .= Display::tag(
            'H4',
            get_lang('ReportQuarterlySessionsByDuration'),
            ['style' => 'margin-bottom: 25px;']
        );
        $content .= Display::url(
            get_lang('Show'),
            'javascript://',
            ['onclick' => 'loadReportQuarterlySessionsByDuration();', 'class' => 'btn btn-default']
        );
        $content .= Display::div(
            '',
            ['id' => 'tracking-report-quarterly-sessions-by-duration', 'style' => 'margin: 30px;']
        );
        $content .= Display::tag(
            'H4',
            get_lang('ReportQuarterlyCoursesAndSessions'),
            ['style' => 'margin-bottom: 25px;']
        );
        $content .= Display::url(
            get_lang('Show'),
            'javascript://',
            ['onclick' => 'loadReportQuarterlyCoursesAndSessions();', 'class' => 'btn btn-default']
        );
        $content .= Display::div(
            '',
            [
                'id' => 'tracking-report-quarterly-courses-and-sessions',
                'style' => 'margin: 30px;',
            ]
        );

        if (api_get_current_access_url_id() === 1) {
            $content .= Display::tag(
                'H4',
                get_lang('ReportQuarterlyTotalDiskUsage'),
                ['style' => 'margin-bottom: 25px;']
            );
            $content .= Display::url(
                get_lang('Show'),
                'javascript://',
                ['onclick' => 'loadReportQuarterlyTotalDiskUsage();', 'class' => 'btn btn-default']
            );
            $content .= Display::div(
                '',
                [
                    'id' => 'tracking-report-quarterly-total-disk-usage',
                    'style' => 'margin: 30px;',
                ]
            );
        }

        break;
    case 'duplicated_users':
        $interbreadcrumb[] = [
            'name' => $tool_name,
            'url' => 'index.php',
        ];

        $additionalExtraFieldsInfo = TrackingCourseLog::getAdditionalProfileExtraFields();

        $frmFields = TrackingCourseLog::displayAdditionalProfileFields([], api_get_self());
        $table = Statistics::returnDuplicatedUsersTable($additionalExtraFieldsInfo);

        if (isset($_GET['action_table'])) {
            $data = $table->toArray(true, true);

            if ('export_excel' === $_GET['action_table']) {
                Export::arrayToXls($data);
            } elseif ('export_csv' === $_GET['action_table']) {
                Export::arrayToCsv($data);
            }

            exit;
        }

        $htmlHeadXtra[] = '<script>'.UserManager::getScriptFunctionForActiveFilter().'</script>';

        $content .= Display::page_subheader2(get_lang('DuplicatedUsers'));
        $content .= Display::return_message(get_lang('ThisReportOnlyListsUsersThatHaveTheSameFirstnameAndLastname'));

        $content .= $frmFields;
        $content .= $table->return_table();
        break;
}

Display::display_header($tool_name);
echo Display::page_header($tool_name);
echo '<table><tr>';
foreach ($tools as $section => $items) {
    echo '<td style="vertical-align:top;">';
    echo '<h3>'.$section.'</h3>';
    echo '<ul>';
    foreach ($items as $key => $value) {
        echo '<li><a href="index.php?'.$key.'">'.$value.'</a></li>';
    }
    echo '</ul>';
    echo '</td>';
}
echo '</tr></table>';

//@todo: spaces between elements should be handled in the css, br should be removed if only there for presentation
echo '<br/><br/>';

echo $content;

Display::display_footer();

if (isset($_GET['export'])) {
    ob_end_clean();
}

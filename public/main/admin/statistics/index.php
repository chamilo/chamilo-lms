<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Enums\ActionIcon;
use Chamilo\CoreBundle\Framework\Container;

/**
 * This tool show global Statistics on general platform events.
 */
$cidReset = true;

require_once __DIR__.'/../../inc/global.inc.php';
api_protect_admin_script();

$interbreadcrumb[] = ['url' => '../index.php', 'name' => get_lang('Administration')];

$report = $_REQUEST['report'] ?? '';
$sessionDuration = isset($_GET['session_duration']) ? (int) $_GET['session_duration'] : '';
$validated = false;

if (
in_array(
    $report,
    ['recentlogins', 'tools', 'courses', 'coursebylanguage', 'users', 'users_active', 'session_by_date', 'new_user_registrations']
)
) {
    $htmlHeadXtra[] = api_get_build_js('libs/chartjs/chart.js');
    //$htmlHeadXtra[] = api_get_asset('chartjs-plugin-labels/build/chartjs-plugin-labels.min.js');
    // Prepare variables for the JS charts
    $url = $reportName = $reportType = $reportOptions = '';
    switch ($report) {
        case 'recentlogins':
            $url = api_get_path(
                    WEB_CODE_PATH
                ).'inc/ajax/statistics.ajax.php?a=recent_logins&session_duration='.$sessionDuration;
            $reportName = '';
            $reportType = 'line';
            $reportOptions = '';
            $htmlHeadXtra[] = Statistics::getJSChartTemplate($url, $reportType, $reportOptions);
            break;
        case 'tools':
            $url = api_get_path(WEB_CODE_PATH).'inc/ajax/statistics.ajax.php?a=tools_usage';
            $reportName = 'Tools access';
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
            $reportName = 'Courses count';
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
            $reportName = 'Courses count by language';
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
            $reportName1 = get_lang('Number of users');
            $reportName2 = get_lang('Trainers');
            $reportName3 = get_lang('Learners');
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
                get_lang('Date range'),
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

            $reportName1 = get_lang('Users created in the selected period');
            $reportName2 = get_lang('Users by status');
            $reportName3 = get_lang('Users per language');
            $reportName4 = get_lang('Users by target language');
            $reportName5 = get_lang('Users by career');
            $reportName6 = get_lang('Users by contract');
            $reportName7 = get_lang('Users by certificate');
            $reportName8 = get_lang('Users by age');

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
                get_lang('Date range'),
                true,
                ['format' => 'YYYY-MM-DD', 'timePicker' => 'false', 'validate_format' => 'Y-m-d']
            );
            $options = SessionManager::getStatusList();
            $form->addSelect('status_id', get_lang('Session status'), $options, ['placeholder' => get_lang('All')]);

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

                $statusId = (int) $_REQUEST['status_id'];

                $conditions = "&date_start=$dateStart&date_end=$dateEnd&status=$statusId";

                $url1 = $urlBase.'a=session_by_date&filter=category'.$conditions;
                $url2 = $urlBase.'a=session_by_date&filter=language'.$conditions;
                $url3 = $urlBase.'a=session_by_date&filter=status'.$conditions;
                $url4 = $urlBase.'a=session_by_date&filter=course_in_session'.$conditions;

                $reportName1 = get_lang('Sessions per category');
                $reportName2 = get_lang('Sessions per language');
                $reportName3 = get_lang('Sessions per status');
                $reportName4 = get_lang('Courses in sessions');

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
        'report=courses' => get_lang('Courses'),
        'report=tools' => get_lang('Tools access'),
        'report=tool_usage' => get_lang('Tool-based resource count'),
        'report=courselastvisit' => get_lang('Latest access'),
        'report=coursebylanguage' => get_lang('Number of courses by language'),
    ],
    get_lang('Users') => [
        'report=users' => get_lang('Number of users'),
        'report=recentlogins' => get_lang('Logins'),
        'report=logins&amp;type=month' => get_lang('Logins').' ('.get_lang('Month').')',
        'report=logins&amp;type=day' => get_lang('Logins').' ('.get_lang('Day').')',
        'report=logins&amp;type=hour' => get_lang('Logins').' ('.get_lang('Hour').')',
        'report=pictures' => get_lang('Number of users').' ('.get_lang('Picture').')',
        'report=logins_by_date' => get_lang('Logins by date'),
        'report=no_login_users' => get_lang('Not logged in for some time'),
        'report=zombies' => get_lang('Zombies'),
        'report=users_active' => get_lang('Users statistics'),
        'report=users_online' => get_lang('Users online'),
        'report=new_user_registrations' => get_lang('New users registrations'),
        'report=subscription_by_day' => get_lang('Course/Session subscriptions by day'),
    ],
    get_lang('System') => [
        'report=activities' => get_lang('Important activities'),
        'report=user_session' => get_lang('Portal user session stats'),
        'report=quarterly_report' => get_lang('Quarterly report'),
    ],
    get_lang('Social') => [
        'report=messagereceived' => get_lang('Number of messages received'),
        'report=messagesent' => get_lang('Number of messages sent'),
        'report=friends' => get_lang('Contacts count'),
    ],
    get_lang('Session') => [
        'report=session_by_date' => get_lang('Sessions by date'),
    ],
];

$content = '';

switch ($report) {
    case 'subscription_by_day':
        $form = new FormValidator('subscription_by_day', 'get', api_get_self());
        $form->addDateRangePicker('daterange', get_lang('Date range'), true, [
            'format' => 'YYYY-MM-DD',
            'timePicker' => 'false',
            'validate_format' => 'Y-m-d'
        ]);
        $form->addHidden('report', 'subscription_by_day');
        $form->addButtonSearch(get_lang('Search'));
        $validated = $form->validate() || isset($_REQUEST['daterange']);

        if ($validated) {
            $values = $form->getSubmitValues();
            $dateStart = Security::remove_XSS($values['daterange_start']);
            $dateEnd = Security::remove_XSS($values['daterange_end']);

            $dates = [];
            $period = new DatePeriod(
                new DateTime($dateStart),
                new DateInterval('P1D'),
                (new DateTime($dateEnd))->modify('+1 day')
            );
            foreach ($period as $date) {
                $key = $date->format('Y-m-d');
                $dates[$key] = [
                    'subscriptions' => 0,
                    'unsubscriptions' => 0,
                ];
            }

            $subscriptions = Statistics::getSubscriptionsByDay($dateStart, $dateEnd);
            foreach ($subscriptions as $item) {
                $dates[$item['date']]['subscriptions'] = $item['count'];
            }

            $unsubscriptions = Statistics::getUnsubscriptionsByDay($dateStart, $dateEnd);
            foreach ($unsubscriptions as $item) {
                $dates[$item['date']]['unsubscriptions'] = $item['count'];
            }

            $labels = array_keys($dates);
            $subscriptionsData = array_map(fn($v) => $v['subscriptions'] ?? 0, $dates);
            $unsubscriptionsData = array_map(fn($v) => $v['unsubscriptions'] ?? 0, $dates);

            $chartData = [
                'labels' => $labels,
                'datasets' => [
                    ['label' => get_lang('Subscriptions'), 'data' => $subscriptionsData],
                    ['label' => get_lang('Unsubscriptions'), 'data' => $unsubscriptionsData],
                ]
            ];

            $htmlHeadXtra[] = Statistics::getJSChartTemplateWithData(
                $chartData,
                'bar',
                'title: { text: "'.get_lang('Subscriptions vs Unsubscriptions by day').'", display: true }',
                'subscriptions_chart',
                true
            );

            $content .= '<canvas id="subscriptions_chart"></canvas>';
            $table = new HTML_Table(['class' => 'table table-hover table-striped table-bordered data_table']);

            $table->setHeaderContents(0, 0, get_lang('Date'));
            $table->setHeaderContents(0, 1, get_lang('Subscriptions'));
            $table->setHeaderContents(0, 2, get_lang('Unsubscriptions'));

            $row = 1;
            foreach ($dates as $date => $values) {
                $table->setCellContents($row, 0, $date);
                $table->setCellContents($row, 1, $values['subscriptions'] ?? 0);
                $table->setCellContents($row, 2, $values['unsubscriptions'] ?? 0);
                $row++;
            }
            $content .= $table->toHtml();
        }

        $content = $form->returnForm() . $content;
        break;
    case 'session_by_date':
        $sessions = [];
        if ($validated) {
            $values = $form->getSubmitValues();
            $dateStart = $values['range_start'];
            $dateEnd = $values['range_end'];
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
            $tableSessionRelUser = Database::get_main_table(TABLE_MAIN_SESSION_USER);
            $sql = "SELECT * FROM $tableSession
                    WHERE
                        (display_start_date BETWEEN '$start' AND '$end' OR
                        display_end_date BETWEEN '$start' AND '$end')
                        $statusCondition
                    ";
            $result = Database::query($sql);

            $sessionCount = 0;
            $numberUsers = 0;
            while ($row = Database::fetch_assoc($result)) {
                $sessions[] = $row;
                $numberUsers += $row['nbr_users'];
                $sessionCount++;
            }

            $content .= Display::page_subheader2(get_lang('Global statistics'));
            // Coach.
            $sql = "SELECT COUNT(DISTINCT(sru.user_id)) count
                    FROM $tableSession s
                    INNER JOIN $tableSessionRelUser sru
                    ON s.id = sru.session_id
                    WHERE
                        (s.display_start_date BETWEEN '$start' AND '$end' OR
                        s.display_end_date BETWEEN '$start' AND '$end') AND
                        sru.relation_type = ".Session::GENERAL_COACH."
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

            $table->setCellContents($row, 0, get_lang('Sessions count'));
            $table->setCellContents($row, 1, $sessionCount);
            $row++;

            $table->setCellContents($row, 0, get_lang('Sessions per week'));
            $table->setCellContents($row, 1, $sessionAverage);
            $row++;

            $table->setCellContents($row, 0, get_lang('Average number of users per session'));
            $table->setCellContents($row, 1, $averageUser);
            $row++;

            $table->setCellContents($row, 0, get_lang('Average number of sessions per general session coach'));
            $table->setCellContents($row, 1, $averageCoach);
            $row++;

            $content .= $table->toHtml();

            $content .= '<div class="grid grid-cols-3 gap-4">';
            $content .= '<div><h4 class="text-center" id="canvas1_title"></h4><div id="canvas1_table"></div></div>';
            $content .= '<div><h4 class="text-center" id="canvas2_title"></h4><div id="canvas2_table"></div></div>';
            $content .= '<div><h4 class="text-center" id="canvas3_title"></h4><div id="canvas3_table"></div></div>';
            $content .= '</div>';

            $tableCourse = new HTML_Table(['class' => 'table table-responsive']);
            $headers = [
                get_lang('Course'),
                get_lang('Sessions count'),
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

            $content .= '<div class="grid grid-cols-3 gap-4">';
            $content .= '<div><canvas id="canvas1" class="mb-5"></canvas></div>';
            $content .= '<div><canvas id="canvas2" class="mb-5"></canvas></div>';
            $content .= '<div><canvas id="canvas3" class="mb-5"></canvas></div>';
            $content .= '</div>';

            $content .= '<div class="grid grid-cols-1">';
            $content .= '<div><canvas id="canvas4" class="mb-5"></canvas></div>';
            $content .= '</div>';
        }

        $table = new HTML_Table(['class' => 'table table-responsive']);
        $headers = [
            get_lang('Name'),
            get_lang('Start date'),
            get_lang('End date'),
            get_lang('Language'),
            get_lang('Status'),
        ];
        $headers[] = get_lang('Total number of students');
        $row = 0;
        $column = 0;
        foreach ($headers as $header) {
            $table->setHeaderContents($row, $column, $header);
            $column++;
        }
        $row++;

        foreach ($sessions as $session) {
            $courseList = SessionManager::getCoursesInSession($session['id']);
            $table->setCellContents($row, 0, $session['title']);
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
            $table->setCellContents($row, 4, SessionManager::getStatusLabel($session['status']));
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
                Display::getMdiIcon(ActionIcon::EXPORT_SPREADSHEET, 'ch-tool-icon').'&nbsp;'.get_lang('Export to XLS'),
                $url,
                ['class' => 'btn btn--plain']
            );
        }

        $content = $form->returnForm().$content.$link;

        break;
    case 'user_session':
        $form = new FormValidator('user_session', 'get');
        $form->addDateRangePicker('range', get_lang('Date range'), true);
        $form->addHidden('report', 'user_session');
        $form->addButtonSearch(get_lang('Search'));

        $date = new DateTime();
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
            get_lang('Number of users'),
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
        $courseCategoryRepo = Container::getCourseCategoryRepository();
        $categories = $courseCategoryRepo->findAll();
        $content .= '<canvas class="col-md-12" id="canvas" height="300px" style="margin-bottom: 20px"></canvas>';
        // total amount of courses
        foreach ($categories as $category) {
            /* @var Chamilo\CoreBundle\Entity\CourseCategory $category */
            $courses[$category->getTitle()] = $category->getCourses()->count();
        }
        // courses for each course category
        $content .= Statistics::printStats(get_lang('Courses'), $courses);

        $content .= '
            <button class="btn btn--info mb-3" onclick="toggleNonRegisteredUsers()">
                '.get_lang('Show/Hide users active in open courses (not enrolled)').'
            </button>

            <div id="non-registered-users-block" style="display: none; margin-top: 10px;">
        ';

        $sessionId = api_get_session_id();
        $userList = Statistics::getUsersWithActivityButNotRegistered($sessionId);

        if (!empty($userList)) {
            $content .= Display::page_subheader2(get_lang('Users active in open courses (not enrolled)'));
            $content .= Display::tag('p', get_lang('The following users have accessed one or more courses without being officially registered. They generated activity in open courses but are not listed in the course subscription tables.'));
            $table = new HTML_Table(['class' => 'table table-hover table-striped data_table']);
            $table->setHeaderContents(0, 0, get_lang('Name'));
            $table->setHeaderContents(0, 1, get_lang('Course'));
            $table->setHeaderContents(0, 2, get_lang('Last access'));
            $row = 1;
            foreach ($userList as $user) {
                $name = Display::tag('strong', $user['firstname'].' '.$user['lastname']);
                $course = Display::tag('em', $user['courseTitle'].' ('.$user['courseCode'].')');
                $access = Security::remove_XSS($user['lastAccess']);

                $table->setCellContents($row, 0, $name);
                $table->setCellContents($row, 1, $course);
                $table->setCellContents($row, 2, $access);
                $row++;
            }
            $content .= $table->toHtml();
        } else {
            $content .= Display::tag('p', get_lang('No users found with activity in open courses without enrollment.'));
        }
        $content .= '</div>';
        $content .= '
        <script>
            function toggleNonRegisteredUsers() {
                const block = document.getElementById("non-registered-users-block");
                block.style.display = block.style.display === "none" ? "block" : "none";
            }
        </script>';

        break;
    case 'tools':
        $content .= '<canvas class="col-md-12" id="canvas" height="300px" style="margin-bottom: 20px"></canvas>';
        $content .= Statistics::printToolStats();
        break;
    case 'tool_usage':
        $courseTools = Statistics::getAvailableTools();

        if (empty($courseTools)) {
            $content .= '<div class="alert alert-info">'.get_lang('No tools available for this report').'</div>';
            break;
        }

        $form = new FormValidator('tool_usage', 'get');
        $form->addHeader(get_lang('Tool-based resource count'));
        $form->addSelect(
            'tool_ids',
            get_lang('Select Tools'),
            $courseTools,
            ['multiple' => true, 'required' => true]
        );
        $form->addButtonSearch(get_lang('Generate report'));
        $form->addHidden('report', 'tool_usage');

        $content .= $form->returnForm();

        if ($form->validate()) {
            $values = $form->getSubmitValues();
            $toolIds = $values['tool_ids'];
            $reportData = Statistics::getToolUsageReportByTools($toolIds);

            $table = new HTML_Table(['class' => 'table table-hover table-striped data_table stats_table']);
            $headers = [
                get_lang('Tool'),
                get_lang('Session'),
                get_lang('Course'),
                get_lang('Resource count'),
                get_lang('Last updated'),
            ];
            $row = 0;

            foreach ($headers as $index => $header) {
                $table->setHeaderContents($row, $index, $header);
            }
            $row++;

            foreach ($reportData as $data) {
                $linkHtml = $data['link'] !== '-'
                    ? sprintf(
                        '<a href="%s" class="text-blue-500 underline hover:text-blue-700" target="_self">%s</a>',
                        $data['link'],
                        htmlspecialchars($data['tool_name'])
                    )
                    : htmlspecialchars($data['tool_name']);

                $table->setCellContents($row, 0, $linkHtml);
                $table->setCellContents($row, 1, htmlspecialchars($data['session_name']));
                $table->setCellContents($row, 2, htmlspecialchars($data['course_name']));
                $table->setCellContents($row, 3, (int) $data['resource_count']);
                $table->setCellContents($row, 4, htmlspecialchars($data['last_updated']));
                $row++;
            }
            $content .= $table->toHtml();
        }
        break;
    case 'coursebylanguage':
        $content .= '<canvas class="col-md-12" id="canvas" height="300px" style="margin-bottom: 20px"></canvas>';
        $result = Statistics::printCourseByLanguageStats();
        $content .= Statistics::printStats(get_lang('Number of courses by language'), $result, true);
        break;
    case 'courselastvisit':
        $content .= Statistics::printCourseLastVisit();
        break;
    case 'users_active':
        $content = '';
        if ($validated) {
            $startDate = $values['daterange_start'];
            $endDate = $values['daterange_end'];

            $graph = '<div class="grid grid-cols-3 gap-4">';
            $graph .= '<div><canvas id="canvas1" class="mb-5 mt-5 mx-auto"></canvas></div>';
            $graph .= '<div><canvas id="canvas2" class="mb-5 mt-5 mx-auto"></canvas></div>';
            $graph .= '<div><canvas id="canvas3" class="mb-5 mt-5 mx-auto"></canvas></div>';
            $graph .= '</div>';

            $graph .= '<div class="grid grid-cols-2 gap-4">';
            $graph .= '<div><canvas id="canvas4" class="mb-5 mt-5 mx-auto"></canvas></div>';
            $graph .= '<div><canvas id="canvas8" class="mb-5 mt-5 mx-auto"></canvas></div>';
            $graph .= '</div>';

            $graph .= '<div class="grid grid-cols-2 gap-4">';
            $graph .= '<div><canvas id="canvas5" class="mb-5 mt-5 mx-auto"></canvas></div>';
            $graph .= '<div><canvas id="canvas6" class="mb-5 mt-5 mx-auto"></canvas></div>';
            $graph .= '</div>';

            $graph .= '<div class="grid grid-cols-2 gap-4">';
            $graph .= '<div><canvas id="canvas7" class="mb-5 mt-5 mx-auto"></canvas></div>';
            $graph .= '</div>';

            $conditions = [];
            $extraConditions = '';
            if (!empty($startDate) && !empty($endDate)) {
                // $extraConditions is already cleaned inside the function getUserListExtraConditions
                $extraConditions .= " AND created_at BETWEEN '$startDate' AND '$endDate' ";
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
                    'label' => get_lang('Export to XLS'),
                    'icon' => Display::getMdiIcon(ActionIcon::EXPORT_SPREADSHEET,'ch-tool-icon'),
                ],
            ];

            $first = ($table->page_nr - 1) * $pagination;
            $limit = $table->page_nr * $pagination;

            $data = [];
            $headers = [
                get_lang('FirstName'),
                get_lang('LastName'),
                get_lang('Registration date'),
                get_lang('Native language'),
                get_lang('Users by target language'),
                get_lang('Apprenticeship contract'),
                get_lang('Country of residence'),
                get_lang('Career'),
                get_lang('Status'),
                get_lang('Active'),
                get_lang('Certificate'),
                get_lang('Birthday'),
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
            $languages = api_get_languages();
            foreach ($users as $user) {
                $userId = $user['user_id'];
                $userInfo = api_get_user_info($userId);

                $extraDataList = $extraFieldValueUser->getAllValuesByItem($userId);
                $extraFields = [];
                foreach ($extraDataList as $extraData) {
                    $extraFields[$extraData['variable']] = $extraData['value'];
                }

                $certificate = GradebookUtils::get_certificate_by_user_id(0, $userId);
                $language = isset($extraFields['langue_cible']) ? $extraFields['langue_cible'] : '';

                $contract = false;
                $legalAccept = $extraFieldValueUser->get_values_by_handler_and_field_variable($userId, 'legal_accept');
                if ($legalAccept && !empty($legalAccept['value'])) {
                    list($legalId, $legalLanguageId, $legalTime) = explode(':', $legalAccept['value']);
                    if ($legalId) {
                        $contract = true;
                    }
                }

                $residence = isset($extraFields['terms_paysresidence']) ? $extraFields['terms_paysresidence'] : '';
                $career = isset($extraFields['filiere_user']) ? $extraFields['filiere_user'] : '';
                $birthDate = isset($extraFields['terms_datedenaissance']) ? $extraFields['terms_datedenaissance'] : '';

                $userLanguage = '';
                if (!empty($user['locale'])) {
                    $userLanguage = $languages[$user['locale']] ?? 'en';
                }

                $languageTarget = '';
                if (!empty($language)) {
                    $languageTarget = get_lang(ucfirst(str_replace(2, '', strtolower($language))));
                }

                $item = [];
                $item[] = $user['firstname'];
                $item[] = $user['lastname'];
                $item[] = api_get_local_time($user['created_at']);
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
                'canvas1',
                false
            );

            $scoreDisplay = ScoreDisplay::instance();
            $table = new HTML_Table(['class' => 'table table-hover table-striped data_table stats_table']);
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
                    $extraConditions,
                    false
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
                            field_value = '$value' AND
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
                $all[get_lang('Not available')] = $total - $usersFound;

                $data = Statistics::buildJsChartData($all, $reportName2);
                $htmlHeadXtra[] = Statistics::getJSChartTemplateWithData(
                    $data['chart'],
                    'pie',
                    $reportOptions2,
                    'canvas2',
                    false
                );
                $extraTables .= $data['table'];
            }

            // graph 3
            $languages = api_get_languages();
            $all = [];
            foreach ($languages as $locale => $language) {
                $conditions = ['locale' => $locale];
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
                'canvas3',
                false
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
                    $extraConditions,
                    false
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
                            field_value = '$value' AND
                            item_id IN ('$userIdListToString') AND
                            field_id = ".$extraField['id'];
                    $query = Database::query($sql);
                    $result = Database::fetch_array($query);
                    $count = $result['count'];
                    $usersFound += $count;

                    $item['display_text'] = get_lang(ucfirst(str_replace('2', '', strtolower($item['display_text']))));
                    $all[$item['display_text']] = $count;
                }
                $all[get_lang('Not available')] = $total - $usersFound;

                $data = Statistics::buildJsChartData($all, $reportName4);
                $htmlHeadXtra[] = Statistics::getJSChartTemplateWithData(
                    $data['chart'],
                    'pie',
                    $reportOptions4,
                    'canvas4',
                    false
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
                    $extraConditions,
                    false
                );

                $userIdList = array_column($users, 'user_id');
                $userIdListToString = implode("', '", $userIdList);

                $all = [];
                $total = count($users);

                $sql = "SELECT field_value
                        FROM $extraFieldValueUser->table_field_values
                        WHERE
                        item_id IN ('$userIdListToString') AND
                        field_id = ".$extraField['id'];
                $query = Database::query($sql);
                $usersFound = 0;
                $now = new DateTime();
                $all = [
                    //get_lang('Not available') => 0,
                    '16-17' => 0,
                    '18-25' => 0,
                    '26-30' => 0,
                ];

                while ($row = Database::fetch_array($query)) {
                    $usersFound++;
                    if (!empty($row['field_value'])) {
                        $validDate = DateTime::createFromFormat('Y-m-d', $row['field_value']);
                        $validDate = $validDate && $validDate->format('Y-m-d') === $row['field_value'];
                        if ($validDate) {
                            $date1 = new DateTime($row['field_value']);
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
                    'canvas8',
                    false
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
                    $extraConditions,
                    false
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
                            field_value = '$value' AND
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

                $all[get_lang('Not available')] = $total - $usersFound;

                $data = Statistics::buildJsChartData($all, $reportName5);
                $htmlHeadXtra[] = Statistics::getJSChartTemplateWithData(
                    $data['chart'],
                    'pie',
                    $reportOptions5,
                    'canvas5',
                    false
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
                    $extraConditions,
                    false
                );

                $userIdList = array_column($users, 'user_id');
                $userIdListToString = implode("', '", $userIdList);

                $all = [];
                $total = count($users);
                $sql = "SELECT count(id) count
                        FROM $extraFieldValueUser->table_field_values
                        WHERE
                        field_value = 1 AND
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
                    'canvas6',
                    false
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
                    $extraConditions,
                    false
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
                    'canvas7',
                    false
                );
                $extraTables .= $data['table'];
            }

            $header = Display::page_subheader2(get_lang('Total number of students').': '.$studentCount);
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
        $content = '<div class="pull-left">'.get_lang('Users online').'</div>
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
                                <div class="tracking-text">'.get_lang('Users online').' (3\')</div>
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
                                <div class="tracking-text">'.get_lang('Users online').' (5\')</div>
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
                                <div class="tracking-text">'.get_lang('Users online').' (30\')</div>
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
                                <div class="tracking-text">'.get_lang('Users online').' (120\')</div>
                                <div class="tracking-number">'.getOnlineUsersCount(120).'</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <div class="pull-left">'.get_lang('Users active in a test').'</div>
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
    case 'new_user_registrations':
        $form = new FormValidator('new_user_registrations', 'get', api_get_self());
        $form->addDateRangePicker('daterange', get_lang('Date range'), true, [
            'format' => 'YYYY-MM-DD',
            'timePicker' => 'false',
            'validate_format' => 'Y-m-d'
        ]);
        $form->addHidden('report', 'new_user_registrations');
        $form->addButtonSearch(get_lang('Search'));

        $validated = $form->validate() || isset($_REQUEST['daterange']);
        $chartContent = '';
        $chartCreatorContent = '';
        $textChart = '';
        if ($validated) {
            $values = $form->getSubmitValues();
            $dateStart = Security::remove_XSS($values['daterange_start']);
            $dateEnd = Security::remove_XSS($values['daterange_end']);

            $all = Statistics::initializeDateRangeArray($dateStart, $dateEnd);
            $registrations = Statistics::getNewUserRegistrations($dateStart, $dateEnd);

            if (empty($registrations)) {
                $content .= '<div class="alert alert-info">' . get_lang('No data available for the selected date range') . '</div>';
            } else {
                if (Statistics::isMoreThanAMonth($dateStart, $dateEnd)) {
                    $textChart = get_lang('User registrations by month');
                    $all = Statistics::groupByMonth($registrations);
                    $chartData = Statistics::buildJsChartData($all, get_lang('User registrations by month'));

                    // Allow clicks only when showing by month
                    $onClickHandler = '
                    var activePoints = chart.getElementsAtEventForMode(evt, "nearest", { intersect: true }, false);
                    if (activePoints.length > 0) {
                        var firstPoint = activePoints[0];
                        var label = chart.data.labels[firstPoint.index];
                        var yearMonth = label.split("-");
                        var year = yearMonth[0];
                        var month = yearMonth[1];
                        $.ajax({
                            url: "/main/inc/ajax/statistics.ajax.php?a=get_user_registration_by_day",
                            type: "POST",
                            data: { year: year, month: month },
                            success: function(response) {
                                var dailyData = JSON.parse(response);
                                chart.data.labels = dailyData.labels;
                                chart.data.datasets[0].data = dailyData.data;
                                chart.data.datasets[0].label = "User Registrations for " + year + "-" + month;
                                chart.update();

                                $("#backButton").show();
                            }
                        });
                    }';
                } else {
                    $textChart = get_lang('User registrations by day');
                    foreach ($registrations as $registration) {
                        $date = $registration['date'];
                        if (isset($all[$date])) {
                            $all[$date] += $registration['count'];
                        }
                    }
                    $chartData = Statistics::buildJsChartData($all, $textChart);
                    $onClickHandler = '';
                }

                $htmlHeadXtra[] = Statistics::getJSChartTemplateWithData(
                    $chartData['chart'],
                    'bar',
                    'title: { text: "'.$textChart.'", display: true },
                            scales: {
                                x: { beginAtZero: true },
                                y: { barPercentage: 0.4, categoryPercentage: 0.5, barThickness: 10, maxBarThickness: 15 }
                            },
                            layout: {
                                padding: { left: 10, right: 10, top: 10, bottom: 10 }
                            }',
                    'user_registration_chart',
                    true,
                    $onClickHandler,
                    '
                            $("#backButton").click(function() {
                                $.ajax({
                                    url: "/main/inc/ajax/statistics.ajax.php?a=get_user_registration_by_month",
                                    type: "POST",
                                    data: { date_start: "'.$dateStart.'", date_end: "'.$dateEnd.'" },
                                    success: function(response) {
                                        var monthlyData = JSON.parse(response);
                                        chart.data.labels = monthlyData.labels;
                                        chart.data.datasets[0].data = monthlyData.data;
                                        chart.data.datasets[0].label = "'.get_lang('User registrations by month').'";
                                        chart.update();
                                        $("#backButton").hide();
                                    }
                                });
                            });
                        '
                );

                $chartContent .= '<canvas id="user_registration_chart"></canvas>';
                $chartContent .= '<button id="backButton" style="display:none;" class="btn btn--info">'.get_lang('Back to months').'</button>';

                $creators = Statistics::getUserRegistrationsByCreator($dateStart, $dateEnd);
                if (!empty($creators)) {
                    $chartCreatorContent = '<hr />';
                    $creatorLabels = [];
                    $creatorData = [];
                    foreach ($creators as $creator) {
                        $creatorLabels[] = $creator['name'];
                        $creatorData[] = $creator['count'];
                    }

                    $htmlHeadXtra[] = Statistics::getJSChartTemplateWithData(
                        ['labels' => $creatorLabels, 'datasets' => [['label' => get_lang('User registrations by creator'), 'data' => $creatorData]]],
                        'pie',
                        'title: { text: "'.get_lang('User registrations by creator').'", display: true },
                        legend: { position: "top" },
                        layout: {
                            padding: { left: 10, right: 10, top: 10, bottom: 10 }
                        }',
                        'user_registration_by_creator_chart',
                        false,
                        '',
                        '',
                        ['width' => 700, 'height' => 700]
                    );

                    $chartCreatorContent .= '<canvas id="user_registration_by_creator_chart"></canvas>';
            }
        }
    }

    $content .= $form->returnForm();
    $content .= $chartContent;
    $content .= $chartCreatorContent;

    break;
    case 'users':
        $content .= '<div class="grid grid-cols-3 gap-4">';
        $content .= '<div><canvas id="canvas1" class="mb-5"></canvas></div>';
        $content .= '<div><canvas id="canvas2" class="mb-5"></canvas></div>';
        $content .= '<div><canvas id="canvas3" class="mb-5"></canvas></div>';
        $content .= '</div>';

        // total amount of users
        $teachers = $students = [];
        $countInvisible = isset($_GET['count_invisible_courses']) ? intval($_GET['count_invisible_courses']) : null;
        $content .= Statistics::printStats(
            get_lang('Number of users'),
            [
                get_lang('Trainers') => Statistics::countUsers(COURSEMANAGER, null, $countInvisible),
                get_lang('Learners') => Statistics::countUsers(STUDENT, null, $countInvisible),
            ]
        );
        $courseCategoryRepo = Container::getCourseCategoryRepository();
        $categories = $courseCategoryRepo->findAll();
        foreach ($categories as $category) {
            /* @var Chamilo\CoreBundle\Entity\CourseCategory $category */
            $code = $category->getCode();
            $name = $category->getTitle();
            $name = str_replace(get_lang('Department'), '', $name);
            $teachers[$name] = Statistics::countUsers(COURSEMANAGER, $code, $countInvisible);
            $students[$name] = Statistics::countUsers(STUDENT, $code, $countInvisible);
        }
        // docents for each course category
        $content .= Statistics::printStats(get_lang('Trainers'), $teachers);
        // students for each course category
        $content .= Statistics::printStats(get_lang('Learners'), $students);
        break;
    case 'recentlogins':
        $content .= '<h2>'.sprintf(get_lang('Last %s days'), '15').'</h2>';
        $form = new FormValidator(
            'session_time',
            'get',
            api_get_self().'?report=recentlogins&session_duration='.$sessionDuration
        );
        $sessionTimeList = ['', 5 => 5, 15 => 15, 30 => 30, 60 => 60];
        $form->addSelect('session_duration', [get_lang('Session min duration'), get_lang('Minutes')], $sessionTimeList);
        $form->addButtonSend(get_lang('Filter'));
        $form->addHidden('report', 'recentlogins');
        $content .= $form->returnForm();

        $content .= '<canvas class="col-md-12" id="canvas" height="200px" style="margin-bottom: 20px"></canvas>';
        $content .= Statistics::printRecentLoginStats(false, $sessionDuration?:0);
        $content .= Statistics::printRecentLoginStats(true, $sessionDuration?:0);
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
        $content .= Statistics::printStats(get_lang('Number of messages sent'), $messages_sent);
        break;
    case 'messagereceived':
        $messages_received = Statistics::getMessages('received');
        $content .= Statistics::printStats(get_lang('Number of messages received'), $messages_received);
        break;
    case 'friends':
        // total amount of friends
        $friends = Statistics::getFriends();
        $content .= Statistics::printStats(get_lang('Contacts count'), $friends);
        break;
    case 'logins_by_date':
        $content .= Statistics::printLoginsByDate();
        break;
    case 'quarterly_report':
        global $htmlHeadXtra;
        $ajaxPath = api_get_path(WEB_AJAX_PATH);
        $waitIcon = Display::getMdiIcon('clock-time-four', 'ch-tool-icon-disabled', null, ICON_SIZE_SMALL, false);
        $htmlHeadXtra[] .= '<script>
                function loadReportQuarterlyUsers () {
                    $("#tracking-report-quarterly-users")
                        .html(\'<p>'.$waitIcon.'</p>\')
                        .load("'.$ajaxPath.'statistics.ajax.php?a=report_quarterly_users'.'");
            }</script>';
        $htmlHeadXtra[] .= '<script>
                function loadReportQuarterlyCourses () {
                    $("#tracking-report-quarterly-courses")
                        .html(\'<p>'.$waitIcon.'</p>\')
                        .load("'.$ajaxPath.'statistics.ajax.php?a=report_quarterly_courses'.'");
            }</script>';
        $htmlHeadXtra[] .= '<script>
                function loadReportQuarterlyHoursOfTraining () {
                    $("#tracking-report-quarterly-hours-of-training")
                        .html(\'<p>'.$waitIcon.'</p>\')
                        .load("'.$ajaxPath.'statistics.ajax.php?a=report_quarterly_hours_of_training'.'");
            }</script>';
        $htmlHeadXtra[] .= '<script>
                function loadReportQuarterlyCertificatesGenerated () {
                    $("#tracking-report-quarterly-number-of-certificates-generated")
                        .html(\'<p>'.$waitIcon.'</p>\')
                        .load("'.$ajaxPath.'statistics.ajax.php?a=report_quarterly_number_of_certificates_generated'.'");
            }</script>';
        $htmlHeadXtra[] .= '<script>
                function loadReportQuarterlySessionsByDuration () {
                    $("#tracking-report-quarterly-sessions-by-duration")
                        .html(\'<p>'.$waitIcon.'</p>\')
                        .load("'.$ajaxPath.'statistics.ajax.php?a=report_quarterly_sessions_by_duration'.'");
            }</script>';
        $htmlHeadXtra[] .= '<script>
                function loadReportQuarterlyCoursesAndSessions () {
                    $("#tracking-report-quarterly-courses-and-sessions")
                        .html(\'<p>'.$waitIcon.'</p>\')
                        .load("'.$ajaxPath.'statistics.ajax.php?a=report_quarterly_courses_and_sessions'.'");
            }</script>';
        if (api_get_current_access_url_id() === 1) {
            $htmlHeadXtra[] .= '<script>
                function loadReportQuarterlyTotalDiskUsage () {
                    $("#tracking-report-quarterly-total-disk-usage")
                        .html(\'<p>'.$waitIcon.'</p>\')
                        .load("'.$ajaxPath.'statistics.ajax.php?a=report_quarterly_total_disk_usage'.'");
            }</script>';
        }
        $content .= Display::tag('H4', get_lang('Number of users registered and connected'), ['style' => 'margin-bottom: 25px;']);
        $content .= Display::url(
            get_lang('Show'),
            'javascript://',
            ['onclick' => 'loadReportQuarterlyUsers();', 'class' => 'btn btn-default']
        );
        $content .= Display::div('', ['id' => 'tracking-report-quarterly-users', 'style' => 'margin: 30px;']);
        $content .= Display::tag('H4', get_lang('Number of existing and available courses'), ['style' => 'margin-bottom: 25px;']);
        $content .= Display::url(
            get_lang('Show'),
            'javascript://',
            ['onclick' => 'loadReportQuarterlyCourses();', 'class' => 'btn btn-default']
        );
        $content .= Display::div('', ['id' => 'tracking-report-quarterly-courses', 'style' => 'margin: 30px;']);
        $content .= Display::tag('H4', get_lang('Hours of training'), ['style' => 'margin-bottom: 25px;']);
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
            get_lang('Number of certificates generated'),
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
            get_lang('Number of sessions per duration'),
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
            get_lang('Number of courses, sessions and subscribed users'),
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
                get_lang('Total disk usage'),
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

<?php
/* For licensing terms, see /license.txt */

/**
 * This tool show global Statistics on general platform events.
 *
 * @package chamilo.Statistics
 */
$cidReset = true;

require_once __DIR__.'/../../inc/global.inc.php';
api_protect_admin_script();

$interbreadcrumb[] = ['url' => '../index.php', 'name' => get_lang('PlatformAdmin')];

$report = isset($_REQUEST['report']) ? $_REQUEST['report'] : '';
$sessionDuration = isset($_GET['session_duration']) ? (int) $_GET['session_duration'] : '';

if (
    in_array(
        $report,
        ['recentlogins', 'tools', 'courses', 'coursebylanguage', 'users']
    )
   ) {
    $htmlHeadXtra[] = api_get_js('chartjs/Chart.min.js');
    // Prepare variables for the JS charts
    $url = $reportName = $reportType = $reportOptions = '';
    switch ($report) {
        case 'recentlogins':
            $url = api_get_path(WEB_CODE_PATH).'inc/ajax/statistics.ajax.php?a=recent_logins&session_duration='.$sessionDuration;
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
    }
}

if ($report == 'user_session') {
    $htmlHeadXtra[] = api_get_jqgrid_js();
}

$tool_name = get_lang('Statistics');
Display::display_header($tool_name);
echo Display::page_header($tool_name);

$strCourse = get_lang('Courses');
$strUsers = get_lang('Users');
$strSystem = get_lang('System');
$strSocial = get_lang('Social');
$strSession = get_lang('Session');

// courses ...
$tools[$strCourse]['report=courses'] = get_lang('CountCours');
$tools[$strCourse]['report=tools'] = get_lang('PlatformToolAccess');
$tools[$strCourse]['report=courselastvisit'] = get_lang('LastAccess');
$tools[$strCourse]['report=coursebylanguage'] = get_lang('CountCourseByLanguage');

// users ...
$tools[$strUsers]['report=users'] = get_lang('CountUsers');
$tools[$strUsers]['report=recentlogins'] = get_lang('Logins');
$tools[$strUsers]['report=logins&amp;type=month'] = get_lang('Logins').' ('.get_lang('PeriodMonth').')';
$tools[$strUsers]['report=logins&amp;type=day'] = get_lang('Logins').' ('.get_lang('PeriodDay').')';
$tools[$strUsers]['report=logins&amp;type=hour'] = get_lang('Logins').' ('.get_lang('PeriodHour').')';
$tools[$strUsers]['report=pictures'] = get_lang('CountUsers').' ('.get_lang('UserPicture').')';
$tools[$strUsers]['report=no_login_users'] = get_lang('StatsUsersDidNotLoginInLastPeriods');
$tools[$strUsers]['report=zombies'] = get_lang('Zombies');

// system ...
$tools[$strSystem]['report=activities'] = get_lang('ImportantActivities');

if (api_is_global_platform_admin() && api_is_multiple_url_enabled()) {
    $tools[$strSystem]['report=user_session'] = get_lang('PortalUserSessionStats');
}

// social ...
$tools[$strSocial]['report=messagesent'] = get_lang('MessagesSent');
$tools[$strSocial]['report=messagereceived'] = get_lang('MessagesReceived');
$tools[$strSocial]['report=friends'] = get_lang('CountFriends');

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

$course_categories = Statistics::getCourseCategories();
//@todo: spaces between elements should be handled in the css, br should be removed if only there for presentation
echo '<br/><br/>';

switch ($report) {
    case 'user_session':
        $form = new FormValidator('user_session', 'get');
        $form->addDateRangePicker('range', get_lang('DateRange'), true);
        $form->addHidden('report', 'user_session');
        $form->addButtonSearch(get_lang('Search'));

        $date = new DateTime($now);
        $startDate = $date->format('Y-m-d').' 00:00:00';
        $endDate = $date->format('Y-m-d').' 23:59:59';
        $start = $startDate;
        $end = $endDate;

        if ($form->validate()) {
            $values = $form->getSubmitValues();
            $start = $values['range_start'];
            $end = $values['range_end'];
        }
        echo $form->returnForm();

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
        ?>
        <script>
            $(function() {
                <?php
                echo Display::grid_js(
                    'user_session_grid',
                    $url,
                    $columns,
                    $columnModel,
                    $extraParams,
                    [],
                    $actionLinks,
                    true
                );
                ?>

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
                        jQuery("#user_session_grid").jqGrid("excelExport",{"url":"<?php echo $url; ?>&export_format=xls"});
                    }
                });
            });
        </script>
        <?php
        echo Display::grid_html('user_session_grid');

        break;
    case 'courses':
        echo '<canvas class="col-md-12" id="canvas" height="300px" style="margin-bottom: 20px"></canvas>';
        // total amount of courses
        foreach ($course_categories as $code => $name) {
            $courses[$name] = Statistics::countCourses($code);
        }
        // courses for each course category
        Statistics::printStats(get_lang('CountCours'), $courses);
        break;
    case 'tools':
        echo '<canvas class="col-md-12" id="canvas" height="300px" style="margin-bottom: 20px"></canvas>';
        Statistics::printToolStats();
        break;
    case 'coursebylanguage':
        echo '<canvas class="col-md-12" id="canvas" height="300px" style="margin-bottom: 20px"></canvas>';
        $result = Statistics::printCourseByLanguageStats();
        Statistics::printStats(get_lang('CountCourseByLanguage'), $result, true);
        break;
    case 'courselastvisit':
        Statistics::printCourseLastVisit();
        break;
    case 'users':
        echo '<div class="row">';
        echo '<div class="col-md-4"><canvas id="canvas1" style="margin-bottom: 20px"></canvas></div>';
        echo '<div class="col-md-4"><canvas id="canvas2" style="margin-bottom: 20px"></canvas></div>';
        echo '<div class="col-md-4"><canvas id="canvas3" style="margin-bottom: 20px"></canvas></div>';
        echo '</div>';
        // total amount of users
        $teachers = $students = [];
        $countInvisible = isset($_GET['count_invisible_courses']) ? intval($_GET['count_invisible_courses']) : null;
        Statistics::printStats(
            get_lang('NumberOfUsers'),
            [
                get_lang('Teachers') => Statistics::countUsers(COURSEMANAGER, null, $countInvisible),
                get_lang('Students') => Statistics::countUsers(STUDENT, null, $countInvisible),
            ]
        );
        foreach ($course_categories as $code => $name) {
            $name = str_replace(get_lang('Department'), "", $name);
            $teachers[$name] = Statistics::countUsers(COURSEMANAGER, $code, $countInvisible);
            $students[$name] = Statistics::countUsers(STUDENT, $code, $countInvisible);
        }
        // docents for each course category
        Statistics::printStats(get_lang('Teachers'), $teachers);
        // students for each course category
        Statistics::printStats(get_lang('Students'), $students);
        break;
    case 'recentlogins':
        echo '<h2>'.sprintf(get_lang('LastXDays'), '15').'</h2>';
        $form = new FormValidator('session_time', 'get', api_get_self().'?report=recentlogins&session_duration='.$sessionDuration);
        $sessionTimeList = ['', 5 => 5, 15 => 15, 30 => 30, 60 => 60];
        $form->addSelect('session_duration', [get_lang('SessionMinDuration'), get_lang('Minutes')], $sessionTimeList);
        $form->addButtonSend(get_lang('Filter'));
        $form->addHidden('report', 'recentlogins');
        $form->display();

        echo '<canvas class="col-md-12" id="canvas" height="200px" style="margin-bottom: 20px"></canvas>';
        Statistics::printRecentLoginStats(false, $sessionDuration);
        Statistics::printRecentLoginStats(true, $sessionDuration);
        break;
    case 'logins':
        Statistics::printLoginStats($_GET['type']);
        break;
    case 'pictures':
        Statistics::printUserPicturesStats();
        break;
    case 'no_login_users':
        Statistics::printUsersNotLoggedInStats();
        break;
    case 'zombies':
        ZombieReport::create(['report' => 'zombies'])->display();
        break;
    case 'activities':
        Statistics::printActivitiesStats();
        break;
    case 'messagesent':
        $messages_sent = Statistics::getMessages('sent');
        Statistics::printStats(get_lang('MessagesSent'), $messages_sent);
        break;
    case 'messagereceived':
        $messages_received = Statistics::getMessages('received');
        Statistics::printStats(get_lang('MessagesReceived'), $messages_received);
        break;
    case 'friends':
        // total amount of friends
        $friends = Statistics::getFriends();
        Statistics::printStats(get_lang('CountFriends'), $friends);
        break;
}

Display::display_footer();

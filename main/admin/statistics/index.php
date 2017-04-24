<?php
/* For licensing terms, see /license.txt */

/**
 * This tool show global Statistics on general platform events
 * @package chamilo.Statistics
 */
$cidReset = true;

require_once __DIR__.'/../../inc/global.inc.php';
api_protect_admin_script();

$interbreadcrumb[] = array('url' => '../index.php', 'name' => get_lang('PlatformAdmin'));

$report = isset($_REQUEST['report']) ? $_REQUEST['report'] : '';

if ($report) {
    $htmlHeadXtra[] = api_get_js('chartjs/Chart.min.js');
    $htmlHeadXtra[] = '
        <script>
            $(document).ready(function() {
                $.ajax({
                    url: "'. api_get_path(WEB_CODE_PATH).'inc/ajax/statistics.ajax.php?a=recentlogins",
                    type: "POST",
                    success: function(data) {
                        Chart.defaults.global.responsive = true;
                        var myLine = new Chart(document.getElementById("canvas").getContext("2d")).Line(data);
                    }
            });
        });
        </script>';
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
echo '<br/><br/>'; //@todo: spaces between elements should be handled in the css, br should be removed if only there for presentation

switch ($report) {
    case 'courses':
        // total amount of courses
        foreach ($course_categories as $code => $name) {
            $courses[$name] = Statistics::countCourses($code);
        }
        // courses for each course category
        Statistics::printStats(get_lang('CountCours'), $courses);
        break;
    case 'tools':
        Statistics::printToolStats();
        break;
    case 'coursebylanguage':
        Statistics::printCourseByLanguageStats();
        break;
    case 'courselastvisit':
        Statistics::printCourseLastVisit();
        break;
    case 'users':
        // total amount of users
        $teachers = $students = array();
        $countInvisible = isset($_GET['count_invisible_courses']) ? intval($_GET['count_invisible_courses']) : null;
        Statistics::printStats(
            get_lang('NumberOfUsers'),
            array(
                get_lang('Teachers') => Statistics::countUsers(COURSEMANAGER, null, $countInvisible),
                get_lang('Students') => Statistics::countUsers(STUDENT, null, $countInvisible)
            )
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
        echo '<canvas class="col-md-12" id="canvas" height="100px" style="margin-bottom: 20px"></canvas>';
        Statistics::printRecentLoginStats();
        Statistics::printRecentLoginStats(true);
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
        ZombieReport::create(array('report' => 'zombies'))->display();
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

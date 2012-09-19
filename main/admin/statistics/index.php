<?php

/* For licensing terms, see /license.txt */
/**
 * This tool show global Statistics on general platform events
 * @package chamilo.Statistics
 */
// Language files that need to be included
$language_file = array('admin', 'tracking');
$cidReset = true;

require_once '../../inc/global.inc.php';
api_protect_admin_script();

$interbreadcrumb[] = array('url' => '../index.php', 'name' => get_lang('PlatformAdmin'));

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
$tools[$strUsers]['report=logins&amp;type=month'] = get_lang('Logins') . ' (' . get_lang('PeriodMonth') . ')';
$tools[$strUsers]['report=logins&amp;type=day'] = get_lang('Logins') . ' (' . get_lang('PeriodDay') . ')';
$tools[$strUsers]['report=logins&amp;type=hour'] = get_lang('Logins') . ' (' . get_lang('PeriodHour') . ')';
$tools[$strUsers]['report=pictures'] = get_lang('CountUsers') . ' (' . get_lang('UserPicture') . ')';
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
    echo '<h3>' . $section . '</h3>';
    echo '<ul>';
    foreach ($items as $key => $value) {
        echo '<li><a href="index.php?' . $key . '">' . $value . '</a></li>';
    }
    echo '</ul>';
    echo '</td>';
}
echo '</tr></table>';

$course_categories = Statistics::get_course_categories();
echo '<br/><br/>';//@todo: spaces between elements should be handled in the css, br should be removed if only there for presentation
switch ($_GET['report']) {
    case 'courses':
        // total amount of courses
        foreach ($course_categories as $code => $name) {
            $courses[$name] = Statistics::count_courses($code);
        }
        // courses for each course category
        Statistics::print_stats(get_lang('CountCours'), $courses);
        break;
    case 'tools':
        Statistics::print_tool_stats();
        break;
    case 'coursebylanguage':
        Statistics::print_course_by_language_stats();
        break;
    case 'courselastvisit':
        Statistics::print_course_last_visit();
        break;    
    case 'users':
        // total amount of users
        Statistics::print_stats(
            get_lang('NumberOfUsers'), array(
            get_lang('Teachers') => Statistics::count_users(1, null, $_GET['count_invisible_courses']),
            get_lang('Students') => Statistics::count_users(5, null, $_GET['count_invisible_courses'])
            )
        );
        $teachers = $students = array();
        foreach ($course_categories as $code => $name) {
            $name = str_replace(get_lang('Department'), "", $name);
            $teachers[$name] = Statistics::count_users(1, $code, $_GET['count_invisible_courses']);
            $students[$name] = Statistics::count_users(5, $code, $_GET['count_invisible_courses']);
        }
        // docents for each course category
        Statistics::print_stats(get_lang('Teachers'), $teachers);
        // students for each course category
        Statistics::print_stats(get_lang('Students'), $students);
        break;
    case 'recentlogins':
        Statistics::print_recent_login_stats();
        break;
    case 'logins':
        Statistics::print_login_stats($_GET['type']);
        break;
    case 'pictures':
        Statistics::print_user_pictures_stats();
        break;
    case 'no_login_users':
        Statistics::print_users_not_logged_in_stats();
        break;
    case 'zombies':
        ZombieReport::create(array('report' => 'zombies'))->display();
        break;    
    case 'activities':
        Statistics::print_activities_stats();
        break;    
    case 'messagesent':
        $messages_sent = Statistics::get_messages('sent');
        Statistics::print_stats(get_lang('MessagesSent'), $messages_sent);
        break;
    case 'messagereceived':
        $messages_received = Statistics::get_messages('received');
        Statistics::print_stats(get_lang('MessagesReceived'), $messages_received);
        break;
    case 'friends':
        // total amount of friends
        $friends = Statistics::get_friends();
        Statistics::print_stats(get_lang('CountFriends'), $friends);
        break;
}

Display::display_footer();
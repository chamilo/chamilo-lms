<?php
/* For licensing terms, see /license.txt */
/**
* This tool show global statistics on general platform events
* @package chamilo.statistics
*/
// Language files that need to be included
$language_file = array('admin', 'tracking');
$cidReset = true;

require_once '../../inc/global.inc.php';
api_protect_admin_script();

$interbreadcrumb[] = array ('url' => '../index.php', 'name' => get_lang('PlatformAdmin'));

$tool_name = get_lang('Statistics');
Display::display_header($tool_name);
api_display_tool_title($tool_name);

require_once 'statistics.lib.php';

$strCourse      = get_lang('Courses');
$strUsers       = get_lang('Users');
$strSystem      = get_lang('System');
$strSocial      = get_lang('Social');
$strSession     = get_lang('Session');

// courses ...
$tools[$strCourse]['action=courses'] = get_lang('CountCours');
$tools[$strCourse]['action=tools'] = get_lang('PlatformToolAccess');
$tools[$strCourse]['action=courselastvisit'] = get_lang('LastAccess');
$tools[$strCourse]['action=coursebylanguage'] = get_lang('CountCourseByLanguage');

// users ...
$tools[$strUsers]['action=users'] = get_lang('CountUsers');
$tools[$strUsers]['action=recentlogins'] = get_lang('Logins');
$tools[$strUsers]['action=logins&amp;type=month'] = get_lang('Logins').' ('.get_lang('PeriodMonth').')';
$tools[$strUsers]['action=logins&amp;type=day'] = get_lang('Logins').' ('.get_lang('PeriodDay').')';
$tools[$strUsers]['action=logins&amp;type=hour'] = get_lang('Logins').' ('.get_lang('PeriodHour').')';
$tools[$strUsers]['action=pictures'] = get_lang('CountUsers').' ('.get_lang('UserPicture').')';
$tools[$strUsers]['action=no_login_users'] = get_lang('StatsUsersDidNotLoginInLastPeriods');

// system ...
$tools[$strSystem]['action=activities'] = get_lang('ImportantActivities');

// social ...
$tools[$strSocial]['action=messagesent'] = get_lang('MessagesSent');
$tools[$strSocial]['action=messagereceived'] = get_lang('MessagesReceived');
$tools[$strSocial]['action=friends'] = get_lang('CountFriends');


echo '<table><tr>';
foreach ($tools as $section => $items) {
    echo '<td valign="top">';
    echo '<h3>'.$section.'</h3>';
    echo '<ul>';
    foreach ($items as $key => $value) {
        echo '<li><a href="index.php?'.$key.'">'.$value.'</a></li>';
    }
    echo '</ul>';
    echo '</td>';
}
echo '</tr></table>';

$course_categories = statistics::get_course_categories();
echo '<br/><br/>';
switch ($_GET['action']) {
	case 'courses':
		// total amount of courses
		foreach ($course_categories as $code => $name) {
			$courses[$name] = statistics::count_courses($code);
		}
		// courses for each course category
		statistics::print_stats(get_lang('CountCours'),$courses);
		break;
	case 'users':
		// total amount of users
		statistics::print_stats(
			get_lang('NumberOfUsers'),
			array(
				get_lang('Teachers') => statistics::count_users(1,null,$_GET['count_invisible_courses']),
				get_lang('Students') => statistics::count_users(5,null,$_GET['count_invisible_courses'])
			)
		);
        $teachers = $students = array();
		foreach ($course_categories as $code => $name) {
			$name = str_replace(get_lang('Department'),"",$name);
			$teachers[$name] = statistics::count_users(1,$code,$_GET['count_invisible_courses']);
			$students[$name] = statistics::count_users(5,$code,$_GET['count_invisible_courses']);
		}
		// docents for each course category
		statistics::print_stats(get_lang('Teachers'),$teachers);
		// students for each course category
		statistics::print_stats(get_lang('Students'),$students);
		break;
	case 'coursebylanguage':
		statistics::print_course_by_language_stats();
		break;
	case 'logins':
		statistics::print_login_stats($_GET['type']);
		break;
	case 'tools':
		statistics::print_tool_stats();
		break;
	case 'courselastvisit':
		statistics::print_course_last_visit();
		break;
	case 'recentlogins':
		statistics::print_recent_login_stats();
		break;
	case 'pictures':
		statistics::print_user_pictures_stats();
		break;
	case 'activities':
		statistics::print_activities_stats();
		break;
	case 'messagesent':
		$messages_sent = statistics::get_messages('sent');		
		statistics::print_stats(get_lang('MessagesSent'), $messages_sent);
		break;
	case 'messagereceived':
		$messages_received = statistics::get_messages('received');
		statistics::print_stats(get_lang('MessagesReceived'), $messages_received);
		break;
	case 'friends':
		// total amount of friends
		$friends = statistics::get_friends();
		statistics::print_stats(get_lang('CountFriends'), $friends);
		break;
    case 'no_login_users':
        statistics::print_users_not_logged_in_stats();
        break;
}

Display::display_footer();
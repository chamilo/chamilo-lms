<?php // $Id: course_information.php 18070 2009-01-29 02:01:33Z yannoo $
/* For licensing terms, see /dokeos_license.txt */
/**
 * This script gives information about a course
 * @author Bart Mollet
 * @package dokeos.admin
*/
/*
==============================================================================
		INIT SECTION
==============================================================================
*/

// name of the language file that needs to be included
$language_file = 'admin';
$cidReset = true;
require ('../inc/global.inc.php');
require_once(api_get_path(LIBRARY_PATH).'sortabletable.class.php');
$this_section=SECTION_PLATFORM_ADMIN;

api_protect_admin_script();
/**
 *
 */
function get_course_usage($course_code)
{
	$table = Database::get_main_table(TABLE_MAIN_COURSE);
    $course_code = Database::escape_string($course_code);
	$sql = "SELECT * FROM $table WHERE code='".$course_code."'";
	$res = Database::query($sql,__FILE__,__LINE__);
	$course = mysql_fetch_object($res);
	// Learnpaths
	$table = Database :: get_course_table(TABLE_LP_MAIN, $course->db_name);
	$usage[] = array (get_lang(ucfirst(TOOL_LEARNPATH)), Database::count_rows($table));
	// Forums
	$table = Database :: get_course_table(TABLE_FORUM, $course->db_name);
	$usage[] = array (get_lang('Forums'), Database::count_rows($table));
	// Quizzes
	$table = Database :: get_course_table(TABLE_QUIZ_TEST, $course->db_name);
	$usage[] = array (get_lang(ucfirst(TOOL_QUIZ)), Database::count_rows($table));
	// Documents
	$table = Database :: get_course_table(TABLE_DOCUMENT, $course->db_name);
	$usage[] = array (get_lang(ucfirst(TOOL_DOCUMENT)), Database::count_rows($table));
	// Groups
	$table = Database :: get_course_table(TABLE_GROUP, $course->db_name);
	$usage[] = array (get_lang(ucfirst(TOOL_GROUP)), Database::count_rows($table));
	// Calendar
	$table = Database :: get_course_table(TABLE_AGENDA, $course->db_name);
	$usage[] = array (get_lang(ucfirst(TOOL_CALENDAR_EVENT)), Database::count_rows($table));
	// Link
	$table = Database::get_course_table(TABLE_LINK, $course->db_name);
	$usage[] = array(get_lang(ucfirst(TOOL_LINK)), Database::count_rows($table));
	// Announcements
	$table = Database::get_course_table(TABLE_ANNOUNCEMENT, $course->db_name);
	$usage[] = array(get_lang(ucfirst(TOOL_ANNOUNCEMENT)), Database::count_rows($table));
	return $usage;
}
/*****************************************************************/
if (!isset ($_GET['code']))
{
	api_not_allowed();
}
$interbreadcrumb[] = array ("url" => 'index.php', "name" => get_lang('PlatformAdmin'));
$interbreadcrumb[] = array ("url" => 'course_list.php', "name" => get_lang('Courses'));
$table_course = Database :: get_main_table(TABLE_MAIN_COURSE);
$code = Database::escape_string($_GET['code']);
$sql = "SELECT * FROM $table_course WHERE code = '".$code."'";
$res = Database::query($sql,__FILE__,__LINE__);
$course = mysql_fetch_object($res);
$tool_name = $course->title.' ('.$course->visual_code.')';
Display::display_header($tool_name);
//api_display_tool_title($tool_name);
?>
<p>
<a href="<?php echo api_get_path(WEB_COURSE_PATH).$course->directory; ?>"><?php Display::display_icon('course_home.gif'); ?> <?php echo api_get_path(WEB_COURSE_PATH).$course->directory; ?></a>
<br/>
<?php
if(api_get_setting('server_type') == 'test')
{
	?>
	<a href="course_create_content.php?course_code=<?php echo $course->code ?>"><?php echo get_lang('AddDummyContentToCourse') ?></a>
	<?php
}
?>
</p>
<?php

echo '<h4>'.get_lang('CourseUsage').'</h4>';
echo '<blockquote>';
$table = new SortableTableFromArray(get_course_usage($course->code),0,20,'usage_table');
$table->set_additional_parameters(array ('code' => $_GET['code']));
$table->set_other_tables(array('user_table','class_table'));
$table->set_header(0,get_lang('Tool'), true);
$table->set_header(1,get_lang('NumberOfItems'), true);
$table->display();
echo '</blockquote>';
/**
 * Show all users subscribed in this course
 */
echo '<h4>'.get_lang('Users').'</h4>';
echo '<blockquote>';
$table_course_user = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
$table_user = Database :: get_main_table(TABLE_MAIN_USER);
$sql = "SELECT *,cu.status as course_status FROM $table_course_user cu, $table_user u WHERE cu.user_id = u.user_id AND cu.course_code = '".$code."'";
$res = Database::query($sql,__FILE__,__LINE__);
$is_western_name_order = api_is_western_name_order();
if (mysql_num_rows($res) > 0)
{
	$users = array ();
	while ($obj = mysql_fetch_object($res))
	{
		$user = array ();
		$user[] = $obj->official_code;
		if ($is_western_name_order)
		{
			$user[] = $obj->firstname;
			$user[] = $obj->lastname;
		}
		else
		{
			$user[] = $obj->lastname;
			$user[] = $obj->firstname;
		}
		$user[] = Display :: encrypted_mailto_link($obj->email, $obj->email);
		$user[] = $obj->course_status == 5 ? get_lang('Student') : get_lang('Teacher');
		$user[] = '<a href="user_information.php?user_id='.$obj->user_id.'">'.Display::return_icon('synthese_view.gif',get_lang('UserInfo')).'</a>';
		$users[] = $user;
	}
	$table = new SortableTableFromArray($users,0,20,'user_table');
	$table->set_additional_parameters(array ('code' => $code));
	$table->set_other_tables(array('usage_table','class_table'));
	$table->set_header(0,get_lang('OfficialCode'), true);
	if ($is_western_name_order)
	{
		$table->set_header(1,get_lang('FirstName'), true);
		$table->set_header(2,get_lang('LastName'), true);
	}
	else
	{
		$table->set_header(1,get_lang('LastName'), true);
		$table->set_header(2,get_lang('FirstName'), true);
	}
	$table->set_header(3,get_lang('Email'), true);
	$table->set_header(4,get_lang('Status'), true);
	$table->set_header(5,'', false);
	$table->display();
}
else
{
	echo get_lang('NoUsersInCourse');
}
echo '</blockquote>';
/**
 * Show all classes subscribed in this course
 */
$table_course_class = Database :: get_main_table(TABLE_MAIN_COURSE_CLASS);
$table_class = Database :: get_main_table(TABLE_MAIN_CLASS);
$sql = "SELECT * FROM $table_course_class cc, $table_class c WHERE cc.class_id = c.id AND cc.course_code = '".$code."'";
$res = Database::query($sql,__FILE__,__LINE__);
if (mysql_num_rows($res) > 0)
{
	$data = array ();
	while ($class = mysql_fetch_object($res))
	{
		$row = array ();
		$row[] = $class->name;
		$row[] = '<a href="class_information.php?id='.$class->id.'">'.Display::return_icon('synthese_view.gif', get_lang('Edit')).'</a>';
		$data[] = $row;
	}
	echo '<p><b>'.get_lang('AdminClasses').'</b></p>';
	echo '<blockquote>';
	$table = new SortableTableFromArray($data,0,20,'class_table');
	$table->set_additional_parameters(array ('code' => $_GET['code']));
	$table->set_other_tables(array('usage_table','user_table'));
	$table->set_header(0,get_lang('Title'));
	$table->set_header(1,'');
	$table->display();
	echo '</blockquote>';
}
else
{
	echo '<p>'.get_lang('NoClassesForThisCourse').'</p>';
}
/*
==============================================================================
		FOOTER
==============================================================================
*/
Display::display_footer();
?>
<?php
/* For licensing terms, see /license.txt */
/**
 * This script allows a platform admin to add dummy content to a course.
 *
 * @author Bart Mollet <bart.mollet@hogent.be>
 * @package chamilo.admin
 */
/**
 *		INIT SECTION
 */

// name of the language file that needs to be included
$language_file = 'admin';
include ('../inc/global.inc.php');
$this_section=SECTION_PLATFORM_ADMIN;

api_protect_admin_script();
$tool_name = get_lang('DummyCourseCreator');
$interbreadcrumb[] = array ("url" => 'index.php', "name" => get_lang('PlatformAdmin'));
Display::display_header($tool_name);
//api_display_tool_title($tool_name);
if(api_get_setting('server_type') != 'test')
{
	echo get_lang('DummyCourseOnlyOnTestServer');
}
elseif( isset($_POST['action']))
{
	require_once('../coursecopy/classes/DummyCourseCreator.class.php');
	$dcc = new DummyCourseCreator();
	$dcc->create_dummy_course($_POST['course_code']);
	echo get_lang('Done');
}
else
{
	echo get_lang('DummyCourseDescription');
	echo '<form method="post"><input type="hidden" name="course_code" value="'.Security::remove_XSS($_GET['course_code']).'"/><input type="submit" name="action" value="'.get_lang('Ok').'"/></form>';
}
/*
==============================================================================
		FOOTER
==============================================================================
*/
Display::display_footer();
?>

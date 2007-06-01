<?php //$id: $
/**
 * Script that displays the header frame for lp_view.php
 * @package dokeos.learnpath
 * @author Yannick Warnier <ywarnier@beeznest.org>
 */
/**
 * Script
 */
// name of the language file that needs to be included 
$language_file[] = "scormdocument";
require_once('back_compat.inc.php');
require_once('learnpath.class.php');
require_once('scorm.class.php');
require_once('aicc.class.php');
if(isset($_SESSION['lpobject'])){
	$temp = $_SESSION['lpobject'];
	$_SESSION['oLP'] = unserialize($temp);
}
$path_name = htmlspecialchars($_SESSION['oLP']->get_name());
$path_id = $_SESSION['oLP']->get_id();

// Check if the learnpaths list should be accessible to the user
$show_link = true;
if(!api_is_allowed_to_edit()) //if the user has no edit permission (simple user)
{
	$course_tool_table = Database::get_course_table(TABLE_TOOL_LIST);
	$result = api_sql_query("SELECT * FROM $course_tool_table WHERE name='learnpath'",__FILE__,__LINE__);
	if(Database::num_rows($result)>0)
	{
		$row = Database::fetch_array($result);
		if($row['visibility'] == '0') //if the tool is *not* visible
		{
			$show_link = false;
		}
	}
	else
	{
		$show_link = false;
	}
}

$header_hide_main_div = true;
if($show_link)
{
	$interbreadcrumb[]= array ("url"=>"./lp_controller.php?action=list", "name"=> get_lang(ucfirst(TOOL_LEARNPATH)));
}
else //if the tool is hidden and the user has no edit permissions, make the breadcrumb link point to the course homepage
{
	$web_course_path = api_get_path(WEB_COURSE_PATH);
	$interbreadcrumb[]= array ("url"=>$web_course_path.$_course['path'].'/index.php', "name"=> get_lang(ucfirst(TOOL_LEARNPATH)));
}
$interbreadcrumb[] = array("url"=>"./lp_controller.php?action=view&lp_id=".$path_id,'name'=>$path_name);
$noPHP_SELF = true;
Display::display_header($nameTools,"Path", null);
?>
</div>
</body>
</html>
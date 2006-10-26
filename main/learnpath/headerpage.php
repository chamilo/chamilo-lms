<?php
/**
============================================================================== 
*	@package dokeos.learnpath
============================================================================== 
*/

$langFile = "learnpath";

include('../inc/global.inc.php');
$this_section=SECTION_COURSES;

$source_id = $_REQUEST['source_id'];
$action = $_REQUEST['action'];
$learnpath_id = mysql_real_escape_string($_REQUEST['learnpath_id']);
$chapter_id = $_REQUEST['chapter_id'];
$originalresource = $_REQUEST['originalresource'];

$noPHP_SELF=true;

$tbl_learnpath_main = $_course['dbNameGlu']."learnpath_main";
$sql="SELECT * FROM `$tbl_learnpath_main` WHERE learnpath_id=$learnpath_id";
$result=api_sql_query($sql,__FILE__,__LINE__);
$therow=mysql_fetch_array($result);

$interbreadcrumb[]= array ("url"=>"../scorm/scormdocument.php", "name"=> get_lang('_learning_path'));
Display::display_header($therow['learnpath_name'],"Path");
?>
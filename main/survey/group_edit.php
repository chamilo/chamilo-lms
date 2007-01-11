<?php
/*
    DOKEOS - elearning and course management software

    For a full list of contributors, see documentation/credits.html
   
    This program is free software; you can redistribute it and/or
    modify it under the terms of the GNU General Public License
    as published by the Free Software Foundation; either version 2
    of the License, or (at your option) any later version.
    See "documentation/licence.html" more details.
 
    Contact: 
		Dokeos
		Rue des Palais 44 Paleizenstraat
		B-1030 Brussels - Belgium
		Tel. +32 (2) 211 34 56
*/

/**
*	@package dokeos.survey
* 	@author 
* 	@version $Id: group_edit.php 10680 2007-01-11 21:26:23Z pcool $
*/

/*
==============================================================================
		INIT SECTION
==============================================================================
*/
// name of the language file that needs to be included 
$language_file = 'survey';

// including the global dokeos file
require_once ('../inc/global.inc.php');

// including additional libraries
/** @todo check if these are all needed */
/** @todo check if the starting / is needed. api_get_path probably ends with an / */
require_once (api_get_path(LIBRARY_PATH).'/fileManage.lib.php');
require_once (api_get_path(CONFIGURATION_PATH) ."/add_course.conf.php");
require_once (api_get_path(LIBRARY_PATH)."/add_course.lib.inc.php");
require_once (api_get_path(LIBRARY_PATH)."/surveymanager.lib.php");
require_once (api_get_path(LIBRARY_PATH)."/usermanager.lib.php");

/** @todo replace this with the correct code */
/*
$status = surveymanager::get_status();
api_protect_course_script();
if($status==5)
{
	api_protect_admin_script();
}
*/
/** @todo this has to be moved to a more appropriate place (after the display_header of the code)*/
if (!api_is_allowed_to_edit())
{
	Display :: display_header();
	Display :: display_error_message(get_lang('NotAllowedHere'));
	Display :: display_footer();
	exit;
}

$table_group = Database :: get_course_table(TABLE_SURVEY_GROUP);
$table_user = Database :: get_main_table(TABLE_MAIN_USER);
$tool_name1 = get_lang('CreateNewGroup');
$tool_name = get_lang('ModifyGroupInformation');
$interbreadcrumb[] = array ("url" => "survey_list.php?", "name" => get_lang('Survey'));
$groupid = $_GET['groupid'];
$surveyid = $_GET['surveyid'];
if($_POST['action'] == 'new_group')
{
	if(isset($_POST['back']))
   { 
	 $surveyid = $_REQUEST['surveyid'];	
     header("location:create_new_group.php?surveyid=$surveyid");
	 exit;
   } 
}
if ($_POST['action'] == 'new_group')
{
	$surveyid = $_POST['surveyid'];
	$groupid = $_POST['groupid'];
	$groupname = $_POST['groupname'];
	$groupname=trim($groupname);
	if(empty ($groupname))
	{
			$error_message = get_lang('PleaseEnterGroupName');      
	}
	$introduction = $_REQUEST['content'];
  if(isset($_POST['next']))
    {
	$groupname=trim($groupname);
	if(empty ($groupname))
	{
			$error_message = get_lang('PleaseEnterGroupName');      
	}
	else
	{
    SurveyManager::update_group($groupid,$surveyid,$groupname,$introduction,$curr_dbname);
	header("location:select_question_group.php?surveyid=$surveyid");
	exit;
	}
	} 
   if(isset($_POST['saveandexit']))
   { 
	 $groupname=trim($groupname);
	 if(empty ($groupname))
	 {
			$error_message = get_lang('PleaseEnterGroupName');      
	 }
	 else
	 {
	 SurveyManager::update_group($groupid,$surveyid,$groupname,$introduction,$curr_dbname);
     header("location:survey_list.php");
	 exit;
	 }
   }  	
}
Display::display_header($tool_name1);
api_display_tool_title($tool_name);
if( isset($error_message) )
{
	Display::display_error_message($error_message);	
}
?>
<?php
$sql = "select * from $table_group where group_id='$groupid'";
$res = api_sql_query($sql);
$obj = mysql_fetch_object($res);
$groupname= $obj->groupname;
$introduction = $obj->introduction;
?>
<form name="new_calendar_item" method="post" action="<?php echo $_SERVER['PHP_SELF'];?>">
<input type="hidden" name="action" value="new_group">
<input type="hidden" name="surveyid" value="<?php echo $surveyid; ?>">
<input type="hidden" name="groupid" value="<?php echo $groupid; ?>">
<table>
<tr>
  <td><?php echo get_lang('GroupName'); ?></td>
  <td><input type="text" name="groupname" size="40" value="<?php echo $groupname ?>"></td>
</tr>
	   <tr><td valign="top"><?php echo get_lang('GroupIntroduction'); ?>&nbsp;</td>
        <td>
   <?php
          require_once(api_get_path(LIBRARY_PATH) . "/fckeditor/fckeditor.php");
		$oFCKeditor = new FCKeditor('content') ;
		$oFCKeditor->BasePath	= api_get_path(WEB_PATH) . 'main/inc/lib/fckeditor/' ;
		$oFCKeditor->Height		= '300';
		$oFCKeditor->Width		= '600';
		$oFCKeditor->Value		= $introduction;
		$oFCKeditor->Config['CustomConfigurationsPath'] = api_get_path(REL_PATH)."main/inc/lib/fckeditor/myconfig.js";
		$oFCKeditor->ToolbarSet = "Survey";
		
		$TBL_LANGUAGES = Database::get_main_table(TABLE_MAIN_LANGUAGE);
		$sql="SELECT isocode FROM ".$TBL_LANGUAGES." WHERE english_name='".$_SESSION["_course"]["language"]."'";
		$result_sql=api_sql_query($sql);
		$isocode_language=mysql_result($result_sql,0,0);
		$oFCKeditor->Config['DefaultLanguage'] = $isocode_language;
		
		$return =	$oFCKeditor->CreateHtml();
		
		echo $return;
   ?>
          <br>
        </td>
      </tr>
</table>
<tr>
  <td>&nbsp;</td>
  <td><input type="submit" name="back" value="<?php echo get_lang('Back'); ?>"></td>
  <td><input type="submit" name="saveandexit" value="<?php echo get_lang('SaveAndExit'); ?>"></td>
  <td><input type="submit" name="next" value="<?php echo get_lang('Next'); ?>"></td>
</tr>
</form>
<?php
Display :: display_footer();
?>
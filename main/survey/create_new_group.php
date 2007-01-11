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
* 	@version $Id: create_new_group.php 10680 2007-01-11 21:26:23Z pcool $
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

// Database table definitions
/** @todo use database constants for the survey tables */
$table_survey 			= Database :: get_course_table(TABLE_SURVEY);
$table_group 			= Database :: get_course_table(TABLE_SURVEY_GROUP);
$table_survey_question 	= Database :: get_course_table(TABLE_SURVEY_QUESTION);
$table_course 			= Database :: get_main_table(TABLE_MAIN_COURSE);
$table_survey_group 	= Database :: get_course_table(TABLE_SURVEY_GROUP);



$tool_name1 = get_lang('CreateNewGroup');
$tool_name = get_lang('CreateNewGroup');
$header1 = get_lang('GroupList');
$interbreadcrumb[] = array ("url" => "survey_list.php", "name" => get_lang('Survey'));
$surveyid = $_GET['surveyid'];
$surveyname = SurveyManager::pick_surveyname($surveyid);

if(isset($_GET['direction'])){
	$sql = 'SELECT * 
			FROM '.$table_group.' 
			WHERE group_id='.intval($_GET['id_group']);
	
	$rs = api_sql_query($sql, __FILE__, __LINE__);
	$group = mysql_fetch_object($rs);
	if(is_object($group)){
		$_GET['direction'] == 'up' ? $operateur = '-' : $operateur = '+';
		
		$sql = 'UPDATE '.$table_group.' SET 
				sortby='.$group->sortby.'
				WHERE sortby='.$group->sortby.$operateur.'1' ;
		
		$rs = api_sql_query($sql,__FILE__,__LINE__);
		$sql = 'UPDATE '.$table_group.' SET 
				sortby='.$group->sortby.$operateur.'1'.'
				WHERE group_id='.intval($_GET['id_group']);
		$rs = api_sql_query($sql,__FILE__,__LINE__);
	}
}

if(isset($_REQUEST['delete']))
 {
   $group_id = $_REQUEST['group_delete'];
   $surveyid = $_REQUEST['surveyid'];
   SurveyManager::delete_group($group_id);
   header("Location:create_new_group.php?surveyid=$surveyid");
   exit;		
 }
if ($_POST['action'] == 'new_group')
{
	$surveyid = $_POST['surveyid'];
	$groupname = $_POST['groupname'];
	$surveyintroduction = $_POST['content'];
	 if(isset($_POST['back']))
	   { 
		 header("location:select_question_group.php?surveyid=$surveyid");
		 exit;
	   } 
	
	$groupname=trim($groupname);
	if(empty ($groupname))
	{
			$error_message = get_lang('PleaseEnterGroupName');      
	}	
	else
	{
		$groupid = SurveyManager::create_group($surveyid,$groupname,$surveyintroduction,$table_group);

		if(isset($_POST['next']) && $groupid)
		{ 
		
		 header("location:addanother.php?surveyid=$surveyid&newgroupid=$groupid");
		 exit;
		
		}elseif(isset($_POST['saveandexit']) && $groupid){
		
		 header("location:survey_list.php");
		 exit;
		
		}
		else
		{ 
		
		  $error_message = "This Group Already Exists !";
		
		} 
	}	
}
Display::display_header($tool_name1);
?>
<tr>
<td><?php api_display_tool_title($header1); ?></td>
</tr>
<?php
if( isset($error_message) )
{
	Display::display_error_message($error_message);	
}


		
		$sql = "SELECT * FROM $table_survey_group WHERE survey_id='$surveyid' ORDER BY sortby ASC";
		
		$parameters = array ();
        $parameters['surveyid']=$surveyid;
		$parameters['groupid']=$groupid;
		$res = api_sql_query($sql,__FILE__,__LINE__);
		$countGroups = mysql_num_rows($res);
	if ($countGroups > 0)
	{	
		$i=0;
		//$surveys = array ();		
	while ($obj = mysql_fetch_object($res))
		{
			$gid=$obj->group_id;
			$gname = $obj->groupname;
			$survey = array ();
			$survey[] = $obj->groupname;
			$gid=$obj->group_id;
				$idd=surveymanager::pick_author($surveyid);
				$author=surveymanager::get_survey_author($idd);
				$surveyname=surveymanager::pick_surveyname($surveyid);
				$survey[] = $surveyname;
				$survey[] = $author;
				$directions = '<table cellpadding="0" cellspacing="0" border="0" style="border:0px"><tr>';
				if($i < $countGroups-1){
					$directions .= '<td><a href="'.$_SERVER['PHP_SELF'].'?surveyid='.$surveyid.'&&direction=down&id_group='.$gid.'"><img src="../img/down.gif" border="0" title="lang_move_down"></a></td>';
				}
				else {
					$directions .= '<td width="20"></td>';
				}
				if($i > 0){
					$directions .= '<td><a href="'.$_SERVER['PHP_SELF'].'?surveyid='.$surveyid.'&direction=up&id_group='.$gid.'"><img src="../img/up.gif" border="0" title="lang_move_up"></a></td>';
				}
				else {
					$directions .= '<td></td>';
				}
				$directions .= '</tr></table>';
				$survey[] = $directions;
				$survey[] =  '<a href="group_edit.php?groupid='.$obj->group_id.'&surveyid='.$surveyid.'"><img src="../img/edit.gif" border="0" align="absmiddle" alt="'.get_lang('Edit').'"/></a>'
				.'<a href="create_new_group.php?delete=1&group_delete='.$gid.'&surveyid='.$surveyid.'"  onclick="javascript:if(!confirm('."'".addslashes(htmlentities(get_lang('ConfirmYourChoice')))."'".')) return false;"><img src="../img/delete.gif" border="0" align="absmiddle" alt="'.get_lang('Delete').'"/></a>'
				;
               $surveys[] = $survey;
               $i++;
		
		}
		$table_header[] = array (get_lang('QuesGroup'), true);
		$table_header[] = array (get_lang('SurveyName'), true);
		$table_header[] = array (get_lang('Author'), true);
		$table_header[] = array (get_lang('OrderBy'), true);
		$table_header[] = array (' ', false);
		Display :: display_sortable_table($table_header, $surveys, array ('column'=>get_lang('OrderBy')), array (), $parameters);
	}
	else
	{
		echo get_lang('NoSearchResults');
	}
	echo '<a href="select_question_group.php?surveyid='.$surveyid.'">'.get_lang('BackToQuestions').'</a><br><br>';
api_display_tool_title($tool_name);
?>
<script src=tbl_change.js type="text/javascript" language="javascript"></script>
<form name="new_calendar_item" method="post" action="<?php echo $_SERVER['PHP_SELF'];?>">
<input type="hidden" name="action" value="new_group">
<input type="hidden" name="surveyid" value="<?php echo $surveyid; ?>">
<input type="hidden" name="groupid" value="<?php echo $groupid; ?>">
<!--<input type="hidden" name="cidReq" value="<?php echo $_REQUEST['cidReq']; ?>">-->
<table>
<tr>
  <td><?php echo get_lang('GroupName'); ?></td>
  <td><input type="text" name="groupname" size="40" maxlength="39" value="<?php echo $code ?>"></td>
</tr>
	   <tr><td valign="top"><?php echo get_lang('GroupIntroduction'); ?>&nbsp;</td>
        <td>
   <?php
          require_once(api_get_path(LIBRARY_PATH) . "/fckeditor/fckeditor.php");
		$oFCKeditor = new FCKeditor('content') ;
		$oFCKeditor->BasePath	= api_get_path(WEB_PATH) . 'main/inc/lib/fckeditor/' ;
		$oFCKeditor->Height		= '300';
		$oFCKeditor->Width		= '600';
		$oFCKeditor->Value		= $content;
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
/*
==============================================================================
		FOOTER 
==============================================================================
*/
Display :: display_footer();
?>
<?php
/*
===================================================================================
    Dokeos - elearning and course management software
===================================================================================
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
===================================================================================
*/

/**
==============================================================================
*	@package dokeos.survey
* 	@author 
* 	@version $Id: addanother.php 10605 2007-01-06 17:55:20Z pcool $
==============================================================================
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
require_once (api_get_path(LIBRARY_PATH)."/course.lib.php");
require_once (api_get_path(LIBRARY_PATH)."/groupmanager.lib.php");
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
$table_user 		= Database :: get_main_table(TABLE_MAIN_USER);
$table_survey 		= Database :: get_course_table('survey');
$table_group 		= Database :: get_course_table('survey_group');

// Language variables
$tool_name = get_lang('CreateNewSurvey');
$tool_name1 = get_lang('AddAnotherQuestion');

// breadcrumbs
$interbreadcrumb[] = array ("url" => "survey_list.php", "name" => get_lang('Survey'));

// Variables
/** @todo use $_course array */
$course_id = $_SESSION['_course']['id'];

// $_GET and $_POST
/** @todo replace $_REQUEST with $_GET or $_POST */
$group_id	= $_GET['newgroupid'];
if(isset($_REQUEST['groupid']))
{
	$groupid=$_REQUEST['groupid'];
}
if(isset($_GET['cidReq']))
{
	$cidReq=$_GET['cidReq'];
}
if(isset($_REQUEST['newgroupid']))
{
	$groupid=$_REQUEST['newgroupid'];
}





if(isset($_POST['back']))
{ 
	header("location:select_question_group.php?add_question=$add_question&groupid=$groupid&surveyid=".$_GET['surveyid']."&cidReq=$cidReq");
}

if(isset($_POST['next']))
{
	if(isset($_POST['add_question']))
	{
		if(isset($_POST['exiztinggroup']))
		{
			$groupid=$_POST['exiztinggroup'];
			$add_question=$_POST['add_question'];
			/** @todo it seems a bad idea to use language strings for switch statements and $_POST variables */
			switch ($_POST['add_question'])
			{
				case get_lang('YesNo'):
				header("location:yesno.php?add_question=$add_question&groupid=$groupid&surveyid=".$_GET['surveyid']."&cidReq=$cidReq");
				break;
				case get_lang('MultipleChoiceSingle'):
				header("location:mcsa.php?add_question=$add_question&groupid=$groupid&surveyid=".$_GET['surveyid']."&cidReq=$cidReq");
				break;
				case get_lang('MultipleChoiceMulti'):
				header("location:mcma.php?add_question=$add_question&groupid=$groupid&surveyid=".$_GET['surveyid']."&cidReq=$cidReq");
				break;
				case get_lang('Open'):
				header("location:open.php?add_question=$add_question&groupid=$groupid&surveyid=".$_GET['surveyid']."&cidReq=$cidReq");
				break;
				case get_lang('Numbered'):
				header("location:numbered.php?add_question=$add_question&groupid=$groupid&surveyid=".$_GET['surveyid']."&cidReq=$cidReq");
				break;
				default :
				header("location:select_question_type.php?cidReq=$cidReq");
				break;
			}	
		}
		else
		$error_message = get_lang('PleaseSelectGroup')."<br>";
		
		
		//exit;
	}
	else
	$error_message=get_lang('PleaseSelectQuestionAndGroup');
}
Display::display_header($tool_name1);
$query="SELECT * FROM $table_survey WHERE survey_id='".mysql_real_escape_string($_GET['surveyid'])."'";
$result=api_sql_query($query);
$surveyname=mysql_result($result,0,'title');
//$surveyname=get_lang('SurveyName').$surveyname;
api_display_tool_title(get_lang('SurveyName')."".$surveyname);
api_display_tool_title($tool_name1);
if(isset($error_message))
Display::display_error_message($error_message);
if(isset($group_id))
{
 
 $sql = "SELECT * FROM $table_group WHERE group_id='$group_id'";
 $res = api_sql_query($sql, __FILE__, __LINE__);
 $obj= mysql_fetch_object($res);
 ?>
 <div align="center"><strong><font color="#FF0000"><?php echo "Group";?>&nbsp;&nbsp;<font color="#0000CC"><u><?php echo $obj->groupname;?></u>&nbsp;&nbsp;</font><?php echo get_lang('GroupCreated');?></font></strong></div>
<?php
}

?>
<form name="question" method="post" action="<?php echo $_SERVER['PHP_SELF'];?>?cidReq=<?php echo $cidReq; ?>">
<input type="hidden" name="groupid" value="<?php echo $groupid; ?>">
<input type="hidden" name="surveyid" value="<?php echo $_GET['surveyid']; ?>">
<input type="hidden" name="newgroupid" value="<?php echo $group_id; ?>">
<table>
<tr>
<td>
<?php echo get_lang('SelectQuestionType');?>
</td>
<td>

<select name="add_question" >	
	<?php /** @todo it seems a bad idea to use language strings for switch statements and $_POST variables */ ?>
	<option value="<?php echo get_lang('YesNo'); ?>" ><?php echo get_lang('YesNo');?></option>
	<option value="<?php echo get_lang('MultipleChoiceSingle'); ?>"  ><?php echo get_lang('MultipleChoiceSingle');?></option>
	<option value="<?php echo get_lang('MultipleChoiceMulti'); ?>" ><?php echo get_lang('MultipleChoiceMulti');?></option>
	<option value="<?php echo get_lang('Open');?>" ><?php echo get_lang('Open');?></option>
	<option value="<?php echo get_lang('Numbered');?>"><?php echo get_lang('Numbered');?></option>
</select>
</td>
</tr>
<tr></tr>
<tr></tr>
<tr></tr>
<tr></tr>
<tr>
<td>
<?php echo get_lang('SelectGroup');?>
</td>
<td>
<?php 
	echo SurveyManager::select_group_list($_GET['surveyid'], $groupid, $extra_script);
?>
<!--<select name="select_group" >-->

<?php 
	/*$query="SELECT * FROM $table_group WHERE survey_id='$_GET['surveyid']'";
	//echo $query;
	$result=api_sql_query($query);
	$num=mysql_num_rows($result);

	for($i=0;$i<$num;$i++)
	{
		$groupid=mysql_result($result,$i,'group_id');
		$gname=mysql_result($result,$i,'groupname');
		*/
?>
		<!--<option value="<?php echo $groupid;?>" ><?php echo $gname;?></option>-->
<?php 	//}
?>

<!--</select>-->
</td>
</tr>
<tr></tr>
<tr>
<td>&nbsp;</td>
<td>
	<input type="submit" name="back" value="<?php echo get_lang('Back');?>">
	<input type="submit" name="next" value="<?php echo get_lang('Next');?>">
</tr>

</table>
</form>
<?php 
// Display the footer
Display :: display_footer();
?>
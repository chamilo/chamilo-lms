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
* 	@version $Id: attach_question.php 10705 2007-01-12 22:40:01Z pcool $
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
$table_user 				= Database :: get_main_table(TABLE_MAIN_USER);
$table_survey 				= Database :: get_course_table(TABLE_SURVEY);
$table_group 				= Database :: get_course_table(TABLE_SURVEY_GROUP);
$table_survey_question		= Database :: get_course_table(TABLE_SURVEY_QUESTION);
$table_course 				= Database :: get_main_table(TABLE_MAIN_COURSE);
$table_course_survey_rel 	= Database :: get_main_table(TABLE_MAIN_COURSE_SURVEY);
$table_languages 			= Database::get_main_table(TABLE_MAIN_LANGUAGE);

// Language variables
$MonthsLong = array(get_lang('JanuaryLong'), get_lang('FebruaryLong'), get_lang('"MarchLong'), get_lang('AprilLong'), get_lang('MayLong'), get_lang('JuneLong'), get_lang('JulyLong'), get_lang('AugustLong'), get_lang('SeptemberLong'), get_lang('OctoberLong'), get_lang('NovemberLong'), get_lang('DecemberLong'));
$tool_name = get_lang('CreateNewSurvey');
$tool_name1 = get_lang('CreateNewSurvey');

// breadcrumbs
$interbreadcrumb[] = array ("url" => "survey_list.php", "name" => get_lang('Survey'));

// Variables
$arr_date = explode("-",date("Y-m-d"));
$curr_year = $arr_date[0];
$curr_month = $arr_date[1];
$curr_day = $arr_date[2];
/** @todo use $_course array */
$course_id = $_SESSION['_course']['id'];

// $_GET and $_POST
/** @todo replace $_REQUEST with $_GET or $_POST */
$oldsurveyid = $_REQUEST['surveyid'];
$qids=$_REQUEST['qid'];
$groupid=$_REQUEST['groupid'];

$survey_name = surveymanager::get_surveyname($db_name,$oldsurveyid);

/** @todo this piece of code is duplicated in many scripts. Find out where it is used and remove all other occurences */
if ($_POST['action'] == 'add_survey')
{
	$surveycode=$_POST['survey_code'];
	$surveytitle = $_POST['survey_title'];
	$surveysubtitle = $_POST['survey_subtitle'];
	$author = $_POST['author'];
	$survey_language = $_POST['survey_language'];
	$availablefrom = $_POST['fyear']."-".$_POST['fmonth']."-".$_POST['fday'];
	$availabletill = $_POST['end_fyear']."-".$_POST['end_fmonth']."-".$_POST['end_fday'];
	$isshare = $_POST['isshare'];
	$surveytemplate = $_POST['template'];
	$surveyintroduction = $_POST['content'];
	$surveythanks = $_POST['thanks'];
	$savailablefrom=mktime(0,0,0,$_POST['fmonth'],$_POST['fday'], $_POST['fyear']);
    $savailabletill=mktime(0,0,0,$_POST['end_fmonth'],$_POST['end_fday'], $_POST['end_fyear']);
	$qids=$_REQUEST['qids'];
	$surveyid = $_REQUEST['surveyid'];
	if(isset($_POST['back']))
	{
		$surveyid = $_REQUEST['surveyid'];
		header("location:create_from_existing_survey.php?surveyid=$surveyid");

	}
	$surveytitle=trim($surveytitle);
	$surveycode=trim($surveycode);
	if(empty ($surveytitle))
	{

		$error_message = get_lang('PleaseEnterSurveyTitle');
	}
	elseif ($savailabletill<=$savailablefrom){
	$error_message = get_lang('PleaseEnterValidDate');
	}
	elseif (empty ($surveycode)){
	$error_message = get_lang('PleaseEnterValidCode');
	}
	else
    {
		$result=SurveyManager::get_survey_code($table_survey,$surveycode);
		if(!empty($result))
		{
         	 $error_message=get_lang('ThisCodeAlreadyExist');
		}
		else
		{
	$survey_id = SurveyManager::create_survey($surveycode, $surveytitle, $surveysubtitle, $author, $survey_language, $availablefrom, $availabletill, $isshare, $surveytemplate, $surveyintroduction, $surveythanks, $table_survey, $table_group);
	$curr_dbname=SurveyManager::create_course_survey_rel($cidReq,$survey_id,$table_course,$table_course_survey_rel);
	$qids=$_REQUEST['qids'];
	$message_me=surveymanager::question_import($survey_id,$qids,$db_name,$curr_dbname);
	if (isset($_POST['next']))
	{
		if(isset($message_me) && $message_me)
	  {
		 header("location:select_question_group.php?surveyid=$survey_id&message=$message_me");
	     exit;
	  }
	  else
      {
     	header("location:select_question_group.php?surveyid=$survey_id&message=$message_me");
	    exit;
	  }
	}
	else
	{
		 header("location:survey_list.php");
		 exit;
	}
	}
	}
}
Display::display_header($tool_name);
api_display_tool_title($tool_name1);
//echo "<pre>";
//print_r($_SESSION);
//echo "</pre>";
if( isset($error_message) )
{
	Display::display_error_message($error_message);
}
?>
<SCRIPT LANGUAGE="JavaScript">
<!-- Begin
function displayTemplate(form) {
var inf = form.template.value;
if(inf=="")
	{
	   alert("Please Select a Template");
	}
else
	{
window.open(inf+".htm", 'popup', 'width=900,height=800,toolbar = no, status = no');
	}
//window.open(inf+".htm");
//win.document.write("" + inf + "");
}
//  End -->
</script>

<script src=tbl_change.js type="text/javascript" language="javascript"></script>
<form name="new_calendar_item" method="post" action="<?php echo $_SERVER['PHP_SELF'];?>">
<input type="hidden" name="action" value="add_survey">
<input type="hidden" name="qids" value="<?php echo $qids; ?>">
<input type="hidden" name="db_name" value="<?php echo $db_name; ?>">
<input type="hidden" name="groupid" value="<?php echo $groupid; ?>">
<input type="hidden" name="surveyid" value="<?php echo $oldsurveyid; ?>">
<table>
<tr>
 <td><?php echo get_lang('SurveyCode'); ?></td>
 <td><input type="text" name="survey_code" size="20" maxlength="39" value="<?php echo $surveycode; ?>"></td>
</tr>
<tr>
  <td><?php echo get_lang('SurveyTitle'); ?></td>
  <td><input type="text" name="survey_title" size="40" maxlength="79" value="<?php echo $surveytitle; ?>"></td>
</tr>
<tr>
  <td><?php echo get_lang('SurveySubtitle'); ?></td>
  <td><input type="text" name="survey_subtitle" size="40" maxlength="79" value="<?php echo $surveysubtitle; ?>"></td>
</tr>
<tr>
<?php
   if($_SESSION['is_platformAdmin']=='1'||$_SESSION['is_courseAdmin'])
   {
 echo "<td>";
 echo get_lang('Author');
 echo "</td>";
 echo "<td>";
 UserManager::get_teacher_list($course_id, $author_id);
 echo "</td>";
   }
?>
</tr>
<tr>
  <td><?php echo get_lang('SurveyLanguage'); ?>&nbsp;</td>
  <td>
	<select name="survey_language">
    <option value="english"  selected="selected">English</option>
    <option value="french" >Fran&ccedil;ais</option>
    <option value="dutch" >Nederlands</option>
    </select>
  </td>
</tr>
<tr id="subtitle">
  <td><?php echo get_lang('AvailableFrom'); ?>&nbsp;</td>
  <td>
<select name="fday">
<?php
	for($i=1;$i<=31;$i++){
	if($i<=9) $val = "0".$i;
	else $val = $i;
	if ($val==$curr_day) $selected = "selected";
	else $selected = "";
	echo "<option value=\"$val\" $selected>$i</option>";
	}
?>
</select>
<!-- month: january ->
december -->
<select name="fmonth">
<?php
		for($i=1;$i<count($MonthsLong);$i++)
		{
			if($i<=9)
			$val = "0".$i;
			else
			$val = $i;
			if($val == $curr_month)
			echo   "<option value=\"$val\" selected>".$MonthsLong[$i-1]."</option>\n";
			else
			echo   "<option value=\"$val\">".$MonthsLong[$i-1]."</option>\n";
		}
?>
</select>
<select name="fyear">
<?php
	for($i=$curr_year;$i<=$curr_year+10;$i++){
		if($i == $curr_year)
		echo   "<option value=\"$i\" selected>$i</option>\n";
		else
		echo   "<option value=\"$i\">$i</option>\n";
	}
?>
</select>
<a title="Calendar" href="javascript:openCalendar('new_calendar_item', 'f')"><img src="../img/calendar_select.gif" border="0" align="absmiddle"/></a></td>
	</tr>

<tr id="subtitle">
  <td><?php echo get_lang('AvailableTill'); ?>&nbsp;</td>
  <td>
<select name="end_fday">
	<?php for($i=1;$i<=31;$i++){
		if($i<=9)
		$val = "0".$i;
		else
		$val = $i;
		if ($val==$curr_day) $selected = "selected";
		else $selected = "";
		echo "<option value=\"$val\" $selected>$i</option>";
		}
	?>
</select>
    <!-- month: january ->
december -->
<select name="end_fmonth">
<?php
		for($i=1;$i<count($MonthsLong);$i++)
		{
			if($i<=9)
			$val = "0".$i;
			else
			$val = $i;
			if($val == $curr_month)
			echo   "<option value=\"$val\" selected>".$MonthsLong[$i-1]."</option>\n";
			else
			echo   "<option value=\"$val\">".$MonthsLong[$i-1]."</option>\n";
		}
?>
</select>
<select name="end_fyear">
<?php
	for($i=$curr_year;$i<=$curr_year+10;$i++){
		if($i == $curr_year+1)
		echo   "<option value=\"$i\" selected>$i</option>\n";
		else
		echo   "<option value=\"$i\">$i</option>\n";
	}
?>
</select>
<a title="Calendar" href="javascript:openCalendar('new_calendar_item', 'end_f')"><img src="../img/calendar_select.gif" border="0" align="absmiddle"/></a></td>
	</tr>
<tr>
<td valign="top"><?php echo get_lang('IsShareSurvey'); ?>&nbsp;</td>
<td>
<input type="radio" name="isshare" value="1">Yes&nbsp;<input type="radio" name="isshare" value="0" checked>No
</td>
</tr>
 <tr>
  <td><?php echo get_lang('SurveyTemplate'); ?>&nbsp;</td>
<td>
<select name="template">
<option value="template1">OFO_nl</option>
<option value="template2">IFA_fr</option>
<option value="template3">SPF P&O_FR</option>
<option value="template4">FOD P&O_NL</option>
<option value="template5">Blank</option>
</select>
<input type="button" value="<?php echo get_lang('Preview');?>" onClick="displayTemplate(new_calendar_item)">
</td>
</tr>
<tr><td valign="top"><?php echo get_lang('SurveyIntroduction'); ?>&nbsp;</td>
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

		$sql="SELECT isocode FROM ".$table_languages." WHERE english_name='".$_SESSION["_course"]["language"]."'";
		$result_sql=api_sql_query($sql);
		$isocode_language=mysql_result($result_sql,0,0);
		$oFCKeditor->Config['DefaultLanguage'] = $isocode_language;

		$return =	$oFCKeditor->CreateHtml();

		echo $return;
   ?>
          <br>
        </td>
 </tr>
 <tr><td valign="top"><?php echo get_lang('Thanks'); ?>&nbsp;</td>
 <td>
 <?php
      require_once(api_get_path(LIBRARY_PATH) . "/fckeditor/fckeditor.php");
		$oFCKeditor = new FCKeditor('thanks') ;
		$oFCKeditor->BasePath	= api_get_path(WEB_PATH) . 'main/inc/lib/fckeditor/' ;
		$oFCKeditor->Height		= '300';
		$oFCKeditor->Width		= '600';
		$oFCKeditor->Value		= $thanks;
		$oFCKeditor->Config['CustomConfigurationsPath'] = api_get_path(REL_PATH)."main/inc/lib/fckeditor/myconfig.js";
		$oFCKeditor->ToolbarSet = "Survey";

		$sql="SELECT isocode FROM ".$table_languages." WHERE english_name='".$_SESSION["_course"]["language"]."'";
		$result_sql=api_sql_query($sql);
		$isocode_language=mysql_result($result_sql,0,0);
		$oFCKeditor->Config['DefaultLanguage'] = $isocode_language;

		$return =	$oFCKeditor->CreateHtml();

		echo $return;
 ?>
 <br>
 </td>
 </tr>
 <tr>
 <td><?php echo get_lang('SurveyAttached');?>&nbsp;&nbsp;<?php echo $survey_name;?></td>
 </tr>
 </table>
 <tr>
  <td>&nbsp;</td>
  <td><input type="submit" name="back" value="<?php echo get_lang('Back');?>"></td>
  <td><input type="submit" name="saveandexit" value="<?php echo get_lang('CreateLater'); ?>"></td>
  <td><input type="submit" name="next" value="<?php echo get_lang('Next'); ?>"></td>
</tr>
</table>
</form>
</table>
<?php
Display :: display_footer();
?>
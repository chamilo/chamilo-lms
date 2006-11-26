<?php

// $Id: course_edit.php,v 1.11 2005/07/08 13:25:54 olivierb78 Exp $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2003 University of Ghent (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) Olivier Brouckaert

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact: Dokeos, 181 rue Royale, B-1000 Brussels, Belgium, info@dokeos.com
==============================================================================
*/
/**
==============================================================================
*	@package dokeos.admin
==============================================================================
*/
/*
==============================================================================
		INIT SECTION
==============================================================================
*/
// name of the language file that needs to be included 
$language_file = 'survey';

include ('../inc/global.inc.php');
//api_protect_admin_script();
include (api_get_path(LIBRARY_PATH).'/fileManage.lib.php');
require_once (api_get_path(LIBRARY_PATH)."/surveymanager.lib.php");
$status = surveymanager::get_status();
if($status==5)
{
api_protect_admin_script();
}
require_once (api_get_path(LIBRARY_PATH)."/usermanager.lib.php");
// the variables for the days and the months
// Defining the months of the year to allow translation of the months
$MonthsLong = array(get_lang("JanuaryLong"), get_lang("FebruaryLong"), get_lang("MarchLong"), get_lang("AprilLong"), get_lang("MayLong"), get_lang("JuneLong"), get_lang("JulyLong"), get_lang("AugustLong"), get_lang("SeptemberLong"), get_lang("OctoberLong"), get_lang("NovemberLong"), get_lang("DecemberLong")); 
$arr_date = explode("-",date("Y-m-d"));
$curr_year = $arr_date[0];
$curr_month = $arr_date[1];
$curr_day = $arr_date[2];
$cidReq=$_GET['cidReq'];
$table_course = Database :: get_main_table(TABLE_MAIN_COURSE);
$table_course_survey_rel = Database :: get_main_table(TABLE_MAIN_COURSE_SURVEY);
$table_survey = Database :: get_course_table('survey');
$tbl_category = Database :: get_main_table(TABLE_MAIN_CATEGORY);
$noPHP_SELF = true;
$tool_name = get_lang('ModifySurveyInformation');
$interbredcrump[] = array ("url" => "survey_list.php", "name" => get_lang('Survey'));
$coursePathWeb = api_get_path(WEB_COURSE_PATH);
$coursePathSys = api_get_path(SYS_COURSE_PATH);
$maxFilledSpace = get_setting('default_document_quotum');
$course_code = $id; //note confusion again: int id - string id - string code...
$surveyid = $_REQUEST['surveyid'];
$formSent=0;
if ($_POST['action'] == 'update_survey')
{
	$formSent=1;
	$cidReq=$_GET['cidReq'];
    $surveyid=$_REQUEST['surveyid'];
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
	$surveytitle=trim($surveytitle);
	$surveycode=trim($surveycode);
	if(isset($_POST['back'])){
	header("location:survey_list.php?cidReq=$cidReq");
	exit;
	}
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
	  $cidReq = $_GET['cidReq'];
	  $table_survey = Database :: get_course_table('survey');	   $curr_dbname=SurveyManager::update_survey($surveyid,$surveycode,$surveytitle,$surveysubtitle,$author,$survey_language,$availablefrom,$availabletill,$isshare,$surveytemplate,$surveyintroduction,$surveythanks,$cidReq,$table_course);	  
		if(isset($_POST['next']))
		{
			header("location:select_question_group.php?surveyid=$surveyid&cidReq=$cidReq&curr_dbname=$curr_dbname");
			exit;
		}
		else
		{
		header("location:survey_list.php?cidReq=$cidReq");
	    exit;
		}
	}	
}
Display::display_header($tool_name);
api_display_tool_title($tool_name);
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
window.open(inf+".htm", 'popup', 'width=600,height=600,toolbar = no, status = no');
	}
//window.open(inf+".htm");
//win.document.write("" + inf + "");
}
//  End -->
</script>
<script src=tbl_change.js type="text/javascript" language="javascript"></script>
<?php
$sql = "select * from $table_survey where survey_id='$surveyid'";
$res = api_sql_query($sql);
$obj = mysql_fetch_object($res);
$arr_avail_from = explode("-",$obj->avail_from);
$avail_year_from = $arr_avail_from['0'];
$avail_month_from = $arr_avail_from['1'];
$avail_day_from = $arr_avail_from['2'];

$arr_avail_till = explode("-",$obj->avail_till);
$avail_year_till = $arr_avail_till['0'];
$avail_month_till = $arr_avail_till['1'];
$avail_day_till = $arr_avail_till['2'];
$template = $obj->template;
$lang=$obj->lang;

?>
<form name="new_calendar_item" method="post" action="<?php echo $_SERVER['PHP_SELF'];?>?cidReq=<?php echo $cidReq; ?>">
<input type="hidden" name="surveyid" value="<?php echo $surveyid; ?>">
<input type="hidden" name="action" value="update_survey">
<table>
<tr>
 <td><?php echo get_lang('surveycode'); ?></td>
 <td><input type="text" name="survey_code" size="20"   maxlength="19" value="<?php if($formSent){echo $surveycode;}else {echo $obj->code;} ?>"></td>
</tr>
<tr>
  <td><?php echo get_lang('surveytitle'); ?></td>
  <td><input type="text" name="survey_title" size="40"  maxlength="79" value="<?php if($formSent){echo $surveytitle;}else {echo $obj->title;} ?>"></td>
</tr>
<tr>
  <td><?php echo get_lang('surveysubtitle'); ?></td>
  <td><input type="text" name="survey_subtitle" size="40"   maxlength="79" value="<?php if($formSent){echo $surveysubtitle;}else {echo $obj->subtitle;} ?>"></td>
</tr>
<tr>
  <td><?php echo get_lang('author'); ?></td>
  <td>
  	<?php
	UserManager::get_teacher_list($cidReq,$obj->author);
	?>  	
  </td>
</tr>
<tr>
  <td><?php echo get_lang('surveylanguage'); ?>&nbsp;</td>
  <td>
	<select name="survey_language">
    <option value="english" <?if($lang=='english') echo "selected";?>>English</option>
    <option value="french" <?if($lang=='french') echo "selected";?>>Fran&ccedil;ais</option>
    <option value="dutch" <?if($lang=='dutch') echo "selected";?>>Nederlands</option>
    </select>
  </td>
</tr>
<tr id="subtitle">
  <td><?php echo get_lang('availablefrom'); ?>&nbsp;</td>
  <td>	
        <select name="fday">
		<?php for($i=1;$i<=31;$i++){
			if($i<=9)
			$val = "0".$i;
			else
			$val = $i;
			if($val==$avail_day_from) $selected="selected";
			else $selected="";
			echo "<option value=\"$val\" $selected>$i</option>\n";
			}
		?>
        </select>
        <!-- month: january ->
december -->
<select name="fmonth">
	<?
		for($i=1;$i<count($MonthsLong);$i++)
		{
			if($i<=9)
			$val = "0".$i;
			else
			$val = $i;
			if($val == $avail_month_from)
			echo   "<option value=\"$val\" selected>".$MonthsLong[$i-1]."</option>\n";
			else
			echo   "<option value=\"$val\">".$MonthsLong[$i-1]."</option>\n";
		}
	?>
</select>
<select name="fyear">
<?php 
	for($i=$curr_year;$i<=$curr_year+10;$i++){
		if($i == $avail_year_from)
		echo   "<option value=\"$i\" selected>$i</option>\n";
		else
		echo   "<option value=\"$i\">$i</option>\n";
	}
?>
</select>
<a title="Calendar" href="javascript:openCalendar('new_calendar_item', 'f')"><img src="../img/calendar_select.gif" border="0" align="absmiddle"/></a></td>
	</tr>
				
<tr id="subtitle">
  <td><?php echo get_lang('availabletill'); ?>&nbsp;</td>
  <td>
  	  <select name="end_fday">
		<?php for($i=1;$i<=31;$i++){
			if($i<=9)
			$val = "0".$i;
			else
			$val = $i;
			if($val==$avail_day_till) $selected="selected";
			else $selected="";
			echo "<option value=\"$val\" $selected>$i</option>\n";
			}
		?>
    </select>
    <!-- month: january ->
december -->
<select name="end_fmonth">
	<?
		for($i=1;$i<count($MonthsLong);$i++)
		{
			if($i<=9)
			$val = "0".$i;
			else
			$val = $i;
			if($val == $avail_month_till)
			echo   "<option value=\"$val\" selected>".$MonthsLong[$i-1]."</option>\n";
			else
			echo   "<option value=\"$val\">".$MonthsLong[$i-1]."</option>\n";
		}
	?>
</select>
<select name="end_fyear">
<?php 
	for($i=$curr_year;$i<=$curr_year+10;$i++){
		if($i == $avail_year_till)
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
<input type="radio" name="isshare" value="1" <?php if($obj->is_shared=='1') echo "checked";?>>Yes&nbsp;<input type="radio" name="isshare" value="0" <?php if($obj->is_shared=='0') echo "checked";?>>No
</td>
</tr>

<tr><td valign="top"><?php echo get_lang('surveyintroduction'); ?>&nbsp;</td>
<td>
<?php
   api_disp_html_area('content',$obj->intro,'300px');
?>
 <br>
 </td>
 </tr>
 <tr><td valign="top"><?php echo get_lang('Thanks'); ?>&nbsp;</td>
<td>
<?php
   api_disp_html_area('thanks',$obj->surveythanks,'200px');
?>
 <br>
 </td>
 </tr>
 <tr>
 <td></td>
 <td><input type="submit" name="back" value="<?php echo get_lang('back'); ?>">&nbsp;<input type="submit" name="updateandreturn" value="<?php echo get_lang('saveandexit'); ?>">&nbsp;<input type="submit" name="next" value="<?php echo get_lang('next'); ?>"></td>
 </tr>
</table>
</form>
</table>
<?php
Display :: display_footer();
?>


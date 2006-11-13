<?php
// $Id: course_add.php,v 1.10 2005/05/30 11:46:48 bmol Exp $
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
$langFile = 'survey';

/*
-----------------------------------------------------------
	Including necessary files
-----------------------------------------------------------
*/
require_once ('../inc/global.inc.php');

require_once (api_get_path(LIBRARY_PATH).'/fileManage.lib.php');
require_once (api_get_path(CONFIGURATION_PATH) ."/add_course.conf.php");
require_once (api_get_path(LIBRARY_PATH)."/add_course.lib.inc.php");
require_once (api_get_path(LIBRARY_PATH)."/course.lib.php");
require_once (api_get_path(LIBRARY_PATH)."/groupmanager.lib.php");
require_once (api_get_path(LIBRARY_PATH)."/surveymanager.lib.php");
require_once (api_get_path(LIBRARY_PATH)."/usermanager.lib.php");

/*
-----------------------------------------------------------
	Table definitions
-----------------------------------------------------------
*/
$table_survey 				= Database :: get_course_table('survey');
$table_group 				= Database :: get_course_table('survey_group');
$table_user 				= Database :: get_main_table(MAIN_USER_TABLE);
$table_course 				= Database :: get_main_table(MAIN_COURSE_TABLE);
$table_course_survey_rel 	= Database :: get_main_table(MAIN_COURSE_SURVEY_TABLE);


/*
-----------------------------------------------------------
	some permissions stuff (???)
-----------------------------------------------------------
*/
$status = surveymanager::get_status();
if($status==5)
{
api_protect_admin_script();
}

/*
-----------------------------------------------------------
	some language stuff 
-----------------------------------------------------------
*/
// an api function for this would be nice since this is used in a lot of places in Dokeos
$MonthsLong = array(get_lang("JanuaryLong"), get_lang("FebruaryLong"), get_lang("MarchLong"), get_lang("AprilLong"), get_lang("MayLong"), get_lang("JuneLong"), get_lang("JulyLong"), get_lang("AugustLong"), get_lang("SeptemberLong"), get_lang("OctoberLong"), get_lang("NovemberLong"), get_lang("DecemberLong")); 
$tool_name = get_lang('CreateNewSurvey');
$tool_name1 = get_lang('Newsurvey');

$arr_date = explode("-",date("Y-m-d"));
$curr_year = $arr_date[0];
$curr_month = $arr_date[1];
$curr_day = $arr_date[2];

//$cidReq = $_SESSION[_course][id];
$cidReq = $_GET['cidReq'];
$page = $_REQUEST['page'];
$course_id = $_SESSION['_course']['id'];
$todate=date('j');	

/*
-----------------------------------------------------------
	Breadcrumbs
-----------------------------------------------------------
*/
$interbreadcrumbs[] = array ("url" => "survey_list.php", "name" => get_lang('Survey'));

/*
-----------------------------------------------------------

-----------------------------------------------------------
*/
if($surveyid = $_REQUEST['surveyid'])
{
	if ($_POST['action'] == 'update_survey')
	{
		// @todo: replace the $_REQUEST by $_POST or $_GE
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
				header("location:select_question_group.php?surveyid=$surveyid&cidReq=$cidReq&curr_dbname=$curr_dbname");
			else
				header("location:survey_list.php?cidReq=$cidReq");
			exit;
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
	
	?>
	<form name="new_calendar_item" method="post" action="<?php echo $_SERVER['PHP_SELF'];?>?cidReq=<?php echo $cidReq; ?>">
	<input type="hidden" name="surveyid" value="<?php echo $surveyid; ?>" />
	<input type="hidden" name="action" value="update_survey" />
	<table>
	<tr>
	 <td><?php echo get_lang('SurveyCode'); ?></td>
	 <td><input type="text" name="survey_code" size="20" maxlength="19" value="<?php echo $obj->code; ?>" /></td>
	</tr>
	<tr>
	  <td><?php echo get_lang('SurveyTitle'); ?></td>
	  <td><input type="text" name="survey_title" size="40" maxlength="79" value="<?php echo $obj->title ?>" /></td>
	</tr>
	<tr>
	  <td><?php echo get_lang('SurveySubtitle'); ?></td>
	  <td><input type="text" name="survey_subtitle" size="40" maxlength="79" value="<?php echo $obj->subtitle ?>" /></td>
	</tr>
	<tr>
	  <td><?php echo get_lang('Author'); ?></td>
	  <td>
	  	<?php
		UserManager::get_teacher_list($cidReq,$obj->author);
		?>  	
	  </td>
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
	<tr>
	  <td><?php echo get_lang('AvailableFrom'); ?>&nbsp;</td>
	  <td>	
	        <select name="fday">
			<?php 
		       
	           for($i=$todate;$i<=31;$i++){
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
	<a title="Calendar" href="javascript:openCalendar('new_calendar_item', 'f')"><img src="../img/calendar_select.gif" border="0" align="middle"/></a></td>
		</tr>
					
	<tr>
	  <td><?php echo get_lang('AvailableTill'); ?>&nbsp;</td>
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
	<a title="Calendar" href="javascript:openCalendar('new_calendar_item', 'end_f')"><img src="../img/calendar_select.gif" border="0" align="middle"/></a></td>
		</tr>
	<tr>
	<td valign="top"><?php echo get_lang('IsShareSurvey'); ?>&nbsp;</td>
	<td>
	<input type="radio" name="isshare" value="1" <?php if($obj->is_shared=='1') echo "checked";?> />Yes&nbsp;<input type="radio" name="isshare" value="0" <?php if($obj->is_shared=='0') echo "checked";?> />No
	</td>
	</tr>
	<tr><td valign="top"><?php echo get_lang('SurveyIntroduction'); ?>&nbsp;</td>
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
	 </table>
	 <tr>
	  <td>&nbsp;</td>
	  <td><input type="submit" name="updateandreturn" value="<?php echo get_lang('SaveAndExit'); ?>" /></td>
	   <td><input type="submit" name="next" value="<?php echo get_lang('Next'); ?>" /></td>
	  </tr>
	</table>
	</form>
	</table>
	<?php
	}
/*
-----------------------------------------------------------
	
-----------------------------------------------------------
*/
else
{
	if($_POST['back'])
	{
	 $cidReq=$_GET['cidReq'];
	 header("location:survey.php?cidReq=$cidReq");
	 exit;
	}
	/*
	-----------------------------------------------------------
		Action Handling
	-----------------------------------------------------------
	*/	
	
	if ($_POST['action'] == 'add_survey')
	{
		$surveycode=$_POST['survey_code'];
		$surveytitle = $_POST['survey_title'];
		$surveysubtitle = $_POST['survey_subtitle'];
		//$cidReq = $dbNamePrefix.$_POST['cidReq'];
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
		/*
		elseif (empty ($surveytemplate)){
		$error_message = get_lang('PleaseSelectATemplate');
		}
		*/
		else
	    {
			$result=SurveyManager::get_survey_code($table_survey,$surveycode);
					
			if(!empty($result))
				{
				$error_message=get_lang('ThisCodeAlradyExist');
			}
			else
			{
		$survey_id = SurveyManager::create_survey($surveycode, $surveytitle, $surveysubtitle, $author, $survey_language, $availablefrom, $availabletill, $isshare, $surveytemplate, $surveyintroduction, $surveythanks, $table_survey, $table_group);
	    $cidReq=$_GET['cidReq'];
		$curr_dbname=SurveyManager::create_course_survey_rel($cidReq,$survey_id,$table_course,$table_course_survey_rel);
		if (isset($_POST['next']))
		{
			$cidReq=$_GET['cidReq'];
			$page = $_REQUEST['page']; header("location:select_question_group.php?surveyid=$survey_id&cidReq=$cidReq&curr_dbname=$curr_dbname&page=$page");
			 exit;
		}
		else
		{
			 $cidReq=$_GET['cidReq'];
			 header("location:survey_list.php?&cidReq=$cidReq");
			 exit;
		}
		}
		}	
	}
	
	/*
	-----------------------------------------------------------
		Display
	-----------------------------------------------------------
	*/	
	Display::display_header($tool_name);
	api_display_tool_title($tool_name1);
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
	<form name="new_calendar_item" method="post" action="<?php echo $_SERVER['PHP_SELF'];?>?cidReq=<?php echo $cidReq; ?>">
	<input type="hidden" name="action" value="add_survey" />
	<input type="hidden" name="page" value="<?php echo $page; ?>" />
	<!--<input type="hidden" name="cidReq" value="<?php echo $cidReq; ?>" />-->
	<table>
	<tr>
	 <td><?php echo get_lang('SurveyCode'); ?></td>
	 <td><input type="text" name="survey_code" size="20" value="<?php echo $surveycode; ?>"  maxlength="19" /></td>
	</tr>
	<tr>
	  <td><?php echo get_lang('SurveyTitle'); ?></td>
	  <td><input name="survey_title" type="text" value="<?php echo $surveytitle ?>" size="40" maxlength="79" /></td>
	</tr>
	<tr>
	  <td><?php echo get_lang('SurveySubtitle'); ?></td>
	  <td><input name="survey_subtitle" type="text" value="<?php echo $surveysubtitle ?>" size="40" maxlength="79" /></td>
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
	<tr>
	  <td><?php echo get_lang('AvailableFrom'); ?>&nbsp;</td>
	  <td>	
	<select name="fday">
	<?php 
	for($i=1;$i<=31;$i++)
	{
		if($i<=9)
		{
			$val = "0".$i;
		}
		else 
		{
			$val = $i;
		}
		if ($val==$curr_day) 
		{
			$selected = "selected";
		}
		else 
		{
			$selected = "";
		}
		echo "<option value=\"$val\" $selected>$i</option>";
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
		{
				$val = "0".$i;
		}
				else
		{
				$val = $i;
		}
				if($val == $curr_month)
		{
				echo   "<option value=\"$val\" selected>".$MonthsLong[$i-1]."</option>\n";
		}
				else
		{
				echo   "<option value=\"$val\">".$MonthsLong[$i-1]."</option>\n";
			}
	}
	?>
	</select>
	<select name="fyear">
	<?php 
	for($i=$curr_year;$i<=$curr_year+10;$i++)
	{
			if($i == $curr_year)
		{
			echo   "<option value=\"$i\" selected>$i</option>\n";
		}
			else
		{
			echo   "<option value=\"$i\">$i</option>\n";
		}
	}
	?>
	</select>
	<a title="Calendar" href="javascript:openCalendar('new_calendar_item', 'f')"><img src="../img/calendar_select.gif" border="0" align="middle"/></a></td>
		</tr>
					
	<tr>
	  <td><?php echo get_lang('AvailableTill'); ?>&nbsp;</td>
	  <td>
	<select name="end_fday">
	<?php 
	for($i=1;$i<=31;$i++)
	{
			if($i<=9)
		{
			$val = "0".$i;
		}
			else
		{
			$val = $i;
		}
		if ($val==$curr_day)
		{
			$selected = "selected";
		}
		else
		{
			$selected = "";
		}
			echo "<option value=\"$val\" $selected>$i</option>";	
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
		{
				$val = "0".$i;
		}
				else
		{
				$val = $i;
		}
				if($val == $curr_month)
		{
				echo   "<option value=\"$val\" selected>".$MonthsLong[$i-1]."</option>\n";
		}
				else
		{
				echo   "<option value=\"$val\">".$MonthsLong[$i-1]."</option>\n";
			}
	}
	?>
	</select>
	
	<select name="end_fyear">
	<?php 
	for($i=$curr_year;$i<=$curr_year+10;$i++)
	{
			if($i == $curr_year+1)
		{
			echo   "<option value=\"$i\" selected>$i</option>\n";
		}
			else
		{
			echo   "<option value=\"$i\">$i</option>\n";
		}
	}
	?>
	</select>
	<a title="Calendar" href="javascript:openCalendar('new_calendar_item', 'end_f')"><img src="../img/calendar_select.gif" border="0" align="middle"/></a></td>
		</tr>
	<tr>
	<td valign="top"><?php echo get_lang('IsShareSurvey'); ?>&nbsp;</td>
	<td>
	<input type="radio" name="isshare" value="1" />Yes&nbsp;<input type="radio" name="isshare" value="0"  checked="checked" />No
	</td>
	</tr>
	
	<tr>
		<td valign="top"><?php echo get_lang('SurveyIntroduction'); ?>&nbsp;</td>
		<td><?php api_disp_html_area('content',$content); ?></td>
	 </tr>
	 <tr>
	 	<td valign="top"><?php echo get_lang('Thanks'); ?>&nbsp;</td>
	 	<td><?php api_disp_html_area('thanks',$thanks); ?></td>
	 </tr>
	 </table>
	 <tr>
	  <td>&nbsp;</td>
	  <td><input type="submit" name="back" value="<?php echo get_lang('Back'); ?>" /></td>
	  <td><input type="submit" name="saveandexit" value="<?php echo get_lang('CreateLater'); ?>" /></td>
	  <td><input type="submit" name="next" value="<?php echo get_lang('Next'); ?>" /></td>
	</tr>
	</table>
	</table>
	</form>
	<?php
}
/*
-----------------------------------------------------------
	Footer
-----------------------------------------------------------
*/
Display :: display_footer();
?>
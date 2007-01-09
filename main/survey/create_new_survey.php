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
* 	@author Patrick Cool <patrick.cool@UGent.be>, Ghent University: cleanup, refactoring and rewriting large parts of the code
* 	@version $Id: create_new_survey.php 10632 2007-01-09 18:52:29Z pcool $
* 	@todo use quickform for the forms
* 	@todo the page contains code for adding and for editing. Both are almost the same and the edit code is not used because it (currently) uses a different file (survey_edit.php);
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
require_once (api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');
/*
-----------------------------------------------------------
	Table definitions
-----------------------------------------------------------
*/
$table_survey 				= Database :: get_course_table('survey');
$table_group 				= Database :: get_course_table('survey_group');
$table_user 				= Database :: get_main_table(TABLE_MAIN_USER);
$table_course 				= Database :: get_main_table(TABLE_MAIN_COURSE);
$table_course_survey_rel 	= Database :: get_main_table(TABLE_MAIN_COURSE_SURVEY);


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

// 	some language stuff 
// an api function for this would be nice since this is used in a lot of places in Dokeos
$MonthsLong = array(get_lang('JanuaryLong'), get_lang('FebruaryLong'), get_lang('"MarchLong'), get_lang('AprilLong'), get_lang('MayLong'), get_lang('JuneLong'), get_lang('JulyLong'), get_lang('AugustLong'), get_lang('SeptemberLong'), get_lang('OctoberLong'), get_lang('NovemberLong'), get_lang('DecemberLong')); 
$tool_name = get_lang('CreateNewSurvey');

/** @todo see if this is used, if not, remove it */
$page = $_REQUEST['page'];

/** @todo us the $_course arrray */
$course_id = $_SESSION['_course']['id'];
$todate=date('j');	

// breadcrumbs
$interbreadcrumb[] = array ("url" => "survey_list.php", "name" => get_lang('Survey'));

// Displaying the header
Display::display_header($tool_name);

// Displaying the tool title
api_display_tool_title($tool_name);

// initiate the object
$form = new FormValidator('forumcategory');

// settting the form elements
if ($_GET['action'] == 'edit' AND isset($_GET['survey_id']) AND is_numeric($_GET['survey_id']))
{
	$form->addElement('hidden', 'survey_id');
	
}
$form->addElement('text', 'survey_code', get_lang('SurveyCode'));
$form->addElement('text', 'survey_title', get_lang('SurveyTitle'));
$form->addElement('text', 'survey_subtitle', get_lang('SurveySubTitle'));
// author: won't do since we can use $_user
/** @todo only the available platform languages should be used => need an api get_languages and and api_get_available_languages (or a parameter) */
$lang_array = api_get_languages();
foreach ($lang_array['name'] as $key=>$value) 
{
	$languages[$lang_array['folder'][$key]] = $value; 
}
$form->addElement('select', 'survey_language', get_lang('Language'), $languages);
$form->addElement('datepicker', 'start_date', get_lang('StartDate'));
$form->addElement('datepicker', 'end_date', get_lang('EndDate'));
$group='';
$group[] =& HTML_QuickForm::createElement('radio', 'survey_share',null, get_lang('Yes'),1);
$group[] =& HTML_QuickForm::createElement('radio', 'survey_share',null, get_lang('No'),0);
$form->addGroup($group, 'survey_share', get_lang('ShareSurvey'), '&nbsp;');	
$form->addElement('html_editor', 'survey_introduction', get_lang('SurveyIntroduction'));
$form->addElement('html_editor', 'survey_thanks', get_lang('SurveyThanks'));
$form->addElement('submit', 'submit_survey', get_lang('Ok'));

// setting the rules
$form->addRule('survey_code', '<div class="required">'.get_lang('ThisFieldIsRequired'), 'required');
$form->addRule('survey_title', '<div class="required">'.get_lang('ThisFieldIsRequired'), 'required');
$form->addRule('start_date', get_lang('InvalidDate'), 'date');
$form->addRule('end_date', get_lang('InvalidDate'), 'date');
/** @todo add a rule that checks if the end_date > start_date */

// setting the default values
if ($_GET['action'] == 'edit' AND isset($_GET['survey_id']) AND is_numeric($_GET['survey_id']))
{
	$defaults['survey_id'] = $_GET['survey_id'];
}
else 
{
	$defaults['survey_language'] = $_course['language'];
	$defaults['start_date']['d'] = date('d');
	$defaults['start_date']['F'] = date('F');
	$defaults['start_date']['Y'] = date('Y');
	$defaults['start_date']['H'] = date('H');
	$defaults['start_date']['i'] = date('i');
	$defaults['end_date']['d'] = date('d');
	$defaults['end_date']['F'] = date('F');
	$defaults['end_date']['Y'] = date('Y');
	$defaults['end_date']['H'] = date('H');
	$defaults['end_date']['i'] = date('i');
	$defaults['survey_share']['survey_share'] = 0;
}
$form->setDefaults($defaults);

// The validation or display
if( $form->validate() )
{
   $values = $form->exportValues();
   $return = store_survey($values);
   Display::display_confirmation_message($return['message']);
}
else
{
	$form->display();
}





// Footer
Display :: display_footer();

/**
 * This function stores a survey in the database
 *
 * @param array $values
 * @return array $return the type of return message that has to be displayed and the message in it
 * 
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @version januari 2007
 * 
 * @todo move this function to surveymanager.inc.lib.php
 */
function store_survey($values)
{
	global $_user; 
	
	if (!$values['survey_id'] OR !is_numeric($values['survey_id']))
	{
		$table_survey = Database :: get_course_table('survey');
		$sql = "INSERT INTO $table_survey (code,title, subtitle, author, lang, avail_from, avail_till, is_shared, template, intro, surveythanks, creation_date) VALUES (
					'".mysql_real_escape_string($values['survey_code'])."',
					'".mysql_real_escape_string($values['survey_title'])."',
					'".mysql_real_escape_string($values['survey_subtitle'])."',
					'".mysql_real_escape_string($_user['user_id'])."',
					'".mysql_real_escape_string($values['survey_language'])."',
					'".mysql_real_escape_string($values['start_date'])."',
					'".mysql_real_escape_string($values['end_date'])."',
					'".mysql_real_escape_string($values[''])."',
				
				
				','$surveytitle','$surveysubtitle','$author','$survey_language','$availablefrom','$availabletill','$isshare','$surveytemplate','$surveyintroduction','$surveythanks',curdate())";
		$result = api_sql_query($sql, __FILE__, __LINE__);
		$survey_id = mysql_insert_id();
		
		$table_survey_group = Database :: get_course_table('survey_group');
		$sql = "INSERT INTO $table_survey_group (group_id,survey_id,groupname,introduction) values('','$survey_id','No Group','This is your Default Group')";
		$result = api_sql_query($sql, __FILE__, __LINE__);
		
		$return['message'] = 'insert';
		$return['type'] = 'confirmation';
	}
	else 
	{
		$return['message'] = 'update';
		$return['type'] = 'confirmation';
	}
	
	return $return;
}









































// finding the current day, month, year
$arr_date = explode("-",date("Y-m-d"));
$curr_year = $arr_date[0];
$curr_month = $arr_date[1];
$curr_day = $arr_date[2];
// number of days in current month
$todate=date('j');	
/*
-----------------------------------------------------------
	Editing a survey
-----------------------------------------------------------
*/
if($surveyid = $_REQUEST['surveyid'])
{
	if ($_POST['action'] == 'update_survey')
	{
		// @todo: replace the $_REQUEST by $_POST or $_GE
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
		$surveyintroduction = $_POST['introduction'];
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
		  /** @todo remove the unused parameters) */
		  $curr_dbname=SurveyManager::update_survey($surveyid,$surveycode,$surveytitle,$surveysubtitle,$author,$survey_language,$availablefrom,$availabletill,$isshare,$surveytemplate,$surveyintroduction,$surveythanks,$_GET['cidReq'],$table_course);	  
			if(isset($_POST['next']))
				header("location:select_question_group.php?surveyid=$surveyid&curr_dbname=$curr_dbname");
			else
				header("location:survey_list.php");
			exit;
		}	
	}

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
	<form name="new_calendar_item" method="post" action="<?php echo $_SERVER['PHP_SELF'];?>">
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
	  	/** @todo remove the unused parameters */
		UserManager::get_teacher_list($_GET['cidReq'],$obj->author);
		?>  	
	  </td>
	</tr>
	<tr>
	  <td><?php echo get_lang('SurveyLanguage'); ?>&nbsp;</td>
	  <td>
		<select name="survey_language">
			<?php
				$languages = api_get_languages();
				foreach ($languages['name'] as $index => $name)
				{
					echo '<option value="'.$languages['folder'][$index].'">'.$name.'</option>';
				}
			?>
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
		<?php
			for($i=1;$i<=count($MonthsLong);$i++)
			{
				if($i<=9)
				{
					$val = "0".$i;
				}
				else
				{
					$val = $i;
				}
				if($val == $avail_month_from)
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
		<?php 
			for($i=1;$i<=count($MonthsLong);$i++)
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
	   require_once(api_get_path(LIBRARY_PATH) . "/fckeditor/fckeditor.php");
		$oFCKeditor = new FCKeditor('introduction') ;
		$oFCKeditor->BasePath	= api_get_path(WEB_PATH) . 'main/inc/lib/fckeditor/' ;
		$oFCKeditor->Height		= '300';
		$oFCKeditor->Width		= '600';
		$oFCKeditor->Value		= $obj->intro;
		$oFCKeditor->Config		= Array("Survey");
		
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
	<tr><td valign="top"><?php echo get_lang('Thanks'); ?>&nbsp;</td>
	<td>
	<?php
	   $oFCKeditor = new FCKeditor('thanks') ;
		$oFCKeditor->BasePath	= api_get_path(WEB_PATH) . 'main/inc/lib/fckeditor/' ;
		$oFCKeditor->Height		= '300';
		$oFCKeditor->Width		= '600';
		$oFCKeditor->Value		= $obj->surveythanks;
		
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
	Adding a survey
-----------------------------------------------------------
*/
else
{
	if($_POST['back'])
	{
		header("location:survey.php");
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
		$author = $_POST['author'];
		$survey_language = $_POST['survey_language'];
		$availablefrom = $_POST['fyear']."-".$_POST['fmonth']."-".$_POST['fday'];
		$availabletill = $_POST['end_fyear']."-".$_POST['end_fmonth']."-".$_POST['end_fday'];
		$isshare = $_POST['isshare'];
		$surveytemplate = $_POST['template'];
		$surveyintroduction = $_POST['introduction'];
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
				$error_message=get_lang('ThisCodeAlradyExists');
			}
			else
			{
		$survey_id = SurveyManager::create_survey($surveycode, $surveytitle, $surveysubtitle, $author, $survey_language, $availablefrom, $availabletill, $isshare, $surveytemplate, $surveyintroduction, $surveythanks, $table_survey, $table_group);
		/** @todo remove the unused parameters */
		$curr_dbname=SurveyManager::create_course_survey_rel($_GET['cidReq'],$survey_id,$table_course,$table_course_survey_rel);
		if (isset($_POST['next']))
		{
			$page = $_REQUEST['page']; 
			header("location:select_question_group.php?surveyid=$survey_id&curr_dbname=$curr_dbname&page=$page");
			exit;
		}
		else
		{
			 header("location:survey_list.php");
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
	//api_display_tool_title($tool_name);
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
	<form name="new_calendar_item" method="post" action="<?php echo $_SERVER['PHP_SELF'];?>">
	<input type="hidden" name="action" value="add_survey" />
	<input type="hidden" name="page" value="<?php echo $page; ?>" />
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
	  <td><?php echo get_lang('SurveyLanguage');?>&nbsp;</td>
	   <td>
		<select name="survey_language">
			<?php
				$languages = api_get_languages();
				foreach ($languages['name'] as $index => $name)
				{
					echo '<option value="'.$languages['folder'][$index];
					if ($languages['folder'][$index] == $_course['language'])
					{
						echo ' selected = "selected"';
					}
					echo '">'.$name.'</option>';
				}
			?>
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
	<?php
			for($i=1; $i<=12; $i++)
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
					echo   "<option value=\"$val\">".$MonthsLong[$i-1]."y</option>\n";
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
	<?php
			for($i=1;$i<=count($MonthsLong);$i++)
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
		<td>
		<?php 
			require_once(api_get_path(LIBRARY_PATH) . "/fckeditor/fckeditor.php");
			$oFCKeditor = new FCKeditor('introduction') ;
			$oFCKeditor->BasePath	= api_get_path(WEB_PATH) . 'main/inc/lib/fckeditor/' ;
			$oFCKeditor->Height		= '300';
			$oFCKeditor->Width		= '600';
			$oFCKeditor->Value		= $surveyintroduction;
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
		</td>
	 </tr>
	 <tr>
	 	<td valign="top"><?php echo get_lang('Thanks'); ?>&nbsp;</td>
	 	<td>
	 	<?php
	 	require_once(api_get_path(LIBRARY_PATH) . "/fckeditor/fckeditor.php");
			$oFCKeditor = new FCKeditor('thanks') ;
			$oFCKeditor->BasePath	= api_get_path(WEB_PATH) . 'main/inc/lib/fckeditor/' ;
			$oFCKeditor->Height		= '300';
			$oFCKeditor->Width		= '600';
			$oFCKeditor->Value		= $surveythanks;
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
 		</td>
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
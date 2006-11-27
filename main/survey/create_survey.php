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
* 	@version $Id: create_survey.php 10223 2006-11-27 14:45:59Z pcool $
*/

/*
==============================================================================
		INIT SECTION
==============================================================================
*/
// name of the language file that needs to be included 
$language_file = 'admin';

require_once ('../inc/global.inc.php');
api_protect_admin_script();
require_once (api_get_path(LIBRARY_PATH).'/fileManage.lib.php');
require_once (api_get_path(CONFIGURATION_PATH) ."/add_course.conf.php");
require_once (api_get_path(LIBRARY_PATH)."/add_course.lib.inc.php");
require_once (api_get_path(LIBRARY_PATH)."/surveymanager.lib.php");
//$table_category = Database :: get_main_table(TABLE_MAIN_CATEGORY);
$table_survey = Database :: get_main_table(MAIN_SURVEY_IFA_TABLE);
$table_course = Database::get_main_table(TABLE_MAIN_COURSE);
$tool_name = get_lang('a_survey');
$interbredcrump[] = array ("url" => "index.php", "name" => get_lang('AdministrationTools'));
$coursePathWeb = api_get_path(WEB_COURSE_PATH);
$coursePathSys = api_get_path(SYS_COURSE_PATH);
//define("NEW_SURVEY", 1);
//define("EXISTING_SURVEY", 2);
$newsurvey = '0';
//$existingsurvey = '1';


/*
if ($_POST['action'] == 'add_survey')
{
	$sql = "SELECT * FROM $table_survey WHERE id='".intval($_POST['id'])."'";
	$res = api_sql_query($sql,__FILE__,__LINE__);
	$id = mysql_fetch_object($res);
	$code = trim(strtoupper(stripslashes($_POST['code'])));
	//$author_name = $_POST['author'];
	//$title = $_POST['title'];
	//$language = trim(stripslashes($_POST['lang']));
	//$datefrom = $_POST['datefrom'];
	//$datetill = $_POST['datetill'];
	
	if (empty ($code))
		$error_message = get_lang('PleaseEnterSurveyCode');

	}
*/


if ($_POST['action'] == 'add_survey')
{
	
	
	$survey = $_POST['survey'];
	//$existingsurvey = trim(stripslashes($_POST['existingsurvey']));
			
	if ($survey==0)
	{
		 header("location:create_new_survey.php");
		 exit;
	}
	else
	{
		 header("location:create_ex_survey.php");
		 exit;

	}
}

Display::display_header($tool_name);
api_display_tool_title($tool_name);
?>



<form method="post" action="<?php echo $_SERVER['PHP_SELF'];?>">
<input type="hidden" name="action" value="add_survey"/>
<table>
<tr>
<td valign="top"></td>
<td>
<input class="checkbox" checked type="radio" name="survey" id="new_survey" value="<?php echo $newsurvey ?>"> <label for="visibility_open_world"><?php echo get_lang("Newsurvey") ?></label>
<br/>
<input class="checkbox" type="radio" name="survey" id="Existing_survey" value="<?php echo $existingsurvey ?>"  > <label for="visibility_open_platform"><?php echo  get_lang("Existingsurvey") ?></label><?php SurveyManager::select_survey_list();?>


</td></tr>

<tr>
 <td></td>
 <td></td>
</tr>

<tr>
  <td></td>
  
</tr>
<tr>
  <td>&nbsp;</td>
  <td><input type="submit" value="<?php echo get_lang('Ok'); ?>"></td>
</tr>
</table>

</form>



<?php

/*
==============================================================================
		FOOTER 
==============================================================================
*/
Display :: display_footer();
?>
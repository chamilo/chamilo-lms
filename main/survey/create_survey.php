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
$langFile = 'admin';

require_once ('../inc/global.inc.php');
api_protect_admin_script();
require_once (api_get_path(LIBRARY_PATH).'/fileManage.lib.php');
require_once (api_get_path(CONFIGURATION_PATH) ."/add_course.conf.php");
require_once (api_get_path(LIBRARY_PATH)."/add_course.lib.inc.php");
require_once (api_get_path(LIBRARY_PATH)."/surveymanager.lib.php");
//$table_category = Database :: get_main_table(MAIN_CATEGORY_TABLE);
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
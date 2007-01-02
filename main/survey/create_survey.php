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
* 	@version $Id: create_survey.php 10584 2007-01-02 15:09:21Z pcool $
*/

/*
==============================================================================
		INIT SECTION
==============================================================================
*/
// name of the language file that needs to be included 
$language_file = 'admin';

// including the global dokeos file
require_once ('../inc/global.inc.php');

// including additional libraries
/** @todo check if these are all needed */
/** @todo check if the starting / is needed. api_get_path probably ends with an / */
require_once (api_get_path(LIBRARY_PATH).'/fileManage.lib.php');
require_once (api_get_path(CONFIGURATION_PATH) ."/add_course.conf.php");
require_once (api_get_path(LIBRARY_PATH)."/add_course.lib.inc.php");
require_once (api_get_path(LIBRARY_PATH)."/surveymanager.lib.php");

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


//$table_category = Database :: get_main_table(TABLE_MAIN_CATEGORY);
$table_survey = Database :: get_main_table(MAIN_SURVEY_IFA_TABLE);
$table_course = Database::get_main_table(TABLE_MAIN_COURSE);
$tool_name = get_lang('CreateSurvey');
$interbreadcrumb[] = array ("url" => "index.php", "name" => get_lang('AdministrationTools'));
$coursePathWeb = api_get_path(WEB_COURSE_PATH);
$coursePathSys = api_get_path(SYS_COURSE_PATH);
$newsurvey = '0';

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
<input class="checkbox" checked type="radio" name="survey" id="new_survey" value="<?php echo $newsurvey ?>"> <label for="visibility_open_world"><?php echo get_lang('NewSurvey') ?></label>
<br/>
<input class="checkbox" type="radio" name="survey" id="Existing_survey" value="<?php echo $existingsurvey ?>"  > <label for="visibility_open_platform"><?php echo  get_lang('CreateFromExistingSurveys'); ?></label><?php SurveyManager::select_survey_list();?>


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
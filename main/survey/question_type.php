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
* 	@version $Id: question_type.php 10549 2006-12-24 16:08:47Z pcool $
*/

/*
==============================================================================
		INIT SECTION
==============================================================================
*/

// name of the language file that needs to be included 
$language_file = 'survey';

require_once ('../inc/global.inc.php');
api_protect_admin_script();
require_once (api_get_path(LIBRARY_PATH).'/fileManage.lib.php');
require_once (api_get_path(CONFIGURATION_PATH) ."/add_course.conf.php");
require_once (api_get_path(LIBRARY_PATH)."/add_course.lib.inc.php");
require_once (api_get_path(LIBRARY_PATH)."/surveymanager.lib.php");
require_once (api_get_path(LIBRARY_PATH)."/usermanager.lib.php");

// Database table definitions
$table_survey = Database :: get_main_table(TABLE_MAIN_SURVEY);
$table_group =  Database :: get_main_table(TABLE_MAIN_GROUP);
$table_question = Database :: get_main_table(TABLE_MAIN_SURVEYQUESTION);
$tool_name = get_lang('SelectQuestionByType');
$interbreadcrumb[] = array ("url" => "index.php", "name" => get_lang('Survey'));
//$questtype=$_POST['add_question'];

Display::display_header($tool_name);
api_display_tool_title($tool_name);

?>


<SCRIPT LANGUAGE="JavaScript" src="set_default_value.js"></SCRIPT>
		<SCRIPT LANGUAGE="JavaScript" src="validatelibraryitems.js"></script>
<table>
<tr>

<td>
<?api_display_tool_title($group_name);?>
</td>
</tr>
</table>

<?
if( isset($error_message) )
{
	Display::display_error_message($error_message);	
}

?>

<form name="question" method="post" action="question_bytype.php">
<table>
<tr>
<td>
<?php  echo get_lang('Selectype');?>
</td>
<td>

<select name="add_question">
	<option value="0"><?php  echo get_lang('Select');?></option>
	<option value="<?php  echo get_lang('YesNo');?>" <?php  if(isset($_POST['add_question'])){if($_POST['add_question']=="1")echo "selected";}?>><?php echo get_lang('YesNo');?></option>
	<option value="<?php  echo get_lang('MultipleChoiceSingle');?>" <?php  if(isset($_POST['add_question'])){if($_POST['add_question']=="2")echo "selected";}?>><?php echo get_lang('MultipleChoiceSingle');?></option>
	<option value="<?php  echo get_lang('MultipleChoiceMulti');?>" <?php  if(isset($_POST['add_question'])){if($_POST['add_question']=="3")echo "selected";}?>><?php echo get_lang('MultipleChoiceMulti');?></option>
	<option value="<?php  echo get_lang('Open');?>" <?php  if(isset($_POST['add_question'])){if($_POST['add_question']=="4")echo "selected";}?>><?php echo get_lang('Open');?></option>
	<option value="<?php echo get_lang('Numbered');?>" <?php if(isset($_POST['add_question'])){if($_POST['add_question']=="5")echo "selected";}?>><?php echo get_lang('Numbered');?></option>
</select>
</td>
</tr>
<tr></tr>
<tr>
<td></td>
<td>
<input type="submit" name= 'next' value="<?php echo get_lang('Next');?>">
</td>
</tr>
</table>
</form>

<?php
Display :: display_footer();
?>
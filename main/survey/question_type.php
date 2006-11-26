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
$interbredcrump[] = array ("url" => "index.php", "name" => get_lang('Survey'));
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
	<option value="<?php  echo get_lang('yesno');?>" <?php  if(isset($_POST['add_question'])){if($_POST['add_question']=="1")echo "selected";}?>><?php echo get_lang('yesno');?></option>
	<option value="<?php  echo get_lang('MultipleChoiceSingle');?>" <?php  if(isset($_POST['add_question'])){if($_POST['add_question']=="2")echo "selected";}?>><?php echo get_lang('MultipleChoiceSingle');?></option>
	<option value="<?php  echo get_lang('MultipleChoiceMulti');?>" <?php  if(isset($_POST['add_question'])){if($_POST['add_question']=="3")echo "selected";}?>><?php echo get_lang('MultipleChoiceMulti');?></option>
	<option value="<?php  echo get_lang('Open');?>" <?php  if(isset($_POST['add_question'])){if($_POST['add_question']=="4")echo "selected";}?>><?php echo get_lang('Open');?></option>
	<option value="<?php echo get_lang('numbered');?>" <?php if(isset($_POST['add_question'])){if($_POST['add_question']=="5")echo "selected";}?>><?php echo get_lang('numbered');?></option>
</select>
</td>
</tr>
<tr></tr>
<tr>
<td></td>
<td>
<input type="submit" name= 'next' value="<?php echo get_lang('next');?>">
</td>
</tr>
</table>
</form>

<?php
Display :: display_footer();
?>
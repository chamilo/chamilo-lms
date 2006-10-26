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

api_protect_course_script();
if(!api_is_allowed_to_edit())
{
	api_protect_admin_script();
}


require_once (api_get_path(LIBRARY_PATH)."/course.lib.php");
require (api_get_path(LIBRARY_PATH)."/groupmanager.lib.php");
require_once (api_get_path(LIBRARY_PATH).'/fileManage.lib.php');
require_once (api_get_path(CONFIGURATION_PATH) ."/add_course.conf.php");
require_once (api_get_path(LIBRARY_PATH)."/add_course.lib.inc.php");
require_once (api_get_path(LIBRARY_PATH)."/surveymanager.lib.php");

$cidReq = $_REQUEST['cidReq'];
$id_survey = intval($_GET['id_survey']);


if(isset($_POST['submit'])){
	
	SurveyManager::create_survey_in_another_language($id_survey, addslashes($_POST['language_choosen']));	
	header('Location:survey_list.php?cidReq='.$cidReq);
	exit;
	
}

$tool_name = get_lang('CreateInAnotherLanguage');
$interbredcrump[] = array('url'=>'survey_list.php','name'=>get_lang('SurveyList'));
Display::display_header($tool_name);
api_display_tool_title($tool_name);

$survey_language = SurveyManager::get_data($id_survey, 'lang');
$platform_languages = api_get_languages();

echo '
<form method="POST" action="'.$_SERVER['PHP_SELF'].'?cidReq='.$cidReq.'&id_survey='.$id_survey.'">
<table><tr><td>
'.get_lang('SelectWhichLanguage').'
<select name="language_choosen">
';


for($i=0 ; $i<count($platform_languages) ; $i++){
	
	if($survey_language != $platform_languages['folder'][$i])
		echo '<option value="'.$platform_languages['folder'][$i].'">'.$platform_languages['name'][$i].'</option>';
}

echo '</select></td></tr><tr><td align="center"><br /><br />
<input type="submit" name="submit" value="'.get_lang('Ok').'" />
</td></tr></table>
</form>';

Display :: display_footer();
?>
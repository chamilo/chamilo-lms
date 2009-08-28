<?php // $Id: reporting.php 21652 2009-06-27 17:07:35Z herodoto $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004-2008 Dokeos SPRL
	Copyright (c) 2003 Ghent University (UGent)

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact: Dokeos, rue Notre Dame, 152, B-1140 Evere, Belgium, info@dokeos.com
==============================================================================
*/



/**
*	@package dokeos.survey
* 	@author unknown, the initial survey that did not make it in 1.8 because of bad code
* 	@author Patrick Cool <patrick.cool@UGent.be>, Ghent University: cleanup, refactoring and rewriting large parts of the code
* 	@version $Id: reporting.php 21652 2009-06-27 17:07:35Z herodoto $
*
* 	@todo The question has to be more clearly indicated (same style as when filling the survey)
*/

// name of the language file that needs to be included
$language_file = 'survey';

// including the global dokeos file
require ('../inc/global.inc.php');
require_once('survey.lib.php');

$survey_id = Security::remove_XSS($_GET['survey_id']);


// export
/**
 * @todo use export_table_csv($data, $filename = 'export')
 */
if ($_POST['export_report'])
{
	switch($_POST['export_format'])
	{
		case 'xls':
			$survey_data = survey_manager::get_survey($survey_id);
			$filename = 'survey_results_'.$survey_id.'.xls';
			$data = SurveyUtil::export_complete_report_xls($filename, $_GET['user_id']);
			exit;
			break;
		case 'csv':
		default:
			$survey_data = survey_manager::get_survey($survey_id);
			$data = SurveyUtil::export_complete_report($_GET['user_id']);
			//$filename = 'fileexport.csv';
			$filename = 'survey_results_'.$survey_id.'.csv';

			header('Content-type: application/octet-stream');
			header('Content-Type: application/force-download');
			
			if (preg_match("/MSIE 5.5/", $_SERVER['HTTP_USER_AGENT']))
			{
				header('Content-Disposition: filename= '.$filename);
			}
			else
			{
				header('Content-Disposition: attachment; filename= '.$filename);
			}
			if (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE'))
			{
				header('Pragma: ');
				header('Cache-Control: ');
				header('Cache-Control: public'); // IE cannot download from sessions without a cache
			}
			header('Content-Description: '.$filename);
			header('Content-transfer-encoding: binary');
			echo $data;
			exit;
			break;
	}
}

// including additional libraries
//require_once (api_get_path(LIBRARY_PATH)."/survey.lib.php");
require_once (api_get_path(LIBRARY_PATH)."/course.lib.php");

// Checking the parameters
SurveyUtil::check_parameters();

/** @todo this has to be moved to a more appropriate place (after the display_header of the code)*/
if (!api_is_allowed_to_edit(false,true))
{
	Display :: display_header(get_lang('Survey'));
	Display :: display_error_message(get_lang('NotAllowed'), false);
	Display :: display_footer();
	exit;
}

// Database table definitions
$table_survey 					= Database :: get_course_table(TABLE_SURVEY);
$table_survey_question 			= Database :: get_course_table(TABLE_SURVEY_QUESTION);
$table_survey_question_option 	= Database :: get_course_table(TABLE_SURVEY_QUESTION_OPTION);
$table_course 					= Database :: get_main_table(TABLE_MAIN_COURSE);
$table_user 					= Database :: get_main_table(TABLE_MAIN_USER);
$user_info 						= Database :: get_main_table(TABLE_MAIN_SURVEY_REMINDER); // TODO: To be checked. TABLE_MAIN_SURVEY_REMINDER has not been defined.

// getting the survey information

$survey_data = survey_manager::get_survey($survey_id);
if (empty($survey_data)) {
	Display :: display_header(get_lang('Survey'));
	Display :: display_error_message(get_lang('InvallidSurvey'), false);
	Display :: display_footer();
	exit;
}
$urlname = strip_tags(api_substr(api_html_entity_decode($survey_data['title'],ENT_QUOTES,$charset), 0, 40));
if (api_strlen(strip_tags($survey_data['title'])) > 40)
{
	$urlname .= '...';
}

// breadcrumbs
$interbreadcrumb[] = array ("url" => "survey_list.php", "name" => get_lang('SurveyList'));
$interbreadcrumb[] = array ('url' => 'survey.php?survey_id='.$survey_id, 'name' => $urlname);
if (!$_GET['action'] OR $_GET['action'] == 'overview')
{
	$tool_name = get_lang('Reporting');
}
else
{
	$interbreadcrumb[] = array ("url" => "reporting.php?survey_id=".$survey_id, "name" => get_lang('Reporting'));
	switch ($_GET['action'])
	{
		case 'questionreport':
			$tool_name = get_lang('DetailedReportByQuestion');
			break;
		case 'userreport':
			$tool_name = get_lang('DetailedReportByUser');
			break;
		case 'comparativereport':
			$tool_name = get_lang('ComparativeReport');
			break;
		case 'completereport':
			$tool_name = get_lang('CompleteReport');
			break;
	}
}

// Displaying the header
Display::display_header($tool_name,'Survey');

// Action handling
SurveyUtil::handle_reporting_actions();

// actions bar
echo '<div class="actions">';
echo '<a href="survey.php?survey_id='.Security::remove_XSS($_GET['survey_id']).'">'.Display::return_icon('back.png').' '.get_lang('BackTo').' '.strtolower(get_lang('Survey')).'</a>';
echo '</div>';

// content
if (!$_GET['action'] OR $_GET['action'] == 'overview') {
	$myweb_survey_id = $survey_id;
	echo '<div class="sectiontitle"><a href="reporting.php?action=questionreport&amp;survey_id='.$myweb_survey_id.'">'.Display::return_icon('survey_reporting_question.gif',get_lang('DetailedReportByQuestion')).' '.get_lang('DetailedReportByQuestion').'</a></div><div class="sectioncomment">'.get_lang('DetailedReportByQuestionDetail').' </div>';
	echo '<div class="sectiontitle"><a href="reporting.php?action=userreport&amp;survey_id='.$myweb_survey_id.'">'.Display::return_icon('survey_reporting_user.gif',get_lang('DetailedReportByUser')).' '.get_lang('DetailedReportByUser').'</a></div><div class="sectioncomment">'.get_lang('DetailedReportByUserDetail').'.</div>';
	echo '<div class="sectiontitle"><a href="reporting.php?action=comparativereport&amp;survey_id='.$myweb_survey_id.'">'.Display::return_icon('survey_reporting_comparative.gif',get_lang('ComparativeReport')).' '.get_lang('ComparativeReport').'</a></div><div class="sectioncomment">'.get_lang('ComparativeReportDetail').'.</div>';
	echo '<div class="sectiontitle"><a href="reporting.php?action=completereport&amp;survey_id='.$myweb_survey_id.'">'.Display::return_icon('survey_reporting_complete.gif',get_lang('CompleteReport')).' '.get_lang('CompleteReport').'</a></div><div class="sectioncomment">'.get_lang('CompleteReportDetail').'</div>';
}


// Footer
Display :: display_footer();

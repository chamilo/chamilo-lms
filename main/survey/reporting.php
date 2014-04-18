<?php
/* For licensing terms, see /license.txt */

/**
 *	@package chamilo.survey
 * 	@author unknown, the initial survey that did not make it in 1.8 because of bad code
 * 	@author Patrick Cool <patrick.cool@UGent.be>, Ghent University: cleanup, refactoring and rewriting large parts of the code
 * 	@version $Id: reporting.php 21652 2009-06-27 17:07:35Z herodoto $
 *
 * 	@todo The question has to be more clearly indicated (same style as when filling the survey)
 */

// Language file that needs to be included
$language_file = 'survey';

// Including the global initialization file
require_once '../inc/global.inc.php';
require_once 'survey.lib.php';
$this_section = SECTION_COURSES;
$survey_id = intval($_GET['survey_id']);
$survey_data = survey_manager::get_survey($survey_id);

// Export
/**
 * @todo use export_table_csv($data, $filename = 'export')
 */
if (isset($_POST['export_report']) && $_POST['export_report']) {
	switch ($_POST['export_format']) {
		case 'xls':
			$filename = 'survey_results_'.$survey_id.'.xls';
			$data = SurveyUtil::export_complete_report_xls($survey_data, $filename, $_GET['user_id']);
			exit;
			break;
		case 'csv':
		default:
			$data = SurveyUtil::export_complete_report($survey_data, $_GET['user_id']);
			$filename = 'survey_results_'.$survey_id.'.csv';

			header('Content-type: application/octet-stream');
			header('Content-Type: application/force-download');

			if (preg_match("/MSIE 5.5/", $_SERVER['HTTP_USER_AGENT'])) {
				header('Content-Disposition: filename= '.$filename);
			} else {
				header('Content-Disposition: attachment; filename= '.$filename);
			}
			if (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE')) {
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

if ($survey_data['anonymous'] == 0) {
    $people_filled_full_data = true;
} else {
    $people_filled_full_data = false;
}
$people_filled = survey_manager::get_people_who_filled_survey(
    $_GET['survey_id'],
    $people_filled_full_data
);

// Checking the parameters
SurveyUtil::check_parameters($people_filled);

/** @todo this has to be moved to a more appropriate place (after the display_header of the code)*/
if (!api_is_allowed_to_edit(false, true)) {
	Display :: display_header(get_lang('ToolSurvey'));
	Display :: display_error_message(get_lang('NotAllowed'), false);
	Display :: display_footer();
	exit;
}

// Database table definitions
$table_course 					= Database :: get_main_table(TABLE_MAIN_COURSE);
$table_user 					= Database :: get_main_table(TABLE_MAIN_USER);

// Getting the survey information

$survey_data = survey_manager::get_survey($survey_id);
if (empty($survey_data)) {
	Display :: display_header(get_lang('ToolSurvey'));
	Display :: display_error_message(get_lang('InvallidSurvey'), false);
	Display :: display_footer();
	exit;
}

$urlname = strip_tags(api_substr(api_html_entity_decode($survey_data['title'], ENT_QUOTES), 0, 40));
if (api_strlen(strip_tags($survey_data['title'])) > 40) {
	$urlname .= '...';
}

// Breadcrumbs
$interbreadcrumb[] = array('url' => api_get_path(WEB_CODE_PATH).'survey/survey_list.php', 'name' => get_lang('SurveyList'));
$interbreadcrumb[] = array('url' => api_get_path(WEB_CODE_PATH).'survey/survey.php?survey_id='.$survey_id, 'name' => $urlname);
if (!isset($_GET['action']) || isset($_GET['action']) && $_GET['action'] == 'overview') {
	$tool_name = get_lang('Reporting');
} else {
	$interbreadcrumb[] = array(
        'url' => api_get_path(WEB_CODE_PATH).'survey/reporting.php?survey_id='.$survey_id,
        'name' => get_lang('Reporting')
    );
	switch ($_GET['action']) {
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
Display::display_header($tool_name, 'Survey');

// Action handling
SurveyUtil::handle_reporting_actions($survey_data, $people_filled);

// Actions bar
echo '<div class="actions">';
echo '<a href="'.api_get_path(WEB_CODE_PATH).'survey/survey.php?survey_id='.$survey_id.'">'.
    Display::return_icon('back.png', get_lang('BackToSurvey'),'',ICON_SIZE_MEDIUM).'</a>';
echo '</div>';

// Content
if (!isset($_GET['action']) || isset($_GET['action']) && $_GET['action'] == 'overview') {
	$myweb_survey_id = $survey_id;
	echo '<div class="sectiontitle"><a href="'.api_get_path(WEB_CODE_PATH).'survey/reporting.php?action=questionreport&amp;survey_id='.$myweb_survey_id.'">'.Display::return_icon('survey_reporting_question.gif',get_lang('DetailedReportByQuestion')).' '.get_lang('DetailedReportByQuestion').'</a></div><div class="sectioncomment">'.get_lang('DetailedReportByQuestionDetail').' </div>';
	echo '<div class="sectiontitle"><a href="'.api_get_path(WEB_CODE_PATH).'survey/reporting.php?action=userreport&amp;survey_id='.$myweb_survey_id.'">'.Display::return_icon('survey_reporting_user.gif',get_lang('DetailedReportByUser')).' '.get_lang('DetailedReportByUser').'</a></div><div class="sectioncomment">'.get_lang('DetailedReportByUserDetail').'.</div>';
	echo '<div class="sectiontitle"><a href="'.api_get_path(WEB_CODE_PATH).'survey/reporting.php?action=comparativereport&amp;survey_id='.$myweb_survey_id.'">'.Display::return_icon('survey_reporting_comparative.gif',get_lang('ComparativeReport')).' '.get_lang('ComparativeReport').'</a></div><div class="sectioncomment">'.get_lang('ComparativeReportDetail').'.</div>';
	echo '<div class="sectiontitle"><a href="reporting.php?action=completereport&amp;survey_id='.$myweb_survey_id.'">'.Display::return_icon('survey_reporting_complete.gif',get_lang('CompleteReport')).' '.get_lang('CompleteReport').'</a></div><div class="sectioncomment">'.get_lang('CompleteReportDetail').'</div>';
}

// Footer
Display :: display_footer();

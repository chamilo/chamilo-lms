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
* 	@version $Id: reporting.php 10584 2007-01-02 15:09:21Z pcool $
*/

/*
==============================================================================
		INIT SECTION
==============================================================================
*/
// name of the language file that needs to be included 
$language_file = 'survey';

// including the global dokeos file
require_once ('../inc/global.inc.php');

// including additional libraries
/** @todo check if these are all needed */
/** @todo check if the starting / is needed. api_get_path probably ends with an / */
require_once (api_get_path(LIBRARY_PATH)."/course.lib.php");
require (api_get_path(LIBRARY_PATH)."/groupmanager.lib.php");

$table_survey = Database :: get_course_table('survey');

$surveyid=intval($_REQUEST['surveyid']);
 $cidReq=stripslashes($_REQUEST['cidReq']);
 $db_name = stripslashes($_REQUEST['db_name']);
if($_SESSION['status']==5)
{
	api_protect_admin_script();
}

$tool_name = get_lang('SurveyReporting');
$interbreadcrumb[] = array ("url" => "survey_list.php", "name" => get_lang('Survey'));
Display::display_header($tool_name);

echo '<table align="center">
		<tr>
			<td><a href="complete_report.php?action=reporting&cidReq='.$cidReq.'&db_name='.$db_name.'&surveyid='.$surveyid.'"">'.get_lang('CompleteReport').'</a><br />
			'.get_lang('CompleteReportDetails').'<br /><br />
			</td>
		</tr>
		<tr>
			<td><a href="survey_report.php?action=reporting&cidReq='.$cidReq.'&db_name='.$db_name.'&surveyid='.$surveyid.'">'.get_lang('AdvancedReport').'</a><br />
			'.get_lang('AdvancedReportDetails').'</td>
		</tr>
	  </table>';

Display::display_footer();
?>

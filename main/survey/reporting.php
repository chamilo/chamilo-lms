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
// name of the language file that needs to be included 
$language_file = 'survey';

require_once ('../inc/global.inc.php');
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
$interbredcrump[] = array ("url" => "survey_list.php", "name" => get_lang('Survey'));
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

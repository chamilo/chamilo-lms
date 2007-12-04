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
* 	@author unknown, the initial survey that did not make it in 1.8 because of bad code
* 	@author Patrick Cool <patrick.cool@UGent.be>, Ghent University: cleanup, refactoring and rewriting large parts of the code
* 	@version $Id: survey_list.php 13922 2007-12-04 23:20:19Z yannoo $
*
* 	@todo use quickforms for the forms
*/

// name of the language file that needs to be included
$language_file = 'survey';

// including the global dokeos file
require ('../inc/global.inc.php');

// including additional libraries
//require_once (api_get_path(LIBRARY_PATH)."/survey.lib.php");
require_once('survey.lib.php');
require_once (api_get_path(LIBRARY_PATH)."/course.lib.php");

/** @todo this has to be moved to a more appropriate place (after the display_header of the code)*/
if (!api_is_allowed_to_edit())
{
	Display :: display_header();
	Display :: display_error_message(get_lang('NotAllowed'), false);
	Display :: display_footer();
	exit;
}

// Database table definitions
$table_survey 			= Database :: get_course_table(TABLE_SURVEY);
$table_survey_question 	= Database :: get_course_table(TABLE_SURVEY_QUESTION);
$table_course 			= Database :: get_main_table(TABLE_MAIN_COURSE);
$table_user 			= Database :: get_main_table(TABLE_MAIN_USER);

// language variables
if (isset ($_GET['search']) && $_GET['search'] == 'advanced')
{
	$interbreadcrumb[] = array ('url' => 'survey_list.php', 'name' => get_lang('SurveyList'));
	$tool_name = get_lang('SearchASurvey');
}
else
{
	$tool_name = get_lang('SurveyList');
}

// Header
Display :: display_header($tool_name);
//api_display_tool_title($tool_name);

// Action handling: searching
if (isset ($_GET['search']) AND $_GET['search'] == 'advanced')
{
	display_survey_search_form();
}
// Action handling: deleting a survey
if (isset($_GET['action']) AND $_GET['action'] == 'delete' AND isset($_GET['survey_id']) AND is_numeric($_GET['survey_id']))
{
	// getting the information of the survey (used for when the survey is shared)
	$survey_data = survey_manager::get_survey($_GET['survey_id']);
	// if the survey is shared => also delete the shared content
	if (is_numeric($survey_data['survey_share']))
	{
		survey_manager::delete_survey($survey_data['survey_share'], true);
	}
	$return = survey_manager :: delete_survey($_GET['survey_id']);
	if ($return)
	{
		Display :: display_confirmation_message(get_lang('SurveyDeleted'), false);
	}
	else
	{
		Display :: display_error_message(get_lang('ErrorOccurred'), false);
	}
}

if(isset($_GET['action']) && $_GET['action'] == 'empty')
{
	$return = survey_manager::empty_survey(intval($_GET['survey_id']));
	if ($return)
	{
		Display :: display_confirmation_message(get_lang('SurveyEmptied'), false);
	}
	else
	{
		Display :: display_error_message(get_lang('ErrorOccurred'), false);
	}
}

// Action handling: performing the same action on multiple surveys
if ($_POST['action'])
{
	if (is_array($_POST['id']))
	{
		foreach ($_POST['id'] as $key=>$value)
		{
			// getting the information of the survey (used for when the survey is shared)
			$survey_data = survey_manager::get_survey($value);
			// if the survey is shared => also delete the shared content
			if (is_numeric($survey_data['survey_share']))
			{
				survey_manager::delete_survey($survey_data['survey_share'], true);
			}
			// delete the actual survey
			survey_manager::delete_survey($value);
		}
		Display :: display_confirmation_message(get_lang('SurveysDeleted'), false);
	}
	else
	{
		Display :: display_error_message(get_lang('NoSurveysSelected'), false);
	}
}


// Action links
echo '<a href="create_new_survey.php?'.api_get_cidreq().'&amp;action=add">'.get_lang('CreateNewSurvey').'</a> | ';
//echo '<a href="survey_all_courses.php">'.get_lang('CreateExistingSurvey').'</a> | ';
echo '<a href="'.api_get_self().'?'.api_get_cidreq().'&amp;search=advanced">'.get_lang('Search').'</a>';

// Main content
display_survey_list();

// Footer
Display :: display_footer();



/**
 * This function displays the form for searching a survey
 *
 * @return html code
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @version January 2007
 *
 * @todo use quickforms
 * @todo consider moving this to surveymanager.inc.lib.php
 */
function display_survey_search_form()
{
	echo '<form method="get" action="survey_list.php?search=advanced">';
	echo '<table>
			<tr>
				<td>'.get_lang('Title').'</td>
				<td><input type="text" name="keyword_title"/></td>
			</tr>
			<tr>
				<td>'.get_lang('Code').'</td>
				<td><input type="text" name="keyword_code"/></td>
			</tr>
			<tr>
				<td>'.get_lang('Language').'</td>
				<td>
					<select name="keyword_language"><option value="%">'.get_lang('All').'</option>';
	$languages = api_get_languages();
	foreach ($languages['name'] as $index => $name)
	{
		echo '<option value="'.$languages['folder'][$index].'">'.$name.'</option>';
	}
	echo '			</select>
				</td>
			</tr>
			<tr>
				<td>&nbsp;</td>
				<td><input type="submit" name="do_search" value="'.get_lang('Ok').'"/></td>
			</tr>
		</table>
	</form>';
}

/**
 * This function displays the sortable table with all the surveys
 *
 * @return html code
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @version January 2007
 */
function display_survey_list()
{
	$parameters = array();
	if ($_GET['do_search'])
	{
		$message = get_lang('DisplaySearchResults').'<br />';
		$message .= '<a href="'.api_get_self().'">'.get_lang('DisplayAll').'</a>';
		Display::display_normal_message($message, false);
	}

	// Create a sortable table with survey-data
	$table = new SortableTable('surveys', 'get_number_of_surveys', 'get_survey_data',2);
	$table->set_additional_parameters($parameters);
	$table->set_header(0, '', false);
	$table->set_header(1, get_lang('SurveyName'));
	$table->set_header(2, get_lang('SurveyCode'));
	$table->set_header(3, get_lang('NumberOfQuestions'));
	$table->set_header(4, get_lang('Author'));
	$table->set_header(5, get_lang('Language'));
	$table->set_header(6, get_lang('Shared'));
	$table->set_header(7, get_lang('AvailableFrom'));
	$table->set_header(8, get_lang('AvailableUntill'));
	$table->set_header(9, get_lang('Invite'));
	$table->set_header(10, get_lang('Anonymous'));
	$table->set_header(11, get_lang('Modify'), false);
	$table->set_column_filter(10, 'anonymous_filter');
	$table->set_column_filter(11, 'modify_filter');
	$table->set_form_actions(array ('delete' => get_lang('DeleteSurvey')));
	$table->display();

}

/**
 * This function calculates the total number of surveys
 *
 * @return integer
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @version January 2007
 */
function get_number_of_surveys()
{
	global $table_survey;

	$search_restriction = survey_search_restriction();
	if ($search_restriction)
	{
		$search_restriction = 'WHERE '.$search_restriction;
	}

	$sql = "SELECT count(survey_id) AS total_number_of_items FROM ".$table_survey.' '.$search_restriction;
	$res = api_sql_query($sql, __FILE__, __LINE__);
	$obj = mysql_fetch_object($res);
	return $obj->total_number_of_items;

}

/**
 * This function gets all the survey data that is to be displayed in the sortable table
 *
 * @param unknown_type $from
 * @param unknown_type $number_of_items
 * @param unknown_type $column
 * @param unknown_type $direction
 * @return unknown
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @version January 2007
 */
function get_survey_data($from, $number_of_items, $column, $direction)
{
	global $table_survey;
	global $table_user;
	global $table_survey_question;

	// searching
	$search_restriction = survey_search_restriction();
	if ($search_restriction)
	{
		$search_restriction = ' AND '.$search_restriction;
	}
	$sql = "SELECT
				survey.survey_id							AS col0,
                CONCAT('<a href=\"survey.php?survey_id=',survey.survey_id,'\">',survey.title,'</a>')		AS col1,
				survey.code									AS col2,
				count(survey_question.question_id)			AS col3,
                CONCAT(user.firstname, ' ', user.lastname)	AS col4,
                survey.lang									AS col5,
                IF(is_shared<>0,'V','-')	 					AS col6,
				survey.avail_from							AS col7,
                survey.avail_till							AS col8,
                CONCAT('<a href=\"survey_invitation.php?view=answered&amp;survey_id=',survey.survey_id,'\">',survey.answered,'</a> / <a href=\"survey_invitation.php?view=invited&amp;survey_id=',survey.survey_id,'\">',survey.invited, '</a>')	AS col9,
                survey.anonymous							AS col10,
                survey.survey_id							AS col11
             FROM $table_survey survey
			 LEFT JOIN $table_survey_question survey_question ON survey.survey_id = survey_question.survey_id
             , $table_user user
             WHERE survey.author = user.user_id
             $search_restriction
             ";
	$sql .= " GROUP BY survey.survey_id";
	$sql .= " ORDER BY col$column $direction ";
	$sql .= " LIMIT $from,$number_of_items";
	$res = api_sql_query($sql, __FILE__, __LINE__);
	$surveys = array ();
	while ($survey = mysql_fetch_row($res))
	{
		$surveys[] = $survey;
	}
	return $surveys;
}


/**
 * This function changes the modify column of the sortable table
 *
 * @param integer $survey_id the id of the survey
 * @return html code that are the actions that can be performed on any survey
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @version January 2007
 */
function modify_filter($survey_id)
{
	global $charset;
	$return = '<a href="create_new_survey.php?'.api_get_cidreq().'&amp;action=edit&amp;survey_id='.$survey_id.'">'.Display::return_icon('edit.gif', get_lang('Edit')).'</a>';
	$return .= '<a href="survey_list.php?'.api_get_cidreq().'&amp;action=delete&amp;survey_id='.$survey_id.'" onclick="javascript:if(!confirm(\''.addslashes(htmlentities(get_lang("DeleteSurvey").'?',ENT_QUOTES,$charset)).'\')) return false;">'.Display::return_icon('delete.gif', get_lang('Delete')).'</a>';
	//$return .= '<a href="create_survey_in_another_language.php?id_survey='.$survey_id.'">'.Display::return_icon('copy.gif', get_lang('Copy')).'</a>';
	//$return .= '<a href="survey.php?survey_id='.$survey_id.'">'.Display::return_icon('add.gif', get_lang('Add')).'</a>';
	$return .= '<a href="preview.php?'.api_get_cidreq().'&amp;survey_id='.$survey_id.'">'.Display::return_icon('preview.gif', get_lang('Preview')).'</a>';
	$return .= '<a href="survey_invite.php?'.api_get_cidreq().'&amp;survey_id='.$survey_id.'">'.Display::return_icon('survey_publish.gif', get_lang('Publish')).'</a>';
	$return .= '<a href="survey_list.php?'.api_get_cidreq().'&amp;action=empty&amp;survey_id='.$survey_id.'" onclick="javascript:if(!confirm(\''.addslashes(htmlentities(get_lang("EmptySurvey").'?')).'\')) return false;">'.Display::return_icon('empty.gif', get_lang('EmptySurvey')).'</a>';
	$return .= '<a href="reporting.php?'.api_get_cidreq().'&amp;survey_id='.$survey_id.'">'.Display::return_icon('statistics.gif', get_lang('Reporting')).'</a>';
	return $return;
}

function anonymous_filter($anonymous)
{
	if ($anonymous == 1)
	{
		return get_lang('Yes');
	}
	else
	{
		return get_lang('No');
	}
}


/**
 * this function handles the search restriction for the SQL statements
 *
 * @return false or a part of a SQL statement
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @version January 2007
 */
function survey_search_restriction()
{
	if ($_GET['do_search'])
	{
		if ($_GET['keyword_title']<>'')
		{
			$search_term[] = 'title =\''.Database::escape_string($_GET['keyword_title']).'\'';
		}
		if ($_GET['keyword_code']<>'')
		{
			$search_term[] = 'code =\''.Database::escape_string($_GET['keyword_code']).'\'';
		}
		if ($_GET['keyword_language']<>'%')
		{
			$search_term[] = 'lang =\''.Database::escape_string($_GET['keyword_language']).'\'';
		}

		$search_restriction = implode(' AND ', $search_term);
		return $search_restriction;
	}
	else
	{
		return false;
	}
}
?>
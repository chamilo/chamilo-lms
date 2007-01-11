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
* 	@author unknown
* 	@author Patrick Cool <patrick.cool@UGent.be>, Ghent University: cleanup, refactoring and rewriting large parts of the code
* 	@version $Id: survey_list.php 10680 2007-01-11 21:26:23Z pcool $
* 
* 	@todo The ansTarget column is not done
* 	@todo try to understand the white, blue, ... template stuff. 
* 	@todo use quickforms for the forms
*/

// name of the language file that needs to be included 
$language_file = 'survey';

// including the global dokeos file
require ('../inc/global.inc.php');

// including additional libraries
require_once (api_get_path(LIBRARY_PATH)."/surveymanager.lib.php");
require_once (api_get_path(LIBRARY_PATH)."/course.lib.php");

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

// Database table definitions
/** @todo use database constants for the survey tables */
$table_survey 			= Database :: get_course_table(TABLE_SURVEY);
$table_group 			= Database :: get_course_table(TABLE_SURVEY_GROUP);
$table_survey_question 	= Database :: get_course_table(TABLE_SURVEY_QUESTION);
$table_course 			= Database :: get_main_table(TABLE_MAIN_COURSE);
$table_user 			= Database :: get_main_table(TABLE_MAIN_USER);
$user_info 				= Database :: get_main_table(TABLE_MAIN_SURVEY_REMINDER);

// language variables
if (isset ($_GET['search']) && $_GET['search'] == 'advanced')
{
	$tool_name = get_lang('SearchASurvey');
}
else
{
	$tool_name = get_lang('Survey');
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
	$return = SurveyManager::delete_survey($_GET['survey_id']);
	if ($return)
	{
		Display :: display_confirmation_message(get_lang('SurveyDeleted'));	
	}
	else 
	{
		Display :: display_error_message(get_lang('ErrorOccurred'));
	}
}

// Action handling: performing the same action on multiple surveys
if ($_POST['action'])
{
	if (is_array($_POST['id']))
	{
		foreach ($_POST['id'] as $key=>$value) 
		{
			SurveyManager::delete_survey($value);
		}
		Display :: display_confirmation_message(get_lang('SurveyDeleted'));	
	}
	else 
	{
		Display :: display_error_message(get_lang('NoSurveysSelected'));
	}
}


// Action links
echo '<a href="create_new_survey.php?action=add">'.get_lang('CreateNewSurvey').'</a> | ';
echo '<a href="survey_all_courses.php">'.get_lang('CreateExistingSurvey').'</a> | ';
echo '<a href="'.$_SERVER['PHP_SELF'].'?search=advanced">'.get_lang('Search').'</a>';

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
	if ($_GET['do_search'])
	{
		$message = get_lang('DisplaySearchResults').'<br />'; 
		$message .= '<a href="'.$_SERVER['PHP_SELF'].'">'.get_lang('DisplayAll').'</a>';
		Display::display_normal_message($message);
	}
	
	// Create a sortable table with survey-data
	$table = new SortableTable('surveys', 'get_number_of_surveys', 'get_survey_data',2);
	$table->set_additional_parameters($parameters);
	$table->set_header(0, '', false);
	$table->set_header(1, get_lang('SurveyName'));
	$table->set_header(2, get_lang('SurveyCode'));
	$table->set_header(3, get_lang('Author'));
	$table->set_header(4, get_lang('Language'));
	$table->set_header(5, get_lang('AvailableFrom'));
	$table->set_header(6, get_lang('AvailableUntill'));
	$table->set_header(7, get_lang('AnsTarget'));
	$table->set_header(8, get_lang('Modify'), false);
	$table->set_column_filter(8, 'modify_filter');
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
	
	// searching
	$search_restriction = survey_search_restriction();
	if ($search_restriction)
	{
		$search_restriction = ' AND '.$search_restriction;
	}
	
	$sql = "SELECT  
				survey.survey_id							AS col0,
                survey.title								AS col1,
				survey.code									AS col2,
                CONCAT(user.firstname, ' ', user.lastname)	AS col3,
                survey.lang									AS col4,
				survey.avail_from							AS col5,
                survey.avail_till							AS col6, 
                ''				AS col7,
                survey.survey_id							AS col8
             FROM $table_survey survey, $table_user user
             WHERE survey.author = user.user_id
             $search_restriction
             ";
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
	$return = '<a href="create_new_survey.php?action=edit&amp;survey_id='.$survey_id.'">'.Display::return_icon('edit.gif').'</a>';
	$return .= '<a href="survey_question.php?action=add&amp;survey_id='.$survey_id.'">'.Display::return_icon('add.gif').'</a>';
	$return .= '<a href="survey_list.php?action=delete&amp;survey_id='.$survey_id.'">'.Display::return_icon('delete.gif').'</a>';
	$return .= '<a href="create_survey_in_another_language.php?id_survey='.$survey_id.'">'.Display::return_icon('copy.gif').'</a>';
	$return .= '<a href="survey_white.php?survey_id='.$survey_id.'">'.Display::return_icon('preview.gif').'</a>';
	$return .= '<a href="../announcements/announcements.php?action=add&amp;publish_survey='.$survey_id.'">'.Display::return_icon('survey_publish.gif').'</a>';
	$return .= '<a href="reporting.php?action=reporting&amp;surveyid='.$survey_id.'">'.Display::return_icon('surveyreporting.gif').'</a>';
	return $return; 
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
			$search_term[] = 'title =\''.mysql_real_escape_string($_GET['keyword_title']).'\'';
		}
		if ($_GET['keyword_code']<>'')
		{
			$search_term[] = 'code =\''.mysql_real_escape_string($_GET['keyword_code']).'\'';
		}
		if ($_GET['keyword_language']<>'%')
		{
			$search_term[] = 'lang =\''.mysql_real_escape_string($_GET['keyword_language']).'\'';
		}
		
		$search_restriction = implode(' AND ', $search_term);
		return $search_restriction;
	}
	else 
	{
		return false;
	}
}




/*	if(isset($published))
	{
		$sname = surveymanager::pick_surveyname($surveyid);
		$error_message = get_lang('YourSurveyHasBeenPublished');
		Display::display_error_message("Survey "."'".$sname."'"." ".$error_message);	
	}
	$res = api_sql_query($sql,__FILE__,__LINE__);
	if (mysql_num_rows($res) > 0)
	{
		
		$courses = array ();
		while ($obj = mysql_fetch_object($res))
		{
			$template=$obj->template;
			$surveyid = $obj->survey_id;
			if($template=='template1'){$view='white';}
			elseif($template=='template2'){$view='blue';}
			elseif($template=='template3'){$view='brown';}
			elseif($template=='template4'){$view='gray';}
			else{$view=='blank';}
			$sql_sent="	SELECT DISTINCT user_info.* 
							FROM $user_info as user_info
							INNER JOIN $table_survey as survey
							ON user_info.sid = survey.survey_id
							AND survey.code = '".$obj->code."'";
			
			$res_sent=api_sql_query($sql_sent);
			$sent=mysql_num_rows($res_sent);
			
			$attempted=0;
			
			$sqlAttempt = '	SELECT DISTINCT *
							FROM '.Database::get_main_table(TABLE_MAIN_SURVEY_USER).'
							WHERE survey_id='.$obj->survey_id.' AND db_name="'.$db_name.'"';
			$res_attempt=api_sql_query($sqlAttempt);
			$attempted=mysql_num_rows($res_attempt);
			
			if($sent=='0')
			{$ratio=$attempted."/".$sent." "."(Not Published)";}
			else
				$ratio=$attempted."/".$sent;
			$survey = array ();
			$survey[] = '<input type="checkbox" name="survey_delete[]" value="'.$obj->survey_id.'">';
			$survey[] = $obj->title;
			$survey[] = $obj->code;
			$idd=surveymanager::get_author($db_name,$surveyid);		
			$author=surveymanager::get_survey_author($idd);
			$survey[] = $author;
			$survey[] = $obj->lang;
			$survey[] = $obj->avail_from ;
			$survey[] = $obj->avail_till ;	
			$survey[] = $ratio;
			//$NoOfQuestion=surveymanager::no_of_question($gid);
			//$language=surveymanager::no_of_question($sid);
			$survey[] = '<a href="survey_edit.php?surveyid='.$obj->survey_id.'"><img src="../img/edit.gif" border="0" align="absmiddle" alt="'.get_lang('Edit').'"/></a>'.'<a href="survey_list.php?&action=delete_surveys&survey_delete[]='.$obj->survey_id.'&delete_survey='.$obj->survey_id.'"  onclick="javascript:if(!confirm('."'".addslashes(htmlentities(get_lang('ConfirmYourChoice')))."'".')) return false;"><img src="../img/delete.gif" border="0" align="absmiddle" alt="'.get_lang('Delete').'"/></a>'.'<a href="create_survey_in_another_language.php?id_survey='.$obj->survey_id.'"><img width="28" src="../img/copy.gif" border="0" align="absmiddle" alt="'.get_lang('CreateInAnotherLanguage').'" title="'.get_lang('CreateInAnotherLanguage').'" /></a>'.'<a href="survey_white.php?surveyid='.$surveyid.'&temp='.$template.'">&nbsp;<img src="../img/visible.gif" border="0" align="absmiddle" alt="'.get_lang('ViewSurvey').'"></a>'.'<a href="../announcements/announcements.php?action=add&publish_survey='.$obj->survey_id.'">&nbsp;<img src="../img/survey_publish.gif" border="0" align="absmiddle" alt="'.get_lang('Publish').'"></a>'.'<a href="reporting.php?action=reporting&surveyid='.$obj->survey_id.'">&nbsp;<img src="../img/surveyreporting.gif" border="0" align="absmiddle" alt="'.get_lang('Reporting').'"></a>';
			$surveys[] = $survey;
		}
		$table_header[] = array (' ', false);
		$table_header[] = array (get_lang('SurveyName'), true);
		$table_header[] = array (get_lang('SurveyCode'), true);
		$table_header[] = array (get_lang('Author'), true);
		$table_header[] = array (get_lang('Language'), true);
		$table_header[] = array (get_lang('AvailableFrom'), true);
		$table_header[] = array (get_lang('AvailableTill'), true);
		$table_header[] = array (get_lang('AnsTarget'), true);
		$table_header[] = array (' ', false);
		echo '<form method="get" action="survey_list.php" name="frm">';
		Display :: display_sortable_table($table_header, $surveys, array (), array (), $parameters);
		echo '<select name="action">';
		echo '<option value="delete_surveys">'.get_lang('DeleteSurvey').'</option>';
		echo '</select>';
		
		echo '&nbsp;&nbsp;<input type="submit" value="'.get_lang('Ok').'" onclick="return validate(\'frm\');"/>';
		echo '</form>';
	}
	else
	{
		if((isset ($_GET['keyword'])) || (isset ($_GET['keyword_title']))){		
		echo get_lang('NoSearchResults') ;
		}
		else{
			$nosurvey=get_lang('NoSurveyAvailable');
			api_display_tool_title($nosurvey);		
		}
	}
}
*/
?>

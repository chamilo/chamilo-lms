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
 *	@author Arnaud Ligot <arnaud@cblue.be>
 *	@version $Id: $
 *
 *	small peace code to enable user to access images included into survey
 *	which are accessible by non authenticated users. This file is included
 *	by document/download.php
 */
function check_download_survey($course, $invitation, $doc_url) {

	require_once('survey.lib.php');
	require_once (api_get_path(LIBRARY_PATH)."/course.lib.php");

	// getting all the course information
	$_course = CourseManager::get_course_information($course);

	// Database table definitions
	$table_survey 					= Database :: get_course_table(TABLE_SURVEY, $_course['db_name']);
	$table_survey_question 			= Database :: get_course_table(TABLE_SURVEY_QUESTION, $_course['db_name']);
	$table_survey_question_option 	= Database :: get_course_table(TABLE_SURVEY_QUESTION_OPTION, $_course['db_name']);
	$table_course 					= Database :: get_main_table(TABLE_MAIN_COURSE);
	$table_user 					= Database :: get_main_table(TABLE_MAIN_USER);
	$table_survey_invitation 		= Database :: get_course_table(TABLE_SURVEY_INVITATION, $_course['db_name']);


	// now we check if the invitationcode is valid
	$sql = "SELECT * FROM $table_survey_invitation WHERE invitation_code = '".Database::escape_string($invitation)."'";
	$result = Database::query($sql, __FILE__, __LINE__);
	if (mysql_num_rows($result) < 1)
	{
		Display :: display_error_message(get_lang('WrongInvitationCode'), false);
		Display :: display_footer();
		exit;
	}
	$survey_invitation = mysql_fetch_assoc($result);

	// now we check if the user already filled the survey
	if ($survey_invitation['answered'] == 1)
	{
		Display :: display_error_message(get_lang('YouAlreadyFilledThisSurvey'), false);
		Display :: display_footer();
		exit;
	}

	// very basic security check: check if a text field from a survey/answer/option contains the name of the document requested


	//////////////
	// fetch survey ID
	//////////////

	// If this is the case there will be a language choice
	$sql = "SELECT * FROM $table_survey WHERE code='".Database::escape_string($survey_invitation['survey_code'])."'";
	$result = Database::query($sql, __FILE__, __LINE__);
	if (mysql_num_rows($result) > 1)
	{
		if ($_POST['language'])
		{
			$survey_invitation['survey_id'] = $_POST['language'];
		}
		else
		{
			echo '<form id="language" name="language" method="POST" action="'.api_get_self().'?course='.$_GET['course'].'&invitationcode='.$_GET['invitationcode'].'">';
			echo '  <select name="language">';
			while ($row=mysql_fetch_assoc($result))
			{
				echo '<option value="'.$row['survey_id'].'">'.$row['lang'].'</option>';
			}
			echo '</select>';
			echo '  <input type="submit" name="Submit" value="'.get_lang('Ok').'" />';
			echo '</form>';
			display::display_footer();
			exit;
		}
	}
	else
	{
		$row=mysql_fetch_assoc($result);
		$survey_invitation['survey_id'] = $row['survey_id'];
	}
	$sql = "select count(*) from $table_survey where survey_id = ".$survey_invitation['survey_id']."
								and (
									title LIKE '%$doc_url%'
									or subtitle LIKE '%$doc_url%'
									or intro LIKE '%$doc_url%'
									or surveythanks LIKE '%$doc_url%'
								)
		union select count(*) from $table_survey_question  where survey_id = ".$survey_invitation['survey_id']."
								and (
									survey_question LIKE '%$doc_url%'
									or survey_question_comment LIKE '%$doc_url%'
								)
		union select count(*) from $table_survey_question_option where survey_id = ".$survey_invitation['survey_id']."
								and (
									option_text LIKE '%$doc_url%'
								)";
	$result = Database::query($sql, __FILE__, __LINE__);

	if (mysql_num_rows($result) == 0)
	{
		Display :: display_error_message(get_lang('WrongInvitationCode'), false);
		Display :: display_footer();
		exit;
	}


	return $_course;
}

?>

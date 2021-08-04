<?php
/* For licensing terms, see /license.txt */

/**
 * @author Arnaud Ligot <arnaud@cblue.be>
 *
 * A small peace of code to enable user to access images included into survey
 * which are accessible by non authenticated users. This file is included
 * by document/download.php
 */
function check_download_survey($course, $invitation, $doc_url)
{
    // Getting all the course information
    $_course = api_get_course_info($course);
    $course_id = $_course['real_id'];

    // Database table definitions
    $table_survey = Database::get_course_table(TABLE_SURVEY);
    $table_survey_question = Database::get_course_table(TABLE_SURVEY_QUESTION);
    $table_survey_question_option = Database::get_course_table(TABLE_SURVEY_QUESTION_OPTION);
    $table_survey_invitation = Database::get_course_table(TABLE_SURVEY_INVITATION);

    // Now we check if the invitationcode is valid
    $sql = "SELECT * FROM $table_survey_invitation
            WHERE
                c_id = $course_id AND
                invitation_code = '".Database::escape_string($invitation)."'";
    $result = Database::query($sql);
    if (Database::num_rows($result) < 1) {
        echo Display::return_message(get_lang('WrongInvitationCode'), 'error', false);
        exit;
    }
    $survey_invitation = Database::fetch_assoc($result);

    // Now we check if the user already filled the survey
    /*if ($survey_invitation['answered'] == 1) {
        echo Display::return_message(get_lang('YouAlreadyFilledThisSurvey'), 'error', false);
        exit;
    }*/

    // Very basic security check: check if a text field from
    // a survey/answer/option contains the name of the document requested
    // Fetch survey ID
    // If this is the case there will be a language choice
    $sql = "SELECT * FROM $table_survey
            WHERE
                c_id = $course_id AND
                code='".Database::escape_string($survey_invitation['survey_code'])."'";
    $result = Database::query($sql);
    if (Database::num_rows($result) > 1) {
        if ($_POST['language']) {
            $survey_invitation['survey_id'] = $_POST['language'];
        } else {
            echo '<form
                id="language"
                name="language"
                method="POST"
                action="'.api_get_self().'?course='.Security::remove_XSS($_GET['course']).'&invitationcode='.Security::remove_XSS($_GET['invitationcode']).'">';
            echo '  <select name="language">';
            while ($row = Database::fetch_assoc($result)) {
                echo '<option value="'.$row['survey_id'].'">'.$row['lang'].'</option>';
            }
            echo '</select>';
            echo '  <input type="submit" name="Submit" value="'.get_lang('Ok').'" />';
            echo '</form>';
            Display::display_footer();
            exit;
        }
    } else {
        $row = Database::fetch_assoc($result);
        $survey_invitation['survey_id'] = $row['survey_id'];
    }

    $doc_url = Database::escape_string($doc_url);
    $survey_invitation['survey_id'] = Database::escape_string($survey_invitation['survey_id']);

    $sql = "SELECT count(*)
            FROM $table_survey
            WHERE
                c_id = $course_id AND
                survey_id = ".$survey_invitation['survey_id']." AND (
                    title LIKE '%$doc_url%'
                    or subtitle LIKE '%$doc_url%'
                    or intro LIKE '%$doc_url%'
                    or surveythanks LIKE '%$doc_url%'
                )
            UNION
                SELECT count(*)
                FROM $table_survey_question
                WHERE
                    c_id = $course_id AND
                    survey_id = ".$survey_invitation['survey_id']." AND (
                        survey_question LIKE '%$doc_url%' OR
                        survey_question_comment LIKE '%$doc_url%'
                    )
            UNION
                SELECT count(*)
                FROM $table_survey_question_option
                WHERE
                    c_id = $course_id AND
                    survey_id = ".$survey_invitation['survey_id']." AND (
                        option_text LIKE '%$doc_url%'
                    )";
    $result = Database::query($sql);
    if (Database::num_rows($result) == 0) {
        echo Display::return_message(get_lang('WrongInvitationCode'), 'error', false);
        exit;
    }

    return $_course;
}

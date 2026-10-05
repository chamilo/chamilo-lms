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
    $invitedUser = (string) $survey_invitation['user'];
    // The "auto" invitation codes are derived from public data (the user id and the survey
    // code), so, unlike the random codes sent by mail, they cannot act as a shared secret.
    // When such a code belongs to a platform user, only that very user is allowed to use it.
    if (0 === strpos((string) $survey_invitation['invitation_code'], 'auto-') &&
        ctype_digit($invitedUser) &&
        (int) $invitedUser > 0 &&
        (int) $invitedUser !== api_get_user_id()
    ) {
        echo Display::return_message(get_lang('WrongInvitationCode'), 'error', false);
        exit;
    }

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
        if (!empty($_POST['language'])) {
            $survey_invitation['survey_id'] = (int) $_POST['language'];
        } else {
            echo '<form
                id="language"
                name="language"
                method="POST"
                action="'.api_get_self().'?course='.urlencode($course).'&invitationcode='.urlencode($invitation).'">';
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

    $surveyId = (int) $survey_invitation['survey_id'];

    // The document is only served when the survey itself references it. Spaces reach this
    // point as "+" (mod_rewrite) while the survey text may spell them out or encode them,
    // so every spelling has to be accepted.
    $docUrlVariants = array_unique(
        [
            $doc_url,
            str_replace('+', ' ', $doc_url),
            str_replace('+', '%20', $doc_url),
        ]
    );

    $searchedFields = [
        $table_survey => ['title', 'subtitle', 'intro', 'surveythanks'],
        $table_survey_question => ['survey_question', 'survey_question_comment'],
        $table_survey_question_option => ['option_text'],
    ];

    $documentIsInSurvey = false;
    foreach ($searchedFields as $table => $fields) {
        $conditions = [];
        foreach ($fields as $field) {
            foreach ($docUrlVariants as $docUrlVariant) {
                // "%" and "_" are LIKE wildcards: neutralise them after the string escaping
                // so a crafted file name cannot widen the match.
                $escapedUrl = str_replace(
                    ['%', '_'],
                    ['\\%', '\\_'],
                    Database::escape_string($docUrlVariant)
                );
                $conditions[] = "$field LIKE '%$escapedUrl%'";
            }
        }

        $sql = "SELECT count(*) AS total
                FROM $table
                WHERE
                    c_id = $course_id AND
                    survey_id = $surveyId AND
                    (".implode(' OR ', $conditions).")";
        $result = Database::query($sql);
        $row = Database::fetch_assoc($result);
        if (!empty($row['total'])) {
            $documentIsInSurvey = true;
            break;
        }
    }

    if (!$documentIsInSurvey) {
        echo Display::return_message(get_lang('WrongInvitationCode'), 'error', false);
        exit;
    }

    return $_course;
}

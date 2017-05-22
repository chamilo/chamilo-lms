<?php
/* For licensing terms, see /license.txt */

/**
 * @package chamilo.survey
 * @author unknown, the initial survey that did not make it in 1.8 because of bad code
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University: cleanup, refactoring and rewriting large parts of the code
 * @author Julio Montoya Chamilo: cleanup, refactoring, security improvements
 * @version $Id: survey_invite.php 10680 2007-01-11 21:26:23Z pcool $
 *
 * @todo checking if the additional emails are valid (or add a rule for this)
 * @todo check if the mailtext contains the **link** part, if not, add the link to the end
 * @todo add rules: title and text cannot be empty
 */

require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_COURSES;

if (!api_is_allowed_to_edit(false, true)) {
    api_not_allowed(true);
}

$course_id = api_get_course_int_id();

// Getting the survey information
$survey_id = Security::remove_XSS($_GET['survey_id']);
$survey_data = SurveyManager::get_survey($survey_id);
if (empty($survey_data)) {
    api_not_allowed(true);
}

// Database table definitions
$table_survey = Database::get_course_table(TABLE_SURVEY);
$table_survey_question = Database::get_course_table(TABLE_SURVEY_QUESTION);
$table_survey_question_option = Database::get_course_table(TABLE_SURVEY_QUESTION_OPTION);
$table_course = Database::get_main_table(TABLE_MAIN_COURSE);
$table_user = Database::get_main_table(TABLE_MAIN_USER);

$urlname = strip_tags(api_substr(api_html_entity_decode($survey_data['title'], ENT_QUOTES), 0, 40));
if (api_strlen(strip_tags($survey_data['title'])) > 40) {
    $urlname .= '...';
}

// Breadcrumbs
$interbreadcrumb[] = array('url' => api_get_path(WEB_CODE_PATH).'survey/survey_list.php', 'name' => get_lang('SurveyList'));
if (api_is_course_admin()) {
    $interbreadcrumb[] = array(
        'url' => api_get_path(WEB_CODE_PATH).'survey/survey.php?survey_id='.$survey_id.'&'.api_get_cidreq(),
        'name' => $urlname,
    );
} else {
    $interbreadcrumb[] = array(
        'url' => api_get_path(WEB_CODE_PATH).'survey/survey_invite.php?survey_id='.$survey_id.'&'.api_get_cidreq(),
        'name' => $urlname,
    );
}
$tool_name = get_lang('SurveyPublication');

// Displaying the header
Display::display_header($tool_name, 'Survey');

echo '<script>
$(function() {
    $("#check_mail").change(function() {
        $("#mail_text_wrapper").toggle();
    });
});
</script>';

// Checking if there is another survey with this code.
// If this is the case there will be a language choice
$sql = "SELECT * FROM $table_survey
        WHERE c_id = $course_id AND code='".Database::escape_string($survey_data['code'])."'";
$result = Database::query($sql);
if (Database::num_rows($result) > 1) {
    echo Display::return_message(get_lang('IdenticalSurveycodeWarning'), 'warning');
}

// Invited / answered message
if ($survey_data['invited'] > 0 && !isset($_POST['submit'])) {
    $message  = '<a href="'.api_get_path(WEB_CODE_PATH).'survey/survey_invitation.php?view=answered&survey_id='.$survey_data['survey_id'].'">'.$survey_data['answered'].'</a> ';
    $message .= get_lang('HaveAnswered').' ';
    $message .= '<a href="'.api_get_path(WEB_CODE_PATH).'survey/survey_invitation.php?view=invited&survey_id='.$survey_data['survey_id'].'">'.$survey_data['invited'].'</a> ';
    $message .= get_lang('WereInvited');
    echo Display::return_message($message, 'normal', false);
}

// Building the form for publishing the survey
$form = new FormValidator(
    'publish_form',
    'post',
    api_get_self().'?survey_id='.$survey_id.'&'.api_get_cidreq()
);
$form->addElement('header', '', $tool_name);

// Course users
$complete_user_list = CourseManager::get_user_list_from_course_code(
    api_get_course_id(),
    api_get_session_id(),
    '',
    api_sort_by_first_name() ? 'ORDER BY firstname' : 'ORDER BY lastname'
);
$possible_users = array();
foreach ($complete_user_list as & $user) {
    $possible_users[$user['user_id']] = api_get_person_name($user['firstname'], $user['lastname']);
}

CourseManager::addUserGroupMultiSelect($form, array());

/*$form->addElement(
    'advmultiselect',
    'course_users',
    get_lang('CourseUsers'),
    $possible_users,
    'style="width: 250px; height: 200px;"'
);*/

// Additional users
$form->addElement(
    'textarea',
    'additional_users',
    array(get_lang('AdditonalUsers'), get_lang('AdditonalUsersComment')),
    array('rows' => 5)
);

$form->addElement('html', '<div id="check_mail">');
$form->addElement('checkbox', 'send_mail', '', get_lang('SendMail'));
$form->addElement('html', '</div>');

$form->addElement('html', '<div id="mail_text_wrapper">');

// The title of the mail
$form->addText('mail_title', get_lang('MailTitle'), false);
// The text of the mail
$form->addHtmlEditor(
    'mail_text',
    array(get_lang('MailText'), get_lang('UseLinkSyntax')),
    false,
    array('ToolbarSet' => 'Survey', 'Height' => '150')
);
$form->addElement('html', '</div>');
// You cab send a reminder to unanswered people if the survey is not anonymous
if ($survey_data['anonymous'] != 1) {
    $form->addElement('checkbox', 'remindUnAnswered', '', get_lang('RemindUnanswered'));
}
// Allow resending to all selected users
$form->addElement('checkbox', 'resend_to_all', '', get_lang('ReminderResendToAllUsers'));

// Submit button
$form->addButtonSave(get_lang('PublishSurvey'));
// The rules (required fields)
/*if ($survey_data['send_mail'] == 0) {
    $form->addRule('mail_title', get_lang('ThisFieldIsRequired'), 'required');
    $form->addRule('mail_text', get_lang('ThisFieldIsRequired'), 'required');
}*/
$portal_url = api_get_path(WEB_PATH);
if (api_is_multiple_url_enabled()) {
    $access_url_id = api_get_current_access_url_id();
    if ($access_url_id != -1) {
        $url = api_get_access_url($access_url_id);
        $portal_url = $url['url'];
    }
}

// Show the URL that can be used by users to fill a survey without invitation
$auto_survey_link = $portal_url.'main/survey/fillsurvey.php?course='.$_course['sysCode'].'&invitationcode=auto&scode='.$survey_data['survey_code'];

$form->addElement('label', null, get_lang('AutoInviteLink'));
$form->addElement('label', null, $auto_survey_link);

if ($form->validate()) {
    $values = $form->exportValues();

    $resendAll = isset($values['resend_to_all']) ? $values['resend_to_all'] : '';
    $sendMail = isset($values['send_mail']) ? $values['send_mail'] : '';
    $remindUnAnswered = isset($values['remindUnAnswered']) ? $values['remindUnAnswered'] : '';

    if ($sendMail) {
        if (empty($values['mail_title']) || empty($values['mail_text'])) {
            echo Display::return_message(get_lang('FormHasErrorsPleaseComplete'), 'error');
            // Getting the invited users
            $defaults = SurveyUtil::get_invited_users($survey_data['code']);

            // Getting the survey mail text
            if (!empty($survey_data['reminder_mail'])) {
                $defaults['mail_text'] = $survey_data['reminder_mail'];
            } else {
                $defaults['mail_text'] = $survey_data['invite_mail'];
            }
            $defaults['mail_title'] = $survey_data['mail_subject'];
            $defaults['send_mail'] = 1;
            $form->setDefaults($defaults);
            $form->display();
            return;
        }
    }
    // Save the invitation mail
    SurveyUtil::save_invite_mail(
        $values['mail_text'],
        $values['mail_title'],
        !empty($survey_data['invite_mail'])
    );

    // Saving the invitations for the course users
    $count_course_users = SurveyUtil::saveInvitations(
        $values['users'],
        $values['mail_title'],
        $values['mail_text'],
        $resendAll,
        $sendMail,
        $remindUnAnswered
    );

    // Saving the invitations for the additional users
    $values['additional_users'] = $values['additional_users'].';'; // This is for the case when you enter only one email
    $temp = str_replace(',', ';', $values['additional_users']); // This is to allow , and ; as email separators
    $additional_users = explode(';', $temp);
    for ($i = 0; $i < count($additional_users); $i++) {
        $additional_users[$i] = trim($additional_users[$i]);
    }

    $counter_additional_users = SurveyUtil::saveInvitations(
        $additional_users,
        $values['mail_title'],
        $values['mail_text'],
        $resendAll,
        $sendMail,
        $remindUnAnswered
    );

    // Updating the invited field in the survey table
    SurveyUtil::update_count_invited($survey_data['code']);
    $total_count = $count_course_users + $counter_additional_users;
    $table_survey = Database::get_course_table(TABLE_SURVEY);
    // Counting the number of people that are invited
    $sql = "SELECT * FROM $table_survey
            WHERE
                c_id = $course_id AND
                code = '".Database::escape_string($survey_data['code'])."'
            ";
    $result = Database::query($sql);
    $row = Database::fetch_array($result);
    $total_invited = $row['invited'];
    if ($total_invited > 0) {
        $message  = '<a href="'.api_get_path(WEB_CODE_PATH).'survey/survey_invitation.php?view=answered&survey_id='.$survey_data['survey_id'].'">'.
            $survey_data['answered'].'</a> ';
        $message .= get_lang('HaveAnswered').' ';
        $message .= '<a href="'.api_get_path(WEB_CODE_PATH).'survey/survey_invitation.php?view=invited&survey_id='.$survey_data['survey_id'].'">'.
            $total_invited.'</a> ';
        $message .= get_lang('WereInvited');
        echo Display::return_message($message, 'normal', false);
        if ($sendMail) {
            echo Display::return_message($total_count.' '.get_lang('InvitationsSend'), 'success', false);
        }
    }
} else {
    // Getting the invited users
    $defaults = SurveyUtil::get_invited_users($survey_data['code']);
    // Getting the survey mail text
    if (!empty($survey_data['reminder_mail'])) {
        $defaults['mail_text'] = $survey_data['reminder_mail'];
    } else {
        $defaults['mail_text'] = $survey_data['invite_mail'];
    }
    $defaults['mail_title'] = $survey_data['mail_subject'];
    $defaults['send_mail'] = 1;

    $form->setDefaults($defaults);
    $form->display();
}
Display :: display_footer();

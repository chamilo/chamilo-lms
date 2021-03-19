<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CSurvey;

require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_COURSES;

if (!api_is_allowed_to_edit(false, true)) {
    api_not_allowed(true);
}
$course = api_get_course_entity();
$course_id = api_get_course_int_id();

$surveyId = $_GET['survey_id'] ?? 0;
$repo = Container::getSurveyRepository();
$survey = null;
if (!empty($surveyId)) {
    /** @var CSurvey $survey */
    $survey = $repo->find($surveyId);
    $surveyId = $survey->getIid();
}
if (null === $survey) {
    api_not_allowed(true);
}

$survey_data = SurveyManager::get_survey($surveyId);
$table_survey = Database::get_course_table(TABLE_SURVEY);
$urlname = strip_tags(api_substr(api_html_entity_decode($survey->getTitle(), ENT_QUOTES), 0, 40));
if (api_strlen(strip_tags($survey->getTitle())) > 40) {
    $urlname .= '...';
}

$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'survey/survey_list.php?'.api_get_cidreq(),
    'name' => get_lang('Survey list'),
];
if (api_is_course_admin()) {
    if (3 == $survey->getSurveyType()) {
        $interbreadcrumb[] = [
            'url' => api_get_path(WEB_CODE_PATH).'survey/meeting.php?survey_id='.$surveyId.'&'.api_get_cidreq(),
            'name' => $urlname,
        ];
    } else {
        $interbreadcrumb[] = [
            'url' => api_get_path(WEB_CODE_PATH).'survey/survey.php?survey_id='.$surveyId.'&'.api_get_cidreq(),
            'name' => $urlname,
        ];
    }
} else {
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'survey/survey_invite.php?survey_id='.$surveyId.'&'.api_get_cidreq(),
        'name' => $urlname,
    ];
}
$tool_name = get_lang('Publication of the survey');
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
/*$sql = "SELECT * FROM $table_survey
        WHERE code='".Database::escape_string($survey_data['code'])."'";
$result = Database::query($sql);
if (Database::num_rows($result) > 1) {
    echo Display::return_message(
        get_lang(
            'This survey code already exists. That probably means the survey exists in other languages. Invited people will choose between different languages.'
        ),
        'warning'
    );
}*/

$invitationUrl = api_get_path(WEB_CODE_PATH).'survey/survey_invitation.php?survey_id='.$surveyId.'&'.api_get_cidreq();
// Invited / answered message
if ($survey->getInvited() > 0 && !isset($_POST['submit'])) {
    $message = Display::url($survey->getAnswered(), $invitationUrl.'&view=answered');
    $message .= ' '.get_lang('have answered').' ';
    $message .= Display::url($survey_data['invited'], $invitationUrl.'&view=invited');
    $message .= ' '.get_lang('were invited');
    echo Display::return_message($message, 'normal', false);
}

// Building the form for publishing the survey
$form = new FormValidator(
    'publish_form',
    'post',
    api_get_self().'?survey_id='.$surveyId.'&'.api_get_cidreq()
);
$form->addHeader($tool_name);
$sessionId = api_get_session_id();
CourseManager::addUserGroupMultiSelect($form, [], true);

$form->addTextarea(
    'additional_users',
    [get_lang('Additional users'), get_lang('Additional usersComment')],
    //['rows' => 5]
);

$form->addHtml('<div id="check_mail">');
$form->addCheckBox('send_mail', '', get_lang('Send mail'));
$form->addHtml('</div>');
$form->addHtml('<div id="mail_text_wrapper">');

// The title of the mail
$form->addText('mail_title', get_lang('Mail subject'), false);
// The text of the mail
$form->addHtmlEditor(
    'mail_text',
    [
        get_lang('E-mail message'),
        get_lang(
            'The selected users will receive an email with the text above and a unique link that they have to click to fill the survey. If you want to put the link somewhere in your text you have to put the following text wherever you want in your text: **link** (star star link star star). This will then automatically be replaced by the unique link. If you do not add **link** to your text then the email link will be added to the end of the mail'
        ),
    ],
    false,
    ['ToolbarSet' => 'Survey', 'Height' => '150']
);
$form->addElement('html', '</div>');
// You cab send a reminder to unanswered people if the survey is not anonymous
if (1 != $survey->getAnonymous() || api_get_configuration_value('survey_anonymous_show_answered')) {
    $form->addElement('checkbox', 'remindUnAnswered', '', get_lang('Remind only users who didn\'t answer'));
}
// Allow resending to all selected users
$form->addCheckBox(
    'resend_to_all',
    '',
    get_lang(
        'Remind all users of the survey. If you do not check this checkbox only the newly-added users will receive an e-mail.'
    )
);
$form->addElement('checkbox', 'hide_link', '', get_lang('Hide survey invitation link'));

// Submit button
$form->addButtonSave(get_lang('Publish survey'));

// Show the URL that can be used by users to fill a survey without invitation
$auto_survey_link = SurveyUtil::generateFillSurveyLink(
    $survey,
    'auto',
    $course,
    $sessionId
);

$form->addElement('label', null, get_lang('Users who are not invited can use this link to take the survey:'));
$form->addElement('label', null, $auto_survey_link);

if ($form->validate()) {
    $values = $form->exportValues();
    $resendAll = isset($values['resend_to_all']) ? $values['resend_to_all'] : '';
    $sendMail = isset($values['send_mail']) ? $values['send_mail'] : '';
    $remindUnAnswered = isset($values['remindUnAnswered']) ? $values['remindUnAnswered'] : '';
    $users = isset($values['users']) ? $values['users'] : [];
    $hideLink = isset($values['hide_link']) && $values['hide_link'] ? true : false;

    if ($sendMail) {
        if (empty($values['mail_title']) || empty($values['mail_text'])) {
            echo Display::return_message(
                get_lang('The form contains incorrect or incomplete data. Please check your input.'),
                'error'
            );
            // Getting the invited users
            $defaults = SurveyUtil::get_invited_users($survey);

            // Getting the survey mail text
            if (!empty($survey->getReminderMail())) {
                $defaults['mail_text'] = $survey->getReminderMail();
            } else {
                $defaults['mail_text'] = $survey->getInviteMail();
            }
            $defaults['mail_title'] = $survey->getMailSubject();
            $defaults['send_mail'] = 1;
            $form->setDefaults($defaults);
            $form->display();
        }
    }

    // Save the invitation mail
    SurveyUtil::saveInviteMail(
        $survey,
        $values['mail_text'],
        $values['mail_title'],
        !empty($survey->getInviteMail())
    );

    // Saving the invitations for the course users
    $count_course_users = SurveyUtil::saveInvitations(
        $survey,
        $users,
        $values['mail_title'],
        $values['mail_text'],
        $resendAll,
        $sendMail,
        $remindUnAnswered,
        false,
        $hideLink
    );

    // Saving the invitations for the additional users
    $values['additional_users'] = $values['additional_users'].';'; // This is for the case when you enter only one email
    $temp = str_replace(',', ';', $values['additional_users']); // This is to allow , and ; as email separators
    $additional_users = explode(';', $temp);
    for ($i = 0; $i < count($additional_users); $i++) {
        $additional_users[$i] = trim($additional_users[$i]);
    }

    $counter_additional_users = SurveyUtil::saveInvitations(
        $survey,
        $additional_users,
        $values['mail_title'],
        $values['mail_text'],
        $resendAll,
        $sendMail,
        $remindUnAnswered,
        true
    );

    // Updating the invited field in the survey table
    // Counting the number of people that are invited
    $total_invited = SurveyUtil::updateInvitedCount($survey);
    $total_count = $count_course_users + $counter_additional_users;

    if ($total_invited > 0) {
        $message = '<a href="'.$invitationUrl.'&view=answered">'.
            $survey_data['answered'].'</a> ';
        $message .= get_lang('have answered').' ';
        $message .= '<a href="'.$invitationUrl.'&view=invited">'.
            $total_invited.'</a> ';
        $message .= get_lang('were invited');
        echo Display::return_message($message, 'normal', false);
        if ($sendMail) {
            echo Display::return_message($total_count.' '.get_lang('invitations sent.'), 'success', false);
        }
    }
} else {
    // Getting the invited users
    $defaults = SurveyUtil::get_invited_users($survey);
    // Getting the survey mail text
    if (!empty($survey->getReminderMail())) {
        $defaults['mail_text'] = $survey->getReminderMail();
    } else {
        $defaults['mail_text'] = $survey->getInviteMail();
    }
    $defaults['mail_title'] = $survey->getMailSubject();
    $defaults['send_mail'] = 1;

    $form->setDefaults($defaults);
    $form->display();
}
Display::display_footer();

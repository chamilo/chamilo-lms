<?php
/* For licensing terms, see /license.txt */

/**
 * @package chamilo.survey
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University: cleanup, refactoring and rewriting large parts of the code
 *
 * @version $Id: survey_invite.php 10680 2007-01-11 21:26:23Z pcool $
 *
 * @todo the answered column
 */
require_once __DIR__.'/../inc/global.inc.php';

/** @todo this has to be moved to a more appropriate place (after the display_header of the code)*/
if (!api_is_allowed_to_edit(false, true)) {
    api_not_allowed(true);
}

// Database table definitions
$table_survey = Database::get_course_table(TABLE_SURVEY);
$table_survey_question = Database::get_course_table(TABLE_SURVEY_QUESTION);
$table_survey_question_option = Database::get_course_table(TABLE_SURVEY_QUESTION_OPTION);
$table_course = Database::get_main_table(TABLE_MAIN_COURSE);
$table_user = Database::get_main_table(TABLE_MAIN_USER);
$table_survey_invitation = Database::get_course_table(TABLE_SURVEY_INVITATION);
$tool_name = get_lang('SurveyInvitations');
$courseInfo = api_get_course_info();

// Getting the survey information
$survey_id = Security::remove_XSS($_GET['survey_id']);
$survey_data = SurveyManager::get_survey($survey_id);
if (empty($survey_data)) {
    api_not_allowed(true);
}

$view = isset($_GET['view']) ? $_GET['view'] : 'invited';

$urlname = strip_tags(
    api_substr(api_html_entity_decode($survey_data['title'], ENT_QUOTES), 0, 40)
);
if (api_strlen(strip_tags($survey_data['title'])) > 40) {
    $urlname .= '...';
}

// Breadcrumbs
$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'survey/survey_list.php',
    'name' => get_lang('SurveyList'),
];
$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'survey/survey.php?survey_id='.$survey_id,
    'name' => $urlname,
];

// Displaying the header
Display::display_header($tool_name);

// Getting all the people who have filled this survey
$answered_data = SurveyManager::get_people_who_filled_survey($survey_id);
if ($survey_data['anonymous'] == 1 && !api_get_configuration_value('survey_anonymous_show_answered')) {
    echo Display::return_message(
        get_lang('AnonymousSurveyCannotKnowWhoAnswered').' '.count(
            $answered_data
        ).' '.get_lang('PeopleAnswered')
    );
    $answered_data = [];
}
$url = api_get_self().'?survey_id='.$survey_id.'&'.api_get_cidreq();

if ($view == 'invited') {
    echo get_lang('ViewInvited').' | ';
} else {
    echo '	<a href="'.$url.'&view=invited">'.
        get_lang('ViewInvited').'</a> |';
}
if ($view == 'answered') {
    echo get_lang('ViewAnswered').' | ';
} else {
    echo '	<a href="'.$url.'&view=answered">'.
        get_lang('ViewAnswered').'</a> |';
}

if ($view == 'unanswered') {
    echo get_lang('ViewUnanswered');
} else {
    echo '	<a href="'.$url.'&view=unanswered">'.
        get_lang('ViewUnanswered').'</a>';
}

// Table header
echo '<table class="data_table">';
echo '	<tr>';
echo '		<th>'.get_lang('User').'</th>';
echo '		<th>'.get_lang('InvitationDate').'</th>';

switch ($view) {
    case 'unanswered':
        echo '		<th>'.get_lang('SurveyInviteLink').'</th>';
        break;
    case 'invited':
        echo '		<th>'.get_lang('Answered').'</th>';
        break;
}

echo '	</tr>';

$course_id = api_get_course_int_id();
$sessionId = api_get_session_id();

$sentIntitations = SurveyUtil::getSentInvitations($survey_data['code'], $course_id, $sessionId);

foreach ($sentIntitations as $row) {
    $id = $row['iid'];
    if ($view == 'invited' ||
        ($view == 'answered' && in_array($row['user'], $answered_data) && count($answered_data) > 1) ||
        ($view == 'unanswered' && !in_array($row['user'], $answered_data) && count($answered_data) > 1)
    ) {
        echo '<tr>';
        if (is_numeric($row['user'])) {
            $userInfo = api_get_user_info($row['user']);
            echo '<td>';
            echo UserManager::getUserProfileLink($userInfo);
            echo '</td>';
        } else {
            echo '<td>'.$row['user'].'</td>';
        }
        echo '	<td>'.Display::dateToStringAgoAndLongDate($row['invitation_date']).'</td>';

        /*if (in_array($row['user'], $answered_data) && !api_get_configuration_value('hide_survey_reporting_button') &&
            !api_get_configuration_value('survey_anonymous_show_answered')) {
            echo '<a href="'.
                api_get_path(WEB_CODE_PATH).
                'survey/reporting.php?action=userreport&survey_id='.$survey_id.'&user='.$row['user'].'&'.api_get_cidreq().'">'.
                get_lang('ViewAnswers').'</a>';
        } else {
            echo '-';
        }*/
        $answered = '';
        if (in_array($row['user'], $answered_data)) {
            $answered = Display::url(
                get_lang('ViewAnswers'),
                api_get_path(WEB_CODE_PATH).'survey/reporting.php?action=userreport&survey_id='.$survey_id.'&user='.$row['user'].'&'.api_get_cidreq(),
                ['class' => 'btn btn-primary']
            );
        }
        switch ($view) {
            case 'unanswered':
                echo '	<td>';
                $code = $row['invitation_code'];

                $link = SurveyUtil::generateFillSurveyLink($code, $courseInfo, $sessionId);
                $link = Display::input('text', 'copy_'.$id, $link, ['id' => 'copy_'.$id, 'class' => '']);
                $link .= ' '.Display::url(
                    Display::returnFontAwesomeIcon('copy').get_lang('CopyTextToClipboard'),
                    'javascript:void()',
                    ['onclick' => "copyTextToClipBoard('copy_".$id."')", 'class' => 'btn btn-primary btn-sm']
                );

                echo $link;
                echo '	</td>';
                break;
            case 'invited':
                echo '	<td>';
                echo $answered;
                echo '	</td>';
                break;
        }

        echo '</tr>';
    }
}

// Closing the table
echo '</table>';

Display::display_footer();

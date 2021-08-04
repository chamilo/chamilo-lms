<?php
/* For licensing terms, see /license.txt */

/**
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

$course_id = api_get_course_int_id();
$sessionId = api_get_session_id();

$sentInvitations = SurveyUtil::getSentInvitations($survey_data['code'], $course_id, $sessionId);

// Getting all the people who have filled this survey
$answered_data = SurveyManager::get_people_who_filled_survey($survey_id);

$invitationsCount = count($sentInvitations);
$answeredCount = count($answered_data);
$unasnweredCount = count($sentInvitations) - count($answered_data);

if ($survey_data['anonymous'] == 1 && !api_get_configuration_value('survey_anonymous_show_answered')) {
    echo Display::return_message(
        get_lang('AnonymousSurveyCannotKnowWhoAnswered').' '.$answeredCount.' '.get_lang('PeopleAnswered')
    );
    $answered_data = [];
}
if ($survey_data['anonymous'] == 1) {
    if ($answeredCount < 2) {
        $answeredCount = 0;
        $unasnweredCount = $invitationsCount;
    }
}
$url = api_get_self().'?survey_id='.$survey_id.'&'.api_get_cidreq();

echo '<ul class="nav nav-tabs">';

if ($view == 'invited') {
    echo '<li role="presentation" class="active"><a href="#">'.get_lang('ViewInvited');
} else {
    echo '<li role="presentation"><a href="'.$url.'&view=invited">'.
        get_lang('ViewInvited');
}
echo ' <span class="badge badge-default">'.$invitationsCount.'</span>';
echo '</a></li>';
if ($view == 'answered') {
    echo '<li role="presentation" class="active"><a href="#">'.get_lang('ViewAnswered');
} else {
    echo '<li role="presentation"><a href="'.$url.'&view=answered">'.
        get_lang('ViewAnswered');
}
echo ' <span class="badge badge-default">'.$answeredCount.'</span>';
echo '</a></li>';

if ($view == 'unanswered') {
    echo '<li role="presentation" class="active"><a href="#">'.get_lang('ViewUnanswered');
} else {
    echo '<li role="presentation"><a href="'.$url.'&view=unanswered">'.
        get_lang('ViewUnanswered');
}
echo ' <span class="badge badge-default">'.$unasnweredCount.'</span>';
echo '</a></li>';
echo '</ul>';

// Table header
echo '<table class="table table-hover table-striped data_table" style="margin-top: 5px;">';
echo '	<tr>';
echo '		<th>'.get_lang('User').'</th>';
echo '		<th>'.get_lang('InvitationDate').'</th>';

switch ($view) {
    case 'unanswered':
        echo '		<th>'.get_lang('SurveyInviteLink').'</th>';
        break;
    default:
        echo '		<th>'.get_lang('Answered').'</th>';
        break;
}

echo '	</tr>';

$surveyAnonymousShowAnswered = api_get_configuration_value('survey_anonymous_show_answered');
$hideSurveyReportingButton = api_get_configuration_value('hide_survey_reporting_button');

foreach ($sentInvitations as $row) {
    $id = $row['iid'];
    if ($view == 'invited' ||
        ($view == 'answered' && in_array($row['user'], $answered_data) && $answeredCount > 1) ||
        ($view == 'unanswered' && !in_array($row['user'], $answered_data) && $answeredCount > 1)
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

        if (in_array($row['user'], $answered_data)) {
            if (!$surveyAnonymousShowAnswered && !$hideSurveyReportingButton) {
                echo '<td>';
                echo '<a href="'.
                    api_get_path(WEB_CODE_PATH).
                    'survey/reporting.php?action=userreport&survey_id='.$survey_id.'&user='.$row['user'].'&'.api_get_cidreq().'">'.
                    get_lang('ViewAnswers').'</a>';
                echo '</td>';
            } else {
                if ($survey_data['anonymous'] == 1 && $answeredCount > 1) {
                    echo '<td>'.get_lang('Answered').'</td>';
                } else {
                    echo '<td>-</td>';
                }
            }
        } else {
            if ($view == 'unanswered') {
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
            } else {
                echo '<td>-</td>';
            }
        }

        echo '</tr>';
    } elseif ($view === 'unanswered' && $answeredCount == 0) {
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
        echo '</tr>';
    }
}

// Closing the table
echo '</table>';

Display::display_footer();

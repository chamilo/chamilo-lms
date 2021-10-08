<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CSurvey;

require_once __DIR__.'/../inc/global.inc.php';

if (!api_is_allowed_to_edit(false, true)) {
    api_not_allowed(true);
}

$tool_name = get_lang('Survey invitations');
$courseInfo = api_get_course_info();
$course = api_get_course_entity();
$repo = Container::getSurveyRepository();
$surveyId = $_GET['survey_id'] ?? 0;
if (empty($surveyId)) {
    api_not_allowed(true);
}
/** @var CSurvey $survey */
$survey = $repo->find($surveyId);
if (null === $survey) {
    api_not_allowed(true);
}
$surveyId = $survey->getIid();
$view = $_GET['view'] ?? 'invited';

$urlname = strip_tags(
    api_substr(api_html_entity_decode($survey->getTitle(), ENT_QUOTES), 0, 40)
);
if (api_strlen(strip_tags($survey->getTitle())) > 40) {
    $urlname .= '...';
}

// Breadcrumbs
$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'survey/survey_list.php',
    'name' => get_lang('Survey list'),
];
$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'survey/survey.php?survey_id='.$surveyId,
    'name' => $urlname,
];

Display::display_header($tool_name);

$course_id = api_get_course_int_id();
$sessionId = api_get_session_id();

$sentInvitations = SurveyUtil::getSentInvitations($survey->getIid(), $course_id, $sessionId);

// Getting all the people who have filled this survey
$answered_data = SurveyManager::get_people_who_filled_survey($surveyId);

$invitationsCount = count($sentInvitations);
$answeredCount = count($answered_data);
$unAnsweredCount = count($sentInvitations) - count($answered_data);

if (1 == $survey->getAnonymous() && !api_get_configuration_value('survey_anonymous_show_answered')) {
    echo Display::return_message(
        get_lang('This survey is anonymous. You can\'t see who answered.').' '.$answeredCount.' '.get_lang(
            'people answered'
        )
    );
    $answered_data = [];
}
if (1 == $survey->getAnonymous()) {
    if ($answeredCount < 2) {
        $answeredCount = 0;
        $unAnsweredCount = $invitationsCount;
    }
}
$url = api_get_self().'?survey_id='.$surveyId.'&'.api_get_cidreq();

echo '<ul class="nav nav-tabs">';
if ('invited' === $view) {
    echo '<li role="presentation" class="active"><a "href="#">'.get_lang('View invited');
} else {
    echo '<li role="presentation"><a href="'.$url.'&view=invited">'.
        get_lang('View invited');
}
echo Display::badge($invitationsCount);
echo '</a></li>';
if ('answered' === $view) {
    echo '<li role="presentation" class="active"><a href="#">'.get_lang('View people who answered');
} else {
    echo '<li role="presentation">
        <a href="'.$url.'&view=answered">'.
        get_lang('View people who answered');
}
echo Display::badge($answeredCount);
echo '</a></li>';

if ('unanswered' === $view) {
    echo '<li role="presentation" class="active">
        <a href="#">'.get_lang('View people who didn\'t answer');
} else {
    echo '<li role="presentation">
        <a href="'.$url.'&view=unanswered">'.
        get_lang('View people who didn\'t answer');
}
echo Display::badge($unAnsweredCount);
echo '</a></li>';
echo '</ul>';
echo '<table class="table table-hover table-striped data_table" style="margin-top: 5px;">';
echo '<tr>';
echo '<th>'.get_lang('User').'</th>';
echo '<th>'.get_lang('Invitation date').'</th>';

switch ($view) {
    case 'unanswered':
        echo '<th>'.get_lang('Survey invitation link').'</th>';
        break;
    default:
        echo '<th>'.get_lang('Answered').'</th>';
        break;
}
echo '</tr>';
$surveyAnonymousShowAnswered = api_get_configuration_value('survey_anonymous_show_answered');
$hideSurveyReportingButton = api_get_configuration_value('hide_survey_reporting_button');

foreach ($sentInvitations as $row) {
    $id = $row['iid'];
    $user = $row['user'];
    if ('invited' === $view ||
        ('answered' === $view && in_array($user, $answered_data) && $answeredCount > 1) ||
        ('unanswered' === $view && !in_array($user, $answered_data) && $answeredCount > 1)
    ) {
        echo '<tr>';
        if (is_numeric($user)) {
            $userInfo = api_get_user_info($user);
            echo '<td>';
            echo UserManager::getUserProfileLink($userInfo);
            echo '</td>';
        } else {
            echo '<td>'.$user.'</td>';
        }
        echo '	<td>'.Display::dateToStringAgoAndLongDate($row['invitation_date']).'</td>';

        if (in_array($user, $answered_data)) {
            if (!$surveyAnonymousShowAnswered && !$hideSurveyReportingButton) {
                echo '<td>';
                echo '<a href="'.
                    api_get_path(WEB_CODE_PATH).
                    'survey/reporting.php?action=userreport&survey_id='.$surveyId.'&user='.$user.'&'.api_get_cidreq().'">'.
                    get_lang('View answers').'</a>';
                echo '</td>';
            } else {
                if (1 == $survey->getAnonymous() && $answeredCount > 1) {
                    echo '<td>'.get_lang('Answered').'</td>';
                } else {
                    echo '<td>-</td>';
                }
            }
        } else {
            if ('unanswered' === $view) {
                echo '<td>';
                $code = $row['invitation_code'];
                $link = SurveyUtil::generateFillSurveyLink($survey, $code, $course, $sessionId);
                $link = Display::input('text', 'copy_'.$id, $link, ['id' => 'copy_'.$id, 'class' => '']);
                $link .= ' '.Display::url(
                        Display::getMdiIcon('content-copy').get_lang('Copy text'),
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
    } elseif ('unanswered' === $view && 0 == $answeredCount) {
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
        $link = SurveyUtil::generateFillSurveyLink($survey, $code, $course, $sessionId);
        $link = Display::input('text', 'copy_'.$id, $link, ['id' => 'copy_'.$id, 'class' => '']);
        $link .= ' '.Display::url(
                Display::getMdiIcon('content-copy').get_lang('Copy text'),
                'javascript:void()',
                ['onclick' => "copyTextToClipBoard('copy_".$id."')", 'class' => 'btn btn-primary btn-sm']
            );

        echo $link;
        echo '</td></tr>';
    }
}

echo '</table>';
Display::display_footer();

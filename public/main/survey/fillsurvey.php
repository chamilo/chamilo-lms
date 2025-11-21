<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CSurvey;
use ChamiloSession as Session;

$lastQuestion = 0;

/*
 * @author unknown, the initial survey that did not make it in 1.8 because of bad code
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University: cleanup,
 * refactoring and rewriting large parts of the code
 * @author Julio Montoya <gugli100@gmail.com>, Chamilo: Personality Test
 * modification and rewriting large parts of the code as well
 *
 * @todo check if the user already filled the survey and if this
 * is the case then the answers have to be updated and not stored again.
 * @todo performance could be improved if not the survey_id was
 * stored with the invitation but the survey_code
 */

// Unsetting the course id (because it is in the URL)
if (!isset($_GET['cidReq'])) {
    $cidReset = true;
} else {
    $_cid = $_GET['cidReq'];
}

require_once __DIR__.'/../inc/global.inc.php';

// -----------------------------------------------------------------------------
// DB tables
// -----------------------------------------------------------------------------
$table_survey = Database::get_course_table(TABLE_SURVEY);
$table_survey_answer = Database::get_course_table(TABLE_SURVEY_ANSWER);
$table_survey_question = Database::get_course_table(TABLE_SURVEY_QUESTION);
$table_survey_question_option = Database::get_course_table(TABLE_SURVEY_QUESTION_OPTION);
$table_survey_invitation = Database::get_course_table(TABLE_SURVEY_INVITATION);
$table_user = Database::get_main_table(TABLE_MAIN_USER);

$allowRequiredSurveyQuestions = true;

// -----------------------------------------------------------------------------
// Auth / context
// -----------------------------------------------------------------------------
$isAnonymous = api_is_anonymous(api_get_user_id(), true);

$courseInfo = isset($_GET['course'])
    ? api_get_course_info($_GET['course'])
    : api_get_course_info();

if (empty($courseInfo)) {
    api_not_allowed(true);
}

$courseId = $courseInfo['real_id'];
$userInfo = api_get_user_info();
$sessionId = isset($_GET['sid']) ? (int) $_GET['sid'] : api_get_session_id();
$lpItemId = isset($_GET['lp_item_id']) ? (int) $_GET['lp_item_id'] : 0;
$allowSurveyInLp = true;

if (!empty($userInfo)) {
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'survey/survey_list.php?cid='.$courseId.'&sid='.$sessionId,
        'name' => get_lang('Survey list'),
    ];
}

// -----------------------------------------------------------------------------
// Required params
// -----------------------------------------------------------------------------
if ((!isset($_GET['course']) || !isset($_GET['invitationcode'])) && !isset($_GET['user_id'])) {
    api_not_allowed(true, get_lang('There is a parameter missing in the link. Please use copy and past'));
}

$repo = Container::getSurveyRepository();
$surveyId = isset($_GET['iid']) ? (int) $_GET['iid'] : 0;

if (empty($surveyId) && (isset($_POST['language']) && is_numeric($_POST['language']))) {
    $surveyId = (int) $_POST['language'];
}

/** @var CSurvey $survey */
$survey = $repo->find($surveyId);
if (null === $survey) {
    api_not_allowed(true);
}

$surveyId = $survey->getIid();
$invitationCode = $_GET['invitationcode'] ?? null;

$lpItemCondition = '';
if ($allowSurveyInLp && !empty($lpItemId)) {
    $lpItemCondition = " AND c_lp_item_id = $lpItemId";
}

$sessionCondition = '';
if (true === api_get_setting('survey.show_surveys_base_in_sessions')) {
    $sessionCondition = api_get_session_condition($sessionId);
}

// Anonymous restriction
if (0 == $survey->getAnonymous() && api_is_anonymous()) {
    api_not_allowed(true);
}

// -----------------------------------------------------------------------------
// Auto-invitation flow
// -----------------------------------------------------------------------------
if ('auto' === $invitationCode) {
    $userid = api_get_user_id();
    $surveyCode = $survey->getCode();

    if ($isAnonymous) {
        $autoInvitationCode = 'auto-ANONY_'.md5(time())."-$surveyCode";
    } else {
        $invitations = SurveyManager::getUserInvitationsForSurveyInCourse(
            $userid,
            $surveyCode,
            $courseId,
            $sessionId,
            0,
            $lpItemId
        );
        $lastInvitation = current($invitations);
        $autoInvitationCode = $lastInvitation
            ? $lastInvitation->getInvitationCode()
            : "auto-$userid-$surveyCode";
    }

    SurveyManager::checkTimeAvailability($survey);

    $sql = "SELECT user_id
            FROM $table_survey_invitation
            WHERE c_id = $courseId
              AND invitation_code = '".Database::escape_string($autoInvitationCode)."'
              $sessionCondition
              $lpItemCondition";
    $result = Database::query($sql);
    $now = api_get_utc_datetime();

    if (0 == Database::num_rows($result)) {
        $params = [
            'c_id' => $courseId,
            'survey_id' => $surveyId,
            'user_id' => $userid,
            'invitation_code' => $autoInvitationCode,
            'invitation_date' => $now,
            'answered' => 0,
            'c_lp_item_id' => $lpItemId,
        ];
        Database::insert($table_survey_invitation, $params);
    }

    $_GET['invitationcode'] = $autoInvitationCode;
    Session::write('auto_invitation_code_'.$surveyCode, $autoInvitationCode);
    $invitationCode = $autoInvitationCode;
}

// Validate invitation code
$sql = "SELECT *
        FROM $table_survey_invitation
        WHERE c_id = $courseId
          AND invitation_code = '".Database::escape_string($invitationCode)."'
          $sessionCondition
          $lpItemCondition";
$result = Database::query($sql);
if (Database::num_rows($result) < 1) {
    api_not_allowed(true, get_lang('Wrong invitation code'));
}

$survey_invitation = Database::fetch_assoc($result);
$surveyUserFromSession = Session::read('surveyuser');

// Block if already answered
if (
    !isset($_POST['finish_survey'])
    && (
        ($isAnonymous && !empty($surveyUserFromSession) && SurveyUtil::isSurveyAnsweredFlagged($survey->getCode(), $survey_invitation['c_id']))
        || (1 == $survey_invitation['answered'] && !isset($_GET['user_id']))
    )
) {
    api_not_allowed(true, Display::return_message(get_lang('You already filled this survey')));
}

Event::registerLog([
    'tool' => TOOL_SURVEY,
    'tool_id' => $survey_invitation['iid'],
    'action' => 'invitationcode',
    'action_details' => $invitationCode,
]);

$survey_invitation['survey_id'] = $surveyId;

// Availability check
SurveyManager::checkTimeAvailability($survey);

// Redirect if meeting
$surveyType = $survey->getSurveyType();
if (3 === $surveyType) {
    header(
        'Location: '.
        api_get_path(WEB_CODE_PATH).
        'survey/meeting.php?cid='.$courseId.'&sid='.$sessionId.'&invitationcode='.Security::remove_XSS($invitationCode)
    );

    exit;
}

if (!empty($survey->getAnonymous())) {
    define('USER_IN_ANON_SURVEY', true);
}

// -----------------------------------------------------------------------------
// Answer saving
// -----------------------------------------------------------------------------
if (count($_POST) > 0) {
    if (0 === $surveyType) {
        // Standard survey flow
        $types = [];
        $required = [];
        $questionList = [];

        foreach ($survey->getQuestions() as $question) {
            $id = $question->getIid();
            $questionList[$id] = $question;
            $types[$id] = $question->getType();
            $required[$id] = $allowRequiredSurveyQuestions && $question->isMandatory();
        }

        foreach ($_POST as $key => &$value) {
            // Only question inputs
            if (!str_contains($key, 'other_question')
                  && str_contains($key, 'question') && '_qf__question' !== $key) {
                $survey_question_id = str_replace('question', '', $key);
                if (empty($survey_question_id)) {
                    continue;
                }

                $other = isset($_POST['other_question'.$survey_question_id]) ? $_POST['other_question'.$survey_question_id] : '';
                $question = $questionList[$survey_question_id] ?? null;
                if (null === $question) {
                    continue;
                }

                if (is_array($value)) {
                    // Score or multiple
                    SurveyUtil::remove_answer($survey_invitation['user_id'], $surveyId, $survey_question_id, $lpItemId);

                    foreach ($value as $answer_key => &$answer_value) {
                        if ('score' === $types[$survey_question_id]) {
                            $option_id = $answer_key;
                            $option_value = $answer_value;
                        } else {
                            $option_id = $answer_value;
                            $option_value = '';
                        }

                        SurveyUtil::saveAnswer(
                            $survey_invitation['user_id'],
                            $survey,
                            $question,
                            $option_id,
                            $option_value,
                            '',
                            $lpItemId
                        );
                    }
                } else {
                    // Open / single / percentage
                    $option_value = 0;
                    if (isset($types[$survey_question_id]) && 'percentage' === $types[$survey_question_id]) {
                        $sql = "SELECT * FROM $table_survey_question_option WHERE iid='".(int) $value."'";
                        $result = Database::query($sql);
                        $row = Database::fetch_assoc($result);
                        if ($row) {
                            $option_value = $row['option_text'];
                        }
                    } elseif (isset($types[$survey_question_id]) && 'open' === $types[$survey_question_id]) {
                        $option_value = $value;
                    }

                    SurveyUtil::remove_answer($survey_invitation['user_id'], $surveyId, $survey_question_id, $lpItemId);

                    SurveyUtil::saveAnswer(
                        $survey_invitation['user_id'],
                        $survey,
                        $question,
                        $value,
                        $option_value,
                        $other,
                        $lpItemId
                    );
                }
            }
        }
    } elseif (1 === $survey->getSurveyType()) {
        // Conditional / personality test
        $shuffle = '';
        if (1 == $survey->getShuffle()) {
            $shuffle = ' ORDER BY RAND() ';
        }

        $questionList = [];
        foreach ($survey->getQuestions() as $question) {
            $questionList[$question->getIid()] = $question;
        }

        foreach ($_POST as $key => &$value) {
            if (str_contains($key, 'question')) {
                $survey_question_id = str_replace('question', '', $key);
                if (empty($survey_question_id)) {
                    continue;
                }

                $sql = "SELECT value FROM $table_survey_question_option WHERE iid='".(int) $value."'";
                $result = Database::query($sql);
                $row = Database::fetch_assoc($result);
                $option_value = $row['value'];

                SurveyUtil::remove_answer(
                    $survey_invitation['user_id'],
                    $survey_invitation['survey_id'],
                    $survey_question_id,
                    $lpItemId
                );

                $surveyId = (int) $survey_invitation['survey_id'];
                $repo = Container::getSurveyRepository();
                $survey = $repo->find($surveyId);

                SurveyUtil::saveAnswer(
                    api_get_user_entity($survey_invitation['user_id']),
                    $survey,
                    $questionList[$survey_question_id],
                    $value,
                    $option_value,
                    '',
                    $lpItemId
                );
            }
        }
    } else {
        api_not_allowed(true, get_lang('Survey type unknown'));
    }
}

// -----------------------------------------------------------------------------
// Profile form (if requested by survey)
// -----------------------------------------------------------------------------
$user_id = api_get_user_id();
if (0 == $user_id) {
    $user_id = $survey_invitation['user_id'];
}
$user_data = api_get_user_info($user_id);

if ('' != $survey->getFormFields() && 0 == $survey->getAnonymous() && is_array($user_data)) {
    $form_fields = explode('@', $survey->getFormFields());
    $list = [];
    foreach ($form_fields as $field) {
        $field_value = explode(':', $field);
        if (isset($field_value[1]) && 1 == $field_value[1]) {
            if ('' != $field_value[0]) {
                $val = api_substr($field_value[0], 8, api_strlen($field_value[0]));
                $list[$val] = 1;
            }
        }
    }

    $url = api_get_self().'?'.api_get_cidreq();
    $listQueryParams = explode('&', $_SERVER['QUERY_STRING']);
    foreach ($listQueryParams as $param) {
        $url .= '&'.Security::remove_XSS($param);
    }
    if (!empty($lpItemId)) {
        $url .= '&lp_item_id='.$lpItemId;
    }

    // Same form as auth/profile.php
    $form = new FormValidator('profile', 'post', $url);
    if (api_is_western_name_order()) {
        if (isset($list['firstname']) && 1 == $list['firstname']) {
            $form->addElement('text', 'firstname', get_lang('First name'), ['size' => 40]);
            if ('true' !== api_get_setting('profile', 'name')) {
                $form->freeze(['firstname']);
            }
            $form->applyFilter(['firstname'], 'stripslashes');
            $form->applyFilter(['firstname'], 'trim');
            $form->addRule('firstname', get_lang('Required field'), 'required');
        }
        if (isset($list['lastname']) && 1 == $list['lastname']) {
            $form->addElement('text', 'lastname', get_lang('Last name'), ['size' => 40]);
            if ('true' !== api_get_setting('profile', 'name')) {
                $form->freeze(['lastname']);
            }
            $form->applyFilter(['lastname'], 'stripslashes');
            $form->applyFilter(['lastname'], 'trim');
            $form->addRule('lastname', get_lang('Required field'), 'required');
        }
    } else {
        if (isset($list['lastname']) && 1 == $list['lastname']) {
            $form->addElement('text', 'lastname', get_lang('Last name'), ['size' => 40]);
            if ('true' !== api_get_setting('profile', 'name')) {
                $form->freeze(['lastname']);
            }
            $form->applyFilter(['lastname'], 'stripslashes');
            $form->applyFilter(['lastname'], 'trim');
            $form->addRule('lastname', get_lang('Required field'), 'required');
        }
        if (isset($list['firstname']) && 1 == $list['firstname']) {
            $form->addElement('text', 'firstname', get_lang('First name'), ['size' => 40]);
            if ('true' !== api_get_setting('profile', 'name')) {
                $form->freeze(['firstname']);
            }
            $form->applyFilter(['firstname'], 'stripslashes');
            $form->applyFilter(['firstname'], 'trim');
            $form->addRule('firstname', get_lang('Required field'), 'required');
        }
    }

    if (isset($list['official_code']) && 1 == $list['official_code']) {
        $form->addElement('text', 'official_code', get_lang('Code'), ['size' => 40]);
        if ('true' !== api_get_setting('profile', 'officialcode')) {
            $form->freeze('official_code');
        }
        $form->applyFilter('official_code', 'stripslashes');
        $form->applyFilter('official_code', 'trim');
        if ('true' === api_get_setting('registration', 'officialcode')
            && 'true' === api_get_setting('profile', 'officialcode')) {
            $form->addRule('official_code', get_lang('Required field'), 'required');
        }
    }

    if (isset($list['email']) && 1 == $list['email']) {
        $form->addElement('text', 'email', get_lang('E-mail'), ['size' => 40]);
        if ('true' !== api_get_setting('profile', 'email')) {
            $form->freeze('email');
        }
        $form->applyFilter('email', 'stripslashes');
        $form->applyFilter('email', 'trim');
        if ('true' === api_get_setting('registration', 'email')) {
            $form->addRule('email', get_lang('Required field'), 'required');
        }
        $form->addEmailRule('email');
    }

    if (isset($list['phone']) && 1 == $list['phone']) {
        $form->addElement('text', 'phone', get_lang('Phone'), ['size' => 20]);
        if ('true' !== api_get_setting('profile', 'phone')) {
            $form->freeze('phone');
        }
        $form->applyFilter('phone', 'stripslashes');
        $form->applyFilter('phone', 'trim');
        if ('true' == api_get_setting('profile', 'phone')) {
            $form->addRule('phone', get_lang('Required field'), 'required');
        }
    }

    if (isset($list['language']) && 1 == $list['language']) {
        $form->addSelectLanguage('language', get_lang('Language'));
        if ('true' !== api_get_setting('profile', 'language')) {
            $form->freeze('language');
        }
        if ('true' === api_get_setting('profile', 'language')) {
            $form->addRule('language', get_lang('Required field'), 'required');
        }
    }

    $extraField = new ExtraField('user');
    $returnParams = $extraField->addElements($form, api_get_user_id());
    $jquery_ready_content = $returnParams['jquery_ready_content'];

    $htmlHeadXtra[] = '<script>$(function(){ '.$jquery_ready_content.' });</script>';
    $form->addButtonNext(get_lang('Next'));
    $form->setDefaults($user_data);
}

// -----------------------------------------------------------------------------
// JS assets for selective display and question widgets
// -----------------------------------------------------------------------------
$htmlHeadXtra[] = ch_selectivedisplay::getJs();
$htmlHeadXtra[] = survey_question::getJs();

// -----------------------------------------------------------------------------
// Header + page container
// -----------------------------------------------------------------------------
Display::display_header(get_lang('Surveys'));

echo '<div class="mx-auto mt-8 bg-white shadow rounded-2xl p-6 border border-gray-50">';
echo '<h2 class="text-2xl font-bold text-gray-800 mb-2">'.Security::remove_XSS(strip_tags($survey->getTitle(), '<span>')).'</h2>';

if (!empty($survey->getSubtitle())) {
    echo '<p class="text-gray-600 mb-4">'.Security::remove_XSS($survey->getSubtitle()).'</p>';
}

// Intro (first load)
if (!isset($_GET['show']) || (isset($_GET['show']) && '' == $_GET['show'])) {
    Session::erase('paged_questions');
    Session::erase('page_questions_sec');
    $paged_questions_sec = [];
    if (!empty($survey->getIntro())) {
        echo '<div class="prose prose-slate max-w-none mb-6">'.Security::remove_XSS($survey->getIntro()).'</div>';
    }
    $limit = 0;
}

// Profile form handling
if ($survey->getFormFields() && 0 == $survey->getAnonymous() && is_array($user_data) && !isset($_GET['show'])) {
    if ($form->validate()) {
        $user_data = $form->exportValues();
        if (is_array($user_data) && count($user_data) > 0) {
            $sql = "UPDATE $table_user SET";
            $update = false;
            $allowedFields = ['firstname', 'lastname', 'official_code', 'email', 'phone', 'language'];

            foreach ($user_data as $key => $value) {
                if (in_array($key, $allowedFields)) {
                    $sql .= " $key = '".Database::escape_string($value)."',";
                    $update = true;
                }
            }
            $sql = rtrim($sql, ',')." WHERE id = $user_id";
            if ($update) {
                Database::query($sql);
            }

            $extraFieldValue = new ExtraFieldValue('user');
            $extraFieldValue->saveFieldValues($user_data);

            echo Display::return_message(get_lang('Information updated').' '.get_lang('Please fill survey'), 'confirm', false);
        }

        $_GET['show'] = 0;
        $show = 0;

        Session::erase('paged_questions');
        Session::erase('page_questions_sec');
        $paged_questions_sec = [];
    } else {
        echo '<div class="mb-4 text-gray-700">'.get_lang('Update information').'</div>';
        Session::erase('paged_questions');
        Session::erase('page_questions_sec');
        $paged_questions_sec = [];
        $form->display();
    }
}

// -----------------------------------------------------------------------------
// Finish screen
// -----------------------------------------------------------------------------
if (isset($_POST['finish_survey'])) {
    echo Display::return_message(get_lang('You have finished this survey.'), 'confirm');
    echo '<div class="prose prose-slate">'.Security::remove_XSS($survey->getSurveythanks()).'</div>';

    SurveyManager::updateSurveyAnswered($survey, $survey_invitation['user_id'], $lpItemId);
    SurveyUtil::flagSurveyAsAnswered($survey->getCode(), $survey_invitation['c_id']);

    if ($courseInfo && !api_is_anonymous() && 'learnpath' !== api_get_origin()) {
        echo '<div class="mt-6">';
        echo Display::toolbarButton(
            get_lang('Return to Course Homepage'),
            api_get_course_url($courseInfo['real_id']),
            'home-outline'
        );
        echo '</div>';
    }

    // Close container + footer and exit early
    echo '</div>';
    Display::display_footer();

    exit;
}

// -----------------------------------------------------------------------------
// Page / question building
// -----------------------------------------------------------------------------
$shuffle = '';
if (1 == $survey->getShuffle()) {
    $shuffle = ' BY RAND() ';
}

$pageBreakText = [];
$paged_questions = []; // keep this defined for later usage
$questions_exists = true;

if ((isset($_GET['show']) && '' != $_GET['show']) || isset($_POST['personality'])) {
    $questions_displayed = [];
    $counter = 0;
    $select = ' survey_question.parent_id, survey_question.parent_option_id, ';

    if (0 === $survey->getSurveyType()) {
        if (empty($paged_questions)) {
            $sql = "SELECT *
                    FROM $table_survey_question
                    WHERE survey_question NOT LIKE '%{{%'
                      AND survey_id = '".$surveyId."'
                    ORDER BY sort ASC";
            $result = Database::query($sql);
            if (0 == Database::num_rows($result)) {
                $questions_exists = false;
            }
            while ($row = Database::fetch_assoc($result)) {
                if (1 == $survey->getOneQuestionPerPage()) {
                    if ('pagebreak' !== $row['type']) {
                        $paged_questions[$counter][] = $row['iid'];
                        $counter++;

                        continue;
                    }
                } else {
                    if ('pagebreak' === $row['type']) {
                        $counter++;
                        $pageBreakText[$counter] = $row['survey_question'];
                    } else {
                        $paged_questions[$counter][] = $row['iid'];
                    }
                }
            }
            Session::write('paged_questions', $paged_questions);
        }

        // Fix contexts (support ticket #5529)
        $courseId = $survey_invitation['c_id'];
        Session::write('_cid', $courseId);
        Session::write('_real_cid', $courseId);

        if (array_key_exists($_GET['show'], $paged_questions)) {
            if (isset($_GET['user_id'])) {
                $my_user_id = 1 == $survey->getAnonymous() ? $surveyUserFromSession : api_get_user_id();

                $sql = "SELECT
                            survey_question.survey_group_sec1,
                            survey_question.survey_group_sec2,
                            survey_question.survey_group_pri,
                            survey_question.iid question_id,
                            survey_question.survey_id,
                            survey_question.survey_question,
                            survey_question.display,
                            survey_question.sort,
                            survey_question.type,
                            survey_question.max_value,
                            survey_question_option.iid question_option_id,
                            survey_question_option.option_text,
                            $select
                            survey_question_option.sort as option_sort
                        FROM $table_survey_question survey_question
                        LEFT JOIN $table_survey_question_option survey_question_option
                          ON survey_question.iid = survey_question_option.question_id
                         AND survey_question_option.c_id = $courseId
                        WHERE survey_question.survey_id = '".$surveyId."'
                          AND survey_question.iid NOT IN (
                                SELECT sa.question_id
                                  FROM ".$table_survey_answer." sa
                                 WHERE sa.user='".$my_user_id."')
                          AND survey_question.c_id =  $courseId
                        ORDER BY survey_question.sort, survey_question_option.sort ASC";
            } else {
                $sql = "SELECT
                            survey_question.survey_group_sec1,
                            survey_question.survey_group_sec2,
                            survey_question.survey_group_pri,
                            survey_question.iid question_id,
                            survey_question.survey_id,
                            survey_question.survey_question,
                            survey_question.display,
                            survey_question.sort,
                            survey_question.type,
                            survey_question.max_value,
                            survey_question_option.iid question_option_id,
                            survey_question_option.option_text,
                            $select
                            survey_question_option.sort as option_sort
                            ".($allowRequiredSurveyQuestions ? ', survey_question.is_required' : '')."
                        FROM $table_survey_question survey_question
                        LEFT JOIN $table_survey_question_option survey_question_option
                          ON survey_question.iid = survey_question_option.question_id
                        WHERE survey_question NOT LIKE '%{{%'
                          AND survey_question.survey_id = '".$surveyId."'
                          AND survey_question.iid IN (".implode(',', $paged_questions[$_GET['show']]).')
                        ORDER BY survey_question.sort, survey_question_option.sort ASC';
            }

            $result = Database::query($sql);
            $question_counter_max = Database::num_rows($result);
            $counter = 0;
            $limit = 0;
            $questions = [];
            while ($row = Database::fetch_assoc($result)) {
                if ('pagebreak' !== $row['type']) {
                    $sort = $row['sort'];
                    $questions[$sort]['question_id'] = $row['question_id'];
                    $questions[$sort]['survey_id'] = $row['survey_id'];
                    $questions[$sort]['survey_question'] = $row['survey_question'];
                    $questions[$sort]['display'] = $row['display'];
                    $questions[$sort]['type'] = $row['type'];
                    $questions[$sort]['options'][$row['question_option_id']] = $row['option_text'];
                    $questions[$sort]['maximum_score'] = $row['max_value'];
                    $questions[$sort]['sort'] = $sort;
                    $questions[$sort]['is_required'] = $allowRequiredSurveyQuestions && ($row['is_required'] ?? 0);
                    $questions[$sort]['parent_id'] = $row['parent_id'] ?? 0;
                    $questions[$sort]['parent_option_id'] = $row['parent_option_id'] ?? 0;
                }
                $counter++;
                if (isset($_GET['show']) && (int) $_GET['show'] >= 0) {
                    $lastQuestion = (int) $_GET['show'] - 1;
                } else {
                    $lastQuestion = (int) $row['question_option_id'];
                }
            }
        }
    } elseif (1 === $survey->getSurveyType()) {
        $my_survey_id = (int) $survey_invitation['survey_id'];
        $current_user = Database::escape_string($survey_invitation['user_id']);

        if (isset($_POST['personality'])) {
            $order = '' == $shuffle ? 'BY sort ASC ' : $shuffle;

            // Current user results
            $results = [];
            $sql = "SELECT survey_group_pri, user, SUM(value) as value
                    FROM $table_survey_answer as survey_answer
                    INNER JOIN $table_survey_question as survey_question
                        ON (survey_question.iid = survey_answer.question_id)
                    WHERE survey_answer.survey_id='".$my_survey_id."'
                      AND survey_answer.user='".$current_user."'
                    GROUP BY survey_group_pri
                    ORDER BY survey_group_pri";
            $result = Database::query($sql);
            while ($row = Database::fetch_array($result)) {
                $results[] = ['value' => $row['value'], 'group' => $row['survey_group_pri']];
            }

            // Totals by group
            $totals = [];
            $sql = "SELECT SUM(temp.value) as value, temp.survey_group_pri FROM
                    (
                        SELECT MAX(value) as value, survey_group_pri, survey_question.iid question_id
                        FROM $table_survey_question as survey_question
                        INNER JOIN $table_survey_question_option as survey_question_option
                            ON (survey_question.iid = survey_question_option.question_id)
                        WHERE survey_question.survey_id='".$my_survey_id."'
                          AND survey_question.c_id = $courseId
                          AND survey_question_option.c_id = $courseId
                          AND survey_group_sec1='0' AND survey_group_sec2='0'
                        GROUP BY survey_group_pri, survey_question.iid
                    ) as temp
                    GROUP BY temp.survey_group_pri
                    ORDER BY temp.survey_group_pri";
            $result = Database::query($sql);
            while ($row = Database::fetch_array($result)) {
                $totals[] = ['value' => $row['value'], 'group' => $row['survey_group_pri']];
            }

            // Percentages
            $final_results = [];
            for ($i = 0; $i < count($totals); $i++) {
                for ($j = 0; $j < count($results); $j++) {
                    if ($totals[$i]['group'] == $results[$j]['group']) {
                        $group = $totals[$i]['group'];
                        $percent = ($results[$j]['value'] / $totals[$i]['value']);
                        $final_results[$group] = $percent;
                    }
                }
            }

            arsort($final_results);
            $groups = array_keys($final_results);
            $result = [];
            $count_result = 0;
            foreach ($final_results as $key => &$sub_result) {
                $result[] = ['group' => $key, 'value' => $sub_result];
                $count_result++;
            }

            $i = 0;
            $group_cant = 0;
            $equal_count = 0;
            if ($count_result > 0) {
                while (1) {
                    if (($result[$i]['value'] ?? null) == ($result[$i + 1]['value'] ?? null)) {
                        $equal_count++;
                    } else {
                        break;
                    }
                    $i++;
                }
            } else {
                $equal_count = 10; // force undefined
            }

            if ($equal_count < 4) {
                if (0 === $equal_count || 1 === $equal_count) {
                    if (($result[0]['value'] ?? 0) == ($result[1]['value'] ?? 0) && ($result[2]['value'] ?? 0) == ($result[3]['value'] ?? 0)) {
                        $group_cant = 1;
                    } elseif (($result[0]['value'] ?? 0) != ($result[1]['value'] ?? 0)
                        && ($result[1]['value'] ?? 0) == ($result[2]['value'] ?? 0)
                        && ($result[2]['value'] ?? 0) == ($result[3]['value'] ?? 0)) {
                        $group_cant = 0;
                    } else {
                        $group_cant = 2;
                    }
                } else {
                    $group_cant = $equal_count;
                }

                if ($group_cant > 0) {
                    $secondary = '';
                    for ($i = 0; $i <= $group_cant; $i++) {
                        $group1 = $groups[$i] ?? null;
                        $group2 = $groups[$i + 1] ?? null;
                        if (null === $group1 || null === $group2) {
                            continue;
                        }
                        if (2 == $group_cant && $i == $group_cant) {
                            $group2 = $groups[0];
                        }
                        $secondary .= (empty($secondary) ? '' : ' OR ')
                            ." ( survey_group_sec1 = '$group1' AND survey_group_sec2 = '$group2') "
                            ." OR ( survey_group_sec1 = '$group2' AND survey_group_sec2 = '$group1' ) ";
                    }

                    if (empty($_SESSION['page_questions_sec'])
                        && !is_array($_SESSION['page_questions_sec'])
                        && count(0 == $_SESSION['page_questions_sec'])) {
                        $sql = "SELECT * FROM $table_survey_question
                                WHERE survey_id = '".$my_survey_id."'
                                  AND ($secondary)
                                ORDER BY sort ASC";
                        $result = Database::query($sql);
                        $counter = 0;
                        while ($row = Database::fetch_assoc($result)) {
                            if (1 == $survey->getOneQuestionPerPage()) {
                                $paged_questions_sec[$counter][] = $row['question_id'];
                                $counter++;
                            } elseif ('pagebreak' === $row['type']) {
                                $counter++;
                                $pageBreakText[$counter] = $row['survey_question'];
                            } else {
                                $paged_questions_sec[$counter][] = $row['question_id'];
                            }
                        }
                        Session::write('page_questions_sec', $paged_questions_sec);
                    } else {
                        $paged_questions_sec = Session::read('page_questions_sec');
                    }

                    $paged_questions = Session::read('paged_questions');
                    if ('' == $shuffle) {
                        $shuffle = ' BY survey_question.sort, survey_question_option.sort ASC ';
                    }
                    $val = (int) $_POST['personality'];
                    if (is_array($paged_questions_sec)) {
                        $sql = "SELECT
                                    survey_question.survey_group_sec1,
                                    survey_question.survey_group_sec2,
                                    survey_question.survey_group_pri,
                                    survey_question.iid question_id,
                                    survey_question.survey_id,
                                    survey_question.survey_question,
                                    survey_question.display,
                                    survey_question.sort,
                                    survey_question.type,
                                    survey_question.max_value,
                                    survey_question_option.question_option_id,
                                    survey_question_option.option_text,
                                    survey_question_option.sort as option_sort
                                FROM $table_survey_question survey_question
                                LEFT JOIN $table_survey_question_option survey_question_option
                                   ON survey_question.iid = survey_question_option.question_id
                                WHERE survey_question NOT LIKE '%{{%'
                                  AND survey_question.survey_id = '".$my_survey_id."'
                                  AND survey_question.iid IN (".implode(',', $paged_questions_sec[$val]).")
                                ORDER $shuffle";
                        $result = Database::query($sql);
                        $question_counter_max = Database::num_rows($result);
                        $counter = 0;
                        $limit = 0;
                        $questions = [];
                        while ($row = Database::fetch_assoc($result)) {
                            if ('pagebreak' !== $row['type']) {
                                $questions[$row['sort']]['question_id'] = $row['question_id'];
                                $questions[$row['sort']]['survey_id'] = $row['survey_id'];
                                $questions[$row['sort']]['survey_question'] = $row['survey_question'];
                                $questions[$row['sort']]['display'] = $row['display'];
                                $questions[$row['sort']]['type'] = $row['type'];
                                $questions[$row['sort']]['options'][$row['question_option_id']] = $row['option_text'];
                                $questions[$row['sort']]['maximum_score'] = $row['max_value'];
                                $questions[$row['sort']]['survey_group_sec1'] = $row['survey_group_sec1'];
                                $questions[$row['sort']]['survey_group_sec2'] = $row['survey_group_sec2'];
                                $questions[$row['sort']]['survey_group_pri'] = $row['survey_group_pri'];
                                $questions[$row['sort']]['sort'] = $row['sort'];
                            } else {
                                break;
                            }
                            $counter++;
                        }
                    } else {
                        echo get_lang('Survey undefined');
                    }
                } else {
                    echo get_lang('Survey undefined');
                }
            } else {
                echo get_lang('Survey undefined');
            }
        } else {
            // First personality phase
            Session::erase('page_questions_sec');
            $paged_questions_sec = [];

            $order_sql = '' == $shuffle ? ' BY question_id ' : $shuffle;

            if (empty($_SESSION['paged_questions'])) {
                $sql = "SELECT * FROM $table_survey_question
                        WHERE survey_id = '".$surveyId."'
                          AND survey_group_sec1='0'
                          AND survey_group_sec2='0'
                        ORDER ".$order_sql.' ';
                $result = Database::query($sql);
                $counter = 0;
                while ($row = Database::fetch_assoc($result)) {
                    if (1 == $survey->getOneQuestionPerPage()) {
                        $paged_questions[$counter][] = $row['question_id'];
                        $counter++;
                    } else {
                        if ('pagebreak' === $row['type']) {
                            $counter++;
                            $pageBreakText[$counter] = $row['survey_question'];
                        } else {
                            $paged_questions[$counter][] = $row['question_id'];
                        }
                    }
                }
                Session::write('paged_questions', $paged_questions);
            } else {
                $paged_questions = Session::read('paged_questions');
            }

            $order_sql = '' == $shuffle ? ' BY survey_question.sort, survey_question_option.sort ASC ' : $shuffle;
            $val = $_GET['show'] ?? '';
            $result = null;
            if ('' != $val) {
                $imploded = Database::escape_string(implode(',', $paged_questions[$val]));
                if ('' != $imploded) {
                    $order_sql = ' BY survey_question.sort, survey_question_option.sort ASC ';
                    $sql = 'SELECT
                                survey_question.survey_group_sec1,
                                survey_question.survey_group_sec2,
                                survey_question.survey_group_pri,
                                survey_question.iid question_id,
                                survey_question.survey_id,
                                survey_question.survey_question,
                                survey_question.display,
                                survey_question.sort,
                                survey_question.type,
                                survey_question.max_value,
                                survey_question_option.question_option_id,
                                survey_question_option.option_text,
                                survey_question_option.sort as option_sort
                                '.($allowRequiredSurveyQuestions ? ', survey_question.is_required' : '')."
                            FROM $table_survey_question survey_question
                            LEFT JOIN $table_survey_question_option survey_question_option
                               ON survey_question.iid = survey_question_option.question_id
                              AND survey_question_option.c_id = $courseId
                            WHERE survey_question NOT LIKE '%{{%'
                              AND survey_question.survey_id = '".(int) $survey_invitation['survey_id']."'
                              AND survey_question.c_id = $courseId
                              AND survey_question.iid IN (".$imploded.")
                            ORDER $order_sql";
                    $result = Database::query($sql);
                    $question_counter_max = Database::num_rows($result);
                }
            }

            if (null !== $result) {
                $counter = 0;
                $limit = 0;
                $questions = [];
                while ($row = Database::fetch_assoc($result)) {
                    if ('pagebreak' !== $row['type']) {
                        $questions[$row['sort']]['question_id'] = $row['question_id'];
                        $questions[$row['sort']]['survey_id'] = $row['survey_id'];
                        $questions[$row['sort']]['survey_question'] = $row['survey_question'];
                        $questions[$row['sort']]['display'] = $row['display'];
                        $questions[$row['sort']]['type'] = $row['type'];
                        $questions[$row['sort']]['options'][$row['question_option_id']] = $row['option_text'];
                        $questions[$row['sort']]['maximum_score'] = $row['max_value'];
                        $questions[$row['sort']]['is_required'] = $allowRequiredSurveyQuestions && ($row['is_required'] ?? 0);
                        $questions[$row['sort']]['survey_group_sec1'] = $row['survey_group_sec1'];
                        $questions[$row['sort']]['survey_group_sec2'] = $row['survey_group_sec2'];
                        $questions[$row['sort']]['survey_group_pri'] = $row['survey_group_pri'];
                        $questions[$row['sort']]['sort'] = $row['sort'];
                    }
                    $counter++;
                }
            }
        }
    } else {
        echo get_lang('Survey type unknown');
    }
}

// -----------------------------------------------------------------------------
// Page counters / params
// -----------------------------------------------------------------------------
$numberOfPages = SurveyManager::getCountPages($survey);

$show = 0;
if (isset($_GET['show']) && '' != $_GET['show']) {
    $show = (int) $_GET['show'] + 1;
}

$displayFinishButton = true;
if (isset($_GET['show']) && '' != $_GET['show']) {
    $pagesIndexes = array_keys($paged_questions);
    $pagesIndexes[] = count($pagesIndexes);
    if (end($pagesIndexes) <= $show - 1 && empty($_POST)) {
        $displayFinishButton = false;
    }
}

$personality = isset($_POST['personality']) ? (int) $_POST['personality'] + 1 : 0;

$g_c = isset($_GET['course']) ? Security::remove_XSS($_GET['course']) : '';
$g_ic = isset($_GET['invitationcode']) ? Security::remove_XSS($_GET['invitationcode']) : '';
$g_cr = isset($_GET['cidReq']) ? Security::remove_XSS($_GET['cidReq']) : '';
$p_l = isset($_POST['language']) ? Security::remove_XSS($_POST['language']) : '';

$add_parameters = isset($_GET['user_id']) ? '&user_id='.(int) $_GET['user_id'] : '';

$url = api_get_self().'?'.api_get_cidreq().$add_parameters.
    '&course='.$g_c.
    '&invitationcode='.$g_ic.
    '&show='.$show.
    '&iid='.$surveyId;

if (!empty($_GET['language'])) {
    $lang = Security::remove_XSS($_GET['language']);
    $url .= '&language='.$lang;
}
if (!empty($lpItemId)) {
    $url .= '&lp_item_id='.$lpItemId;
}

// -----------------------------------------------------------------------------
// Form
// -----------------------------------------------------------------------------
$form = new FormValidator('question', 'post', $url, null, null, FormValidator::LAYOUT_HORIZONTAL);
$form->addHidden('language', $p_l);

// Numbering control
$showNumber = !SurveyManager::hasDependency($survey);

// -----------------------------------------------------------------------------
// Render questions (cards)
// -----------------------------------------------------------------------------
if (isset($questions) && is_array($questions)) {
    $originalShow = isset($_GET['show']) ? (int) $_GET['show'] : 0;
    $questionCounter = 1;
    if (!empty($originalShow)) {
        $before = 0;
        foreach ($paged_questions as $keyQuestion => $list) {
            if ($originalShow > $keyQuestion) {
                $before += count($list);
            }
        }
        $questionCounter = $before + 1;
    }

    // Page-break caption
    $js = '';
    if (isset($pageBreakText[$originalShow]) && !empty(strip_tags($pageBreakText[$originalShow]))) {
        $form->addHtml('<div class="mb-4 p-3 bg-gray-30 rounded">'.Security::remove_XSS($pageBreakText[$originalShow]).'</div>');
    }

    foreach ($questions as $key => &$question) {
        $ch_type = 'ch_'.$question['type'];
        $questionNumber = $questionCounter;

        // Use concrete question renderer; keep finalAnswer for prefill
        $display = new $ch_type();
        $parent = $question['parent_id'];
        $parentClass = '';

        if (!empty($parent)) {
            $parentClass = ' with_parent with_parent_'.$question['question_id'];
            $parents = survey_question::getParents($question['question_id']);
            if (!empty($parents)) {
                foreach ($parents as $parentId) {
                    $parentClass .= ' with_parent_only_hide_'.$parentId;
                }
            }
        }

        $js .= survey_question::getQuestionJs($question);

        $form->addHtml('<div class="survey_question '.$ch_type.' '.$parentClass.' mb-6 p-4 bg-gray-10 rounded-lg border border-gray-50">');
        if ($showNumber && $survey->isDisplayQuestionNumber()) {
            $form->addHtml('<div class="font-semibold text-blue-700 mb-1"> '.$questionNumber.'. </div>');
        }
        $form->addHtml('<div class="text-gray-800 mb-2">'.Security::remove_XSS($question['survey_question']).'</div>');

        // Prefill user answer if exists
        $userAnswerData = SurveyUtil::get_answers_of_question_by_user($question['survey_id'], $question['question_id'], $lpItemId);
        $finalAnswer = null;
        if (!empty($userAnswerData[$user_id])) {
            $userAnswer = $userAnswerData[$user_id];

            switch ($question['type']) {
                case 'score':
                    $finalAnswer = [];
                    foreach ($userAnswer as $userChoice) {
                        [$choiceId, $choiceValue] = explode('*', $userChoice);
                        $finalAnswer[$choiceId] = $choiceValue;
                    }

                    break;

                case 'percentage':
                    [$choiceId, $choiceValue] = explode('*', current($userAnswer));
                    $finalAnswer = $choiceId;

                    break;

                default:
                    $finalAnswer = $userAnswer;

                    break;
            }
        }

        $display->render($form, $question, $finalAnswer);
        $form->addHtml('</div>');
        $questionCounter++;
    }

    $form->addHtml($js);
}

// -----------------------------------------------------------------------------
// Navigation buttons (Previous / Next / Finish)
// -----------------------------------------------------------------------------
$form->addHtml('<div class="flex justify-between mt-6 gap-3">');
if (
    isset($_GET['show'])
    && $_GET['show'] > 0
    && 'true' === api_get_setting('survey.survey_backwards_enable')
    && 1 === (int) $survey->getOneQuestionPerPage()
) {
    $currentShow = (int) $_GET['show'];
    $prevShow = max(0, $currentShow - 1);

    $prevUrl = api_get_self().'?'.api_get_cidreq().
        '&course='.urlencode($g_c).
        '&invitationcode='.urlencode($g_ic).
        '&iid='.$surveyId.
        '&show='.$prevShow;

    if (!empty($_GET['language'])) {
        $prevUrl .= '&language='.urlencode($_GET['language']);
    }
    if (!empty($lpItemId)) {
        $prevUrl .= '&lp_item_id='.$lpItemId;
    }
    if (isset($_GET['user_id'])) {
        $prevUrl .= '&user_id='.(int) $_GET['user_id'];
    }

    $form->addHtml(
        '<a href="'.$prevUrl.'" class="btn btn--plain-outline">
            <i class="mdi mdi-arrow-left mr-2"></i>'.get_lang('Previous question').'
        </a>'
    );
}

// Next / Start
if ('0' == (string) $survey->getSurveyType()) {
    if (0 == $survey->getShowFormProfile()) {
        if ($show < $numberOfPages) {
            $label = 0 == $show ? get_lang('Start the Survey') : get_lang('Next question');
            $form->addButton('next_survey_page', $label, 'arrow-right', 'success');
        }
        if ($show >= $numberOfPages && $displayFinishButton) {
            $form->addButton('finish_survey', get_lang('Finish survey'), 'check', 'success');
        }
    } else {
        if (isset($_GET['show'])) {
            $pageCount = count($paged_questions);
            if ($show < $pageCount) {
                $label = 0 == $show ? get_lang('Start the Survey') : get_lang('Next question');
                $form->addButton('next_survey_page', $label, 'arrow-right', 'success');
            }
            if ($show >= $pageCount && $displayFinishButton) {
                $form->addButton('finish_survey', get_lang('Finish survey'), 'check', 'success');
            }
        }
    }
} elseif (1 === $survey->getSurveyType()) {
    if (isset($_GET['show']) || isset($_POST['personality'])) {
        $pageCount = count($paged_questions);
        if (!empty($paged_questions_sec) && count($paged_questions_sec) > 0) {
            $pageCount += count($paged_questions_sec);
        } else {
            Session::erase('page_questions_sec');
            $paged_questions_sec = [];
        }

        if (0 === $personality) {
            if (($show <= $pageCount) || !$_GET['show']) {
                $form->addButton('next_survey_page', get_lang('Next'), 'arrow-right', 'success');
                if (0 == $survey->getOneQuestionPerPage()) {
                    if ($personality >= 0) {
                        $form->addHidden('personality', $personality);
                    }
                } else {
                    if ($personality > 0) {
                        $form->addHidden('personality', $personality);
                    }
                }
                if ($pageCount == $show) {
                    $form->addHidden('personality', $personality);
                }
            }
        }

        if ($show > $pageCount && $_GET['show'] && 0 === $personality) {
            $form->addHidden('personality', $personality);
        } elseif ($personality > 0) {
            if (1 == $survey->getOneQuestionPerPage()) {
                if ($show >= $pageCount) {
                    $form->addButton('finish_survey', get_lang('Finish survey'), 'check', 'success');
                } else {
                    $form->addHidden('personality', $personality);
                    $form->addButton('next_survey_page', get_lang('Next'), 'arrow-right', 'success');
                }
            } else {
                $form->addButton('finish_survey', get_lang('Finish survey'), 'check');
            }
        }
    } elseif ('' == $survey->getFormFields() || !is_array($user_data)) {
        $form->addButton('next_survey_page', get_lang('Next'), 'arrow-right', 'success');
    }
}

$form->addHtml('</div>');

// No-questions notice at end
if (isset($_GET['show']) && ($show >= $numberOfPages || empty($questions))) {
    if (false == $questions_exists) {
        echo '<p class="text-gray-600">'.get_lang('There are no questions for this survey').'</p>';
    }
}

// -----------------------------------------------------------------------------
// Render form + close container + footer
// -----------------------------------------------------------------------------
$form->display();
echo '</div>';
Display::display_footer();

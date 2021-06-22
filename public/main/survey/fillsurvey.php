<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CSurvey;
use ChamiloSession as Session;

$lastQuestion = 0;

/**
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

// Database table definitions
$table_survey = Database::get_course_table(TABLE_SURVEY);
$table_survey_answer = Database::get_course_table(TABLE_SURVEY_ANSWER);
$table_survey_question = Database::get_course_table(TABLE_SURVEY_QUESTION);
$table_survey_question_option = Database::get_course_table(TABLE_SURVEY_QUESTION_OPTION);
$table_survey_invitation = Database::get_course_table(TABLE_SURVEY_INVITATION);
$table_user = Database::get_main_table(TABLE_MAIN_USER);

$allowRequiredSurveyQuestions = api_get_configuration_value('allow_required_survey_questions');

// Check if user is anonymous or not
$isAnonymous = false;
if (api_is_anonymous(api_get_user_id(), true)) {
    $isAnonymous = true;
}

// getting all the course information
if (isset($_GET['course'])) {
    $courseInfo = api_get_course_info($_GET['course']);
} else {
    $courseInfo = api_get_course_info();
}

if (empty($courseInfo)) {
    api_not_allowed(true);
}

$courseId = $courseInfo['real_id'];
$userInfo = api_get_user_info();
$sessionId = isset($_GET['sid']) ? (int) $_GET['sid'] : api_get_session_id();

if (!empty($userInfo)) {
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'survey/survey_list.php?cid='.$courseId.'&sid='.$sessionId,
        'name' => get_lang('Survey list'),
    ];
}

// First we check if the needed parameters are present
if ((!isset($_GET['course']) || !isset($_GET['invitationcode'])) && !isset($_GET['user_id'])) {
    api_not_allowed(true, get_lang('There is a parameter missing in the link. Please use copy and past'));
}

$repo = Container::getSurveyRepository();
$surveyId = isset($_GET['iid']) ? (int) $_GET['iid'] : 0;

/** @var CSurvey $survey */
$survey = $repo->find($surveyId);
if (null === $survey) {
    api_not_allowed(true);
}

$surveyId = $survey->getIid();
$invitationCode = $_GET['invitationcode'] ?? null;

/*$surveyCode = isset($_GET['scode']) ? Database::escape_string($_GET['scode']) : '';
if ('' != $surveyCode) {
    // Firstly we check if this survey is ready for anonymous use:
    $sql = "SELECT anonymous FROM $table_survey
            WHERE c_id = $courseId AND code ='".$surveyCode."'";
    $resultAnonymous = Database::query($sql);
    $rowAnonymous = Database::fetch_array($resultAnonymous, 'ASSOC');
    // If is anonymous and is not allowed to take the survey to anonymous users, forbid access:
    if (!isset($rowAnonymous['anonymous']) ||
        (0 == $rowAnonymous['anonymous'] && api_is_anonymous()) ||
        0 == count($rowAnonymous)
    ) {
        api_not_allowed(true);
    }
    // If is anonymous and it is allowed to take the survey as anonymous, mark survey as anonymous.
}*/

if ((0 == $survey->getAnonymous() && api_is_anonymous())) {
    api_not_allowed(true);
}

// Start auto-invitation feature FS#3403 (all-users-can-do-the-survey-URL handling)
if ('auto' === $invitationCode) {
    $userid = api_get_user_id();
    // Survey_code of the survey
    $surveyCode = $survey->getCode();
    if ($isAnonymous) {
        $autoInvitationCode = 'auto-ANONY_'.md5(time())."-$surveyCode";
    } else {
        $invitations = SurveyManager::getUserInvitationsForSurveyInCourse(
            $userid,
            $surveyCode,
            $courseId,
            $sessionId
        );
        $lastInvitation = current($invitations);

        if (!$lastInvitation) {
            // New invitation code from userid
            $autoInvitationCode = "auto-$userid-$surveyCode";
        } else {
            $autoInvitationCode = $lastInvitation->getInvitationCode();
        }
    }

    // Check availability.
    SurveyManager::checkTimeAvailability($survey);

    // Check for double invitation records (insert should be done once)
    $sql = "SELECT user_id
            FROM $table_survey_invitation
            WHERE
                c_id = $courseId AND
                invitation_code = '".Database::escape_string($autoInvitationCode)."'";
    $result = Database::query($sql);
    $now = api_get_utc_datetime();
    if (0 == Database::num_rows($result)) {
        $params = [
            'c_id' => $courseId,
            'survey_id' => $surveyId,
            'user_id' => $userid,
            'invitation_code' => $autoInvitationCode,
            'invitation_date' => $now,
        ];
        Database::insert($table_survey_invitation, $params);
    }
    // From here we use the new invitationcode auto-userid-surveycode string
    $_GET['invitationcode'] = $autoInvitationCode;
    Session::write('auto_invitation_code_'.$surveyCode, $autoInvitationCode);
    $invitationCode = $autoInvitationCode;
}

// Now we check if the invitation code is valid
$sql = "SELECT * FROM $table_survey_invitation
        WHERE
            c_id = $courseId AND
            invitation_code = '".Database::escape_string($invitationCode)."'";
$result = Database::query($sql);
if (Database::num_rows($result) < 1) {
    api_not_allowed(true, get_lang('Wrong invitation code'));
}

$survey_invitation = Database::fetch_array($result, 'ASSOC');
$surveyUserFromSession = Session::read('surveyuser');
// Now we check if the user already filled the survey
if (!isset($_POST['finish_survey']) &&
    (
        $isAnonymous &&
        !empty($surveyUserFromSession) &&
        SurveyUtil::isSurveyAnsweredFlagged($survey->getCode(), $survey_invitation['c_id'])
    ) ||
    (1 == $survey_invitation['answered'] && !isset($_GET['user_id']))
) {
    api_not_allowed(true, Display::return_message(get_lang('You already filled this survey')));
}

$logInfo = [
    'tool' => TOOL_SURVEY,
    'tool_id' => $survey_invitation['iid'],
    'action' => 'invitationcode',
    'action_details' => $invitationCode,
];
Event::registerLog($logInfo);

// Checking if there is another survey with this code.
// If this is the case there will be a language choice
$sql = "SELECT * FROM $table_survey
        WHERE
            code = '".Database::escape_string($survey->getCode())."'";
$result = Database::query($sql);

if (Database::num_rows($result) > 1) {
    if ($_POST['language']) {
        $survey_invitation['survey_id'] = $_POST['language'];
    } else {
        Display::display_header(get_lang('Surveys'));
        $frmLangUrl = api_get_self().'?'.api_get_cidreq().'&'
            .http_build_query([
                'course' => Security::remove_XSS($_GET['course']),
                'invitationcode' => Security::remove_XSS($_GET['invitationcode']),
            ]);

        echo '<form id="language" name="language" method="POST" action="'.$frmLangUrl.'">';
        echo '<select name="language">';
        while ($row = Database::fetch_array($result, 'ASSOC')) {
            echo '<option value="'.$row['survey_id'].'">'.$row['lang'].'</option>';
        }
        echo '</select>';
        echo '<button type="submit" name="Submit" class="next">'.get_lang('Validate').'</button>';
        echo '</form>';
        Display::display_footer();
        exit();
    }
}

// Checking time availability
SurveyManager::checkTimeAvailability($survey);
$surveyType = $survey->getSurveyType();
if (3 === $surveyType) {
    header('Location: '.
        api_get_path(WEB_CODE_PATH).
        'survey/meeting.php?cid='.$courseId.'&sid='.$sessionId.'&invitationcode='.Security::remove_XSS($invitationCode)
    );
    exit;
}

if (!empty($survey->getAnonymous())) {
    define('USER_IN_ANON_SURVEY', true);
}

// Storing the answers
if (count($_POST) > 0) {
    if (0 === $surveyType) {
        $types = [];
        $required = [];
        $questions = $survey->getQuestions();
        $questionList = [];
        foreach ($questions as $question) {
            $id = $question->getIid();
            $questionList[$id] = $question;
            $types[$id] = $question->getType();
            $required[$id] = $allowRequiredSurveyQuestions && $question->isMandatory();
        }

        // Looping through all the post values
        foreach ($_POST as $key => &$value) {
            // If the post value key contains the string 'question' then it is an answer on a question
            if (false === strpos($key, 'other_question') &&
                false !== strpos($key, 'question') && '_qf__question' !== $key
            ) {
                // Finding the question id by removing 'question'
                $survey_question_id = str_replace('question', '', $key);
                // If not question ID was defined, we're on the start
                // screen or something else that doesn't require
                // saving an answer
                if (empty($survey_question_id)) {
                    continue;
                }

                $other = isset($_POST['other_question'.$survey_question_id]) ? $_POST['other_question'.$survey_question_id] : '';
                $question = $questionList[$survey_question_id] ?? null;

                if (null === $question) {
                    continue;
                }

                /* If the post value is an array then we have a multiple response question or a scoring question type
                remark: when it is a multiple response then the value of the array is the option_id
                when it is a scoring question then the key of the array is the option_id and the value is the value
                */
                if (is_array($value)) {
                    SurveyUtil::remove_answer(
                        $survey_invitation['user_id'],
                        $surveyId,
                        $survey_question_id
                    );

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
                            $option_value
                        );
                    }
                } else {
                    // All the other question types (open question, multiple choice, percentage, ...)
                    $option_value = 0;
                    if (isset($types[$survey_question_id]) && 'percentage' === $types[$survey_question_id]) {
                        $sql = "SELECT * FROM $table_survey_question_option
                                WHERE
                                    iid='".intval($value)."'";
                        $result = Database::query($sql);
                        $row = Database::fetch_array($result, 'ASSOC');
                        if ($row) {
                            $option_value = $row['option_text'];
                        }
                    } else {
                        if (isset($types[$survey_question_id]) && 'open' === $types[$survey_question_id]) {
                            $option_value = $value;
                        }
                    }

                    $survey_question_answer = $value;
                    SurveyUtil::remove_answer(
                        $survey_invitation['user_id'],
                        $surveyId,
                        $survey_question_id
                    );

                    SurveyUtil::saveAnswer(
                        $survey_invitation['user_id'],
                        $survey,
                        $question,
                        $value,
                        $option_value,
                        $other
                    );
                }
            }
        }
    } elseif (1 === $survey->getSurveyType()) {
        //conditional/personality-test type surveys
        // Getting all the types of the question (because of the special treatment of the score question type
        $shuffle = '';
        if (1 == $survey->getShuffle()) {
            $shuffle = ' ORDER BY RAND() ';
        }
        /*$sql = "SELECT * FROM $table_survey_question
                WHERE
                    survey_id = $surveyId AND
                    survey_group_pri = '0'
                    $shuffle";
        $result = Database::query($sql);*/
        // There is only one question type for conditional surveys
        $types = [];
        //while ($row = Database::fetch_array($result, 'ASSOC')) {
        $questions = $survey->getQuestions();
        $questionList = [];
        foreach ($questions as $question) {
            $questionList[$question->getIid()] = $question;
        }

        // Looping through all the post values
        foreach ($_POST as $key => &$value) {
            // If the post value key contains the string 'question' then it is an answer to a question
            if (false !== strpos($key, 'question')) {
                // Finding the question id by removing 'question'
                $survey_question_id = str_replace('question', '', $key);
                // If not question ID was defined, we're on the start
                // screen or something else that doesn't require
                // saving an answer
                if (empty($survey_question_id)) {
                    continue;
                }
                // We select the correct answer and the puntuacion
                $sql = "SELECT value FROM $table_survey_question_option
                        WHERE iid='".intval($value)."'";
                $result = Database::query($sql);
                $row = Database::fetch_array($result, 'ASSOC');
                $option_value = $row['value'];
                $survey_question_answer = $value;

                // We save the answer after making sure that a possible previous attempt is deleted
                SurveyUtil::remove_answer(
                    $survey_invitation['user_id'],
                    $survey_invitation['survey_id'],
                    $survey_question_id
                );

                SurveyUtil::saveAnswer(
                    $survey_invitation['user_id'],
                    $survey_invitation['survey_id'],
                    $questionList[$survey_question_id],
                    $value,
                    $option_value
                );
            }
        }
    } else {
        // In case it's another type than 0 or 1
        api_not_allowed(true, get_lang('Survey type unknown'));
    }
}

$user_id = api_get_user_id();
if (0 == $user_id) {
    $user_id = $survey_invitation['user_id'];
}
$user_data = api_get_user_info($user_id);

if ('' != $survey->getFormFields() &&
    0 == $survey->getAnonymous() &&
    is_array($user_data)
) {
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

    $url = api_get_self().'?cid='.$courseId.'&sid='.$sessionId;
    $listQueryParams = explode('&', $_SERVER['QUERY_STRING']);
    foreach ($listQueryParams as $param) {
        $url .= '&'.Security::remove_XSS($param);
    }

    // We use the same form as in auth/profile.php
    $form = new FormValidator('profile', 'post', $url);
    if (api_is_western_name_order()) {
        if (isset($list['firstname']) && 1 == $list['firstname']) {
            //FIRST NAME
            $form->addElement('text', 'firstname', get_lang('First name'), ['size' => 40]);
            if ('true' !== api_get_setting('profile', 'name')) {
                $form->freeze(['firstname']);
            }
            $form->applyFilter(['firstname'], 'stripslashes');
            $form->applyFilter(['firstname'], 'trim');
            $form->addRule('firstname', get_lang('Required field'), 'required');
        }
        if (isset($list['lastname']) && 1 == $list['lastname']) {
            //    LAST NAME
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
            //    LAST NAME
            $form->addElement('text', 'lastname', get_lang('Last name'), ['size' => 40]);
            if ('true' !== api_get_setting('profile', 'name')) {
                $form->freeze(['lastname']);
            }
            $form->applyFilter(['lastname'], 'stripslashes');
            $form->applyFilter(['lastname'], 'trim');
            $form->addRule('lastname', get_lang('Required field'), 'required');
        }
        if (isset($list['firstname']) && 1 == $list['firstname']) {
            //FIRST NAME
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
        //    OFFICIAL CODE
        $form->addElement('text', 'official_code', get_lang('Code'), ['size' => 40]);
        if ('true' !== api_get_setting('profile', 'officialcode')) {
            $form->freeze('official_code');
        }
        $form->applyFilter('official_code', 'stripslashes');
        $form->applyFilter('official_code', 'trim');
        if ('true' === api_get_setting('registration', 'officialcode') &&
            'true' === api_get_setting('profile', 'officialcode')
        ) {
            $form->addRule('official_code', get_lang('Required field'), 'required');
        }
    }

    if (isset($list['email']) && 1 == $list['email']) {
        //    EMAIL
        $form->addElement('text', 'email', get_lang('e-mail'), ['size' => 40]);
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
        // PHONE
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
        // LANGUAGE
        $form->addSelectLanguage('language', get_lang('Language'));
        if ('true' !== api_get_setting('profile', 'language')) {
            $form->freeze('language');
        }
        if ('true' === api_get_setting('profile', 'language')) {
            $form->addRule('language', get_lang('Required field'), 'required');
        }
    }

    // EXTRA FIELDS
    $extraField = new ExtraField('user');
    $returnParams = $extraField->addElements($form, api_get_user_id());
    $jquery_ready_content = $returnParams['jquery_ready_content'];

    // the $jquery_ready_content variable collects all functions
    // that will be load in the $(document).ready javascript function
    $htmlHeadXtra[] = '<script>
    $(function() {
        '.$jquery_ready_content.'
    });
    </script>';

    $form->addButtonNext(get_lang('Next'));
    $form->setDefaults($user_data);
}

$htmlHeadXtra[] = '<script>'.api_get_language_translate_html().'</script>';
$htmlHeadXtra[] = ch_selectivedisplay::getJs();
$htmlHeadXtra[] = survey_question::getJs();

Display::display_header(get_lang('Surveys'));

// Displaying the survey title and subtitle (appears on every page)
echo '<div class="survey-block">';
echo '<div class="page-header">';
echo '<h2>';
echo strip_tags($survey->getTitle(), '<span>').'</h2></div>';
if (!empty($survey->getSubtitle())) {
    echo '<div class="survey_subtitle"><p>'.strip_tags($survey->getSubtitle()).'</p></div>';
}

// Displaying the survey introduction
if (
    !isset($_GET['show']) ||
    (isset($_GET['show'])) && '' == $_GET['show']) {
    // The first thing we do is delete the session
    Session::erase('paged_questions');
    Session::erase('page_questions_sec');
    $paged_questions_sec = [];
    if (!empty($survey->getIntro())) {
        echo '<div class="survey_content">'.Security::remove_XSS($survey->getIntro()).'</div>';
    }
    $limit = 0;
}

if ($survey->getFormFields() &&
    0 == $survey->getAnonymous() &&
    is_array($user_data) &&
    !isset($_GET['show'])
) {
    if ($form->validate()) {
        $user_data = $form->exportValues();
        if (is_array($user_data)) {
            if (count($user_data) > 0) {
                $extras = [];
                // Build SQL query
                $sql = "UPDATE $table_user SET";
                $update = false;
                $allowedFields = [
                    'firstname',
                    'lastname',
                    'official_code',
                    'email',
                    'phone',
                    'language',
                ];

                foreach ($user_data as $key => $value) {
                    if (in_array($key, $allowedFields)) {
                        $sql .= " $key = '".Database :: escape_string($value)."',";
                        $update = true;
                    }
                }
                // Remove trailing , from the query we have so far
                $sql = rtrim($sql, ',');
                $sql .= " WHERE id  = $user_id";

                if ($update) {
                    Database::query($sql);
                }

                $extraFieldValue = new ExtraFieldValue('user');
                $extraFieldValue->saveFieldValues($user_data);
                echo '<div id="survey_content" class="survey_content">'.
                    get_lang('Information updated').' '.get_lang('Please fill survey').'</div>';
            }
        }
        $_GET['show'] = 0;
        $show = 0;
        // We unset the sessions
        Session::erase('paged_questions');
        Session::erase('page_questions_sec');
        $paged_questions_sec = [];
    } else {
        echo '<div id="survey_content" class="survey_content">'.get_lang('Update information').'</div>';
        // We unset the sessions
        Session::erase('paged_questions');
        Session::erase('page_questions_sec');
        $paged_questions_sec = [];
        $form->display();
    }
}

// Displaying the survey thanks message
if (isset($_POST['finish_survey'])) {
    echo Display::return_message(get_lang('You have finished this survey.'), 'confirm');
    echo Security::remove_XSS($survey->getSurveythanks());
    SurveyManager::updateSurveyAnswered($survey, $survey_invitation['user_id']);
    SurveyUtil::flagSurveyAsAnswered($survey->getCode(), $survey_invitation['c_id']);

    if ($courseInfo && !api_is_anonymous()) {
        echo '<br /><br />';
        echo Display::toolbarButton(
            get_lang('Return to Course Homepage'),
            api_get_course_url($courseInfo['real_id']),
            'home'
        );
    }

    Session::erase('paged_questions');
    Session::erase('page_questions_sec');
    Display::display_footer();
    exit();
}

// Sets the random questions
$shuffle = '';
if (1 == $survey->getShuffle()) {
    $shuffle = ' BY RAND() ';
}

$pageBreakText = [];
if ((isset($_GET['show']) && '' != $_GET['show']) ||
    isset($_POST['personality'])
) {
    // Getting all the questions for this page and add them to a
    // multidimensional array where the first index is the page.
    // As long as there is no pagebreak fount we keep adding questions to the page
    $questions_displayed = [];
    $counter = 0;
    $paged_questions = [];
    // If non-conditional survey
    $select = '';
    if (true === api_get_configuration_value('survey_question_dependency')) {
        $select = ' survey_question.parent_id, survey_question.parent_option_id, ';
    }

    // If non-conditional survey
    if (0 === $survey->getSurveyType()) {
        if (empty($paged_questions)) {
            $sql = "SELECT * FROM $table_survey_question
                    WHERE
                        survey_question NOT LIKE '%{{%' AND
                        survey_id = '".$surveyId."'
                    ORDER BY sort ASC";
            $result = Database::query($sql);
            while ($row = Database::fetch_array($result, 'ASSOC')) {
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

        // Redefinition of variables and session ids to fix issue of survey not
        //  showing questions - see support.chamilo.org #5529
        $courseId = $survey_invitation['c_id'];
        Session::write('_cid', $courseId);
        Session::write('_real_cid', $courseId);

        if (array_key_exists($_GET['show'], $paged_questions)) {
            if (isset($_GET['user_id'])) {
                // Get the user into survey answer table (user or anonymus)
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
                        ON survey_question.iid = survey_question_option.question_id AND
                        survey_question_option.c_id = $courseId
                        WHERE
                            survey_question.survey_id = '".$surveyId."' AND
                            survey_question.iid NOT IN (
                                SELECT sa.question_id
                                FROM ".$table_survey_answer." sa
                                WHERE
                                    sa.user='".$my_user_id."') AND
                                    survey_question.c_id =  $courseId
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
                        WHERE
                            survey_question NOT LIKE '%{{%' AND
                            survey_question.survey_id = '".$surveyId."' AND
                            survey_question.iid IN (".implode(',', $paged_questions[$_GET['show']]).")
                        ORDER BY survey_question.sort, survey_question_option.sort ASC";
            }

            $result = Database::query($sql);
            $question_counter_max = Database::num_rows($result);
            $counter = 0;
            $limit = 0;
            $questions = [];
            while ($row = Database :: fetch_array($result, 'ASSOC')) {
                // If the type is not a pagebreak we store it in the $questions array
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
                    $questions[$sort]['is_required'] = $allowRequiredSurveyQuestions && $row['is_required'];
                    $questions[$sort]['parent_id'] = $row['parent_id'] ?? 0;
                    $questions[$sort]['parent_option_id'] =
                        isset($row['parent_option_id']) ? $row['parent_option_id'] : 0;
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
            // Compute the results to get the 3 groups nearest to the user's personality
            if ('' == $shuffle) {
                $order = 'BY sort ASC ';
            } else {
                $order = $shuffle;
            }
            $answer_list = [];
            // Get current user results
            $results = [];
            $sql = "SELECT
                      survey_group_pri,
                      user,
                      SUM(value) as value
                    FROM $table_survey_answer as survey_answer
                    INNER JOIN $table_survey_question as survey_question
                    ON (survey_question.iid = survey_answer.question_id)
                    WHERE
                        survey_answer.survey_id='".$my_survey_id."' AND
                        survey_answer.user='".$current_user."'
                    GROUP BY survey_group_pri
                    ORDER BY survey_group_pri
                    ";

            $result = Database::query($sql);
            while ($row = Database :: fetch_array($result)) {
                $answer_list['value'] = $row['value'];
                $answer_list['group'] = $row['survey_group_pri'];
                $results[] = $answer_list;
            }

            // Get the total score for each group of questions
            $totals = [];
            $sql = "SELECT SUM(temp.value) as value, temp.survey_group_pri FROM
                    (
                        SELECT
                            MAX(value) as value,
                            survey_group_pri,
                            survey_question.iid question_id
                        FROM $table_survey_question as survey_question
                        INNER JOIN $table_survey_question_option as survey_question_option
                        ON (survey_question.iid = survey_question_option.question_id)
                        WHERE
                            survey_question.survey_id='".$my_survey_id."'  AND
                            survey_question.c_id = $courseId AND
                            survey_question_option.c_id = $courseId AND
                            survey_group_sec1='0' AND
                            survey_group_sec2='0'
                        GROUP BY survey_group_pri, survey_question.iid
                    ) as temp
                    GROUP BY temp.survey_group_pri
                    ORDER BY temp.survey_group_pri";

            $result = Database::query($sql);
            while ($row = Database::fetch_array($result)) {
                $list['value'] = $row['value'];
                $list['group'] = $row['survey_group_pri'];
                $totals[] = $list;
            }
            $final_results = [];
            // Get a percentage score for each group
            for ($i = 0; $i < count($totals); $i++) {
                for ($j = 0; $j < count($results); $j++) {
                    if ($totals[$i]['group'] == $results[$j]['group']) {
                        $group = $totals[$i]['group'];
                        $porcen = ($results[$j]['value'] / $totals[$i]['value']);
                        $final_results[$group] = $porcen;
                    }
                }
            }

            // Sort the results by score (getting a list of group IDs by score into $groups)
            arsort($final_results);
            $groups = array_keys($final_results);
            $result = [];
            $count_result = 0;
            foreach ($final_results as $key => &$sub_result) {
                $result[] = ['group' => $key, 'value' => $sub_result];
                $count_result++;
            }

            // i.e 70% - 70% -70% 70%  $equal_count =3
            $i = 0;
            $group_cant = 0;
            $equal_count = 0;
            // This is the case if the user does not select any question
            if ($count_result > 0) {
                // Count the number of scores equal to the first
                while (1) {
                    if ($result[$i]['value'] == $result[$i + 1]['value']) {
                        $equal_count++;
                    } else {
                        break;
                    }
                    $i++;
                }
            } else {
                // We force the exit of the survey undeterminated
                $equal_count = 10;
            }

            // If we have only 3 or less equal scores (i.e. 0,1 or 2 equalities), then we can use the three first groups
            if ($equal_count < 4) {
                // If there is one or less score equalities
                if (0 === $equal_count || 1 === $equal_count) {
                    // i.e 70% - 70% -60% - 60%  $equal_count = 1 we only get the first 2 options
                    if (($result[0]['value'] == $result[1]['value']) &&
                        ($result[2]['value'] == $result[3]['value'])
                    ) {
                        $group_cant = 1;
                    } elseif (($result[0]['value'] != $result[1]['value']) &&
                        ($result[1]['value'] == $result[2]['value']) && ($result[2]['value'] == $result[3]['value'])
                    ) {
                        // i.e 70% - 70% -0% - 0%     -    $equal_count = 0 we only get the first 2 options
                        /* elseif (($result[0]['value'] == $result[1]['value']) && ($result[1]['value'] != $result[2]['value'])) {
                          $group_cant = 0;
                          } */
                        /*
                          // i.e 70% - 70% -60% - 60%  $equal_count = 0 we only get the first 2 options
                          elseif (($result[0]['value'] == $result[1]['value'])  &&  ($result[2]['value'] == $result[3]['value'])) {
                          $group_cant = 0;
                          } */
                        // i.e. 80% - 70% - 70% - 70%
                        $group_cant = 0;
                    } else {
                        // i.e. 80% - 70% - 70% - 50
                        // i.e. 80% - 80% - 70% - 50
                        // By default we choose the highest 3
                        $group_cant = 2;
                    }
                } else {
                    // If there are two score equalities
                    $group_cant = $equal_count;
                }

                //@todo Translate these comments.
                // conditional_status
                // 0 no determinado
                // 1 determinado
                // 2 un solo valor
                // 3 valores iguales
                if ($group_cant > 0) {
                    //echo '$equal_count'.$group_cant;
                    // We only get highest 3
                    $secondary = '';
                    $combi = '';
                    for ($i = 0; $i <= $group_cant; $i++) {
                        $group1 = $groups[$i];
                        $group2 = $groups[$i + 1];
                        // Here we made all the posibilities with the 3 groups
                        if (2 == $group_cant && $i == $group_cant) {
                            $group2 = $groups[0];
                            $secondary .= " OR ( survey_group_sec1 = '$group1' AND  survey_group_sec2 = '$group2') ";
                            $secondary .= " OR ( survey_group_sec1 = '$group2' AND survey_group_sec2 = '$group1' ) ";
                            $combi .= $group1.' - '.$group2." or ".$group2.' - '.$group1.'<br />';
                        } else {
                            if (0 != $i) {
                                $secondary .= " OR ( survey_group_sec1 = '$group1' AND  survey_group_sec2 = '$group2') ";
                                $secondary .= " OR ( survey_group_sec1 = '$group2' AND survey_group_sec2 = '$group1' ) ";
                                $combi .= $group1.' - '.$group2." or ".$group2.' - '.$group1.'<br />';
                            } else {
                                $secondary .= " ( survey_group_sec1 = '$group1' AND  survey_group_sec2 = '$group2') ";
                                $secondary .= " OR ( survey_group_sec1 = '$group2' AND survey_group_sec2 = '$group1' ) ";
                                $combi .= $group1.' - '.$group2." or ".$group2.' - '.$group1.'<br />';
                            }
                        }
                    }
                    // Create the new select with the questions from the secondary phase
                    if (empty($_SESSION['page_questions_sec']) &&
                        !is_array($_SESSION['page_questions_sec']) &&
                        count(0 == $_SESSION['page_questions_sec'])
                    ) {
                        $sql = "SELECT * FROM $table_survey_question
                                 WHERE
                                    survey_id = '".$my_survey_id."' AND
                                    ($secondary )
                                 ORDER BY sort ASC";
                        $result = Database::query($sql);
                        $counter = 0;
                        while ($row = Database::fetch_array($result, 'ASSOC')) {
                            if (1 == $survey->getOneQuestionPerPage()) {
                                $paged_questions_sec[$counter][] = $row['question_id'];
                                $counter++;
                            } elseif ('pagebreak' === $row['type']) {
                                $counter++;
                                $pageBreakText[$counter] = $row['survey_question'];
                            } else {
                                // ids from question of the current survey
                                $paged_questions_sec[$counter][] = $row['question_id'];
                            }
                        }
                        Session::write('page_questions_sec', $paged_questions_sec);
                    } else {
                        $paged_questions_sec = Session::read('page_questions_sec');
                    }
                    $paged_questions = Session::read('paged_questions'); // For the sake of pages counting
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
                                ON survey_question.iid = survey_question_option.question_id AND
                                WHERE
                                    survey_question NOT LIKE '%{{%' AND
                                    survey_question.survey_id = '".$my_survey_id."'
                                    survey_question.iid IN (".implode(',', $paged_questions_sec[$val]).")
                                ORDER  $shuffle ";

                        $result = Database::query($sql);
                        $question_counter_max = Database::num_rows($result);
                        $counter = 0;
                        $limit = 0;
                        $questions = [];
                        while ($row = Database::fetch_array($result, 'ASSOC')) {
                            // If the type is not a pagebreak we store it in the $questions array
                            if ('pagebreak' !== $row['type']) {
                                $questions[$row['sort']]['question_id'] = $row['question_id'];
                                $questions[$row['sort']]['survey_id'] = $row['survey_id'];
                                $questions[$row['sort']]['survey_question'] = $row['survey_question'];
                                $questions[$row['sort']]['display'] = $row['display'];
                                $questions[$row['sort']]['type'] = $row['type'];
                                $questions[$row['sort']]['options'][$row['question_option_id']] = $row['option_text'];
                                $questions[$row['sort']]['maximum_score'] = $row['max_value'];
                                // Personality params
                                $questions[$row['sort']]['survey_group_sec1'] = $row['survey_group_sec1'];
                                $questions[$row['sort']]['survey_group_sec2'] = $row['survey_group_sec2'];
                                $questions[$row['sort']]['survey_group_pri'] = $row['survey_group_pri'];
                                $questions[$row['sort']]['sort'] = $row['sort'];
                            } else {
                                // If the type is a pagebreak we are finished loading the questions for this page
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
            // We need this variable only in the 2nd set of questions when personality is set.
            Session::erase('page_questions_sec');
            $paged_questions_sec = [];

            // Only the questions from the basic group
            // the 50 questions A B C D E F G
            $order_sql = $shuffle;
            if ('' == $shuffle) {
                $order_sql = ' BY question_id ';
            }

            if (empty($_SESSION['paged_questions'])) {
                $sql = "SELECT * FROM $table_survey_question
                        WHERE
                            survey_id = '".$surveyId."' AND
                            survey_group_sec1='0' AND
                            survey_group_sec2='0'
                        ORDER ".$order_sql." ";
                $result = Database::query($sql);
                $counter = 0;
                while ($row = Database::fetch_array($result, 'ASSOC')) {
                    if (1 == $survey->getOneQuestionPerPage()) {
                        $paged_questions[$counter][] = $row['question_id'];
                        $counter++;
                    } else {
                        if ('pagebreak' === $row['type']) {
                            $counter++;
                            $pageBreakText[$counter] = $row['survey_question'];
                        } else {
                            // ids from question of the current survey
                            $paged_questions[$counter][] = $row['question_id'];
                        }
                    }
                }
                Session::write('paged_questions', $paged_questions);
            } else {
                $paged_questions = Session::read('paged_questions');
            }
            $order_sql = $shuffle;
            if ('' == $shuffle) {
                $order_sql = ' BY survey_question.sort, survey_question_option.sort ASC ';
            }
            $val = $_GET['show'];
            $result = null;
            if ('' != $val) {
                $imploded = Database::escape_string(implode(',', $paged_questions[$val]));
                if ('' != $imploded) {
                    // The answers are always in the same order NO shuffle
                    $order_sql = ' BY survey_question.sort, survey_question_option.sort ASC ';
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
                                ".($allowRequiredSurveyQuestions ? ', survey_question.is_required' : '')."
                            FROM $table_survey_question survey_question
                            LEFT JOIN $table_survey_question_option survey_question_option
                            ON survey_question.iid = survey_question_option.question_id AND
                            survey_question_option.c_id = $courseId
                            WHERE
                                survey_question NOT LIKE '%{{%' AND
                                survey_question.survey_id = '".intval($survey_invitation['survey_id'])."' AND
                                survey_question.c_id = $courseId  AND
                                survey_question.iid IN (".$imploded.")
                            ORDER $order_sql ";
                    $result = Database::query($sql);
                    $question_counter_max = Database :: num_rows($result);
                }
            }

            if (!is_null($result)) {
                $counter = 0;
                $limit = 0;
                $questions = [];
                while ($row = Database :: fetch_array($result, 'ASSOC')) {
                    // If the type is not a pagebreak we store it in the $questions array
                    if ('pagebreak' !== $row['type']) {
                        $questions[$row['sort']]['question_id'] = $row['question_id'];
                        $questions[$row['sort']]['survey_id'] = $row['survey_id'];
                        $questions[$row['sort']]['survey_question'] = $row['survey_question'];
                        $questions[$row['sort']]['display'] = $row['display'];
                        $questions[$row['sort']]['type'] = $row['type'];
                        $questions[$row['sort']]['options'][$row['question_option_id']] = $row['option_text'];
                        $questions[$row['sort']]['maximum_score'] = $row['max_value'];
                        $questions[$row['sort']]['is_required'] = $allowRequiredSurveyQuestions && $row['is_required'];
                        // Personality params
                        $questions[$row['sort']]['survey_group_sec1'] = $row['survey_group_sec1'];
                        $questions[$row['sort']]['survey_group_sec2'] = $row['survey_group_sec2'];
                        $questions[$row['sort']]['survey_group_pri'] = $row['survey_group_pri'];
                        $questions[$row['sort']]['sort'] = $row['sort'];
                    } else {
                        // If the type is a page break we are finished loading the questions for this page
                        //break;
                    }
                    $counter++;
                }
            }
        }
    } else { // In case it's another type than 0 or 1
        echo get_lang('Survey type unknown');
    }
}

$numberOfPages = SurveyManager::getCountPages($survey);

// Displaying the form with the questions
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

// Displaying the form with the questions
$personality = 0;
if (isset($_POST['personality'])) {
    $personality = (int) $_POST['personality'] + 1;
}

// Displaying the form with the questions
$g_c = isset($_GET['course']) ? Security::remove_XSS($_GET['course']) : '';
$g_ic = isset($_GET['invitationcode']) ? Security::remove_XSS($_GET['invitationcode']) : '';
$g_cr = isset($_GET['cidReq']) ? Security::remove_XSS($_GET['cidReq']) : '';
$p_l = isset($_POST['language']) ? Security::remove_XSS($_POST['language']) : '';
$add_parameters = isset($_GET['user_id']) ? '&user_id='.intval($_GET['user_id']) : '';
$url = api_get_self().'?cid='.$courseId.'&sid='.$sessionId.$add_parameters.
    '&course='.$g_c.
    '&invitationcode='.$g_ic.
    '&show='.$show.
    '&iid='.$surveyId
;
if (!empty($_GET['language'])) {
    $lang = Security::remove_XSS($_GET['language']);
    $url .= '&language='.$lang;
}
$form = new FormValidator(
    'question',
    'post',
    $url,
    null,
    null,
    FormValidator::LAYOUT_INLINE
);
$form->addHidden('language', $p_l);

$showNumber = true;
if (SurveyManager::hasDependency($survey)) {
    $showNumber = false;
}

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

    $form->addHtml('<div class="start-survey">');
    $js = '';
    if (isset($pageBreakText[$originalShow]) && !empty(strip_tags($pageBreakText[$originalShow]))) {
        // Only show page-break texts if there is something there, apart from
        // HTML tags
        $form->addHtml(
            '<div>'.
            Security::remove_XSS($pageBreakText[$originalShow]).
            '</div>'
        );
        $form->addHtml('<br />');
    }

    foreach ($questions as $key => &$question) {
        $ch_type = 'ch_'.$question['type'];
        $questionNumber = $questionCounter;
        $display = new $ch_type();
        $parent = $question['parent_id'];
        $parentClass = '';
        // @todo move this in a function.
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

        // @todo move this in a function.
        $form->addHtml('<div class="survey_question '.$ch_type.' '.$parentClass.'">');
        if ($showNumber) {
            $form->addHtml('<div style="float:left; font-weight: bold; margin-right: 5px;"> '.$questionNumber.'. </div>');
        }
        $form->addHtml('<div>'.Security::remove_XSS($question['survey_question']).'</div> ');

        $userAnswerData = SurveyUtil::get_answers_of_question_by_user($question['survey_id'], $question['question_id']);
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

$form->addHtml('<div class="start-survey">');
if ('0' == $survey->getSurveyType()) {
    if (0 == $survey->getShowFormProfile()) {
        // The normal survey as always
        if ($show < $numberOfPages) {
            if (0 == $show) {
                $form->addButton(
                    'next_survey_page',
                    get_lang('Start the Survey'),
                    'arrow-right',
                    'success'
                );
            } else {
                if (
                api_get_configuration_value('survey_backwards_enable')
                ) {
                    if ($lastQuestion >= 0) {
                        $form->addHtml(
                            "<a class=\" btn btn-warning \" href=\"$url&show=$lastQuestion\">".
                            "<em class=\"fa fa-arrow-left\"></em> "
                            .get_lang('Back')." </a>"
                        );
                    }
                }
                $form->addButton(
                    'next_survey_page',
                    get_lang('Next'),
                    'arrow-right',
                    'success'
                );
            }
        }
        if ($show >= $numberOfPages && $displayFinishButton) {
            $form->addButton(
                'finish_survey',
                get_lang('Finish survey'),
                'arrow-right',
                'success'
            );
        }
    } else {
        // The normal survey as always but with the form profile
        if (isset($_GET['show'])) {
            $numberOfPages = count($paged_questions);
            if ($show < $numberOfPages) {
                if (0 == $show) {
                    $form->addButton(
                        'next_survey_page',
                        get_lang('Start the Survey'),
                        'arrow-right',
                        'success'
                    );
                } else {
                    $form->addButton(
                        'next_survey_page',
                        get_lang('Next'),
                        'arrow-right',
                        'success'
                    );
                }
            }

            if ($show >= $numberOfPages && $displayFinishButton) {
                $form->addButton(
                    'finish_survey',
                    get_lang('Finish survey'),
                    'arrow-right',
                    'success'
                );
            }
        }
    }
} elseif (1 === $survey->getSurveyType()) {
    // Conditional/personality-test type survey
    if (isset($_GET['show']) || isset($_POST['personality'])) {
        $numberOfPages = count($paged_questions);
        if (!empty($paged_questions_sec) && count($paged_questions_sec) > 0) {
            // In case we're in the second phase, also sum the second group questions
            $numberOfPages += count($paged_questions_sec);
        } else {
            // We need this variable only if personality == 1
            Session::erase('page_questions_sec');
            $paged_questions_sec = [];
        }

        if (0 == $personality) {
            if (($show <= $numberOfPages) || !$_GET['show']) {
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

                if ($numberOfPages == $show) {
                    $form->addHidden('personality', $personality);
                }
            }
        }

        if ($show > $numberOfPages && $_GET['show'] && 0 == $personality) {
            $form->addHidden('personality', $personality);
        } elseif ($personality > 0) {
            if (1 == $survey->getOneQuestionPerPage()) {
                if ($show >= $numberOfPages) {
                    $form->addButton('finish_survey', get_lang('Finish survey'), 'arrow-right', 'success');
                } else {
                    $form->addHidden('personality', $personality);
                    $form->addButton('next_survey_page', get_lang('Next'), 'arrow-right', 'success');
                }
            } else {
                // if the personality test hidden input was set.
                $form->addButton('finish_survey', get_lang('Finish survey'), 'arrow-right');
            }
        }
    } elseif ('' == $survey->getFormFields()) {
        // This is the case when the show_profile_form is true but there are not form_fields
        $form->addButton('next_survey_page', get_lang('Next'), 'arrow-right', 'success');
    } elseif (!is_array($user_data)) {
        // If the user is not registered in the platform we do not show the form to update his information
        $form->addButton('next_survey_page', get_lang('Next'), 'arrow-right', 'success');
    }
}
$form->addHtml('</div>');
$form->display();
Display::display_footer();

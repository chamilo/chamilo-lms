<?php

/* For licensing terms, see /license.txt */

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

$userInfo = api_get_user_info();
$sessionId = isset($_GET['id_session']) ? (int) $_GET['id_session'] : api_get_session_id();
$lpItemId = isset($_GET['lp_item_id']) ? (int) $_GET['lp_item_id'] : 0;
$allowSurveyInLp = api_get_configuration_value('allow_survey_tool_in_lp');

// Breadcrumbs
if (!empty($userInfo)) {
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'survey/survey_list.php?cidReq='.$courseInfo['code'].'&id_session='.$sessionId,
        'name' => get_lang('SurveyList'),
    ];
}

$course_id = $courseInfo['real_id'];
$surveyCode = isset($_GET['scode']) ? Database::escape_string($_GET['scode']) : '';

if ($surveyCode != '') {
    // Firstly we check if this survey is ready for anonymous use:
    $sql = "SELECT anonymous FROM $table_survey
            WHERE c_id = $course_id AND code ='$surveyCode'";
    $resultAnonymous = Database::query($sql);
    $rowAnonymous = Database::fetch_array($resultAnonymous, 'ASSOC');
    // If is anonymous and is not allowed to take the survey to anonymous users, forbid access:
    if (!isset($rowAnonymous['anonymous']) ||
        ($rowAnonymous['anonymous'] == 0 && api_is_anonymous()) ||
        count($rowAnonymous) == 0
    ) {
        api_not_allowed(true);
    }
    // If is anonymous and it is allowed to take the survey as anonymous, mark survey as anonymous.
}

// First we check if the needed parameters are present
if ((!isset($_GET['course']) || !isset($_GET['invitationcode'])) && !isset($_GET['user_id'])) {
    api_not_allowed(true, get_lang('SurveyParametersMissingUseCopyPaste'));
}

$invitationcode = $_GET['invitationcode'];
$lpItemCondition = '';
if ($allowSurveyInLp) {
    $lpItemCondition = " AND c_lp_item_id = $lpItemId";
}

$sessionCondition = '';
if (true === api_get_configuration_value('show_surveys_base_in_sessions')) {
    $sessionCondition = api_get_session_condition($sessionId);
}

// Start auto-invitation feature FS#3403 (all-users-can-do-the-survey-URL handling)
if ('auto' === $invitationcode && isset($_GET['scode'])) {
    $userid = api_get_user_id();
    // Survey_code of the survey
    $surveyCode = $_GET['scode'];
    if ($isAnonymous) {
        $autoInvitationcode = 'auto-ANONY_'.md5(time())."-$surveyCode";
    } else {
        $invitations = SurveyManager::getUserInvitationsForSurveyInCourse(
            $userid,
            $surveyCode,
            $courseInfo['real_id'],
            $sessionId,
            $lpItemId
        );
        $lastInvitation = current($invitations);

        if (!$lastInvitation) {
            // New invitation code from userid
            $autoInvitationcode = "auto-$userid-$surveyCode";
        } else {
            $autoInvitationcode = $lastInvitation->getInvitationCode();
        }
    }

    // The survey code must exist in this course, or the URL is invalid
    $sql = "SELECT * FROM $table_survey
            WHERE c_id = $course_id AND code = '".Database::escape_string($surveyCode)."'";
    $result = Database::query($sql);
    if (Database::num_rows($result) > 0) {
        // Check availability
        $row = Database::fetch_array($result, 'ASSOC');
        $tempdata = SurveyManager::get_survey($row['survey_id']);
        SurveyManager::checkTimeAvailability($tempdata);
        // Check for double invitation records (insert should be done once)
        $sql = "SELECT user
                FROM $table_survey_invitation
                WHERE
                    c_id = $course_id AND
                    invitation_code = '".Database::escape_string($autoInvitationcode)."'
                    $sessionCondition
                    $lpItemCondition";
        $result = Database::query($sql);
        $now = api_get_utc_datetime();
        if (0 == Database::num_rows($result)) {
            $params = [
                'c_id' => $course_id,
                'survey_code' => $surveyCode,
                'user' => $userid,
                'invitation_code' => $autoInvitationcode,
                'invitation_date' => $now,
                'session_id' => $sessionId,
            ];
            if ($allowSurveyInLp) {
                $params['c_lp_item_id'] = $lpItemId;
            }
            Database::insert($table_survey_invitation, $params);
        }
        // From here we use the new invitationcode auto-userid-surveycode string
        $_GET['invitationcode'] = $autoInvitationcode;
        Session::write('auto_invitation_code_'.$surveyCode, $autoInvitationcode);
        $invitationcode = $autoInvitationcode;
    }
}

// Now we check if the invitation code is valid
$sql = "SELECT * FROM $table_survey_invitation
        WHERE
            c_id = $course_id AND
            invitation_code = '".Database::escape_string($invitationcode)."'
            $sessionCondition
            $lpItemCondition";
$result = Database::query($sql);
if (Database::num_rows($result) < 1) {
    api_not_allowed(true, get_lang('WrongInvitationCode'));
}

$survey_invitation = Database::fetch_array($result, 'ASSOC');
$surveyUserFromSession = Session::read('surveyuser');
// Now we check if the user already filled the survey
if (!isset($_POST['finish_survey']) &&
    (
        $isAnonymous &&
        !empty($surveyUserFromSession) &&
        SurveyUtil::isSurveyAnsweredFlagged($survey_invitation['survey_code'], $survey_invitation['c_id'])
    ) ||
    ($survey_invitation['answered'] == 1 && !isset($_GET['user_id']))
) {
    api_not_allowed(true, Display::return_message(get_lang('YouAlreadyFilledThisSurvey')));
}

$logInfo = [
    'tool' => TOOL_SURVEY,
    'tool_id' => $survey_invitation['survey_invitation_id'],
    'action' => 'invitationcode',
    'action_details' => $invitationcode,
];
Event::registerLog($logInfo);

// Checking if there is another survey with this code.
// If this is the case there will be a language choice
$sql = "SELECT * FROM $table_survey
        WHERE
            c_id = $course_id AND
            code = '".Database::escape_string($survey_invitation['survey_code'])."'";
if (true === api_get_configuration_value('show_surveys_base_in_sessions')) {
    // It lists the surveys base too
    $sql .= api_get_session_condition($sessionId, true, true);
} else {
    $sql .= api_get_session_condition($sessionId);
}
$result = Database::query($sql);

if (Database::num_rows($result) > 1) {
    if ($_POST['language']) {
        $survey_invitation['survey_id'] = $_POST['language'];
    } else {
        Display::display_header(get_lang('ToolSurvey'));
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
        echo '<button type="submit" name="Submit" class="next">'.get_lang('Ok').'</button>';
        echo '</form>';
        Display::display_footer();
        exit();
    }
} else {
    $row = Database::fetch_array($result, 'ASSOC');
    $survey_invitation['survey_id'] = $row['survey_id'];
}

// Getting the survey information
$survey_data = SurveyManager::get_survey($survey_invitation['survey_id']);
if (empty($survey_data)) {
    api_not_allowed(true);
}

// Checking time availability
SurveyManager::checkTimeAvailability($survey_data);
$survey_data['survey_id'] = $survey_invitation['survey_id'];

if ($survey_data['survey_type'] === '3') {
    header('Location: '.
        api_get_path(WEB_CODE_PATH).
        'survey/meeting.php?cidReq='.$courseInfo['code'].'&id_session='.$sessionId.'&invitationcode='.Security::remove_XSS($invitationcode)
    );
    exit;
}

if (!empty($survey_data['anonymous'])) {
    define('USER_IN_ANON_SURVEY', true);
}

// Storing the answers
if (count($_POST) > 0) {
    if ($survey_data['survey_type'] === '0') {
        $types = [];
        $required = [];
        // Getting all the types of the question
        // (because of the special treatment of the score question type
        $sql = "SELECT * FROM $table_survey_question
                WHERE
                    c_id = $course_id AND
                    survey_id = '".intval($survey_invitation['survey_id'])."'";
        $result = Database::query($sql);

        while ($row = Database::fetch_array($result, 'ASSOC')) {
            $types[$row['question_id']] = $row['type'];
            $required[$row['question_id']] = $allowRequiredSurveyQuestions && $row['is_required'];
        }

        // Looping through all the post values
        foreach ($_POST as $key => &$value) {
            // If the post value key contains the string 'question' then it is an answer on a question
            if (strpos($key, 'other_question') === false &&
                strpos($key, 'question') !== false && $key !== '_qf__question'
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

                /* If the post value is an array then we have a multiple response question or a scoring question type
                remark: when it is a multiple response then the value of the array is the option_id
                when it is a scoring question then the key of the array is the option_id and the value is the value
                */
                if (is_array($value)) {
                    SurveyUtil::remove_answer(
                        $survey_invitation['user'],
                        $survey_invitation['survey_id'],
                        $survey_question_id,
                        $course_id,
                        $sessionId,
                        $lpItemId
                    );

                    foreach ($value as $answer_key => &$answer_value) {
                        if ('score' == $types[$survey_question_id]) {
                            $option_id = $answer_key;
                            $option_value = $answer_value;
                        } else {
                            $option_id = $answer_value;
                            $option_value = '';
                        }

                        SurveyUtil::store_answer(
                            $survey_invitation['user'],
                            $survey_invitation['survey_id'],
                            $survey_question_id,
                            $option_id,
                            $option_value,
                            $survey_data,
                            '',
                            $sessionId,
                            $lpItemId
                        );
                    }
                } else {
                    // All the other question types (open question, multiple choice, percentage, ...)
                    if (isset($types[$survey_question_id]) &&
                        'percentage' === $types[$survey_question_id]) {
                        $sql = "SELECT * FROM $table_survey_question_option
                                WHERE
                                    c_id = $course_id AND
                                    question_option_id='".intval($value)."'";
                        $result = Database::query($sql);
                        $row = Database::fetch_array($result, 'ASSOC');
                        $option_value = $row['option_text'];
                    } else {
                        $option_value = 0;
                        if (isset($types[$survey_question_id]) &&
                            'open' === $types[$survey_question_id]
                        ) {
                            $option_value = $value;
                        }
                    }

                    $survey_question_answer = $value;
                    SurveyUtil::remove_answer(
                        $survey_invitation['user'],
                        $survey_invitation['survey_id'],
                        $survey_question_id,
                        $course_id,
                        $sessionId,
                        $lpItemId
                    );

                    SurveyUtil::store_answer(
                        $survey_invitation['user'],
                        $survey_invitation['survey_id'],
                        $survey_question_id,
                        $value,
                        $option_value,
                        $survey_data,
                        $other,
                        $sessionId,
                        $lpItemId
                    );
                }
            }
        }
    } elseif ($survey_data['survey_type'] === '1') {
        //conditional/personality-test type surveys
        // Getting all the types of the question (because of the special treatment of the score question type
        $shuffle = '';
        if ($survey_data['shuffle'] == '1') {
            $shuffle = ' ORDER BY RAND() ';
        }
        $sql = "SELECT * FROM $table_survey_question
                WHERE
                    c_id = $course_id AND
                    survey_id = '".intval($survey_invitation['survey_id'])."' AND
                    survey_group_pri = '0' $shuffle";
        $result = Database::query($sql);
        // There is only one question type for conditional surveys
        while ($row = Database::fetch_array($result, 'ASSOC')) {
            $types[$row['question_id']] = $row['type'];
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
                        WHERE c_id = $course_id AND question_option_id='".intval($value)."'";
                $result = Database::query($sql);
                $row = Database::fetch_array($result, 'ASSOC');
                $option_value = $row['value'];
                $survey_question_answer = $value;

                // We save the answer after making sure that a possible previous attempt is deleted
                SurveyUtil::remove_answer(
                    $survey_invitation['user'],
                    $survey_invitation['survey_id'],
                    $survey_question_id,
                    $course_id,
                    $sessionId,
                    $lpItemId
                );

                SurveyUtil::store_answer(
                    $survey_invitation['user'],
                    $survey_invitation['survey_id'],
                    $survey_question_id,
                    $value,
                    $option_value,
                    $survey_data,
                    '',
                    $sessionId,
                    $lpItemId
                );
            }
        }
    } else {
        // In case it's another type than 0 or 1
        api_not_allowed(true, get_lang('ErrorSurveyTypeUnknown'));
    }
}

$user_id = api_get_user_id();
if ($user_id == 0) {
    $user_id = $survey_invitation['user'];
}
$user_data = api_get_user_info($user_id);

if ($survey_data['form_fields'] != '' &&
    $survey_data['anonymous'] == 0 &&
    is_array($user_data)
) {
    $form_fields = explode('@', $survey_data['form_fields']);
    $list = [];
    foreach ($form_fields as $field) {
        $field_value = explode(':', $field);
        if (isset($field_value[1]) && $field_value[1] == 1) {
            if ($field_value[0] != '') {
                $val = api_substr($field_value[0], 8, api_strlen($field_value[0]));
                $list[$val] = 1;
            }
        }
    }

    $url = api_get_self().
        '?cidReq='.$courseInfo['code'].
        '&id_session='.$sessionId;
    $listQueryParams = preg_split('/&/', $_SERVER['QUERY_STRING']);
    foreach ($listQueryParams as $param) {
        $url .= '&'.Security::remove_XSS($param);
    }
    if (!empty($lpItemId) && $allowSurveyInLp) {
        $url .= '&lp_item_id='.$lpItemId.'&origin=learnpath';
    }

    // We use the same form as in auth/profile.php
    $form = new FormValidator('profile', 'post', $url);
    if (api_is_western_name_order()) {
        if (isset($list['firstname']) && $list['firstname'] == 1) {
            //FIRST NAME
            $form->addElement('text', 'firstname', get_lang('FirstName'), ['size' => 40]);
            if (api_get_setting('profile', 'name') !== 'true') {
                $form->freeze(['firstname']);
            }
            $form->applyFilter(['firstname'], 'stripslashes');
            $form->applyFilter(['firstname'], 'trim');
            $form->addRule('firstname', get_lang('ThisFieldIsRequired'), 'required');
        }
        if (isset($list['lastname']) && $list['lastname'] == 1) {
            //    LAST NAME
            $form->addElement('text', 'lastname', get_lang('LastName'), ['size' => 40]);
            if (api_get_setting('profile', 'name') !== 'true') {
                $form->freeze(['lastname']);
            }
            $form->applyFilter(['lastname'], 'stripslashes');
            $form->applyFilter(['lastname'], 'trim');
            $form->addRule('lastname', get_lang('ThisFieldIsRequired'), 'required');
        }
    } else {
        if (isset($list['lastname']) && $list['lastname'] == 1) {
            //    LAST NAME
            $form->addElement('text', 'lastname', get_lang('LastName'), ['size' => 40]);
            if (api_get_setting('profile', 'name') !== 'true') {
                $form->freeze(['lastname']);
            }
            $form->applyFilter(['lastname'], 'stripslashes');
            $form->applyFilter(['lastname'], 'trim');
            $form->addRule('lastname', get_lang('ThisFieldIsRequired'), 'required');
        }
        if (isset($list['firstname']) && $list['firstname'] == 1) {
            //FIRST NAME
            $form->addElement('text', 'firstname', get_lang('FirstName'), ['size' => 40]);
            if (api_get_setting('profile', 'name') !== 'true') {
                $form->freeze(['firstname']);
            }
            $form->applyFilter(['firstname'], 'stripslashes');
            $form->applyFilter(['firstname'], 'trim');
            $form->addRule('firstname', get_lang('ThisFieldIsRequired'), 'required');
        }
    }

    if (isset($list['official_code']) && $list['official_code'] == 1) {
        // OFFICIAL CODE
        if (CONFVAL_ASK_FOR_OFFICIAL_CODE) {
            $form->addElement('text', 'official_code', get_lang('OfficialCode'), ['size' => 40]);
            if (api_get_setting('profile', 'officialcode') !== 'true') {
                $form->freeze('official_code');
            }
            $form->applyFilter('official_code', 'stripslashes');
            $form->applyFilter('official_code', 'trim');
            if (api_get_setting('registration', 'officialcode') === 'true' &&
                api_get_setting('profile', 'officialcode') === 'true'
            ) {
                $form->addRule('official_code', get_lang('ThisFieldIsRequired'), 'required');
            }
        }
    }

    if (isset($list['email']) && $list['email'] == 1) {
        //    EMAIL
        $form->addElement('text', 'email', get_lang('Email'), ['size' => 40]);
        if (api_get_setting('profile', 'email') !== 'true') {
            $form->freeze('email');
        }
        $form->applyFilter('email', 'stripslashes');
        $form->applyFilter('email', 'trim');
        if (api_get_setting('registration', 'email') === 'true') {
            $form->addRule('email', get_lang('ThisFieldIsRequired'), 'required');
        }
        $form->addRule('email', get_lang('EmailWrong'), 'email');
    }

    if (isset($list['phone']) && $list['phone'] == 1) {
        // PHONE
        $form->addElement('text', 'phone', get_lang('Phone'), ['size' => 20]);
        if (api_get_setting('profile', 'phone') !== 'true') {
            $form->freeze('phone');
        }
        $form->applyFilter('phone', 'stripslashes');
        $form->applyFilter('phone', 'trim');
        if (api_get_setting('profile', 'phone') === 'true') {
            $form->addRule('phone', get_lang('ThisFieldIsRequired'), 'required');
        }
    }

    if (isset($list['language']) && $list['language'] == 1) {
        // LANGUAGE
        $form->addSelectLanguage('language', get_lang('Language'));
        if (api_get_setting('profile', 'language') !== 'true') {
            $form->freeze('language');
        }
        if (api_get_setting('profile', 'language') === 'true') {
            $form->addRule('language', get_lang('ThisFieldIsRequired'), 'required');
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

Display::display_header(get_lang('ToolSurvey'));
echo '<div class="survey-block">';
echo '<div class="page-header">';
echo '<h2>';
echo Security::remove_XSS($survey_data['survey_title']).'</h2></div>';
if (!empty($survey_data['survey_subtitle'])) {
    echo '<div class="survey_subtitle"><p>'.Security::remove_XSS($survey_data['survey_subtitle']).'</p></div>';
}

// Displaying the survey introduction
if (
    !isset($_GET['show']) ||
    (isset($_GET['show'])) && $_GET['show'] == '') {
    // The first thing we do is delete the session
    Session::erase('paged_questions');
    Session::erase('page_questions_sec');

    $paged_questions_sec = [];
    if (!empty($survey_data['survey_introduction'])) {
        echo '<div class="survey_content">'.Security::remove_XSS($survey_data['survey_introduction']).'</div>';
    }
    $limit = 0;
}

if ($survey_data['form_fields'] &&
    $survey_data['anonymous'] == 0 &&
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
                        $sql .= " $key = '".Database::escape_string($value)."',";
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
                    get_lang('InformationUpdated').' '.get_lang('PleaseFillSurvey').'</div>';
            }
        }
        $_GET['show'] = 0;
        $show = 0;
        // We unset the sessions
        Session::erase('paged_questions');
        Session::erase('page_questions_sec');
        $paged_questions_sec = [];
    } else {
        echo '<div id="survey_content" class="survey_content">'.get_lang('UpdateInformation').'</div>';
        // We unset the sessions
        Session::erase('paged_questions');
        Session::erase('page_questions_sec');
        $paged_questions_sec = [];
        $form->display();
    }
}

// Displaying the survey thanks message
if (isset($_POST['finish_survey'])) {
    echo Display::return_message(get_lang('SurveyFinished'), 'confirm');
    echo Security::remove_XSS($survey_data['survey_thanks']);

    SurveyManager::update_survey_answered(
        $survey_data,
        $survey_invitation['user'],
        $survey_invitation['survey_code'],
        $lpItemId
    );

    SurveyUtil::flagSurveyAsAnswered(
        $survey_invitation['survey_code'],
        $survey_invitation['c_id']
    );

    if ($courseInfo && !api_is_anonymous() && empty($lpItemId)) {
        echo '<br /><br />';
        echo Display::toolbarButton(
            get_lang('ReturnToCourseHomepage'),
            api_get_course_url($courseInfo['code']),
            'home'
        );
    }

    Session::erase('paged_questions');
    Session::erase('page_questions_sec');
    Session::erase('auto_invitation_code_'.$survey_data['code']);
    Display::display_footer();
    exit();
}

// Sets the random questions
$shuffle = '';
if (1 == $survey_data['shuffle']) {
    $shuffle = ' BY RAND() ';
}

$pageBreakText = [];
if ((isset($_GET['show']) && $_GET['show'] != '') ||
    isset($_POST['personality'])
) {
    // Getting all the questions for this page and add them to a
    // multidimensional array where the first index is the page.
    // As long as there is no pagebreak fount we keep adding questions to the page
    $questions_displayed = [];
    $counter = 0;
    $paged_questions = [];

    $select = '';
    if (true === api_get_configuration_value('survey_question_dependency')) {
        $select = ' survey_question.parent_id, survey_question.parent_option_id, ';
    }

    // If non-conditional survey
    if ($survey_data['survey_type'] == '0') {
        if (empty($paged_questions)) {
            $sql = "SELECT * FROM $table_survey_question
                    WHERE
                        survey_question NOT LIKE '%{{%' AND
                        c_id = $course_id AND
                        survey_id = '".intval($survey_invitation['survey_id'])."'
                    ORDER BY sort ASC";
            $result = Database::query($sql);
            while ($row = Database::fetch_array($result, 'ASSOC')) {
                if ($survey_data['one_question_per_page'] == 1) {
                    if ($row['type'] !== 'pagebreak') {
                        $paged_questions[$counter][] = $row['question_id'];
                        $counter++;
                        continue;
                    }
                } else {
                    if ($row['type'] === 'pagebreak') {
                        $counter++;
                        $pageBreakText[$counter] = $row['survey_question'];
                    } else {
                        $paged_questions[$counter][] = $row['question_id'];
                    }
                }
            }
            Session::write('paged_questions', $paged_questions);
        }

        // Redefinition of variables and session ids to fix issue of survey not
        //  showing questions - see support.chamilo.org #5529
        $course_id = $survey_invitation['c_id'];
        Session::write('_cid', $course_id);
        Session::write('_real_cid', $course_id);

        if (array_key_exists($_GET['show'], $paged_questions)) {
            if (isset($_GET['user_id'])) {
                // Get the user into survey answer table (user or anonymus)
                $my_user_id = $survey_data['anonymous'] == 1 ? $surveyUserFromSession : api_get_user_id();

                // To show the answers by lp item
                $lpItemCondition = '';
                if ($allowSurveyInLp) {
                    $lpItemCondition = " AND sa.c_lp_item_id = $lpItemId";
                }
                // To show the answers by session
                $sessionCondition = '';
                if (true === api_get_configuration_value('show_surveys_base_in_sessions')) {
                    $sessionCondition = api_get_session_condition($sessionId, true, false, 'sa.session_id');
                }

                $sql = "SELECT
                            survey_question.survey_group_sec1,
                            survey_question.survey_group_sec2,
                            survey_question.survey_group_pri,
                            survey_question.question_id,
                            survey_question.survey_id,
                            survey_question.survey_question,
                            survey_question.display,
                            survey_question.sort,
                            survey_question.type,
                            survey_question.max_value,
                            survey_question_option.question_option_id,
                            survey_question_option.option_text,
                            $select
                            survey_question_option.sort as option_sort
                        FROM $table_survey_question survey_question
                        LEFT JOIN $table_survey_question_option survey_question_option
                        ON survey_question.question_id = survey_question_option.question_id AND
                        survey_question_option.c_id = $course_id
                        WHERE
                            survey_question.survey_id = '".Database::escape_string($survey_invitation['survey_id'])."' AND
                            survey_question.question_id NOT IN (
                                SELECT sa.question_id
                                FROM ".$table_survey_answer." sa
                                WHERE
                                    sa.user='".$my_user_id."' $sessionCondition $lpItemCondition) AND
                                    survey_question.c_id =  $course_id
                                ORDER BY survey_question.sort, survey_question_option.sort ASC";
            } else {
                $sql = "SELECT
                            survey_question.survey_group_sec1,
                            survey_question.survey_group_sec2,
                            survey_question.survey_group_pri,
                            survey_question.question_id,
                            survey_question.survey_id,
                            survey_question.survey_question,
                            survey_question.display,
                            survey_question.sort,
                            survey_question.type,
                            survey_question.max_value,
                            survey_question_option.question_option_id,
                            survey_question_option.option_text,
                            $select
                            survey_question_option.sort as option_sort
                            ".($allowRequiredSurveyQuestions ? ', survey_question.is_required' : '')."
                        FROM $table_survey_question survey_question
                        LEFT JOIN $table_survey_question_option survey_question_option
                        ON survey_question.question_id = survey_question_option.question_id AND
                            survey_question_option.c_id = $course_id
                        WHERE
                            survey_question NOT LIKE '%{{%' AND
                            survey_question.survey_id = '".intval($survey_invitation['survey_id'])."' AND
                            survey_question.question_id IN (".implode(',', $paged_questions[$_GET['show']]).") AND
                            survey_question.c_id =  $course_id
                        ORDER BY survey_question.sort, survey_question_option.sort ASC";
            }

            $result = Database::query($sql);
            $question_counter_max = Database::num_rows($result);
            $counter = 0;
            $limit = 0;
            $questions = [];
            while ($row = Database::fetch_array($result, 'ASSOC')) {
                // If the type is not a pagebreak we store it in the $questions array
                if ($row['type'] !== 'pagebreak') {
                    $sort = $row['sort'];
                    $questions[$sort]['question_id'] = $row['question_id'];
                    $questions[$sort]['survey_id'] = $row['survey_id'];
                    $questions[$sort]['survey_question'] = $row['survey_question'];
                    $questions[$sort]['display'] = $row['display'];
                    $questions[$sort]['type'] = $row['type'];
                    $questions[$sort]['options'][$row['question_option_id']] = Security::remove_XSS($row['option_text']);
                    $questions[$sort]['maximum_score'] = $row['max_value'];
                    $questions[$sort]['sort'] = $sort;
                    $questions[$sort]['is_required'] = $allowRequiredSurveyQuestions && $row['is_required'];
                    $questions[$sort]['parent_id'] = isset($row['parent_id']) ? $row['parent_id'] : 0;
                    $questions[$sort]['parent_option_id'] = isset($row['parent_option_id']) ? $row['parent_option_id'] : 0;
                }
                $counter++;
                // see GH#3582
                if (isset($_GET['show']) && (int) $_GET['show'] >= 0) {
                    $lastQuestion = (int) $_GET['show'] - 1;
                } else {
                    $lastQuestion = (int) $row['question_option_id'];
                }
            }
        }
    } elseif ('1' === $survey_data['survey_type']) {
        $my_survey_id = (int) $survey_invitation['survey_id'];
        $current_user = Database::escape_string($survey_invitation['user']);

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

            // To display de answers by Lp Item
            $lpItemCondition = '';
            if ($allowSurveyInLp) {
                $lpItemCondition = " AND survey_answer.c_lp_item_id = $lpItemId";
            }
            // To display the answers by session
            $sessionCondition = '';
            if (true === api_get_configuration_value('show_surveys_base_in_sessions')) {
                $sessionCondition = api_get_session_condition($sessionId, true, false, 'survey_answer.session_id');
            }

            $sql = "SELECT
                      survey_group_pri,
                      user,
                      SUM(value) as value
                    FROM $table_survey_answer as survey_answer
                    INNER JOIN $table_survey_question as survey_question
                    ON (survey_question.question_id = survey_answer.question_id)
                    WHERE
                        survey_answer.survey_id='".$my_survey_id."' AND
                        survey_answer.user='".$current_user."' AND
                        survey_answer.c_id = $course_id AND
                        survey_question.c_id = $course_id
                        $sessionCondition
                        $lpItemCondition
                    GROUP BY survey_group_pri
                    ORDER BY survey_group_pri
                    ";

            $result = Database::query($sql);
            while ($row = Database::fetch_array($result)) {
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
                            survey_question.question_id
                        FROM $table_survey_question as survey_question
                        INNER JOIN $table_survey_question_option as survey_question_option
                        ON (survey_question.question_id = survey_question_option.question_id)
                        WHERE
                            survey_question.survey_id='".$my_survey_id."'  AND
                            survey_question.c_id = $course_id AND
                            survey_question_option.c_id = $course_id AND
                            survey_group_sec1='0' AND
                            survey_group_sec2='0'
                        GROUP BY survey_group_pri, survey_question.question_id
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

            /*
              //i.e 70% - 70% -70% 70%  $equal_count =3
              while (1) {
              if ($result[$i]['value']  == $result[$i+1]['value']) {
              $equal_count++;
              } else {
              break;
              }
              $i++;
              }
              echo 'eq'. $equal_count;
              echo '<br />';
              if     ($equal_count == 0) {
              //i.e 70% 70% -60% 60%  $equal_count = 1 only we get the first 2 options
              if (($result[0]['value'] == $result[1]['value'])  &&  ($result[2]['value'] == $result[3]['value'])) {
              $group_cant = 1;
              } else {
              // By default we chose the highest 3
              $group_cant=2;
              }
              } elseif ($equal_count == 2) {
              $group_cant = 2;
              } else {
              $group_cant = -1;
              }
             */

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
                if ($equal_count === 0 || $equal_count === 1) {
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
                        if ($group_cant == 2 && $i == $group_cant) {
                            $group2 = $groups[0];
                            $secondary .= " OR ( survey_group_sec1 = '$group1' AND  survey_group_sec2 = '$group2') ";
                            $secondary .= " OR ( survey_group_sec1 = '$group2' AND survey_group_sec2 = '$group1' ) ";
                            $combi .= $group1.' - '.$group2." or ".$group2.' - '.$group1.'<br />';
                        } else {
                            if ($i != 0) {
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
                        count($_SESSION['page_questions_sec'] == 0)
                    ) {
                        $sql = "SELECT * FROM $table_survey_question
                                 WHERE
                                    c_id = $course_id AND
                                    survey_id = '".$my_survey_id."' AND
                                    ($secondary )
                                 ORDER BY sort ASC";
                        $result = Database::query($sql);
                        $counter = 0;
                        while ($row = Database::fetch_array($result, 'ASSOC')) {
                            if ($survey_data['one_question_per_page'] == 1) {
                                $paged_questions_sec[$counter][] = $row['question_id'];
                                $counter++;
                            } elseif ($row['type'] == 'pagebreak') {
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
                    if ($shuffle == '') {
                        $shuffle = ' BY survey_question.sort, survey_question_option.sort ASC ';
                    }
                    $val = (int) $_POST['personality'];
                    if (is_array($paged_questions_sec)) {
                        $sql = "SELECT
                                    survey_question.survey_group_sec1,
                                    survey_question.survey_group_sec2,
                                    survey_question.survey_group_pri,
                                    survey_question.question_id,
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
                                ON survey_question.question_id = survey_question_option.question_id AND
                                survey_question_option.c_id = $course_id
                                WHERE
                                    survey_question NOT LIKE '%{{%' AND
                                    survey_question.survey_id = '".$my_survey_id."' AND
                                    survey_question.c_id = $course_id AND
                                    survey_question.question_id IN (".implode(',', $paged_questions_sec[$val]).")
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
                        echo get_lang('SurveyUndetermined');
                    }
                } else {
                    echo get_lang('SurveyUndetermined');
                }
            } else {
                echo get_lang('SurveyUndetermined');
            }
        } else {
            // We need this variable only in the 2nd set of questions when personality is set.
            Session::erase('page_questions_sec');
            $paged_questions_sec = [];

            // Only the questions from the basic group
            // the 50 questions A B C D E F G
            $order_sql = $shuffle;
            if ($shuffle == '') {
                $order_sql = ' BY question_id ';
            }

            if (empty($_SESSION['paged_questions'])) {
                $sql = "SELECT * FROM $table_survey_question
                        WHERE
                            c_id = $course_id AND
                            survey_id = '".intval($survey_invitation['survey_id'])."' AND
                            survey_group_sec1='0' AND
                            survey_group_sec2='0'
                        ORDER ".$order_sql." ";
                $result = Database::query($sql);
                $counter = 0;
                while ($row = Database::fetch_array($result, 'ASSOC')) {
                    if ($survey_data['one_question_per_page'] == 1) {
                        $paged_questions[$counter][] = $row['question_id'];
                        $counter++;
                    } else {
                        if ($row['type'] == 'pagebreak') {
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
            if ($shuffle == '') {
                $order_sql = ' BY survey_question.sort, survey_question_option.sort ASC ';
            }
            $val = $_GET['show'];
            $result = null;
            if ($val != '') {
                $imploded = Database::escape_string(implode(',', $paged_questions[$val]));
                if ($imploded != '') {
                    // The answers are always in the same order NO shuffle
                    $order_sql = ' BY survey_question.sort, survey_question_option.sort ASC ';
                    $sql = "SELECT
                                survey_question.survey_group_sec1,
                                survey_question.survey_group_sec2,
                                survey_question.survey_group_pri,
                                survey_question.question_id,
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
                            ON survey_question.question_id = survey_question_option.question_id AND
                            survey_question_option.c_id = $course_id
                            WHERE
                                survey_question NOT LIKE '%{{%' AND
                                survey_question.survey_id = '".intval($survey_invitation['survey_id'])."' AND
                                survey_question.c_id = $course_id  AND
                                survey_question.question_id IN (".$imploded.")
                            ORDER $order_sql ";
                    $result = Database::query($sql);
                    $question_counter_max = Database::num_rows($result);
                }
            }

            if (!is_null($result)) {
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
        echo get_lang('ErrorSurveyTypeUnknown');
    }
}

$numberOfPages = SurveyManager::getCountPages($survey_data);

// Displaying the form with the questions
$show = 0;
if (isset($_GET['show']) && $_GET['show'] != '') {
    $show = (int) $_GET['show'] + 1;
}

$displayFinishButton = true;
if (isset($_GET['show']) && $_GET['show'] != '') {
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
$url = api_get_self().'?cidReq='.$courseInfo['code'].
    '&id_session='.$sessionId.
    $add_parameters.
    '&course='.$g_c.
    '&invitationcode='.$g_ic.
    '&show='.$show;
if (!empty($_GET['language'])) {
    $lang = Security::remove_XSS($_GET['language']);
    $url .= '&language='.$lang;
}
if (!empty($lpItemId) && $allowSurveyInLp) {
    $url .= '&lp_item_id='.$lpItemId.'&origin=learnpath';
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
if (SurveyManager::hasDependency($survey_data)) {
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
        $form->addHtml('<div>'.Security::remove_XSS($question['survey_question']).'</div>');

        $userAnswerData = SurveyUtil::get_answers_of_question_by_user($question['survey_id'], $question['question_id'], $lpItemId);
        $finalAnswer = null;

        if (!empty($userAnswerData[$user_id])) {
            $userAnswer = $userAnswerData[$user_id];
            switch ($question['type']) {
                case 'score':
                    $finalAnswer = [];
                    foreach ($userAnswer as $userChoice) {
                        list($choiceId, $choiceValue) = explode('*', $userChoice);
                        $finalAnswer[$choiceId] = $choiceValue;
                    }
                    break;
                case 'percentage':
                    list($choiceId, $choiceValue) = explode('*', current($userAnswer));
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
if ($survey_data['survey_type'] == '0') {
    if ($survey_data['show_form_profile'] == 0) {
        // The normal survey as always
        if ($show < $numberOfPages) {
            if ($show == 0) {
                $form->addButton(
                    'next_survey_page',
                    get_lang('StartSurvey'),
                    'arrow-right',
                    'success'
                );
            } else {
                // see GH#3582
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
                get_lang('FinishSurvey'),
                'arrow-right',
                'success'
            );
        }
    } else {
        // The normal survey as always but with the form profile
        if (isset($_GET['show'])) {
            $numberOfPages = count($paged_questions);
            if ($show < $numberOfPages) {
                if ($show == 0) {
                    $form->addButton(
                        'next_survey_page',
                        get_lang('StartSurvey'),
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
                    get_lang('FinishSurvey'),
                    'arrow-right',
                    'success'
                );
            }
        }
    }
} elseif ($survey_data['survey_type'] == '1') {
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

        if ($personality == 0) {
            if (($show <= $numberOfPages) || !$_GET['show']) {
                $form->addButton('next_survey_page', get_lang('Next'), 'arrow-right', 'success');
                if ($survey_data['one_question_per_page'] == 0) {
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

        if ($show > $numberOfPages && $_GET['show'] && $personality == 0) {
            $form->addHidden('personality', $personality);
        } elseif ($personality > 0) {
            if ($survey_data['one_question_per_page'] == 1) {
                if ($show >= $numberOfPages) {
                    $form->addButton('finish_survey', get_lang('FinishSurvey'), 'arrow-right', 'success');
                } else {
                    $form->addHidden('personality', $personality);
                    $form->addButton('next_survey_page', get_lang('Next'), 'arrow-right', 'success');
                }
            } else {
                // if the personality test hidden input was set.
                $form->addButton('finish_survey', get_lang('FinishSurvey'), 'arrow-right');
            }
        }
    } elseif ($survey_data['form_fields'] == '') {
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

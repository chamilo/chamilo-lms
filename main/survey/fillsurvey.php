<?php
/* For licensing terms, see /license.txt */

/**
* @package chamilo.survey
* @author unknown, the initial survey that did not make it in 1.8 because of bad code
* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University: cleanup, refactoring and rewriting large parts of the code
* @author Julio Montoya Armas <gugli100@gmail.com>, Chamilo: Personality Test modification and rewriting large parts of the code as well
 * @todo check if the user already filled the survey and if this is the case then the answers have to be updated and not stored again.
* @todo performance could be improved if not the survey_id was stored with the invitation but the survey_code
 */
// Unsetting the course id (because it is in the URL)
if (!isset($_GET['cidReq'])) {
    $cidReset = true;
} else {
    $_cid = $_GET['cidReq'];
}

// Including the global initialization file
require_once __DIR__.'/../inc/global.inc.php';

// Breadcrumbs
if (!empty($_user)) {
    $interbreadcrumb[] = array(
        'url' => api_get_path(WEB_CODE_PATH).'survey/survey_list.php?cidReq='.Security::remove_XSS($_GET['course']),
        'name' => get_lang('SurveyList')
    );
}

// Database table definitions
$table_survey = Database::get_course_table(TABLE_SURVEY);
$table_survey_answer = Database::get_course_table(TABLE_SURVEY_ANSWER);
$table_survey_question = Database::get_course_table(TABLE_SURVEY_QUESTION);
$table_survey_question_option = Database::get_course_table(TABLE_SURVEY_QUESTION_OPTION);
$table_survey_invitation = Database::get_course_table(TABLE_SURVEY_INVITATION);
$table_user = Database::get_main_table(TABLE_MAIN_USER);

// Check if user is anonymous or not
if (api_is_anonymous(api_get_user_id(), true)) {
    $isAnonymous = true;
} else {
    $isAnonymous = false;
}

// getting all the course information
if (isset($_GET['course'])) {
    $course_info = api_get_course_info($_GET['course']);
} else {
    $course_info = api_get_course_info();
}

if (empty($course_info)) {
    api_not_allowed();
}

$course_id = $course_info['real_id'];
$surveyCode = isset($_GET['scode']) ? Database::escape_string($_GET['scode']) : '';

if ($surveyCode != "") {
    // Firstly we check if this survey is ready for anonymous use:
    $sql = "SELECT anonymous FROM $table_survey
            WHERE c_id = $course_id AND code ='".$surveyCode."'";
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

// Start auto-invitation feature FS#3403 (all-users-can-do-the-survey-URL handling)
if ($invitationcode == 'auto' && isset($_GET['scode'])) {
    $userid = api_get_user_id();
    // Survey_code of the survey
    $surveyCode = $_GET['scode'];
    if ($isAnonymous) {
        $autoInvitationcode = "auto-ANONY_".md5(time())."-$surveyCode";
    } else {
        // New invitation code from userid
        $autoInvitationcode = "auto-$userid-$surveyCode";
    }

    // The survey code must exist in this course, or the URL is invalid
    $sql = "SELECT * FROM $table_survey
            WHERE c_id = $course_id AND code = '".Database::escape_string($surveyCode)."'";
    $result = Database::query($sql);
    if (Database :: num_rows($result) > 0) {
        // Check availability
        $row = Database :: fetch_array($result, 'ASSOC');
        $tempdata = SurveyManager :: get_survey($row['survey_id']);

        check_time_availability($tempdata);
        // Check for double invitation records (insert should be done once)
        $sql = "SELECT user
                FROM $table_survey_invitation
                WHERE
                    c_id = $course_id AND
                    invitation_code = '".Database::escape_string($autoInvitationcode)."'";
        $result = Database::query($sql);
        $now = api_get_utc_datetime();
        if (Database :: num_rows($result) == 0) {
            $params = [
                'c_id' => $course_id,
                'survey_code' => $surveyCode,
                'user' => $userid,
                'invitation_code' => $autoInvitationcode,
                'invitation_date' => $now,
            ];
            Database::insert($table_survey_invitation, $params);
        }
        // From here we use the new invitationcode auto-userid-surveycode string
        $_GET['invitationcode'] = $autoInvitationcode;
        $invitationcode = $autoInvitationcode;
    }
}

// Now we check if the invitation code is valid
$sql = "SELECT * FROM $table_survey_invitation
        WHERE
            c_id = $course_id AND
            invitation_code = '".Database :: escape_string($invitationcode)."'";
$result = Database::query($sql);
if (Database::num_rows($result) < 1) {
    api_not_allowed(true, get_lang('WrongInvitationCode'));
}

$survey_invitation = Database::fetch_array($result, 'ASSOC');

// Now we check if the user already filled the survey
if (
    !isset($_POST['finish_survey']) &&
    (
        $isAnonymous &&
        isset($_SESSION['surveyuser']) &&
        SurveyUtil::isSurveyAnsweredFlagged($survey_invitation['survey_code'], $survey_invitation['c_id'])
    ) ||
    ($survey_invitation['answered'] == 1 && !isset($_GET['user_id']))
) {
    api_not_allowed(true, get_lang('YouAlreadyFilledThisSurvey'));
}

// Checking if there is another survey with this code.
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
        // Header
        Display :: display_header(get_lang('ToolSurvey'));
        echo '<form id="language" name="language" method="POST" action="'.api_get_self().'?course='.Security::remove_XSS($_GET['course']).'&invitationcode='.Security::remove_XSS($_GET['invitationcode']).'&cidReq='.Security::remove_XSS($_GET['cidReq']).'">';
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
$survey_data['survey_id'] = $survey_invitation['survey_id'];

// Storing the answers
if (count($_POST) > 0) {
    if ($survey_data['survey_type'] === '0') {
        // Getting all the types of the question
        // (because of the special treatment of the score question type
        $sql = "SELECT * FROM $table_survey_question
                WHERE
                    c_id = $course_id AND
                    survey_id = '".intval($survey_invitation['survey_id'])."'";
        $result = Database::query($sql);

        while ($row = Database::fetch_array($result, 'ASSOC')) {
            $types[$row['question_id']] = $row['type'];
        }

        // Looping through all the post values
        foreach ($_POST as $key => & $value) {
            // If the post value key contains the string 'question' then it is an answer on a question
            if (strpos($key, 'question') !== false && ($key != '_qf__question')) {
                // Finding the question id by removing 'question'
                $survey_question_id = str_replace('question', '', $key);
                // If not question ID was defined, we're on the start
                // screen or something else that doesn't require
                // saving an answer
                if (empty($survey_question_id)) {
                    continue;
                }
                /* If the post value is an array then we have a multiple response question or a scoring question type
                remark: when it is a multiple response then the value of the array is the option_id
                when it is a scoring question then the key of the array is the option_id and the value is the value
                */
                if (is_array($value)) {

                    SurveyUtil::remove_answer(
                        $survey_invitation['user'],
                        $survey_invitation['survey_id'],
                        $survey_question_id,
                        $course_id
                    );

                    foreach ($value as $answer_key => & $answer_value) {
                        if ($types[$survey_question_id] == 'score') {
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
                            $survey_data
                        );
                    }
                } else {
                    // All the other question types (open question, multiple choice, percentage, ...)
                    if (isset($types[$survey_question_id]) &&
                        $types[$survey_question_id] == 'percentage') {
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
                            $types[$survey_question_id] == 'open'
                        ) {
                            $option_value = $value;
                        }
                    }

                    $survey_question_answer = $value;

                    SurveyUtil::remove_answer(
                        $survey_invitation['user'],
                        $survey_invitation['survey_id'],
                        $survey_question_id,
                        $course_id
                    );

                    SurveyUtil::store_answer(
                        $survey_invitation['user'],
                        $survey_invitation['survey_id'],
                        $survey_question_id,
                        $value,
                        $option_value,
                        $survey_data
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
        foreach ($_POST as $key => & $value) {
            // If the post value key contains the string 'question' then it is an answer to a question
            if (strpos($key, 'question') !== false) {
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
                //$option_value = 0;
                $survey_question_answer = $value;

                // We save the answer after making sure that a possible previous attempt is deleted
                SurveyUtil::remove_answer(
                    $survey_invitation['user'],
                    $survey_invitation['survey_id'],
                    $survey_question_id,
                    $course_id
                );

                SurveyUtil::store_answer(
                    $survey_invitation['user'],
                    $survey_invitation['survey_id'],
                    $survey_question_id,
                    $value,
                    $option_value,
                    $survey_data
                );
            }
        }
    } else {
        // In case it's another type than 0 or 1

        die(get_lang('ErrorSurveyTypeUnknown'));
    }
}

$user_id = api_get_user_id();

if ($user_id == 0) {
    $user_id = $survey_invitation['user'];
}
$user_data = api_get_user_info($user_id);

if ($survey_data['form_fields'] != '' &&
    $survey_data['anonymous'] == 0 && is_array($user_data)
) {
    $form_fields = explode('@', $survey_data['form_fields']);
    $list = array();
    foreach ($form_fields as $field) {
        $field_value = explode(':', $field);
        if (isset($field_value[1]) && $field_value[1] == 1) {
            if ($field_value[0] != '') {
                $val = api_substr($field_value[0], 8, api_strlen($field_value[0]));
                $list[$val] = 1;
            }
        }
    }

    // We use the same form as in auth/profile.php
    $form = new FormValidator(
        'profile',
        'post',
        api_get_self()."?".str_replace('&show_form=1', '&show_form=1', Security::remove_XSS($_SERVER['QUERY_STRING']))
    );

    if (api_is_western_name_order()) {
        if (isset($list['firstname']) && $list['firstname'] == 1) {
            //FIRST NAME
            $form->addElement('text', 'firstname', get_lang('FirstName'), array('size' => 40));
            if (api_get_setting('profile', 'name') !== 'true') {
                $form->freeze(array('firstname'));
            }
            $form->applyFilter(array('firstname'), 'stripslashes');
            $form->applyFilter(array('firstname'), 'trim');
            $form->addRule('firstname', get_lang('ThisFieldIsRequired'), 'required');
        }
        if (isset($list['lastname']) && $list['lastname'] == 1) {
            //    LAST NAME
            $form->addElement('text', 'lastname', get_lang('LastName'), array('size' => 40));
            if (api_get_setting('profile', 'name') !== 'true') {
                $form->freeze(array('lastname'));
            }
            $form->applyFilter(array('lastname'), 'stripslashes');
            $form->applyFilter(array('lastname'), 'trim');
            $form->addRule('lastname', get_lang('ThisFieldIsRequired'), 'required');
        }
    } else {
        if (isset($list['lastname']) && $list['lastname'] == 1) {
            //    LAST NAME
            $form->addElement('text', 'lastname', get_lang('LastName'), array('size' => 40));
            if (api_get_setting('profile', 'name') !== 'true') {
                $form->freeze(array('lastname'));
            }
            $form->applyFilter(array('lastname'), 'stripslashes');
            $form->applyFilter(array('lastname'), 'trim');
            $form->addRule('lastname', get_lang('ThisFieldIsRequired'), 'required');
        }
        if (isset($list['firstname']) && $list['firstname'] == 1) {
            //FIRST NAME
            $form->addElement('text', 'firstname', get_lang('FirstName'), array('size' => 40));
            if (api_get_setting('profile', 'name') !== 'true') {
                $form->freeze(array('firstname'));
            }
            $form->applyFilter(array('firstname'), 'stripslashes');
            $form->applyFilter(array('firstname'), 'trim');
            $form->addRule('firstname', get_lang('ThisFieldIsRequired'), 'required');
        }
    }

    if (isset($list['official_code']) && $list['official_code'] == 1) {
        //    OFFICIAL CODE
        if (CONFVAL_ASK_FOR_OFFICIAL_CODE) {
            $form->addElement('text', 'official_code', get_lang('OfficialCode'), array('size' => 40));
            if (api_get_setting('profile', 'officialcode') !== 'true') {
                $form->freeze('official_code');
            }
            $form->applyFilter('official_code', 'stripslashes');
            $form->applyFilter('official_code', 'trim');
            if (api_get_setting('registration', 'officialcode') == 'true' &&
                api_get_setting('profile', 'officialcode') == 'true'
            ) {
                $form->addRule('official_code', get_lang('ThisFieldIsRequired'), 'required');
            }
        }
    }

    if (isset($list['email']) && $list['email'] == 1) {
        //    EMAIL
        $form->addElement('text', 'email', get_lang('Email'), array('size' => 40));
        if (api_get_setting('profile', 'email') !== 'true') {
            $form->freeze('email');
        }
        $form->applyFilter('email', 'stripslashes');
        $form->applyFilter('email', 'trim');
        if (api_get_setting('registration', 'email') == 'true') {
            $form->addRule('email', get_lang('ThisFieldIsRequired'), 'required');
        }
        $form->addRule('email', get_lang('EmailWrong'), 'email');
    }

    if (isset($list['phone']) && $list['phone'] == 1) {
        //    PHONE
        $form->addElement('text', 'phone', get_lang('Phone'), array('size' => 20));
        if (api_get_setting('profile', 'phone') !== 'true') {
            $form->freeze('phone');
        }
        $form->applyFilter('phone', 'stripslashes');
        $form->applyFilter('phone', 'trim');
        if (api_get_setting('profile', 'phone') == 'true') {
            $form->addRule('phone', get_lang('ThisFieldIsRequired'), 'required');
        }
    }

    if (isset($list['language']) && $list['language'] == 1) {
        // LANGUAGE
        $form->addSelectLanguage('language', get_lang('Language'));
        if (api_get_setting('profile', 'language') !== 'true') {
            $form->freeze('language');
        }
        if (api_get_setting('profile', 'language') == 'true') {
            $form->addRule('language', get_lang('ThisFieldIsRequired'), 'required');
        }
    }

    // EXTRA FIELDS
    $extraField = new ExtraField('user');
    $returnParams = $extraField->addElements($form, api_get_user_id());

    $jquery_ready_content = $returnParams['jquery_ready_content'];

// the $jquery_ready_content variable collects all functions that will be load in the $(document).ready javascript function
    $htmlHeadXtra[] = '<script>
    $(document).ready(function(){
        '.$jquery_ready_content.'
    });
    </script>';

    $form->addButtonNext(get_lang('Next'));
    $form->setDefaults($user_data);
}

// Checking time availability
check_time_availability($survey_data);

// Header
Display :: display_header(get_lang('ToolSurvey'));

// Displaying the survey title and subtitle (appears on every page)
echo '<div class="survey-block">';
echo '<div id="survey_title">';
echo Display::return_icon(
    'statistics.png',
    get_lang('CreateNewSurvey'),
    array('style'=>'display:inline-block; margin-right:5px;'),
    ICON_SIZE_SMALL
);
echo strip_tags($survey_data['survey_title']).'</div>';
echo '<div id="survey_subtitle">'.strip_tags($survey_data['survey_subtitle']).'</div>';

// Displaying the survey introduction
if (!isset($_GET['show'])) {
    // The first thing we do is delete the session
    unset($_SESSION['paged_questions']);
    unset($_SESSION['page_questions_sec']);
    $paged_questions_sec = array();

    if (!empty($survey_data['survey_introduction'])) {
        echo '<div id="survey_content" class="survey_content">'.$survey_data['survey_introduction'].'</div>';
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
                $extras = array();
                // Build SQL query
                $sql = "UPDATE $table_user SET";
                $update = false;
                $allowedFields = [
                    'firstname',
                    'lastname',
                    'official_code',
                    'email',
                    'phone',
                    'language'
                ];

                foreach ($user_data as $key => $value) {
                    if (in_array($key, $allowedFields)) {
                        $sql .= " $key = '".Database :: escape_string($value)."',";
                        $update = true;
                    }

                }
                // Remove trailing , from the query we have so far
                $sql = rtrim($sql, ',');

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
        unset($_SESSION['paged_questions']);
        unset($_SESSION['page_questions_sec']);
        $paged_questions_sec = array();
    } else {
        echo '<div id="survey_content" class="survey_content">'.get_lang('UpdateInformation').'</div>';
        // We unset the sessions
        unset($_SESSION['paged_questions']);
        unset($_SESSION['page_questions_sec']);
        $paged_questions_sec = array();
        $form->display();
    }
}

// Displaying the survey thanks message
if (isset($_POST['finish_survey'])) {
    Display::display_confirmation_message(get_lang('SurveyFinished'));
    echo $survey_data['survey_thanks'];

    SurveyManager::update_survey_answered(
        $survey_data,
        $survey_invitation['user'],
        $survey_invitation['survey_code']
    );

    SurveyUtil::flagSurveyAsAnswered($survey_invitation['survey_code'], $survey_invitation['c_id']);

    unset($_SESSION['paged_questions']);
    unset($_SESSION['page_questions_sec']);
    Display :: display_footer();
    exit();
}

// Sets the random questions
$shuffle = '';
if ($survey_data['shuffle'] == 1) {
    $shuffle = ' BY RAND() ';
}

if (isset($_GET['show']) || isset($_POST['personality'])) {
    // Getting all the questions for this page and add them to a
    // multidimensional array where the first index is the page.
    // As long as there is no pagebreak fount we keep adding questions to the page
    $questions_displayed = array();
    $counter = 0;
    $paged_questions = array();

    // If non-conditional survey
    if ($survey_data['survey_type'] === '0') {
        if (empty($_SESSION['paged_questions'])) {
            $sql = "SELECT * FROM $table_survey_question
                    WHERE c_id = $course_id AND survey_id = '".intval($survey_invitation['survey_id'])."'
                    ORDER BY sort ASC";
            $result = Database::query($sql);
            while ($row = Database::fetch_array($result, 'ASSOC')) {
                if ($row['type'] == 'pagebreak') {
                    $counter++;
                } else {
                    // ids from question of the current survey
                    $paged_questions[$counter][] = $row['question_id'];
                }
            }
            $_SESSION['paged_questions'] = $paged_questions;
        } else {
            $paged_questions = $_SESSION['paged_questions'];
        }

        // Redefinition of variables and session ids to fix issue of survey not
        //  showing questions - see support.chamilo.org #5529
        $course_id = $survey_invitation['c_id'];
        $_SESSION['_cid'] = $course_id;
        $_SESSION['_real_cid'] = $course_id;

        if (array_key_exists($_GET['show'], $paged_questions)) {
            if (isset($_GET['user_id'])) {

                // Get the user into survey answer table (user or anonymus)
                $my_user_id = ($survey_data['anonymous'] == 1) ? $_SESSION['surveyuser'] : api_get_user_id();

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
                            survey_question.survey_id = '".Database :: escape_string($survey_invitation['survey_id'])."' AND
                            survey_question.question_id NOT IN (
                                SELECT sa.question_id
                                FROM ".$table_survey_answer." sa
                                WHERE
                                    sa.user='".$my_user_id."') AND
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
                            survey_question_option.sort as option_sort
                        FROM $table_survey_question survey_question
                        LEFT JOIN $table_survey_question_option survey_question_option
                            ON survey_question.question_id = survey_question_option.question_id AND
                            survey_question_option.c_id = $course_id
                        WHERE
                            survey_question.survey_id = '".intval($survey_invitation['survey_id'])."' AND
                            survey_question.question_id IN (".implode(',', $paged_questions[$_GET['show']]).") AND
                            survey_question.c_id =  $course_id
                        ORDER BY survey_question.sort, survey_question_option.sort ASC";
            }

            $result = Database::query($sql);
            $question_counter_max = Database::num_rows($result);
            $counter = 0;
            $limit = 0;
            $questions = array();

            while ($row = Database :: fetch_array($result, 'ASSOC')) {

                // If the type is not a pagebreak we store it in the $questions array
                if ($row['type'] != 'pagebreak') {
                    $questions[$row['sort']]['question_id'] = $row['question_id'];
                    $questions[$row['sort']]['survey_id'] = $row['survey_id'];
                    $questions[$row['sort']]['survey_question'] = $row['survey_question'];
                    $questions[$row['sort']]['display'] = $row['display'];
                    $questions[$row['sort']]['type'] = $row['type'];
                    $questions[$row['sort']]['options'][$row['question_option_id']] = $row['option_text'];
                    $questions[$row['sort']]['maximum_score'] = $row['max_value'];
                } else {
                    // If the type is a pagebreak we are finished loading the questions for this page
                    break;
                }
                $counter++;
            }
        }
    } elseif ($survey_data['survey_type'] === '1') {
        $my_survey_id = intval($survey_invitation['survey_id']);
        $current_user = Database::escape_string($survey_invitation['user']);

        if (isset($_POST['personality'])) {
            // Compute the results to get the 3 groups nearest to the user's personality
            if ($shuffle == '') {
                $order = 'BY sort ASC ';
            } else {
                $order = $shuffle;
            }

            $answer_list = array();

            // Get current user results
            $results = array();
            $sql = "SELECT survey_group_pri, user, SUM(value) as value
                    FROM $table_survey_answer as survey_answer
                    INNER JOIN $table_survey_question as survey_question
                    ON (survey_question.question_id = survey_answer.question_id)
                    WHERE
                        survey_answer.survey_id='".$my_survey_id."' AND
                        survey_answer.user='".$current_user."' AND
                        survey_answer.c_id = $course_id AND
                        survey_question.c_id = $course_id AND
                    GROUP BY survey_group_pri
                    ORDER BY survey_group_pri
                    ";

            $result = Database::query($sql);
            while ($row = Database :: fetch_array($result)) {
                $answer_list['value'] = $row['value'];
                $answer_list['group'] = $row['survey_group_pri'];
                $results[] = $answer_list;
            }

            //echo '<br />'; print_r($results); echo '<br />';
            // Get the total score for each group of questions
            $totals = array();
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
            //echo '<pre>'; print_r($totals);

            $final_results = array();

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
            $result = array();
            $count_result = 0;
            foreach ($final_results as $key => & $sub_result) {
                $result[] = array('group' => $key, 'value' => $sub_result);
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
                    if (($result[0]['value'] == $result[1]['value']) && ($result[2]['value'] == $result[3]['value'])) {
                        $group_cant = 1;
                    }
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
                    elseif (($result[0]['value'] != $result[1]['value']) && ($result[1]['value'] == $result[2]['value']) && ($result[2]['value'] == $result[3]['value'])) {
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
                    /*
                      echo '<pre>';
                      echo 'Pair of Groups <br /><br />';
                      echo $combi;
                      echo '</pre>';
                     */
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
                            } else {
                                // ids from question of the current survey
                                $paged_questions_sec[$counter][] = $row['question_id'];
                            }
                        }
                        $_SESSION['paged_questions_sec'] = $paged_questions_sec;
                    } else {
                        $paged_questions_sec = $_SESSION['paged_questions_sec'];
                    }
                    //print_r($paged_questions_sec);

                    $paged_questions = $_SESSION['paged_questions']; // For the sake of pages counting
                    //$paged_questions = $paged_questions_sec; // For the sake of pages counting coming up at display time...

                    if ($shuffle == '') {
                        $shuffle = ' BY survey_question.sort, survey_question_option.sort ASC ';
                    }

                    //$val = 0;
                    //if ($survey_data['one_question_per_page'] == 0) {
                    $val = (int) $_POST['personality'];
                    //}
                    //echo '<pre>'; print_r($paged_questions_sec); echo '</pre>';
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
                                    survey_question.survey_id = '".$my_survey_id."' AND
                                    survey_question.c_id = $course_id AND
                                    survey_question.question_id IN (".implode(',', $paged_questions_sec[$val]).")
                                ORDER  $shuffle ";

                        $result = Database::query($sql);
                        $question_counter_max = Database::num_rows($result);
                        $counter = 0;
                        $limit = 0;
                        $questions = array();
                        while ($row = Database::fetch_array($result, 'ASSOC')) {
                            // If the type is not a pagebreak we store it in the $questions array
                            if ($row['type'] != 'pagebreak') {
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
            unset($_SESSION['page_questions_sec']);
            $paged_questions_sec = array();

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
                //echo '<br />'; echo '<br />';
                $result = Database::query($sql);
                $counter = 0;
                while ($row = Database::fetch_array($result, 'ASSOC')) {
                    if ($survey_data['one_question_per_page'] == 1) {
                        $paged_questions[$counter][] = $row['question_id'];
                        $counter++;
                    } else {
                        if ($row['type'] == 'pagebreak') {
                            $counter++;
                        } else {
                            // ids from question of the current survey
                            $paged_questions[$counter][] = $row['question_id'];
                        }
                    }
                }
                $_SESSION['paged_questions'] = $paged_questions;
            } else {
                $paged_questions = $_SESSION['paged_questions'];
            }

            //print_r($paged_questions);
            //print_r($paged_questions);
            //if (key_exists($_GET['show'], $paged_questions)) {
            $order_sql = $shuffle;
            if ($shuffle == '') {
                $order_sql = ' BY survey_question.sort, survey_question_option.sort ASC ';
            }

            //$val = 0;
            //if ($survey_data['one_question_per_page'] == 0) {
            $val = $_GET['show'];
            //}
            //echo '<pre>'; print_r($paged_questions); echo $val;

            $result = null;
            if ($val != '') {
                $imploded = implode(',', $paged_questions[$val]);
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
                            FROM $table_survey_question survey_question
                            LEFT JOIN $table_survey_question_option survey_question_option
                            ON survey_question.question_id = survey_question_option.question_id AND
                            survey_question_option.c_id = $course_id
                            WHERE
                                survey_question.survey_id = '".intval($survey_invitation['survey_id'])."' AND
                                survey_question.c_id = $course_id  AND
                                survey_question.question_id IN (".$imploded.")
                            ORDER $order_sql ";
                    $result = Database::query($sql);
                    $question_counter_max = Database :: num_rows($result);
                }
            }
            if (!is_null($result)) {
                $counter = 0;
                $limit = 0;
                $questions = array();
                while ($row = Database :: fetch_array($result, 'ASSOC')) {
                    // If the type is not a pagebreak we store it in the $questions array
                    if ($row['type'] != 'pagebreak') {
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
                    } else {
                        // If the type is a page break we are finished loading the questions for this page
                        break;
                    }
                    $counter++;
                }
            }
        }
    } else { // In case it's another type than 0 or 1
        echo get_lang('ErrorSurveyTypeUnknown');
    }
}

// Selecting the maximum number of pages
$sql = "SELECT * FROM $table_survey_question
        WHERE
            c_id = $course_id AND
            type='".Database::escape_string('pagebreak')."' AND
            survey_id='".intval($survey_invitation['survey_id'])."'";
$result = Database::query($sql);
$numberofpages = Database::num_rows($result) + 1;

// Displaying the form with the questions
if (isset($_GET['show'])) {
    $show = (int) $_GET['show'] + 1;
} else {
    $show = 0;
}

// Displaying the form with the questions
if (isset($_POST['personality'])) {
    $personality = (int) $_POST['personality'] + 1;
} else {
    $personality = 0;
}

// Displaying the form with the questions
$g_c = isset($_GET['course']) ? Security::remove_XSS($_GET['course']) : '';
$g_ic = isset($_GET['invitationcode']) ? Security::remove_XSS($_GET['invitationcode']) : '';
$g_cr = isset($_GET['cidReq']) ? Security::remove_XSS($_GET['cidReq']) : '';
$p_l = isset($_POST['language']) ? Security::remove_XSS($_POST['language']) : '';

$add_parameters = isset($_GET['user_id']) ? 'user_id='.intval($_GET['user_id']).'&amp;' : '';

$url = api_get_self().'?'.$add_parameters.'course='.$g_c.'&invitationcode='.$g_ic.'&show='.$show.'&cidReq='.$g_cr;
$form = new FormValidator('question', 'post', $url);
$form->addHidden('language', $p_l);

if (isset($questions) && is_array($questions)) {
    foreach ($questions as $key => & $question) {
        $ch_type = 'ch_'.$question['type'];
        $display = new $ch_type;
        // @todo move this in a function.
        $form->addHtml('<div class="survey_question_wrapper"><div class="survey_question">');
        $form->addHtml($question['survey_question']);
        $display->render($form, $question);
        $form->addHtml('</div></div>');
    }
}

if ($survey_data['survey_type'] === '0') {
    if ($survey_data['show_form_profile'] == 0) {
        // The normal survey as always
        if (($show < $numberofpages) || !$_GET['show']) {
            if ($show == 0) {
                $form->addButton('next_survey_page', get_lang('StartSurvey'), 'arrow-right', 'success', 'large');
            } else {
                $form->addButton('next_survey_page', get_lang('Next'), 'arrow-right');
            }
        }
        if ($show >= $numberofpages && $_GET['show']) {
            $form->addButton('finish_survey', get_lang('FinishSurvey'), 'arrow-right');
        }
    } else {
        // The normal survey as always but with the form profile
        if (isset($_GET['show'])) {
            $numberofpages = count($paged_questions);
            if (($show < $numberofpages) || !$_GET['show']) { //$show = $_GET['show'] + 1
                if ($show == 0) {
                    $form->addButton('next_survey_page', get_lang('StartSurvey'), 'arrow-right', 'success', 'large');
                } else {
                    $form->addButton('next_survey_page', get_lang('Next'), 'arrow-right');
                }
            }

            if ($show >= $numberofpages && $_GET['show']) {
                $form->addButton('finish_survey', get_lang('FinishSurvey'), 'arrow-right');
            }
        }
    }
} elseif ($survey_data['survey_type'] === '1') { //conditional/personality-test type survey
    if (isset($_GET['show']) || isset($_POST['personality'])) {
        $numberofpages = count($paged_questions);
        if (!empty($paged_questions_sec) && count($paged_questions_sec) > 0) {
            // In case we're in the second phase, also sum the second group questions
            $numberofpages += count($paged_questions_sec);
            //echo 'pagesec :';
        } else {
            // We need this variable only if personality == 1
            unset($_SESSION['page_questions_sec']);
            $paged_questions_sec = array();
        }

        if ($personality == 0) {
            if (($show <= $numberofpages) || !$_GET['show']) {
                $form->addButton('next_survey_page', get_lang('Next'), 'arrow-right');
                if ($survey_data['one_question_per_page'] == 0) {
                    if ($personality >= 0) {
                        $form->addHidden('personality', $personality);
                    }
                } else {
                    if ($personality > 0) {
                        $form->addHidden('personality', $personality);
                    }
                }

                if ($numberofpages == $show) {
                    $form->addHidden('personality', $personality);
                }
            }
        }

        if ($show > $numberofpages && $_GET['show'] && $personality == 0) {
            $form->addHidden('personality', $personality);
        } elseif ($personality > 0) {
            if ($survey_data['one_question_per_page'] == 1) {
                if ($show >= $numberofpages) {
                    $form->addButton('finish_survey', get_lang('FinishSurvey'), 'arrow-right');
                } else {
                    $form->addHidden('personality', $personality);
                    $form->addButton('next_survey_page', get_lang('Next'), 'arrow-right');
                }
            } else {
                // if the personality test hidden input was set.
                $form->addButton('finish_survey', get_lang('FinishSurvey'), 'arrow-right');
            }
        }
    } elseif ($survey_data['form_fields'] == '') {
        // This is the case when the show_profile_form is true but there are not form_fields
        $form->addButton('next_survey_page', get_lang('Next'), 'arrow-right');
    } elseif (!is_array($user_data)) {
        // If the user is not registered in the platform we do not show the form to update his information
        $form->addButton('next_survey_page', get_lang('Next'), 'arrow-right');
    }
}
$form->display();

// Footer
Display :: display_footer();

/**
 * Check whether this survey has ended. If so, display message and exit rhis script
 */
function check_time_availability($surv_data) {

    $start_date = mktime(0, 0, 0, substr($surv_data['start_date'], 5, 2), substr($surv_data['start_date'], 8, 2), substr($surv_data['start_date'], 0, 4));
    $end_date = mktime(0, 0, 0, substr($surv_data['end_date'], 5, 2), substr($surv_data['end_date'], 8, 2), substr($surv_data['end_date'], 0, 4));
    $cur_date = time();

    if ($cur_date < $start_date) {
        api_not_allowed(
            true,
            Display:: return_message(
                get_lang('SurveyNotAvailableYet'),
                'warning',
                false
            )
        );
    }

    if ($cur_date > $end_date) {
        api_not_allowed(
            true,
            Display:: return_message(
                get_lang('SurveyNotAvailableAnymore'),
                'warning',
                false
            )
        );
    }
}

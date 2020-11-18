<?php

/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University: cleanup, refactoring and rewriting large parts of
 *         the code
 * @author Julio Montoya Armas <gugli100@gmail.com>, Chamilo: Personality Test modification and rewriting large parts
 *         of the code
 *
 * @todo   use quickforms for the forms
 */
if (!isset($_GET['cidReq'])) {
    $_GET['cidReq'] = 'none'; // Prevent sql errors
    $cidReset = true;
}

require_once __DIR__.'/../inc/global.inc.php';
$this_section = SECTION_COURSES;
$current_course_tool = TOOL_SURVEY;
$currentUserId = api_get_user_id();

api_protect_course_script(true);
$action = isset($_REQUEST['action']) ? Security::remove_XSS($_REQUEST['action']) : '';

Event::event_access_tool(TOOL_SURVEY);

$logInfo = [
    'tool' => TOOL_SURVEY,
];
Event::registerLog($logInfo);

/** @todo
 * This has to be moved to a more appropriate place (after the display_header
 * of the code)
 */
$courseInfo = api_get_course_info();
$sessionId = api_get_session_id();
$isDrhOfCourse = CourseManager::isUserSubscribedInCourseAsDrh(
    $currentUserId,
    $courseInfo
);

$htmlHeadXtra[] = '<script>'.api_get_language_translate_html().'</script>';

if ($isDrhOfCourse) {
    Display::display_header(get_lang('SurveyList'));
    Display::display_introduction_section('survey', 'left');
    SurveyUtil::displaySurveyListForDrh();
    Display::display_footer();
    exit;
}

if (!api_is_allowed_to_edit(false, true)) {
    // Coach can see this
    Display::display_header(get_lang('SurveyList'));
    Display::display_introduction_section('survey', 'left');
    SurveyUtil::getSurveyList($currentUserId);
    Display::display_footer();
    exit;
}

$extend_rights_for_coachs = api_get_setting('extend_rights_for_coach_on_survey');

Session::erase('answer_count');
Session::erase('answer_list');
$tool_name = get_lang('SurveyList');
// Language variables
if (isset($_GET['search']) && 'advanced' === $_GET['search']) {
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'survey/survey_list.php',
        'name' => get_lang('SurveyList'),
    ];
    $tool_name = get_lang('SearchASurvey');
}

$listUrl = api_get_path(WEB_CODE_PATH).'survey/survey_list.php?'.api_get_cidreq();
$surveyId = isset($_GET['survey_id']) ? $_GET['survey_id'] : 0;

// Action handling: performing the same action on multiple surveys
if (isset($_POST['action']) && $_POST['action'] && isset($_POST['id']) && is_array($_POST['id'])) {
    if (!api_is_allowed_to_edit()) {
        api_not_allowed(true);
    }

    switch ($action) {
        case 'export_by_class':
            $surveyList = [];
            $course_id = api_get_course_int_id();
            $extraFieldValue = new ExtraFieldValue('survey');

            $table_survey_question = Database::get_course_table(TABLE_SURVEY_QUESTION);
            $table_survey_question_option = Database::get_course_table(TABLE_SURVEY_QUESTION_OPTION);
            $table_survey_answer = Database::get_course_table(TABLE_SURVEY_ANSWER);

            foreach ($_POST['id'] as $value) {
                $surveyData = SurveyManager::get_survey($value);
                $surveyId = $surveyData['survey_id'];
                if (empty($surveyData)) {
                    continue;
                }
                $surveyData['title'] = api_html_entity_decode(trim(strip_tags($surveyData['title'])));
                $groupData = $extraFieldValue->get_values_by_handler_and_field_variable(
                    $surveyId,
                    'group_id'
                );
                $groupTitle = '';
                if ($groupData && !empty($groupData['value'])) {
                    $groupInfo = GroupManager::get_group_properties($groupData['value']);
                    if ($groupInfo['name']) {
                        $groupTitle = api_html_entity_decode($groupInfo['name']);
                    }
                }
                $surveyData['group_title'] = $groupTitle;

                $firstUser = '';
                $table = Database::get_course_table(TABLE_SURVEY_INVITATION);
                $sql = "SELECT * FROM $table
                        WHERE
                            answered = 1 AND
                            c_id = $course_id AND
                            session_id = $sessionId AND
                            survey_code = '".Database::escape_string($surveyData['code'])."'
                        ";
                $result = Database::query($sql);
                $usersWithAnswers = [];
                while ($row = Database::fetch_array($result)) {
                    if (isset($usersWithAnswers[$row['user']])) {
                        continue;
                    }
                    $userInfo = api_get_user_info($row['user']);
                    $usersWithAnswers[$row['user']] = $userInfo;
                }

                $sql = "SELECT
			            survey_question.question_id,
			            survey_question.survey_id,
			            survey_question.survey_question,
			            survey_question.display,
			            survey_question.max_value,
			            survey_question.sort,
			            survey_question.type,
                        survey_question_option.question_option_id,
                        survey_question_option.option_text,
                        survey_question_option.sort as option_sort
					FROM $table_survey_question survey_question
					LEFT JOIN $table_survey_question_option survey_question_option
					ON
					    survey_question.question_id = survey_question_option.question_id AND
					    survey_question_option.c_id = $course_id
					WHERE
					    survey_question NOT LIKE '%{{%' AND
					    survey_question.survey_id = '".$surveyId."' AND
                        survey_question.c_id = $course_id
					ORDER BY survey_question.sort, survey_question_option.sort ASC";
                $result = Database::query($sql);
                $questionsOptions = [];
                while ($row = Database::fetch_array($result, 'ASSOC')) {
                    if ($row['type'] != 'pagebreak') {
                        $questionsOptions[$row['sort']]['question_id'] = $row['question_id'];
                        $questionsOptions[$row['sort']]['survey_id'] = $row['survey_id'];
                        $questionsOptions[$row['sort']]['survey_question'] = $row['survey_question'];
                        $questionsOptions[$row['sort']]['display'] = $row['display'];
                        $questionsOptions[$row['sort']]['type'] = $row['type'];
                        $questionsOptions[$row['sort']]['maximum_score'] = $row['max_value'];
                        $questionsOptions[$row['sort']]['options'][$row['question_option_id']] = $row['option_text'];
                    }
                }

                $sql = "SELECT * FROM $table_survey_answer
                        WHERE c_id = $course_id AND survey_id = $surveyId ";
                $userAnswers = [];
                $all_answers = [];
                $result = Database::query($sql);
                while ($answers_of_user = Database::fetch_array($result, 'ASSOC')) {
                    $userAnswers[$answers_of_user['user']][$surveyId][$answers_of_user['question_id']][] = $answers_of_user['option_id'];
                    $all_answers[$answers_of_user['user']][$answers_of_user['question_id']][] = $answers_of_user;
                }

                foreach ($questionsOptions as $question) {
                    foreach ($usersWithAnswers as $userData) {
                        $userIdItem = $userData['user_id'];
                        // If the question type is a scoring then we have to format the answers differently
                        switch ($question['type']) {
                            /*case 'score':
                                $finalAnswer = [];
                                if (is_array($question) && is_array($all_answers)) {
                                    foreach ($all_answers[$question['question_id']] as $key => &$answer_array) {
                                        $finalAnswer[$answer_array['option_id']] = $answer_array['value'];
                                    }
                                }
                                break;*/
                            case 'multipleresponse':
                                $finalAnswer = isset($userAnswers[$userIdItem][$surveyId][$question['question_id']]) ? $userAnswers[$userIdItem][$surveyId][$question['question_id']] : '';
                                if (is_array($finalAnswer)) {
                                    $items = [];
                                    foreach ($finalAnswer as $option) {
                                        foreach ($question['options'] as $optionId => $text) {
                                            if ($option == $optionId) {
                                                $items[] = api_html_entity_decode(strip_tags($text));
                                            }
                                        }
                                    }
                                    $finalAnswer = implode(' - ', $items);
                                }
                                break;
                            default:
                                $finalAnswer = '';
                                if (isset($all_answers[$userIdItem][$question['question_id']])) {
                                    $finalAnswer = $all_answers[$userIdItem][$question['question_id']][0]['option_id'];
                                    foreach ($question['options'] as $optionId => $text) {
                                        if ($finalAnswer == $optionId) {
                                            $finalAnswer = api_html_entity_decode(strip_tags($text));
                                            break;
                                        }
                                    }
                                }
                                break;
                        }
                        $userAnswers[$userIdItem][$surveyId][$question['question_id']] = $finalAnswer;
                    }
                }

                $surveyData['user_with_answers'] = $usersWithAnswers;
                $surveyData['user_answers'] = $userAnswers;
                $surveyData['questions'] = SurveyManager::get_questions($surveyId);
                $surveyList[] = $surveyData;
            }

            $userGroup = new UserGroup();
            $options = [];
            $options['where'] = [' usergroup.course_id = ? ' => $course_id];
            $classes = $userGroup->getUserGroupInCourse($options, 0);

            @$spreadsheet = new PHPExcel();
            $counter = 0;
            foreach ($classes as $class) {
                $users = $userGroup->getUserListByUserGroup($class['id'], 'u.lastname ASC');
                $page = @$spreadsheet->createSheet($counter);
                @$page->setTitle($class['name']);
                $firstColumn = 3;
                $column = 3;
                $columnQuestion = 3;
                foreach ($surveyList as $survey) {
                    $questions = $survey['questions'];
                    $usersWithAnswers = $survey['user_with_answers'];
                    foreach ($usersWithAnswers as $userAnswer) {
                        $userId = $userAnswer['user_id'];
                        $cell = @$page->setCellValueByColumnAndRow(
                            $column,
                            1,
                            //$survey['title'].$survey['group_title'].' - '.$userAnswer['complete_name']
                            $survey['group_title'].' - '.$userAnswer['complete_name']
                        );
                        $coordinate = $page->getCellByColumnAndRow($column, 1)->getCoordinate();
                        /*if (!empty($coordinate)) {
                            $dimension = $cell->getColumnDimension($coordinate);
                            if ($dimension) {
                                $cell->getColumnDimension($coordinate)->setAutoSize(false);
                                $cell->getColumnDimension($coordinate)->setWidth("120");
                            }
                        }*/
                        //$page->getCellByColumnAndRow($column, 1)->getColumnDimension($coordinate)->setWidth(80);
                        $questionCounter = 0;
                        $firstCoordinate = $coordinate;
                        $lastCoordinate = '';
                        foreach ($questions as $question) {
                            $questionTitle = $question['question'];
                            if (strpos($question['question'], '{{')) {
                                $questionPosition = $column + $questionCounter;
                                $cell = @$page->setCellValueByColumnAndRow(
                                    $questionPosition,
                                    2,
                                    strip_tags($questionTitle),
                                    true
                                );

                                //$coordinate = @$page->getCellByColumnAndRow($questionPosition, 2)->getCoordinate();
                                //$page->getColumnDimension($coordinate);
                                /*$dimension = @$page->getColumnDimension($questionPosition);
                                if ($dimension) {
                                    $dimension->setAutoSize(true);
                                    $dimension->setWidth(200);
                                    //$cell->getColumnDimension($coordinate)->setAutoSize(false);
                                    //$cell->getColumnDimension($coordinate)->setWidth("120");
                                }*/

                                $coordinate = $page->getCellByColumnAndRow($questionPosition, 1)->getCoordinate();
                                $lastCoordinate = $coordinate;
                                $rowStudent = 3;
                                foreach ($users as $user) {
                                    $completeName = $user['firstname'].' '.$user['lastname'];
                                    $questionTitle = str_replace(
                                        '{{student_full_name}}',
                                        $completeName,
                                        $question['question']
                                    );

                                    foreach ($questions as $questionData) {
                                        if (strpos($questionData['question'], '{{') === false) {
                                            if ($questionTitle === $questionData['question']) {
                                                foreach ($survey['user_answers'][$userId][$survey['survey_id']] as $questionId => $answerData) {
                                                    if ($questionData['question_id'] == $questionId) {
                                                        if (is_array($answerData)) {
                                                            $answerData = implode(', ', $answerData);
                                                        }
                                                        @$page->setCellValueByColumnAndRow(
                                                            $questionPosition,
                                                            $rowStudent,
                                                            $answerData,
                                                            true
                                                        );
                                                        break;
                                                    }
                                                }
                                                break;
                                            }
                                        }
                                    }
                                    $rowStudent++;
                                }

                                $questionCounter++;
                                $columnQuestion++;
                            }
                        }
                        $column += $questionCounter;
                        $columnQuestion = $column;

                        if (!empty($lastCoordinate)) {
                            $page->mergeCells($firstCoordinate.':'.$lastCoordinate);
                        }
                    }

                    $questionPerUser = [];
                    foreach ($questions as $question) {
                        if (strpos($question['question'], '{{')) {
                        } else {
                            foreach ($users as $user) {
                                $completeName = $user['firstname'].' '.$user['lastname'];
                                if (strpos($question['question'], $completeName)) {
                                    break;
                                }
                                $questionPerUser[$user['id']][] = $question['question_id'];
                            }
                        }
                    }
                }

                $row = 2;
                foreach ($users as $user) {
                    $userId = $user['id'];
                    $columnUser = 0;
                    @$page->setCellValueByColumnAndRow($columnUser++, $row, $user['lastname']);
                    @$page->setCellValueByColumnAndRow($columnUser++, $row, $user['firstname']);
                    $row++;
                }
                $counter++;
            }
            $spreadsheet->setActiveSheetIndex(0);
            $file = api_get_path(SYS_ARCHIVE_PATH).uniqid('report', true);
            @$writer = new PHPExcel_Writer_Excel2007($spreadsheet);
            @$writer->save($file);

            DocumentManager::file_send_for_download($file, true, get_lang('Report').'.xlsx');
            break;
    }

    $exportList = [];
    foreach ($_POST['id'] as $value) {
        $surveyData = SurveyManager::get_survey($value);
        if (empty($surveyData)) {
            continue;
        }
        $surveyData['title'] = trim(strip_tags($surveyData['title']));

        switch ($action) {
            case 'export_all':
                $filename = $surveyData['code'].'.xlsx';
                $exportList[] = @SurveyUtil::export_complete_report_xls($surveyData, $filename, 0, true);
                break;
            case 'send_to_tutors':
                $result = SurveyManager::sendToTutors($value);
                if ($result) {
                    Display::addFlash(
                        Display::return_message(
                            get_lang('InvitationHasBeenSent').': '.$surveyData['title'],
                            'confirmation',
                            false
                        )
                    );
                } else {
                    Display::addFlash(
                        Display::return_message(
                            get_lang('InvitationHasBeenNotSent').': '.$surveyData['title'],
                            'warning',
                            false
                        )
                    );
                }
                break;
            case 'multiplicate':
                $result = SurveyManager::multiplicateQuestions($surveyData);
                $title = $surveyData['title'];
                if ($result) {
                    Display::addFlash(
                        Display::return_message(
                            sprintf(get_lang('SurveyXMultiplicated'), $title),
                            'confirmation',
                            false
                        )
                    );
                } else {
                    Display::addFlash(
                        Display::return_message(
                            sprintf(get_lang('SurveyXNotMultiplicated'), $title),
                            'warning',
                            false
                        )
                    );
                }
                break;
            case 'delete':
                // if the survey is shared => also delete the shared content
                if (is_numeric($surveyData['survey_share'])) {
                    SurveyManager::delete_survey($surveyData['survey_share'], true);
                }

                SurveyManager::delete_survey($value);
                Display::addFlash(
                    Display::return_message(get_lang('SurveysDeleted').': '.$surveyData['title'], 'confirmation', false)
                );
                break;
        }
    }

    if ($action === 'export_all') {
        $tempZipFile = api_get_path(SYS_ARCHIVE_PATH).api_get_unique_id().'.zip';
        $zip = new PclZip($tempZipFile);
        foreach ($exportList as $file) {
            $zip->add($file, PCLZIP_OPT_REMOVE_ALL_PATH);
        }

        DocumentManager::file_send_for_download(
            $tempZipFile,
            true,
            get_lang('SurveysWordInASCII').'-'.api_get_course_id().'-'.api_get_local_time().'.zip'
        );
        unlink($tempZipFile);
    }

    header('Location: '.$listUrl);
    exit;
}

switch ($action) {
    case 'send_to_tutors':
        if (!api_is_allowed_to_edit()) {
            api_not_allowed(true);
        }
        $result = SurveyManager::sendToTutors($surveyId);
        if ($result) {
            Display::addFlash(Display::return_message(get_lang('Updated'), 'confirmation', false));
        }

        header('Location: '.$listUrl);
        exit;
        break;
    case 'multiplicate':
        if (!api_is_allowed_to_edit()) {
            api_not_allowed(true);
        }
        $surveyData = SurveyManager::get_survey($surveyId);
        if (!empty($surveyData)) {
            SurveyManager::multiplicateQuestions($surveyData);
            Display::cleanFlashMessages();
            Display::addFlash(Display::return_message(get_lang('Updated'), 'confirmation', false));
        }
        header('Location: '.$listUrl);
        exit;
        break;
    case 'remove_multiplicate':
        if (!api_is_allowed_to_edit()) {
            api_not_allowed(true);
        }
        $surveyData = SurveyManager::get_survey($surveyId);
        if (!empty($surveyData)) {
            SurveyManager::removeMultiplicateQuestions($surveyData);
            Display::addFlash(Display::return_message(get_lang('Updated'), 'confirmation', false));
        }
        header('Location: '.$listUrl);
        exit;
        break;
    case 'copy_survey':
        if (!empty($surveyId) && api_is_allowed_to_edit()) {
            SurveyManager::copy_survey($surveyId);
            Display::addFlash(Display::return_message(get_lang('SurveyCopied'), 'confirmation', false));
            header('Location: '.$listUrl);
            exit;
        }
        break;
    case 'delete':
        if (!empty($surveyId)) {
            // Getting the information of the survey (used for when the survey is shared)
            $survey_data = SurveyManager::get_survey($surveyId);
            if (api_is_session_general_coach() && $sessionId != $survey_data['session_id']) {
                // The coach can't delete a survey not belonging to his session
                api_not_allowed();
            }
            // If the survey is shared => also delete the shared content
            if (isset($survey_data['survey_share']) &&
                is_numeric($survey_data['survey_share'])
            ) {
                SurveyManager::delete_survey($survey_data['survey_share'], true);
            }

            $return = SurveyManager::delete_survey($surveyId);

            if ($return) {
                Display::addFlash(Display::return_message(get_lang('SurveyDeleted'), 'confirmation', false));
            } else {
                Display::addFlash(Display::return_message(get_lang('ErrorOccurred'), 'error', false));
            }
            header('Location: '.$listUrl);
            exit;
        }
        break;
    case 'empty':
        $mysession = api_get_session_id();
        if (0 != $mysession) {
            if (!((api_is_session_general_coach() || api_is_platform_admin()) &&
                api_is_element_in_the_session(TOOL_SURVEY, $surveyId))) {
                // The coach can't empty a survey not belonging to his session
                api_not_allowed();
            }
        } else {
            if (!(api_is_course_admin() || api_is_platform_admin())) {
                api_not_allowed();
            }
        }
        $return = SurveyManager::empty_survey($surveyId);
        if ($return) {
            Display::addFlash(Display::return_message(get_lang('SurveyEmptied'), 'confirmation', false));
        } else {
            Display::addFlash(Display::return_message(get_lang('ErrorOccurred'), 'error', false));
        }
        header('Location: '.$listUrl);
        exit;
        break;
}

Display::display_header($tool_name, 'Survey');
Display::display_introduction_section('survey', 'left');

// Action handling: searching
if (isset($_GET['search']) && 'advanced' == $_GET['search']) {
    SurveyUtil::display_survey_search_form();
}

echo '<div class="actions">';
if (!api_is_session_general_coach() || 'true' == $extend_rights_for_coachs) {
    // Action links
    echo '<a href="'.api_get_path(WEB_CODE_PATH).'survey/create_new_survey.php?'.api_get_cidreq().'&amp;action=add">'.
        Display::return_icon('new_survey.png', get_lang('CreateNewSurvey'), '', ICON_SIZE_MEDIUM).'</a> ';
    $url = api_get_path(WEB_CODE_PATH).'survey/create_meeting.php?'.api_get_cidreq();
    echo Display::url(
        Display::return_icon('add_doodle.png', get_lang('CreateNewSurveyDoodle'), '', ICON_SIZE_MEDIUM),
        $url
    );
}
echo '<a href="'.api_get_self().'?'.api_get_cidreq().'&amp;search=advanced">'.
    Display::return_icon('search.png', get_lang('Search'), '', ICON_SIZE_MEDIUM).'</a>';
echo '</div>';

if (api_is_session_general_coach() && 'false' == $extend_rights_for_coachs) {
    SurveyUtil::display_survey_list_for_coach();
} else {
    SurveyUtil::display_survey_list();
}

Display::display_footer();

/* Bypass functions to make direct use from SortableTable possible */
function get_number_of_surveys()
{
    return SurveyUtil::get_number_of_surveys();
}

function get_survey_data($from, $number_of_items, $column, $direction)
{
    return SurveyUtil::get_survey_data($from, $number_of_items, $column, $direction);
}

function modify_filter($survey_id)
{
    return SurveyUtil::modify_filter($survey_id, false);
}

function modify_filter_drh($survey_id)
{
    return SurveyUtil::modify_filter($survey_id, true);
}

function get_number_of_surveys_for_coach()
{
    return SurveyUtil::get_number_of_surveys_for_coach();
}

function get_survey_data_for_coach($from, $number_of_items, $column, $direction)
{
    return SurveyUtil::get_survey_data_for_coach($from, $number_of_items, $column, $direction);
}

function modify_filter_for_coach($survey_id)
{
    return SurveyUtil::modify_filter_for_coach($survey_id);
}

function anonymous_filter($anonymous)
{
    return SurveyUtil::anonymous_filter($anonymous);
}

function get_survey_data_drh($from, $number_of_items, $column, $direction)
{
    return SurveyUtil::get_survey_data($from, $number_of_items, $column, $direction, true);
}

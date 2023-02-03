<?php

/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * Upload quiz: This script shows the upload quiz feature.
 */

// setting the help
$help_content = 'exercise_upload';
require_once __DIR__.'/../inc/global.inc.php';

api_protect_course_script(true);
$is_allowed_to_edit = api_is_allowed_to_edit(null, true);
$debug = false;
$origin = api_get_origin();
if (!$is_allowed_to_edit) {
    api_not_allowed(true);
}

$this_section = SECTION_COURSES;
$htmlHeadXtra[] = "<script>
$(function () {
    $('#user_custom_score').on('change', function () {
        if ($('#user_custom_score[type=\"checkbox\"]').prop('checked')) {
            $('#options').removeClass('hidden')
        } else {
            $('#options').addClass('hidden')
        }
    });
});
</script>";

// Action handling
lp_upload_quiz_action_handling();

$interbreadcrumb[] = [
    'url' => 'exercise.php?'.api_get_cidreq(),
    'name' => get_lang('Exercises'),
];

Display::display_header(get_lang('ImportExcelQuiz'), 'Exercises');

echo '<div class="actions">';
echo lp_upload_quiz_actions();
echo '</div>';

lp_upload_quiz_main();

function lp_upload_quiz_actions()
{
    $return = '<a href="exercise.php?'.api_get_cidreq().'">'.
        Display::return_icon(
            'back.png',
            get_lang('BackToExercisesList'),
            '',
            ICON_SIZE_MEDIUM
        ).'</a>';

    return $return;
}

function lp_upload_quiz_main()
{
    $lp_id = isset($_GET['lp_id']) ? (int) $_GET['lp_id'] : null;

    $form = new FormValidator(
        'upload',
        'POST',
        api_get_self().'?'.api_get_cidreq().'&lp_id='.$lp_id,
        '',
        ['enctype' => 'multipart/form-data']
    );
    $form->addElement('header', get_lang('ImportExcelQuiz'));
    $form->addElement('file', 'user_upload_quiz', get_lang('FileUpload'));

    $link = '<a href="../exercise/quiz_template.xls">'.
        Display::return_icon('export_excel.png', get_lang('DownloadExcelTemplate')).get_lang('DownloadExcelTemplate').'</a>';
    $form->addElement('label', '', $link);

    $table = new HTML_Table(['class' => 'table']);

    $tableList = [
        UNIQUE_ANSWER => get_lang('UniqueSelect'),
        MULTIPLE_ANSWER => get_lang('MultipleSelect'),
        MULTIPLE_ANSWER_DROPDOWN => get_lang('MultipleAnswerDropdown'),
        MULTIPLE_ANSWER_DROPDOWN_COMBINATION => get_lang('MultipleAnswerDropdownCombination'),
        FILL_IN_BLANKS => get_lang('FillBlanks'),
        FILL_IN_BLANKS_COMBINATION => get_lang('FillBlanksCombination'),
        MATCHING => get_lang('Matching'),
        FREE_ANSWER => get_lang('FreeAnswer'),
        GLOBAL_MULTIPLE_ANSWER => get_lang('GlobalMultipleAnswer'),
    ];

    $table->setHeaderContents(0, 0, get_lang('QuestionType'));
    $table->setHeaderContents(0, 1, '#');

    $row = 1;
    foreach ($tableList as $key => $label) {
        $table->setCellContents($row, 0, $label);
        $table->setCellContents($row, 1, $key);
        $row++;
    }
    $table = $table->toHtml();

    $form->addElement('label', get_lang('QuestionType'), $table);
    $form->addElement(
        'checkbox',
        'user_custom_score',
        null,
        get_lang('UseCustomScoreForAllQuestions'),
        ['id' => 'user_custom_score']
    );
    $form->addElement('html', '<div id="options" class="hidden">');
    $form->addElement('text', 'correct_score', get_lang('CorrectScore'));
    $form->addElement('text', 'incorrect_score', get_lang('IncorrectScore'));
    $form->addElement('html', '</div>');

    $form->addRule('user_upload_quiz', get_lang('ThisFieldIsRequired'), 'required');

    $form->addProgress();
    $form->addButtonUpload(get_lang('Upload'), 'submit_upload_quiz');
    $form->display();
}

/**
 * Handles a given Excel spreadsheets as in the template provided.
 */
function lp_upload_quiz_action_handling()
{
    if (!isset($_POST['submit_upload_quiz'])) {
        return;
    }

    $_course = api_get_course_info();

    if (empty($_course)) {
        return false;
    }

    $courseId = $_course['real_id'];
    // Get the extension of the document.
    $path_info = pathinfo($_FILES['user_upload_quiz']['name']);

    // Check if the document is an Excel document
    if (!in_array($path_info['extension'], ['xls', 'xlsx'])) {
        return;
    }

    // Variables
    $numberQuestions = 0;
    $question = [];
    $scoreList = [];
    $feedbackTrueList = [];
    $feedbackFalseList = [];
    $questionDescriptionList = [];
    $noNegativeScoreList = [];
    $questionTypeList = [];
    $answerList = [];
    $quizTitle = '';
    $objPHPExcel = PHPExcel_IOFactory::load($_FILES['user_upload_quiz']['tmp_name']);
    $objPHPExcel->setActiveSheetIndex(0);
    $worksheet = $objPHPExcel->getActiveSheet();
    $highestRow = $worksheet->getHighestRow(); // e.g. 10
    //$highestColumn = $worksheet->getHighestColumn(); // e.g 'F'

    $correctScore = isset($_POST['correct_score']) ? $_POST['correct_score'] : null;
    $incorrectScore = isset($_POST['incorrect_score']) ? $_POST['incorrect_score'] : null;
    $useCustomScore = isset($_POST['user_custom_score']) ? true : false;

    for ($row = 1; $row <= $highestRow; $row++) {
        $cellTitleInfo = $worksheet->getCellByColumnAndRow(0, $row);
        $cellDataInfo = $worksheet->getCellByColumnAndRow(1, $row);
        $cellScoreInfo = $worksheet->getCellByColumnAndRow(2, $row);
        $title = $cellTitleInfo->getValue();

        switch ($title) {
            case 'Quiz':
                $quizTitle = $cellDataInfo->getValue();
                break;
            case 'Question':
                $question[] = $cellDataInfo->getValue();
                // Search cell with Answer title
                $answerRow = $row;
                $continue = true;
                $answerIndex = 0;
                while ($continue) {
                    $answerRow++;
                    $answerInfoTitle = $worksheet->getCellByColumnAndRow(0, $answerRow);
                    $answerInfoData = $worksheet->getCellByColumnAndRow(1, $answerRow);
                    $answerInfoExtra = $worksheet->getCellByColumnAndRow(2, $answerRow);
                    $answerInfoTitle = $answerInfoTitle->getValue();
                    if (strpos($answerInfoTitle, 'Answer') !== false) {
                        $answerList[$numberQuestions][$answerIndex]['data'] = $answerInfoData->getValue();
                        $answerList[$numberQuestions][$answerIndex]['extra'] = $answerInfoExtra->getValue();
                    } else {
                        $continue = false;
                    }
                    $answerIndex++;

                    // To avoid loops
                    if ($answerIndex > 60) {
                        $continue = false;
                    }
                }

                // Search cell with question type
                $answerRow = $row;
                $continue = true;
                $questionTypeIndex = 0;
                while ($continue) {
                    $answerRow++;
                    $questionTypeTitle = $worksheet->getCellByColumnAndRow(0, $answerRow);
                    $questionTypeExtra = $worksheet->getCellByColumnAndRow(2, $answerRow);
                    $title = $questionTypeTitle->getValue();
                    if ($title === 'QuestionType') {
                        $questionTypeList[$numberQuestions] = $questionTypeExtra->getValue();
                        $continue = false;
                    }
                    if ($title === 'Question') {
                        $continue = false;
                    }
                    // To avoid loops
                    if ($questionTypeIndex > 60) {
                        $continue = false;
                    }
                    $questionTypeIndex++;
                }

                // Detect answers
                $numberQuestions++;
                break;
            case 'Score':
                $scoreList[] = $cellScoreInfo->getValue();
                break;
            case 'NoNegativeScore':
                $noNegativeScoreList[] = $cellScoreInfo->getValue();
                break;
            case 'Category':
                $categoryList[] = $cellDataInfo->getValue();
                break;
            case 'FeedbackTrue':
                $feedbackTrueList[] = $cellDataInfo->getValue();
                break;
            case 'FeedbackFalse':
                $feedbackFalseList[] = $cellDataInfo->getValue();
                break;
            case 'EnrichQuestion':
                $questionDescriptionList[] = $cellDataInfo->getValue();
                break;
        }
    }

    $propagateNegative = 0;
    if ($useCustomScore && !empty($incorrectScore)) {
        if ($incorrectScore < 0) {
            $propagateNegative = 1;
        }
    }

    $url = api_get_path(WEB_CODE_PATH).'exercise/upload_exercise.php?'.api_get_cidreq();

    if (empty($quizTitle)) {
        Display::addFlash(Display::return_message('ErrorImportingFile'), 'warning');
        api_location($url);
    }

    // Variables
    $type = 2;
    $random = $active = $results = $max_attempt = $expired_time = 0;
    // Make sure feedback is enabled (3 to disable), otherwise the fields
    // added to the XLS are not shown, which is confusing
    $feedback = 0;

    // Quiz object
    $exercise = new Exercise();
    $exercise->updateTitle($quizTitle);
    $exercise->updateExpiredTime($expired_time);
    $exercise->updateType($type);
    $exercise->setRandom($random);
    $exercise->active = $active;
    $exercise->updateResultsDisabled($results);
    $exercise->updateAttempts($max_attempt);
    $exercise->updateFeedbackType($feedback);
    $exercise->updatePropagateNegative($propagateNegative);
    $quiz_id = $exercise->save();

    if ($quiz_id) {
        // Import questions.
        for ($i = 0; $i < $numberQuestions; $i++) {
            // Question name
            $questionTitle = $question[$i];
            $myAnswerList = isset($answerList[$i]) ? $answerList[$i] : [];
            $description = isset($questionDescriptionList[$i]) ? $questionDescriptionList[$i] : '';
            $categoryId = null;
            if (isset($categoryList[$i]) && !empty($categoryList[$i])) {
                $categoryName = $categoryList[$i];
                $categoryId = TestCategory::get_category_id_for_title($categoryName, $courseId);
                if (empty($categoryId)) {
                    $category = new TestCategory();
                    $category->name = $categoryName;
                    $categoryId = $category->save();
                }
            }

            $question_description_text = '<p></p>';
            if (!empty($description)) {
                // Question description.
                $question_description_text = "<p>$description</p>";
            }

            // Unique answers are the only question types available for now
            // through xls-format import
            $question_id = null;
            if (isset($questionTypeList[$i]) && $questionTypeList[$i] != '') {
                $detectQuestionType = (int) $questionTypeList[$i];
            } else {
                $detectQuestionType = detectQuestionType($myAnswerList);
            }

            /** @var Question $answer */
            switch ($detectQuestionType) {
                case FREE_ANSWER:
                    $answer = new FreeAnswer();
                    break;
                case GLOBAL_MULTIPLE_ANSWER:
                    $answer = new GlobalMultipleAnswer();
                    break;
                case MULTIPLE_ANSWER:
                    $answer = new MultipleAnswer();
                    break;
                case MULTIPLE_ANSWER_DROPDOWN:
                    $answer = new MultipleAnswerDropdown();
                    break;
                case MULTIPLE_ANSWER_DROPDOWN_COMBINATION:
                    $answer = new MultipleAnswerDropdownCombination();
                    break;
                case FILL_IN_BLANKS:
                case FILL_IN_BLANKS_COMBINATION:
                    $answer = new FillBlanks();
                    $question_description_text = '';
                    break;
                case MATCHING:
                    $answer = new Matching();
                    break;
                case UNIQUE_ANSWER:
                default:
                    $answer = new UniqueAnswer();
                    break;
            }

            if ($questionTitle != '') {
                $question_id = $answer->create_question(
                    $quiz_id,
                    $questionTitle,
                    $question_description_text,
                    0, // max score
                    $answer->type
                );

                if (!empty($categoryId)) {
                    TestCategory::addCategoryToQuestion(
                        $categoryId,
                        $question_id,
                        $courseId
                    );
                }
            }
            switch ($detectQuestionType) {
                case GLOBAL_MULTIPLE_ANSWER:
                case MULTIPLE_ANSWER_DROPDOWN:
                case MULTIPLE_ANSWER_DROPDOWN_COMBINATION:
                case MULTIPLE_ANSWER:
                case UNIQUE_ANSWER:
                    $total = 0;
                    if (is_array($myAnswerList) && !empty($myAnswerList) && !empty($question_id)) {
                        $id = 1;
                        $objAnswer = new Answer($question_id, $courseId);
                        $globalScore = isset($scoreList[$i]) ? $scoreList[$i] : null;

                        // Calculate the number of correct answers to divide the
                        // score between them when importing from CSV
                        $numberRightAnswers = 0;
                        foreach ($myAnswerList as $answer_data) {
                            if (strtolower($answer_data['extra']) == 'x') {
                                $numberRightAnswers++;
                            }
                        }

                        foreach ($myAnswerList as $answer_data) {
                            $answerValue = $answer_data['data'];
                            $correct = 0;
                            $score = 0;
                            if (strtolower($answer_data['extra']) == 'x') {
                                $correct = 1;
                                $score = isset($scoreList[$i]) ? $scoreList[$i] : 0;
                                $comment = isset($feedbackTrueList[$i]) ? $feedbackTrueList[$i] : '';
                            } else {
                                $comment = isset($feedbackFalseList[$i]) ? $feedbackFalseList[$i] : '';
                                $floatVal = (float) $answer_data['extra'];
                                if (is_numeric($floatVal)) {
                                    $score = $answer_data['extra'];
                                }
                            }

                            if ($useCustomScore) {
                                if ($correct) {
                                    $score = $correctScore;
                                } else {
                                    $score = $incorrectScore;
                                }
                            }

                            // Fixing scores:
                            switch ($detectQuestionType) {
                                case GLOBAL_MULTIPLE_ANSWER:
                                    if ($correct) {
                                        $score = abs($scoreList[$i]);
                                    } else {
                                        if (isset($noNegativeScoreList[$i]) && $noNegativeScoreList[$i] == 'x') {
                                            $score = 0;
                                        } else {
                                            $score = -abs($scoreList[$i]);
                                        }
                                    }
                                    $score /= $numberRightAnswers;
                                    break;
                                case UNIQUE_ANSWER:
                                    break;
                                case MULTIPLE_ANSWER:
                                    if (!$correct) {
                                        //$total = $total - $score;
                                    }
                                    break;
                                case MULTIPLE_ANSWER_DROPDOWN_COMBINATION:
                                    $score = 0;
                                    break;
                            }

                            $objAnswer->createAnswer(
                                $answerValue,
                                $correct,
                                $comment,
                                $score,
                                $id
                            );
                            if ($correct) {
                                //only add the item marked as correct ( x )
                                $total += (float) $score;
                            }
                            $id++;
                        }

                        $objAnswer->save();

                        $questionObj = Question::read(
                            $question_id,
                            $_course
                        );

                        if ($questionObj) {
                            switch ($detectQuestionType) {
                                case GLOBAL_MULTIPLE_ANSWER:
                                case MULTIPLE_ANSWER_DROPDOWN_COMBINATION:
                                    $questionObj->updateWeighting($globalScore);
                                    break;
                                case UNIQUE_ANSWER:
                                case MULTIPLE_ANSWER:
                                default:
                                    $questionObj->updateWeighting($total);
                                    break;
                            }
                            $questionObj->save($exercise);
                        }
                    }
                    break;
                case FREE_ANSWER:
                    $globalScore = isset($scoreList[$i]) ? $scoreList[$i] : null;
                    $questionObj = Question::read($question_id, $_course);
                    if ($questionObj) {
                        $questionObj->updateWeighting($globalScore);
                        $questionObj->save($exercise);
                    }
                    break;
                case FILL_IN_BLANKS:
                case FILL_IN_BLANKS_COMBINATION:
                    $fillInScoreList = [];
                    $size = [];
                    $globalScore = 0;
                    foreach ($myAnswerList as $data) {
                        $score = isset($data['extra']) ? $data['extra'] : 0;
                        $globalScore += $score;
                        $fillInScoreList[] = $score;
                        $size[] = 200;
                    }

                    $scoreToString = implode(',', $fillInScoreList);
                    $sizeToString = implode(',', $size);

                    //<p>Texte long avec les [mots] à [remplir] mis entre [crochets]</p>::10,10,10:200.36363999999998,200,200:0@'
                    $answerValue = $description.'::'.$scoreToString.':'.$sizeToString.':0@';
                    $objAnswer = new Answer($question_id, $courseId);
                    $objAnswer->createAnswer(
                        $answerValue,
                        '', //$correct,
                        '', //$comment,
                        $globalScore,
                        1
                    );

                    $objAnswer->save();

                    $questionObj = Question::read($question_id, $_course);
                    if ($questionObj) {
                        $questionObj->updateWeighting($globalScore);
                        $questionObj->save($exercise);
                    }
                    break;
                case MATCHING:
                    $globalScore = isset($scoreList[$i]) ? $scoreList[$i] : null;
                    $position = 1;

                    $objAnswer = new Answer($question_id, $courseId);
                    foreach ($myAnswerList as $data) {
                        $option = isset($data['extra']) ? $data['extra'] : '';
                        $objAnswer->createAnswer($option, 0, '', 0, $position);
                        $position++;
                    }

                    $counter = 1;
                    foreach ($myAnswerList as $data) {
                        $value = isset($data['data']) ? $data['data'] : '';
                        $position++;
                        $objAnswer->createAnswer(
                            $value,
                            $counter,
                            ' ',
                            $globalScore,
                            $position
                        );
                        $counter++;
                    }
                    $objAnswer->save();
                    $questionObj = Question::read($question_id, $_course);
                    if ($questionObj) {
                        $questionObj->updateWeighting($globalScore);
                        $questionObj->save($exercise);
                    }
                    break;
            }
        }
    }

    $lpObject = Session::read('lpobject');

    if (!empty($lpObject)) {
        /** @var learnpath $oLP */
        $oLP = UnserializeApi::unserialize('lp', $lpObject);
        if (is_object($oLP)) {
            if ((empty($oLP->cc)) || $oLP->cc != api_get_course_id()) {
                $oLP = null;
                Session::erase('oLP');
                Session::erase('lpobject');
            } else {
                Session::write('oLP', $oLP);
            }
        }
    }
    Display::addFlash(Display::return_message(get_lang('FileImported')));

    if (isset($_SESSION['oLP']) && isset($_GET['lp_id'])) {
        $previous = $_SESSION['oLP']->select_previous_item_id();
        $parent = 0;
        // Add a Quiz as Lp Item
        $_SESSION['oLP']->add_item(
            $parent,
            $previous,
            TOOL_QUIZ,
            $quiz_id,
            $quizTitle,
            ''
        );
        // Redirect to home page for add more content
        header('Location: ../lp/lp_controller.php?'.api_get_cidreq().'&action=add_item&type=step&lp_id='.intval($_GET['lp_id']));
        exit;
    } else {
        $exerciseUrl = api_get_path(WEB_CODE_PATH).
            'exercise/admin.php?'.api_get_cidreq().'&exerciseId='.$quiz_id.'&session_id='.api_get_session_id();
        api_location($exerciseUrl);
    }
}

/**
 * @param array $answers_data
 *
 * @return int
 */
function detectQuestionType($answers_data)
{
    $correct = 0;
    $isNumeric = false;

    if (empty($answers_data)) {
        return FREE_ANSWER;
    }

    foreach ($answers_data as $answer_data) {
        if (strtolower($answer_data['extra']) == 'x') {
            $correct++;
        } else {
            if (is_numeric($answer_data['extra'])) {
                $isNumeric = true;
            }
        }
    }

    if ($correct == 1) {
        $type = UNIQUE_ANSWER;
    } else {
        if ($correct > 1) {
            $type = MULTIPLE_ANSWER;
        } else {
            $type = FREE_ANSWER;
        }
    }

    if ($type == MULTIPLE_ANSWER) {
        if ($isNumeric) {
            $type = MULTIPLE_ANSWER;
        } else {
            $type = GLOBAL_MULTIPLE_ANSWER;
        }
    }

    return $type;
}

if ($origin != 'learnpath') {
    //so we are not in learnpath tool
    Display::display_footer();
}

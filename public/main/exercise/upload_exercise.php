<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Enums\ActionIcon;
use ChamiloSession as Session;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Upload quiz: This script shows the upload quiz feature.
 */
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
$(function(){
    $('#user_custom_score').click(function() {
        $('#options').toggle();
    });
});
</script>";

// Action handling
lp_upload_quiz_action_handling();

$interbreadcrumb[] = [
    'url' => 'exercise.php?'.api_get_cidreq(),
    'name' => get_lang('Tests'),
];

Display::display_header(get_lang('Import quiz from Excel'), 'Exercises');

echo Display::toolbarAction('toolbar', [lp_upload_quiz_actions()]);

lp_upload_quiz_main();

function lp_upload_quiz_actions()
{
    return '<a href="exercise.php?'.api_get_cidreq().'">'.
        Display::getMdiIcon(ActionIcon::BACK, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Back to tests list')).'</a>';
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
    $form->addElement('header', get_lang('Import quiz from Excel'));
    $form->addElement('file', 'user_upload_quiz', get_lang('File upload'));

    $label = Display::getMdiIcon(ActionIcon::EXPORT_SPREADSHEET, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Download the Excel Template')).
        get_lang('Download the Excel Template');
    $link = '<a href="../exercise/quiz_template.xls">'.$label.'</a>';
    $form->addElement('label', '', $link);

    $table = new HTML_Table(['class' => 'table']);

    $tableList = [
        UNIQUE_ANSWER => get_lang('Multiple choice'),
        MULTIPLE_ANSWER => get_lang('Multiple answers'),
        FILL_IN_BLANKS => get_lang('Fill blanks or form'),
        MATCHING => get_lang('Matching'),
        FREE_ANSWER => get_lang('Open question'),
        GLOBAL_MULTIPLE_ANSWER => get_lang('Global multiple answer'),
    ];

    $table->setHeaderContents(0, 0, get_lang('Question type'));
    $table->setHeaderContents(0, 1, '#');

    $row = 1;
    foreach ($tableList as $key => $label) {
        $table->setCellContents($row, 0, $label);
        $table->setCellContents($row, 1, $key);
        $row++;
    }
    $table = $table->toHtml();

    $form->addElement('label', get_lang('Question type'), $table);
    $form->addElement(
        'checkbox',
        'user_custom_score',
        null,
        get_lang('Use custom score for all questions'),
        ['id' => 'user_custom_score']
    );
    $form->addElement('html', '<div id="options" style="display:none">');
    $form->addElement('text', 'correct_score', get_lang('Correct score'));
    $form->addElement('text', 'incorrect_score', get_lang('Incorrect score'));
    $form->addElement('html', '</div>');

    $form->addRule('user_upload_quiz', get_lang('Required field'), 'required');

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

    if (isset($_FILES['user_upload_quiz'])) {
        try {
            $objPHPExcel = IOFactory::load($_FILES['user_upload_quiz']['tmp_name']);
        } catch (\Exception $e) {
            return;
        }
    } else {
        return;
    }

    $objPHPExcel->setActiveSheetIndex(0);
    $worksheet = $objPHPExcel->getActiveSheet();
    $highestRow = $worksheet->getHighestRow(); // e.g. 10
    //  $highestColumn = $worksheet->getHighestColumn(); // e.g 'F'

    $correctScore = isset($_POST['correct_score']) ? $_POST['correct_score'] : null;
    $incorrectScore = isset($_POST['incorrect_score']) ? $_POST['incorrect_score'] : null;
    $useCustomScore = isset($_POST['user_custom_score']) ? true : false;

    for ($row = 1; $row <= $highestRow; $row++) {
        $cellTitleInfo = $worksheet->getCell("A$row");
        $cellDataInfo = $worksheet->getCell("B$row");
        $cellScoreInfo = $worksheet->getCell("C$row");
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
                    $answerInfoTitle = $worksheet->getCell("A$answerRow")->getValue();
                    $answerInfoData = $worksheet->getCell("B$answerRow")->getValue();
                    $answerInfoExtra = $worksheet->getCell("C$answerRow")->getValue();

                    if (false !== strpos($answerInfoTitle, 'Answer')) {
                        $answerList[$numberQuestions][$answerIndex]['data'] = $answerInfoData;
                        $answerList[$numberQuestions][$answerIndex]['extra'] = $answerInfoExtra;
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
                    $questionTypeTitle = $worksheet->getCell("A$answerRow")->getValue();
                    $questionTypeExtra = $worksheet->getCell("C$answerRow")->getValue();

                    if ('QuestionType' == $questionTypeTitle) {
                        $questionTypeList[$numberQuestions] = $questionTypeExtra;
                        $continue = false;
                    }
                    if ('Question' == $questionTypeTitle) {
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
            if (isset($questionTypeList[$i]) && '' != $questionTypeList[$i]) {
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
                case FILL_IN_BLANKS:
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

            if ('' != $questionTitle) {
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
                            if ('x' == strtolower($answer_data['extra'])) {
                                $numberRightAnswers++;
                            }
                        }

                        foreach ($myAnswerList as $answer_data) {
                            $answerValue = $answer_data['data'];
                            $correct = 0;
                            $score = 0;
                            if ('x' == strtolower($answer_data['extra'])) {
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
                                        if (isset($noNegativeScoreList[$i]) && 'x' == $noNegativeScoreList[$i]) {
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
                            }

                            $objAnswer->createAnswer(
                                $answerValue,
                                $correct,
                                $comment,
                                $score,
                                $id
                            );
                            if ($correct) {
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
            if (empty($oLP->cc) || $oLP->cc != api_get_course_id()) {
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
        header('Location: ../lp/lp_controller.php?'.api_get_cidreq().'&action=add_item&type=step&lp_id='.(int) ($_GET['lp_id']));
        exit;
    } else {
        //  header('location: exercise.php?' . api_get_cidreq());
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
        if ('x' == strtolower($answer_data['extra'])) {
            $correct++;
        } else {
            if (is_numeric($answer_data['extra'])) {
                $isNumeric = true;
            }
        }
    }

    if (1 == $correct) {
        $type = UNIQUE_ANSWER;
    } else {
        if ($correct > 1) {
            $type = MULTIPLE_ANSWER;
        } else {
            $type = FREE_ANSWER;
        }
    }

    if (MULTIPLE_ANSWER == $type) {
        if ($isNumeric) {
            $type = MULTIPLE_ANSWER;
        } else {
            $type = GLOBAL_MULTIPLE_ANSWER;
        }
    }

    return $type;
}

if ('learnpath' != $origin) {
    //so we are not in learnpath tool
    Display::display_footer();
}

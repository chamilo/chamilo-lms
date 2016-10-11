<?php
/* For licensing terms, see /license.txt */

use \ChamiloSession as Session;

/**
 * 	Upload quiz: This script shows the upload quiz feature
 *  Initial work by Isaac flores on Nov 4 of 2010
 *  Encoding fixes Julio Montoya
 * 	@package chamilo.exercise
 */

// setting the help
$help_content = 'exercise_upload';

require_once '../inc/global.inc.php';

require_once api_get_path(LIBRARY_PATH) . 'pear/excelreader/reader.php';

// Security check
$is_allowed_to_edit = api_is_allowed_to_edit(null, true);
if (!$is_allowed_to_edit) {
    api_not_allowed(true);
}
// setting the tabs
$this_section = SECTION_COURSES;
$htmlHeadXtra[] = "<script>
$(document).ready( function(){
    $('#user_custom_score').click(function() {
        $('#options').toggle();
    });
});
</script>";

// Action handling
lp_upload_quiz_action_handling();

$interbreadcrumb[] = array(
    "url" => "exercise.php?".api_get_cidreq(),
    "name" => get_lang('Exercises'),
);

// Display the header
Display :: display_header(get_lang('ImportExcelQuiz'), 'Exercises');

if (isset($_GET['message'])) {
    if (in_array($_GET['message'], array('ExerciseEdited'))) {
        Display :: display_confirmation_message(get_lang($_GET['message']));
    }
}

// display the actions
echo '<div class="actions">';
echo lp_upload_quiz_actions();
echo '</div>';

// the main content
lp_upload_quiz_main();

function lp_upload_quiz_actions()
{
    $return = '<a href="exercise.php?'.api_get_cidreq().'">'.
        Display::return_icon('back.png', get_lang('BackToExercisesList'),'',ICON_SIZE_MEDIUM).'</a>';
    return $return;
}

function lp_upload_quiz_secondary_actions()
{
    $return = '<a href="exercise_report.php?' . api_get_cidreq() . '">' .
        Display :: return_icon('reporting32.png', get_lang('Tracking')) . get_lang('Tracking') . '</a>';
    return $return;
}

function lp_upload_quiz_main()
{
    // variable initialisation
    $lp_id = isset($_GET['lp_id']) ? intval($_GET['lp_id']) : null;

    $form = new FormValidator(
        'upload',
        'POST',
        api_get_self().'?'.api_get_cidreq().'&lp_id='.$lp_id,
        '',
        array('enctype' => 'multipart/form-data')
    );
    $form->addElement('header', get_lang('ImportExcelQuiz'));
    $form->addElement('file', 'user_upload_quiz', get_lang('FileUpload'));

    $link = '<a href="../exercise/quiz_template.xls">'.
        Display::return_icon('export_excel.png', get_lang('DownloadExcelTemplate')).get_lang('DownloadExcelTemplate').'</a>';
    $form->addElement('label', '', $link);

    $table = new HTML_Table(array('class' => 'table'));

    $tableList = array(
        UNIQUE_ANSWER => get_lang('UniqueSelect'),
        MULTIPLE_ANSWER => get_lang('MultipleSelect'),
        FILL_IN_BLANKS => get_lang('FillBlanks'),
        MATCHING => get_lang('Matching'),
        FREE_ANSWER => get_lang('FreeAnswer'),
        GLOBAL_MULTIPLE_ANSWER => get_lang('GlobalMultipleAnswer')
    );

    $table->setHeaderContents(0, 0, get_lang('QuestionType'));
    $table->setHeaderContents(0, 1, '#');

    $row = 1;
    foreach ($tableList as $key => $label ) {
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
        array('id' => 'user_custom_score')
    );
    $form->addElement('html', '<div id="options" style="display:none">');
    $form->addElement('text', 'correct_score', get_lang('CorrectScore'));
    $form->addElement('text', 'incorrect_score', get_lang('IncorrectScore'));
    $form->addElement('html', '</div>');

    $form->addRule('user_upload_quiz', get_lang('ThisFieldIsRequired'), 'required');

    $form->addProgress();
    $form->addButtonUpload(get_lang('Upload'), 'submit_upload_quiz');

    // Display the upload field
    $form->display();
}

/**
 * Handles a given Excel spreadsheets as in the template provided
 */
function lp_upload_quiz_action_handling()
{
    global $debug;
    $_course = api_get_course_info();
    $courseId = $_course['real_id'];

    if (!isset($_POST['submit_upload_quiz'])) {
        return;
    }

    // Get the extension of the document.
    $path_info = pathinfo($_FILES['user_upload_quiz']['name']);

    // Check if the document is an Excel document
    if ($path_info['extension'] != 'xls') {
        return;
    }

    // Read the Excel document
    $data = new Spreadsheet_Excel_Reader();
    // Set output Encoding.
    $data->setOutputEncoding(api_get_system_encoding());
    // Reading the xls document.
    $data->read($_FILES['user_upload_quiz']['tmp_name']);

    $correctScore = isset($_POST['correct_score']) ? $_POST['correct_score'] : null;
    $incorrectScore = isset($_POST['incorrect_score']) ? $_POST['incorrect_score'] : null;
    $useCustomScore = isset($_POST['user_custom_score']) ? true : false;

    $propagateNegative = 0;
    if ($useCustomScore && !empty($incorrectScore)) {
        if ($incorrectScore < 0) {
            $propagateNegative = 1;
        }
    }

    // Variables
    $quiz_index = 0;
    $question_title_index = array();
    $question_name_index_init = array();
    $question_name_index_end = array();
    $score_index = array();
    $feedback_true_index = array();
    $feedback_false_index = array();
    $number_questions = 0;
    $question_description_index = array();
    $noNegativeScoreIndex = array();
    $questionTypeList = array();
    $questionTypeIndex = array();
    $categoryList = array();

    // Reading all the first column items sequentially to create breakpoints
    for ($i = 1; $i <= $data->sheets[0]['numRows']; $i++) {
        if ($data->sheets[0]['cells'][$i][1] == 'Quiz' && $i == 1) {
            $quiz_index = $i; // Quiz title position, only occurs once
        } elseif ($data->sheets[0]['cells'][$i][1] == 'Question') {
            $question_title_index[] = $i; // Question title position line
            $question_name_index_init[] = $i + 1; // Questions name 1st position line
            $number_questions++;
        } elseif ($data->sheets[0]['cells'][$i][1] == 'Score') {
            $question_name_index_end[] = $i - 1; // Question name position
            $score_index[] = $i; // Question score position
        } elseif ($data->sheets[0]['cells'][$i][1] == 'FeedbackTrue') {
            $feedback_true_index[] = $i; // FeedbackTrue position (line)
        } elseif ($data->sheets[0]['cells'][$i][1] == 'FeedbackFalse') {
            $feedback_false_index[] = $i; // FeedbackFalse position (line)
        } elseif ($data->sheets[0]['cells'][$i][1] == 'EnrichQuestion') {
            $question_description_index[] = $i;
        } elseif ($data->sheets[0]['cells'][$i][1] == 'NoNegativeScore') {
            $noNegativeScoreIndex[] = $i;
        } elseif ($data->sheets[0]['cells'][$i][1] == 'QuestionType') {
            $questionTypeIndex[] = $i;
        }
    }

    // Variables
    $quiz = array();
    $question = array();
    $new_answer = array();
    $score_list = array();
    $feedback_true_list = array();
    $feedback_false_list = array();
    $question_description = array();
    $noNegativeScoreList = array();

    // Getting questions.
    $k = $z = $q = $l = $m = $n = 0;
    for ($i = 1; $i <= $data->sheets[0]['numRows']; $i++) {
        if (is_array($data->sheets[0]['cells'][$i])) {
            $column_data = $data->sheets[0]['cells'][$i];
            // Fill all column with data to have a full array
            for ($x = 1; $x <= $data->sheets[0]['numCols']; $x++) {
                if (empty($column_data[$x])) {
                    $data->sheets[0]['cells'][$i][$x] = '';
                }
            }
            // Array filled with data
            $column_data = $data->sheets[0]['cells'][$i];
        } else {
            $column_data = '';
        }

        // Fill quiz data
        if ($quiz_index == $i) {
            // The title always in the first position
            $quiz = $column_data;
        } elseif (in_array($i, $question_title_index)) {
            //a complete line where 1st column is 'Question'
            $question[$k] = $column_data;

            for ($counter = 0; $counter < 12; $counter++) {
                $myData = isset($data->sheets[0]['cells'][$i + $counter]) ? $data->sheets[0]['cells'][$i + $counter] : null;
                if (isset($myData[1]) && $myData[1] == 'QuestionType') {
                    $questionTypeList[$k] = $myData[3];
                }

                if (isset($myData[1]) && $myData[1] == 'Category') {
                    $categoryList[$k] = $myData[2];
                }
            }

            if (!isset($questionTypeList[$k])) {
                $questionTypeList[$k] = null;
            }

            $k++;
        } elseif (in_array($i, $score_index)) {
            //a complete line where 1st column is 'Score'
            $score_list[$z] = $column_data;
            $z++;
        } elseif (in_array($i, $feedback_true_index)) {
            //a complete line where 1st column is 'FeedbackTrue'
            $feedback_true_list[$q] = $column_data;
            $q++;
        } elseif (in_array($i, $feedback_false_index)) {
            //a complete line where 1st column is 'FeedbackFalse' for wrong answers
            $feedback_false_list[$l] = $column_data;
            $l++;
        } elseif (in_array($i, $question_description_index)) {
            //a complete line where 1st column is 'EnrichQuestion'
            $question_description[$m] = $column_data;
            $m++;
        } elseif (in_array($i, $noNegativeScoreIndex)) {
            //a complete line where 1st column is 'NoNegativeScore'
            $noNegativeScoreList[$z - 1] = $column_data;
        }
    }

    // Get answers
    for ($i = 0; $i < count($question_name_index_init); $i++) {
        for ($j = $question_name_index_init[$i]; $j <= $question_name_index_end[$i]; $j++) {
            if (is_array($data->sheets[0]['cells'][$j])) {
                $column_data = $data->sheets[0]['cells'][$j];
                // Fill all column with data
                for ($x = 1; $x <= $data->sheets[0]['numCols']; $x++) {
                    if (empty($column_data[$x])) {
                        $data->sheets[0]['cells'][$j][$x] = '';
                    }
                }
                $column_data = $data->sheets[0]['cells'][$j];
                // Array filled of data
                if (is_array($column_data) && count($column_data) > 0) {
                    $new_answer[$i][$j] = $column_data;
                }
            }
        }
    }

    // Quiz title.
    $quiz_title = $quiz[2];

    if ($quiz_title != '') {
        // Variables
        $type = 2;
        $random = $active = $results = $max_attempt = $expired_time = 0;
        // Make sure feedback is enabled (3 to disable), otherwise the fields
        // added to the XLS are not shown, which is confusing
        $feedback = 0;

        // Quiz object
        $exercise = new Exercise();

        $quiz_id = $exercise->createExercise(
            $quiz_title,
            $expired_time,
            $type,
            $random,
            $active,
            $results,
            $max_attempt,
            $feedback,
            $propagateNegative
        );

        if ($quiz_id) {
            // insert into the item_property table
            api_item_property_update(
                $_course,
                TOOL_QUIZ,
                $quiz_id,
                'QuizAdded',
                api_get_user_id()
            );

            // Import questions.
            for ($i = 0; $i < $number_questions; $i++) {
                // Question name
                $question_title = $question[$i][2];
                $description = isset($question_description[$i][2]) ? $question_description[$i][2] : '';

                $categoryId = null;
                if (isset($categoryList[$i]) && !empty($categoryList[$i])) {
                    $categoryName = $categoryList[$i];
                    $categoryId = TestCategory::get_category_id_for_title($categoryName, $courseId);
                    if (empty($categoryId)) {
                        $category = new TestCategory();
                        $category->name = $categoryName;
                        $categoryId = $category->addCategoryInBDD();
                    }
                }

                $question_description_text = "<p></p>";
                if (!empty($description)) {
                    // Question description.
                    $question_description_text = "<p>".$description."</p>";
                }

                // Unique answers are the only question types available for now
                // through xls-format import
                $answerList = isset($new_answer[$i]) ? $new_answer[$i] : '';

                $question_id = null;
                if (isset($questionTypeList[$i])) {
                    $detectQuestionType = intval($questionTypeList[$i]);
                } else {
                    $detectQuestionType = detectQuestionType(
                        $answerList,
                        $score_list
                    );
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

                if ($question_title != '') {
                    $question_id = $answer->create_question(
                        $quiz_id,
                        $question_title,
                        $question_description_text,
                        0, // max score
                        $answer->type
                    );

                    if (!empty($categoryId)) {
                        TestCategory::add_category_for_question_id(
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
                        if (is_array($answerList) && !empty($question_id)) {
                            $id = 1;
                            $objAnswer = new Answer($question_id, $courseId);
                            $globalScore = $score_list[$i][3];

                            // Calculate the number of correct answers to divide the
                            // score between them when importing from CSV
                            $numberRightAnswers = 0;
                            foreach ($answerList as $answer_data) {
                                if (strtolower($answer_data[3]) == 'x') {
                                    $numberRightAnswers++;
                                }
                            }

                            foreach ($answerList as $answer_data) {
                                $answerValue = $answer_data[2];
                                $correct = 0;
                                $score = 0;
                                if (strtolower($answer_data[3]) == 'x') {
                                    $correct = 1;
                                    $score = $score_list[$i][3];
                                    $comment = $feedback_true_list[$i][2];
                                } else {
                                    $comment = $feedback_false_list[$i][2];
                                    $floatVal = (float)$answer_data[3];
                                    if (is_numeric($floatVal)) {
                                        $score = $answer_data[3];
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
                                        if (isset($noNegativeScoreList[$i][3])) {
                                            if (!(strtolower($noNegativeScoreList[$i][3]) == 'x') &&
                                                !$correct
                                            ) {
                                                $score = $score_list[$i][3] * -1;
                                            }
                                        } else {
                                            $score = $score_list[$i][3] * -1;
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

                                $total += $score;
                                $id++;
                            }

                            $objAnswer->save();

                            $questionObj = Question::read(
                                $question_id,
                                $courseId
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
                                $questionObj->save();
                            }
                        }
                        break;
                    case FREE_ANSWER:
                        $globalScore = $score_list[$i][3];
                        $questionObj = Question::read($question_id, $courseId);
                        if ($questionObj) {
                            $questionObj->updateWeighting($globalScore);
                            $questionObj->save();
                        }
                        break;
                    case FILL_IN_BLANKS:
                        $scoreList = array();
                        $size = array();
                        $globalScore = 0;
                        foreach ($answerList as $data) {
                            $score = isset($data[3]) ? $data[3] : 0;
                            $globalScore += $score;
                            $scoreList[] = $score;
                            $size[] = 200;
                        }

                        $scoreToString = implode(',', $scoreList);
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

                        $questionObj = Question::read($question_id, $courseId);
                        if ($questionObj) {
                            $questionObj->updateWeighting($globalScore);
                            $questionObj->save();
                        }
                        break;
                    case MATCHING:
                        $globalScore = $score_list[$i][3];
                        $position = 1;

                        $objAnswer = new Answer($question_id, $courseId);
                        foreach ($answerList as $data) {
                            $option = isset($data[3]) ? $data[3] : '';
                            $objAnswer->createAnswer($option, 0, '', 0, $position);
                            $position++;
                        }

                        $counter = 1;
                        foreach ($answerList as $data) {
                            $value = isset($data[2]) ? $data[2] : '';
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

                        $questionObj = Question::read($question_id, $courseId);
                        if ($questionObj) {
                            $questionObj->updateWeighting($globalScore);
                            $questionObj->save();
                        }
                        break;
                }
            }
        }

        if (isset($_SESSION['lpobject'])) {
            if ($debug > 0) {
                error_log('New LP - SESSION[lpobject] is defined', 0);
            }
            $oLP = unserialize($_SESSION['lpobject']);
            if (is_object($oLP)) {
                if ($debug > 0) {
                    error_log('New LP - oLP is object', 0);
                }
                if ((empty($oLP->cc)) OR $oLP->cc != api_get_course_id()) {
                    if ($debug > 0) {
                        error_log('New LP - Course has changed, discard lp object', 0);
                    }
                    $oLP = null;
                    Session::erase('oLP');
                    Session::erase('lpobject');
                } else {
                    $_SESSION['oLP'] = $oLP;
                }
            }
        }

        if (isset($_SESSION['oLP']) && isset($_GET['lp_id'])) {
            $previous = $_SESSION['oLP']->select_previous_item_id();
            $parent = 0;
            // Add a Quiz as Lp Item
            $_SESSION['oLP']->add_item($parent, $previous, TOOL_QUIZ, $quiz_id, $quiz_title, '');
            // Redirect to home page for add more content
            header('location: ../lp/lp_controller.php?'.api_get_cidreq().'&action=add_item&type=step&lp_id='.intval($_GET['lp_id']));
            exit;
        } else {
            //  header('location: exercise.php?' . api_get_cidreq());
            echo '<script>window.location.href = "'.api_get_path(WEB_CODE_PATH).'exercise/admin.php?'.api_get_cidreq().'&exerciseId='.$quiz_id.'&session_id='.api_get_session_id().'"</script>';
        }
    }
}

/**
 * @param array $answers_data
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
        if (strtolower($answer_data[3]) == 'x') {
            $correct++;
        } else {
            if (is_numeric($answer_data[3])) {
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

if (!isset($origin) || isset($origin) && $origin != 'learnpath') {
    //so we are not in learnpath tool
    Display :: display_footer();
}

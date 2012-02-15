<?php
/* For licensing terms, see /license.txt */
/**
 * 	Upload quiz: This script shows the upload quiz feature
 *  Initial work by Isaac flores on Nov 4 of 2010
 *  Encoding fixes Julio Montoya 
 * 	@package chamilo.exercise
 */
/**
 * Language files that should be included
 */
$language_file[] = 'learnpath';
$language_file[] = 'exercice';
// setting the help
$help_content = 'exercise_upload';

// including the global Dokeos file
require_once '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH) . 'fileUpload.lib.php';
require_once api_get_path(LIBRARY_PATH) . 'pear/excelreader/reader.php';
require_once 'exercise.class.php';
require_once 'question.class.php';
require_once 'unique_answer.class.php';
require_once '../newscorm/learnpath.class.php';
require_once '../newscorm/learnpathItem.class.php';

// Security check
$is_allowed_to_edit = api_is_allowed_to_edit(null, true);
if (!$is_allowed_to_edit) {
    api_not_allowed(true);
}

// setting the tabs
$this_section = SECTION_COURSES;
$htmlHeadXtra[] = "<script type='text/javascript'>
  $(document).ready( function(){
  $(\"div.formw\").attr(\"style\",\"width: 73%;\");
  $(\"#img_plus_and_minus\").hide();
});
</script>";

// Action handling
lp_upload_quiz_action_handling();

$interbreadcrumb[]= array ("url"=>"exercice.php", "name"=> get_lang('Exercices'));

// Display the header
if ($origin != 'learnpath') {
    //so we are not in learnpath tool
    Display :: display_header(get_lang('ImportExcelQuiz'), 'Exercises');
    if (isset ($_GET['message'])) {
        if (in_array($_GET['message'], array ('ExerciseEdited'))) {
            Display :: display_confirmation_message(get_lang($_GET['message']));
        }
    }
} else {
    echo '<link rel="stylesheet" type="text/css" href="' . api_get_path(WEB_CODE_PATH) . 'css/default.css"/>';
}
// display the actions
echo '<div class="actions">';
echo lp_upload_quiz_actions();
echo '</div>';

// start the content div
echo '<div id="content_with_secondary_actions" class="gradient">';
// the main content
lp_upload_quiz_main();

// close the content div
echo '</div>';

function lp_upload_quiz_actions() {
    $lp_id = Security::remove_XSS($_GET['lp_id']);
    $return = "";
    $return .= '<a href="exercice.php?'.api_get_cidReq().'">'.Display::return_icon('back.png', get_lang('BackToExercisesList'),'',ICON_SIZE_MEDIUM).'</a>';
    return $return;
}

function lp_upload_quiz_secondary_actions() {
 $lp_id = Security::remove_XSS($_GET['lp_id']);
 $return.= '';
 $return.='<a href="exercise_report.php?' . api_get_cidreq() . '">' . Display :: return_icon('reporting32.png', get_lang('Tracking')) . get_lang('Tracking') . '</a>';
 return $return;
}

function lp_upload_quiz_main() {
        
    // variable initialisation
    $lp_id = Security::remove_XSS($_GET['lp_id']);
    
    $form = new FormValidator('upload', 'POST', api_get_self() . '?' . api_get_cidreq() . '&lp_id=' . $lp_id, '', 'enctype="multipart/form-data"');
    $form->addElement('html', '<div><h3>' .Display::return_icon('import_excel.png', get_lang('ImportExcelQuiz'), array('style'=>'margin-bottom:-2px;'),32). get_lang('ImportExcelQuiz') . '</h3></div>');
    $form->addElement('file', 'user_upload_quiz', '');
    //button send document
    $form->addElement('style_submit_button', 'submit_upload_quiz', get_lang('Send'), 'class="upload"');
    
    // Display the upload field
    echo '<table style="text-align: left; width: 100%;" border="0" cellpadding="2"cellspacing="2"><tbody><tr>';
    echo '<td style="vertical-align: top; width: 25%;">';
    echo '<a href="../exercice/quiz_template.xls">'.Display::return_icon('export_excel.png', get_lang('DownloadExcelTemplate'),null,16).get_lang('DownloadExcelTemplate').'';
    echo '</a>';
    echo '</td>';
    echo '</tr>';
    echo '<tr><td>';
    $form->display();
    echo '</td></tr></tbody></table>';
}

/**
 * Handles a given Excel spreadsheets as in the template provided
 */
function lp_upload_quiz_action_handling() {
    global $_course, $debug;
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
 
    // Variables
    $quiz_index = 0;
    $question_title_index = array();
    $question_name_index_init = array();
    $question_name_index_end = array();
    $score_index = array();
    $feedback_true_index = array();
    $feedback_false_index = array();
    $number_questions = 0;
    // Reading all the first column items sequencially to create breakpoints
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
        }
    }
    // Variables
    $quiz = array();
    $question = array();
    $answer = array();
    $new_answer = array();
    $score_list = array();
    $feedback_true_list = array();
    $feedback_false_list = array();
    // Get questions
    $k = $z = $q = $l = 0;
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
        if ($quiz_index == $i) { // The title always in the first position
            $quiz = $column_data;
        } elseif (in_array($i, $question_title_index)) {
            $question[$k] = $column_data; //a complete line where 1st column is 'Question'
            $k++;
        } elseif (in_array($i, $score_index)) {
            $score_list[$z] = $column_data; //a complete line where 1st column is 'Score'
            $z++;
        } elseif (in_array($i, $feedback_true_index)) {
            $feedback_true_list[$q] = $column_data;//a complete line where 1st column is 'FeedbackTrue'
            $q++;
        } elseif (in_array($i, $feedback_false_index)) {
            $feedback_false_list[$l] = $column_data;//a complete line where 1st column is 'FeedbackFalse' for wrong answers
            $l++;
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
                if (is_array($data->sheets[0]['cells'][$j]) && count($data->sheets[0]['cells'][$j]) > 0) {
                    $new_answer[$i][$j] = $data->sheets[0]['cells'][$j];
                }
            }
        }
    }
    $quiz_title = $quiz[2]; // Quiz title
    if ($quiz_title != '') {
        // Variables
        $type = 2;
        $random = $active = $results = $max_attempt = $expired_time = 0;
        //make sure feedback is enabled (3 to disable), otherwise the fields
        // added to the XLS are not shown, which is confusing
        $feedback = 0; 
        // Quiz object
        $quiz_object = new Exercise();
        
        $quiz_id = $quiz_object->create_quiz(($quiz_title), $expired_time, $type, $random, $active, $results, $max_attempt, $feedback);
        // insert into the item_property table
        api_item_property_update($_course, TOOL_QUIZ, $quiz_id, 'QuizAdded', api_get_user_id());
        // Import questions
        for ($i = 0; $i < $number_questions; $i++) {
            // Create questions
            $question_title = $question[$i][2]; // Question name
            if ($question_title != '') {
                $question_id = Question::create_question($quiz_id, ($question_title));
            }
            $unique_answer = new UniqueAnswer();
            if (is_array($new_answer[$i])) {
                $id = 1;
                $answers_data = $new_answer[$i];
                foreach ($answers_data as $answer_data) {
                    $answer = $answer_data[2];
                    $correct = 0;
                    $score = 0;
                    $comment = '';
                    if (strtolower($answer_data[3]) == 'x') {
                        $correct = 1;
                        $score = $score_list[$i][3];
                        $comment = $feedback_true_list[$i][2];
                    } else {
                        $comment = $feedback_false_list[$i][2];                    	
                    }
/*
                    if ($id == 1) {
                        $comment = $feedback_true_list[$i][2];
                    } elseif ($id == 2) {
                        $comment = $feedback_false_list[$i][2];
                    }
*/
                    // Create answer
                    $unique_answer->create_answer($id, $question_id, ($answer), ($comment), $score, $correct);
                    $id++;
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
                    api_session_unregister('oLP');
                    api_session_unregister('lpobject');
                } else {
                    $_SESSION['oLP'] = $oLP;
                    $lp_found = true;
                }
            }
        }
        if (isset($_SESSION['oLP']) && isset($_GET['lp_id'])) {
        	
            $previous = $_SESSION['oLP']->select_previous_item_id();
            $parent = 0;
            // Add a Quiz as Lp Item
            $_SESSION['oLP']->add_item($parent, $previous, TOOL_QUIZ, $quiz_id, ($quiz_title), '');
            // Redirect to home page for add more content
            header('location: ../newscorm/lp_controller.php?' . api_get_cidreq() . '&action=add_item&type=step&lp_id=' . Security::remove_XSS($_GET['lp_id']).'&session_id='.api_get_session_id());
            exit;
        } else {
            //  header('location: exercice.php?' . api_get_cidreq());	
            echo '<script>window.location.href = "admin.php?'.api_get_cidReq().'&exerciseId='.$quiz_id.'&session_id='.api_get_session_id().'"</script>';
        }
    }
}

<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * Exercise administration
 * This script allows to manage (create, modify) an exercise and its questions
 *
 *  Following scripts are includes for a best code understanding :
 *
 * - exercise.class.php : for the creation of an Exercise object
 * - question.class.php : for the creation of a Question object
 * - answer.class.php : for the creation of an Answer object
 * - exercise.lib.php : functions used in the exercise tool
 * - exercise_admin.inc.php : management of the exercise
 * - question_admin.inc.php : management of a question (statement & answers)
 * - statement_admin.inc.php : management of a statement
 * - question_list_admin.inc.php : management of the question list
 *
 * Main variables used in this script :
 *
 * - $is_allowedToEdit : set to 1 if the user is allowed to manage the exercise
 * - $objExercise : exercise object
 * - $objQuestion : question object
 * - $objAnswer : answer object
 * - $aType : array with answer types
 * - $exerciseId : the exercise ID
 * - $picturePath : the path of question pictures
 * - $newQuestion : ask to create a new question
 * - $modifyQuestion : ID of the question to modify
 * - $editQuestion : ID of the question to edit
 * - $submitQuestion : ask to save question modifications
 * - $cancelQuestion : ask to cancel question modifications
 * - $deleteQuestion : ID of the question to delete
 * - $moveUp : ID of the question to move up
 * - $moveDown : ID of the question to move down
 * - $modifyExercise : ID of the exercise to modify
 * - $submitExercise : ask to save exercise modifications
 * - $cancelExercise : ask to cancel exercise modifications
 * - $modifyAnswers : ID of the question which we want to modify answers for
 * - $cancelAnswers : ask to cancel answer modifications
 * - $buttonBack : ask to go back to the previous page in answers of type "Fill in blanks"
 *
 * @package chamilo.exercise
 * @author Olivier Brouckaert
 * Modified by Hubert Borderiou 21-10-2011 Question by category
 */

require_once __DIR__.'/../inc/global.inc.php';
$current_course_tool = TOOL_QUIZ;
$this_section = SECTION_COURSES;

// Access control
api_protect_course_script(true);

$is_allowedToEdit = api_is_allowed_to_edit(null, true, false, false);
$sessionId = api_get_session_id();
$studentViewActive = api_is_student_view_active();
if (!$is_allowedToEdit) {
    api_not_allowed(true);
}

/*  stripslashes POST data  */
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    foreach ($_POST as $key => $val) {
        if (is_string($val)) {
            $_POST[$key] = stripslashes($val);
        } elseif (is_array($val)) {
            foreach ($val as $key2 => $val2) {
                $_POST[$key][$key2] = stripslashes($val2);
            }
        }
        $GLOBALS[$key] = $_POST[$key];
    }
}

if (empty($exerciseId)) {
    $exerciseId = isset($_GET['exerciseId']) ? intval($_GET['exerciseId']) : '0';
}

$newQuestion = isset($_GET['newQuestion']) ? $_GET['newQuestion'] : 0;

if (empty($modifyAnswers)) {
    $modifyAnswers = isset($_GET['modifyAnswers']) ? $_GET['modifyAnswers'] : 0;
}

$editQuestion = isset($_GET['editQuestion']) ? $_GET['editQuestion'] : 0;

if (empty($modifyQuestion)) {
    $modifyQuestion = isset($_GET['modifyQuestion']) ? $_GET['modifyQuestion'] : 0;
}
if (empty($deleteQuestion)) {
    $deleteQuestion = isset($_GET['deleteQuestion']) ? $_GET['deleteQuestion'] : 0;
}
$clone_question = isset($_REQUEST['clone_question']) ? $_REQUEST['clone_question'] : 0;
if (empty($questionId)) {
    $questionId = Session::read('questionId');
}
if (empty($modifyExercise)) {
    $modifyExercise = isset($_GET['modifyExercise']) ? $_GET['modifyExercise'] : null;
}

$fromExercise = isset($fromExercise) ? $fromExercise : null;
$cancelExercise = isset($cancelExercise) ? $cancelExercise : null;
$cancelAnswers = isset($cancelAnswers) ? $cancelAnswers : null;
$modifyIn = isset($modifyIn) ? $modifyIn : null;
$cancelQuestion = isset($cancelQuestion) ? $cancelQuestion : null;

/* Cleaning all incomplete attempts of the admin/teacher to avoid weird problems
    when changing the exercise settings, number of questions, etc */
Event::delete_all_incomplete_attempts(
    api_get_user_id(),
    $exerciseId,
    api_get_course_int_id(),
    api_get_session_id()
);

// get from session
$objExercise = Session::read('objExercise');
$objQuestion = Session::read('objQuestion');

if (isset($_REQUEST['convertAnswer'])) {
    $objQuestion = $objQuestion->swapSimpleAnswerTypes();
    Session::write('objQuestion', $objQuestion);
}
$objAnswer = Session::read('objAnswer');

// document path
$documentPath = api_get_path(SYS_COURSE_PATH).$_course['path'].'/document';

// picture path
$picturePath = $documentPath.'/images';

// audio path
$audioPath = $documentPath.'/audio';

// the 5 types of answers
$aType = array(
    get_lang('UniqueSelect'),
    get_lang('MultipleSelect'),
    get_lang('FillBlanks'),
    get_lang('Matching'),
    get_lang('FreeAnswer')
);

// tables used in the exercise tool

if (!empty($_GET['action']) && $_GET['action'] == 'exportqti2' && !empty($_GET['questionId'])) {
    require_once 'export/qti2/qti2_export.php';
    $export = export_question_qti($_GET['questionId'], true);
    $qid = (int) $_GET['questionId'];
    $archive_path = api_get_path(SYS_ARCHIVE_PATH);
    $temp_dir_short = uniqid();
    $temp_zip_dir = $archive_path."/".$temp_dir_short;
    if (!is_dir($temp_zip_dir)) {
        mkdir($temp_zip_dir, api_get_permissions_for_new_directories());
    }
    $temp_zip_file = $temp_zip_dir."/".api_get_unique_id().".zip";
    $temp_xml_file = $temp_zip_dir."/qti2export_".$qid.'.xml';
    file_put_contents($temp_xml_file, $export);
    $zip_folder = new PclZip($temp_zip_file);
    $zip_folder->add($temp_xml_file, PCLZIP_OPT_REMOVE_ALL_PATH);
    $name = 'qti2_export_'.$qid.'.zip';

    DocumentManager::file_send_for_download($temp_zip_file, true, $name);
    unlink($temp_zip_file);
    unlink($temp_xml_file);
    rmdir($temp_zip_dir);
    //DocumentManager::string_send_for_download($export,true,'qti2export_q'.$_GET['questionId'].'.xml');
    exit; //otherwise following clicks may become buggy
}

// Exercise object creation.
if (!is_object($objExercise)) {
    // construction of the Exercise object
    $objExercise = new Exercise();

    // creation of a new exercise if wrong or not specified exercise ID
    if ($exerciseId) {
        $objExercise->read($exerciseId);
    }
    // saves the object into the session
    Session::write('objExercise', $objExercise);
}

// Exercise can be edited in their course.
if ($objExercise->sessionId != $sessionId) {
    api_not_allowed(true);
}

// doesn't select the exercise ID if we come from the question pool
if (!$fromExercise) {
    // gets the right exercise ID, and if 0 creates a new exercise
    if (!$exerciseId = $objExercise->selectId()) {
        $modifyExercise = 'yes';
    }
}

$nbrQuestions = $objExercise->selectNbrQuestions();

// Question object creation.
if ($editQuestion || $newQuestion || $modifyQuestion || $modifyAnswers) {
    if ($editQuestion || $newQuestion) {
        // reads question data
        if ($editQuestion) {
            // question not found
            if (!$objQuestion = Question::read($editQuestion)) {
                api_not_allowed();
            }
            // saves the object into the session
            Session::write('objQuestion', $objQuestion);
        }
    }

    // checks if the object exists
    if (is_object($objQuestion)) {
        // gets the question ID
        $questionId = $objQuestion->selectId();
    }
}

// if cancelling an exercise
if ($cancelExercise) {
    // existing exercise
    if ($exerciseId) {
        unset($modifyExercise);
    } else {
        // new exercise
        // goes back to the exercise list
        header('Location: '.api_get_path(WEB_CODE_PATH).'exercise/exercise.php?'.api_get_cidreq());
        exit();
    }
}

// if cancelling question creation/modification
if ($cancelQuestion) {
    // if we are creating a new question from the question pool
    if (!$exerciseId && !$questionId) {
        // goes back to the question pool
        header('Location: question_pool.php');
        exit();
    } else {
        // goes back to the question viewing
        $editQuestion = $modifyQuestion;
        unset($newQuestion, $modifyQuestion);
    }
}

if (!empty($clone_question) && !empty($objExercise->id)) {
    $old_question_obj = Question::read($clone_question);
    $old_question_obj->question = $old_question_obj->question.' - '.get_lang('Copy');

    $new_id = $old_question_obj->duplicate();
    $new_question_obj = Question::read($new_id);
    $new_question_obj->addToList($exerciseId);

    // This should be moved to the duplicate function
    $new_answer_obj = new Answer($clone_question);
    $new_answer_obj->read();
    $new_answer_obj->duplicate($new_question_obj);

    //Reloading tne $objExercise obj
    $objExercise->read($objExercise->id);

    header('Location: admin.php?'.api_get_cidreq().'&exerciseId='.$objExercise->id);
    exit;
}

// if cancelling answer creation/modification
if ($cancelAnswers) {
    // goes back to the question viewing
    $editQuestion = $modifyAnswers;
    unset($modifyAnswers);
}
$nameTools = null;
// modifies the query string that is used in the link of tool name
if ($editQuestion || $modifyQuestion || $newQuestion || $modifyAnswers) {
    $nameTools = get_lang('QuestionManagement');
}

if (isset($_SESSION['gradebook'])) {
    $gradebook = $_SESSION['gradebook'];
}

if (!empty($gradebook) && $gradebook == 'view') {
    $interbreadcrumb[] = array(
        'url' => '../gradebook/'.$_SESSION['gradebook_dest'],
        'name' => get_lang('ToolGradebook')
    );
}

$interbreadcrumb[] = array("url" => "exercise.php", "name" => get_lang('Exercises'));
if (isset($_GET['newQuestion']) || isset($_GET['editQuestion'])) {
    $interbreadcrumb[] = [
        "url" => "admin.php?exerciseId=".$objExercise->id,
        "name" => $objExercise->selectTitle(true),
    ];
} else {
    $interbreadcrumb[] = [
        "url" => "#",
        "name" => $objExercise->selectTitle(true),
    ];
}

// shows a link to go back to the question pool
if (!$exerciseId && $nameTools != get_lang('ExerciseManagement')) {
    $interbreadcrumb[] = array(
        "url" => api_get_path(WEB_CODE_PATH)."exercise/question_pool.php?fromExercise=$fromExercise&".api_get_cidreq(),
        "name" => get_lang('QuestionPool')
    );
}

// if the question is duplicated, disable the link of tool name
if ($modifyIn == 'thisExercise') {
    if ($buttonBack) {
        $modifyIn = 'allExercises';
    } else {
        $noPHP_SELF = true;
    }
}
$htmlHeadXtra[] = '<script>
function multiple_answer_true_false_onchange(variable) {
    var result = variable.checked;
    var id = variable.id;
    var weight_id = "weighting_" + id;
    var array_result=new Array();
    array_result[1]="1";
    array_result[0]= "-0.50";
    array_result[-1]= "0";
    if (result) {
        result = 1;
    } else {
        result = 0;
    }
    document.getElementById(weight_id).value = array_result[result];
}


</script>';

$htmlHeadXtra[] = api_get_js('jqueryui-touch-punch/jquery.ui.touch-punch.min.js');
$htmlHeadXtra[] = api_get_js('jquery.jsPlumb.all.js');

$template = new Template();
$templateName = $template->get_template('exercise/submit.js.tpl');
$htmlHeadXtra[] = $template->fetch($templateName);
$htmlHeadXtra[] = api_get_js('d3/jquery.xcolor.js');
$htmlHeadXtra[] = '<link rel="stylesheet" href="'.api_get_path(WEB_LIBRARY_JS_PATH).'hotspot/css/hotspot.css">';
$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_JS_PATH).'hotspot/js/hotspot.js"></script>';

if (isset($_GET['message'])) {
    if (in_array($_GET['message'], array('ExerciseStored', 'ItemUpdated', 'ItemAdded'))) {
        Display::addFlash(Display::return_message(get_lang($_GET['message']), 'confirmation'));
    }
}

Display::display_header($nameTools, 'Exercise');
/*
if ($objExercise->exercise_was_added_in_lp) {
    if ($objExercise->force_edit_exercise_in_lp == true) {
        Display::addFlash(Display::return_message(get_lang('ForceEditingExerciseInLPWarning'), 'warning'));
    } else {
        Display::addFlash(Display::return_message(get_lang('EditingExerciseCauseProblemsInLP'), 'warning'));
    }
}*/

// If we are in a test
$inATest = isset($exerciseId) && $exerciseId > 0;

if ($inATest) {
    echo '<div class="actions">';
    if (isset($_GET['hotspotadmin']) || isset($_GET['newQuestion']) || isset($_GET['myid']))
        echo '<a href="'.api_get_path(WEB_CODE_PATH).'exercise/admin.php?exerciseId='.$exerciseId.'&'.api_get_cidreq().'">'.
            Display::return_icon('back.png', get_lang('GoBackToQuestionList'), '', ICON_SIZE_MEDIUM).'</a>';

    if (!isset($_GET['hotspotadmin']) && !isset($_GET['newQuestion']) && !isset($_GET['myid']) && !isset($_GET['editQuestion'])) {
        echo '<a href="'.api_get_path(WEB_CODE_PATH).'exercise/exercise.php?'.api_get_cidreq().'">'.
            Display::return_icon('back.png', get_lang('BackToExercisesList'), '', ICON_SIZE_MEDIUM).'</a>';
    }
    echo '<a href="'.api_get_path(WEB_CODE_PATH).'exercise/overview.php?'.api_get_cidreq().'&exerciseId='.$objExercise->id.'&preview=1">'.
        Display::return_icon('preview_view.png', get_lang('Preview'), '', ICON_SIZE_MEDIUM).'</a>';

    echo Display::url(
        Display::return_icon('test_results.png', get_lang('Results'), '', ICON_SIZE_MEDIUM),
        api_get_path(WEB_CODE_PATH).'exercise/exercise_report.php?'.api_get_cidreq().'&exerciseId='.$objExercise->id
    );

    echo '<a href="'.api_get_path(WEB_CODE_PATH).'exercise/exercise_admin.php?'.api_get_cidreq().'&modifyExercise=yes&exerciseId='.$objExercise->id.'">'.
        Display::return_icon('settings.png', get_lang('ModifyExercise'), '', ICON_SIZE_MEDIUM).'</a>';

    $maxScoreAllQuestions = 0;
    $questionList = $objExercise->selectQuestionList(true, true);
    if (!empty($questionList)) {
        foreach ($questionList as $questionItemId) {
            $question = Question::read($questionItemId);
            if ($question) {
                $maxScoreAllQuestions += $question->selectWeighting();
            }
        }
    }

    echo '</div>';

    if ($objExercise->added_in_lp()) {
        echo Display::return_message(get_lang('AddedToLPCannotBeAccessed'), 'warning');
    }

    echo '<div class="alert alert-info">';
    echo sprintf(
        get_lang('XQuestionsWithTotalScoreY'),
        $objExercise->selectNbrQuestions(),
        $maxScoreAllQuestions
    );

    if ($objExercise->random > 0) {
        echo '<br />'.
            sprintf(get_lang('OnlyXQuestionsPickedRandomly'), $objExercise->random);
    }
    echo '</div>';

} elseif (isset($_GET['newQuestion'])) {
    // we are in create a new question from question pool not in a test
    echo '<div class="actions">';
    echo '<a href="'.api_get_path(WEB_CODE_PATH).'exercise/admin.php?'.api_get_cidreq().'">'.
        Display::return_icon('back.png', get_lang('GoBackToQuestionList'), '', ICON_SIZE_MEDIUM).'</a>';
    echo '</div>';
} else {
    // If we are in question_pool but not in an test, go back to question create in pool
    echo '<div class="actions">';
    echo '<a href="'.api_get_path(WEB_CODE_PATH).'exercise/question_pool.php?'.api_get_cidreq().'">'.
        Display::return_icon('back.png', get_lang('GoBackToQuestionList'), '', ICON_SIZE_MEDIUM).
        '</a>';
    echo '</div>';
}

if ($newQuestion || $editQuestion) {
    // Question management
    $type = isset($_REQUEST['answerType']) ? Security::remove_XSS($_REQUEST['answerType']) : null;
    echo '<input type="hidden" name="Type" value="'.$type.'" />';

    if ($newQuestion === 'yes') {
        $objExercise->edit_exercise_in_lp = true;
        require 'question_admin.inc.php';
    }

    if ($editQuestion) {
        // Question preview if teacher clicked the "switch to student"
        if ($studentViewActive && $is_allowedToEdit) {
            echo '<div class="main-question">';
            echo Display::div($objQuestion->selectTitle(), array('class' => 'question_title'));
            ExerciseLib::showQuestion(
                $editQuestion,
                false,
                null,
                null,
                false,
                true,
                false,
                true,
                $objExercise->feedback_type,
                true
            );
            echo '</div>';
        } else {
            require 'question_admin.inc.php';
        }
    }
}

if (isset($_GET['hotspotadmin'])) {
    if (!is_object($objQuestion)) {
        $objQuestion = Question::read($_GET['hotspotadmin']);
    }
    if (!$objQuestion) {
        api_not_allowed();
    }
    require 'hotspot_admin.inc.php';
}

if (!$newQuestion && !$modifyQuestion && !$editQuestion && !isset($_GET['hotspotadmin'])) {
    // question list management
    require 'question_list_admin.inc.php';
}

// if we are in question authoring, display warning to user is feedback not shown at the end of the test -ref #6619
// this test to display only message in the question authoring page and not in the question list page too
// if (is_object($objQuestion) && $objExercise->selectFeedbackType() == EXERCISE_FEEDBACK_TYPE_EXAM && ($newQuestion || $modifyQuestion || $editQuestion)) {
if ($objExercise->selectFeedbackType() == EXERCISE_FEEDBACK_TYPE_EXAM) {
    echo Display::return_message(get_lang('TestFeedbackNotShown'), 'normal');
}

Session::write('objExercise', $objExercise);
Session::write('objQuestion', $objQuestion);
Session::write('objAnswer', $objAnswer);
Display::display_footer();

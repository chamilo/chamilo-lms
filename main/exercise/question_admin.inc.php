<?php
/* For licensing terms, see /license.txt */

/**
 * This script allows to manage the statements of questions.
 * It is included from the script admin.php
 * @package chamilo.exercise
 * @author Olivier Brouckaert
 * @version $Id: question_admin.inc.php 22126 2009-07-15 22:38:39Z juliomontoya $
 */

if (isset($_GET['editQuestion'])) {
    $objQuestion = Question::read($_GET['editQuestion']);
    $action = api_get_self()."?".api_get_cidreq()."&myid=1&modifyQuestion=".$modifyQuestion."&editQuestion=".$objQuestion->id;
} else {
    $objQuestion = Question::getInstance($_REQUEST['answerType']);
    $action = api_get_self()."?".api_get_cidreq()."&modifyQuestion=".$modifyQuestion."&newQuestion=".$newQuestion;
}

if (is_object($objQuestion)) {
    // FORM CREATION
    $form = new FormValidator('question_admin_form', 'post', $action);
    if (isset($_GET['editQuestion'])) {
        $class = "btn btn-default";
        $text = get_lang('ModifyQuestion');
        $type = isset($_GET['type']) ? Security::remove_XSS($_GET['type']) : null;
    } else {
        $class = "btn btn-default";
        $text = get_lang('AddQuestionToExercise');
        $type = $_REQUEST['answerType'];
    }

    $typesInformation = Question::get_question_type_list();
    $form_title_extra = isset($typesInformation[$type][1]) ? get_lang($typesInformation[$type][1]) : null;

    // form title
    $form->addHeader($text.': '.$form_title_extra);

    // question form elements
    $objQuestion->createForm($form, $objExercise);

    // answer form elements
    $objQuestion->createAnswersForm($form);

    // this variable  $show_quiz_edition comes from admin.php blocks the exercise/quiz modifications
    if ($objExercise->edit_exercise_in_lp == false) {
        $form->freeze();
    }

    // FORM VALIDATION
    if (isset($_POST['submitQuestion']) && $form->validate()) {
        // Question
        $objQuestion->processCreation($form, $objExercise);
        $objQuestion->processAnswersCreation($form, $objExercise);

        // TODO: maybe here is the better place to index this tool, including answers text

        // redirect
        if ($objQuestion->type != HOT_SPOT &&
            $objQuestion->type != HOT_SPOT_DELINEATION
        ) {
            if (isset($_GET['editQuestion'])) {
                echo '<script type="text/javascript">window.location.href="admin.php?exerciseId='.$exerciseId.'&'.api_get_cidreq().'&message=ItemUpdated"</script>';
            } else {
                // New question
                echo '<script type="text/javascript">window.location.href="admin.php?exerciseId='.$exerciseId.'&'.api_get_cidreq().'&message=ItemAdded"</script>';
            }
        } else {
            echo '<script type="text/javascript">window.location.href="admin.php?exerciseId='.$exerciseId.'&hotspotadmin='.$objQuestion->id.'&'.api_get_cidreq().'"</script>';
        }
    } else {
        if (isset($questionName)) {
            echo '<h3>'.$questionName.'</h3>';
        }
        if (!empty($pictureName)) {
            echo '<img src="../document/download.php?doc_url=%2Fimages%2F'.$pictureName.'" border="0">';
        }
        if (!empty($msgErr)) {
            echo Display::return_message($msgErr, 'normal');
        }
        // display the form
        $form->display();
    }
}

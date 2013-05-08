<?php
/* For licensing terms, see /license.txt */
/**
 * This script allows to manage the statements of questions.
 * It is included from the script admin.php
 * @package chamilo.exercise
 * @author Olivier Brouckaert
 * @author Julio Montoya
 */
/**
 * Code
 */

$course_id = api_get_course_int_id();

// INIT QUESTION
if (isset($_GET['editQuestion'])) {
    $objQuestion = Question::read($_GET['editQuestion'], null, $objExercise);
    $action      = api_get_self()."?".api_get_cidreq(
    )."&myid=1&modifyQuestion=".$modifyQuestion."&editQuestion=".$objQuestion->id."&exerciseId=$exerciseId";
} else {
    $objQuestion = Question::getInstance($_REQUEST['answerType'], $objExercise);
    $action      = api_get_self()."?".api_get_cidreq(
    )."&modifyQuestion=".$modifyQuestion."&newQuestion=".$newQuestion."&exerciseId=$exerciseId";
}

/** @var Question $objQuestion */

if (is_object($objQuestion)) {
    //Form creation
    $form = new FormValidator('question_admin_form', 'post', $action);

    if (isset($_GET['editQuestion'])) {
        $objQuestion->submitClass = "btn save";
        $objQuestion->submitText  = get_lang('ModifyQuestion');
    } else {
        $objQuestion->submitClass = "btn add";
        $objQuestion->submitText  = get_lang('AddQuestionToExercise');
    }

    if (!isset($_GET['fromExercise'])) {
        $objQuestion->setDefaultQuestionValues = true;
    }

    $types_information = Question::get_question_type_list();
    $form_title_extra  = get_lang($types_information[$type][1]);

    // form title
    $form->addElement('header', $objQuestion->submitText.': '.$form_title_extra);

    if ($fastEdition) {
        $form->setAllowRichEditorInForm(false);
        $form->setAllowedRichEditorList(array('questionDescription'));
    }

    // question form elements
    $objQuestion->createForm($form);

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

        // Answers
        $objQuestion->processAnswersCreation($form);

        // TODO: maybe here is the better place to index this tool, including answers text

        // redirect
        if ($objQuestion->type != HOT_SPOT && $objQuestion->type != HOT_SPOT_DELINEATION) {
            if (isset($_GET['editQuestion'])) {
                echo '<script type="text/javascript">window.location.href="admin.php?exerciseId='.$exerciseId.'&'.api_get_cidreq().'&message=ItemUpdated"</script>';
            } else {
                //New question
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
            Display::display_normal_message($msgErr); //main API
        }
        // display the form
        $form->display();
    }
}

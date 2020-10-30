<?php

/* For licensing terms, see /license.txt */

/**
 * This script allows to manage the statements of questions.
 * It is included from the script admin.php.
 *
 * @author Olivier Brouckaert
 */
if (isset($_GET['editQuestion'])) {
    $objQuestion = Question::read($_GET['editQuestion']);
    $action = api_get_self().'?'.api_get_cidreq().'&modifyQuestion='.$modifyQuestion.'&editQuestion='.$objQuestion->id.'&page='.$page;
} else {
    $objQuestion = Question::getInstance($_REQUEST['answerType']);
    $action = api_get_self().'?'.api_get_cidreq().'&modifyQuestion='.$modifyQuestion.'&newQuestion='.$newQuestion;
}

if (is_object($objQuestion)) {
    // FORM CREATION
    $form = new FormValidator('question_admin_form', 'post', $action);
    if (isset($_GET['editQuestion'])) {
        $class = 'btn btn-default';
        $text = get_lang('ModifyQuestion');
        $type = isset($_GET['type']) ? Security::remove_XSS($_GET['type']) : null;
    } else {
        $class = 'btn btn-default';
        $text = get_lang('AddQuestionToExercise');
        $type = $_REQUEST['answerType'];
    }

    $typesInformation = Question::getQuestionTypeList();
    $form_title_extra = isset($typesInformation[$type][1]) ? get_lang($typesInformation[$type][1]) : null;

    $code = '';
    if (isset($objQuestion->code) && !empty($objQuestion->code)) {
        $code = ' ('.$objQuestion->code.')';
    }

    // form title
    $form->addHeader($text.': '.$form_title_extra.$code);
    // question form elements
    $objQuestion->createForm($form, $objExercise);

    // answer form elements
    $objQuestion->createAnswersForm($form);

    // this variable  $show_quiz_edition comes from admin.php blocks the exercise/quiz modifications
    if (!empty($objExercise->id) && $objExercise->edit_exercise_in_lp == false) {
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
                if (empty($exerciseId)) {
                    Display::addFlash(Display::return_message(get_lang('ItemUpdated')));
                    $url = 'admin.php?exerciseId='.$exerciseId.'&'.api_get_cidreq().'&editQuestion='.$objQuestion->id;
                    echo '<script type="text/javascript">window.location.href="'.$url.'"</script>';
                    exit;
                }
                echo '<script type="text/javascript">window.location.href="admin.php?exerciseId='.$exerciseId.'&'.api_get_cidreq().'&page='.$page.'&message=ItemUpdated"</script>';
            } else {
                // New question
                $page = 1;
                $length = api_get_configuration_value('question_pagination_length');
                if (!empty($length)) {
                    $page = round($objExercise->getQuestionCount() / $length);
                }
                echo '<script type="text/javascript">window.location.href="admin.php?exerciseId='.$exerciseId.'&'.api_get_cidreq().'&page='.$page.'&message=ItemAdded"</script>';
            }
        } else {
            echo '<script type="text/javascript">window.location.href="admin.php?exerciseId='.$exerciseId.'&page='.$page.'&hotspotadmin='.$objQuestion->id.'&'.api_get_cidreq().'"</script>';
        }
    } else {
        if (isset($questionName)) {
            echo '<h3>'.$questionName.'</h3>';
        }
        if (!empty($pictureName)) {
            echo '<img src="../document/download.php?doc_url=%2Fimages%2F'.$pictureName.'" border="0">';
        }
        if (!empty($msgErr)) {
            echo Display::return_message($msgErr);
        }
        // display the form
        $form->display();
    }
}

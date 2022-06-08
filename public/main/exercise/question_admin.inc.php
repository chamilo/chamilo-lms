<?php

/* For licensing terms, see /license.txt */

/**
 * This script allows to manage the statements of questions.
 * It is included from the script admin.php.
 *
 * @author Olivier Brouckaert
 */
$type = isset($_REQUEST['answerType']) ? (int) $_REQUEST['answerType'] : 0;

if (isset($_GET['editQuestion'])) {
    $objQuestion = Question::read($_GET['editQuestion']);
    $action = api_get_self().'?'.api_get_cidreq().'&answerType='.$type.'&modifyQuestion='.$modifyQuestion.'&editQuestion='.$objQuestion->id.'&page='.$page;
} else {
    $objQuestion = Question::getInstance($_REQUEST['answerType']);
    $action = api_get_self().'?'.api_get_cidreq().'&answerType='.$type.'&modifyQuestion='.$modifyQuestion.'&newQuestion='.$newQuestion;
}

if (is_object($objQuestion)) {
    // FORM CREATION
    $form = new FormValidator('question_admin_form', 'post', $action);

    $class = 'btn btn--plain';
    if (isset($_GET['editQuestion'])) {
        $text = get_lang('Save the question');
        $type = isset($_GET['type']) ? Security::remove_XSS($_GET['type']) : null;
    } else {
        $text = get_lang('Add this question to the test');
        $type = $_REQUEST['answerType'];
    }

    $code = '';
    if (isset($objQuestion->code) && !empty($objQuestion->code)) {
        $code = ' ('.$objQuestion->code.')';
    }

    // form title
    $form->addHeader($text.': '.$objQuestion->getExplanation().$code);

    // question form elements
    $objQuestion->createForm($form, $objExercise);

    // answer form elements
    $objQuestion->createAnswersForm($form);

    // this variable  $show_quiz_edition comes from admin.php blocks the exercise/quiz modifications
    if (!empty($objExercise->id) && false == $objExercise->edit_exercise_in_lp) {
        $form->freeze();
    }

    // FORM VALIDATION
    if (isset($_POST['submitQuestion']) && $form->validate()) {
        // Question
        $objQuestion->processCreation($form, $objExercise);
        $objQuestion->processAnswersCreation($form, $objExercise);
        // TODO: maybe here is the better place to index this tool, including answers text
        // redirect
        if (HOT_SPOT != $objQuestion->type &&
            HOT_SPOT_DELINEATION != $objQuestion->type
        ) {
            if (isset($_GET['editQuestion'])) {
                if (empty($exerciseId)) {
                    Display::addFlash(Display::return_message(get_lang('Item updated')));
                    $url = api_get_path(WEB_CODE_PATH).'exercise/admin.php?exerciseId='.$exerciseId.'&'.api_get_cidreq().'&editQuestion='.$objQuestion->id;
                    header("Location: $url");
                    exit;
                }

                Display::addFlash(Display::return_message(get_lang('Item updated')));
                $url = api_get_path(WEB_CODE_PATH).'exercise/admin.php?exerciseId='.$exerciseId.'&'.api_get_cidreq().'&page='.$page;
                header("Location: $url");
                exit;
            } else {
                // New question
                $page = 1;
                $length = api_get_configuration_value('question_pagination_length');
                if (!empty($length)) {
                    $page = round($objExercise->getQuestionCount() / $length);
                }
                Display::addFlash(Display::return_message(get_lang('Item added')));
                $url = api_get_path(WEB_CODE_PATH).'exercise/admin.php?exerciseId='.$exerciseId.'&'.api_get_cidreq().'&page='.$page;
                header("Location: $url");
                exit;
            }
        } else {
            echo '<script>window.location.href="admin.php?exerciseId='.$exerciseId.'&page='.$page.'&hotspotadmin='.$objQuestion->id.'&'.api_get_cidreq().'"</script>';
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

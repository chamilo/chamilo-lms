<?php
/* For licensing terms, see /license.txt */

/**
 * This script allows to manage the statements of personality test questions.
 * It is included from the script admin.php.
 *
 * @package chamilo.exercise
 *
 * @author Jose Angel Ruiz (NOSOLORED)
 */
if (isset($_GET['editQuestion'])) {
    $objQuestion = Question::read($_GET['editQuestion'], null, true, true);
    $action = api_get_self().'?'.http_build_query([
        'exerciseId' => $objExercise->id,
        'modifyQuestion' => $modifyQuestion,
        'editQuestion' => $objQuestion->id,
        'page' => $page,
    ]).api_get_cidreq();
} else {
    $objQuestion = Question::getInstance($_REQUEST['answerType'], true);
    $action = api_get_self().'?'.http_build_query([
        'exerciseId' => $objExercise->id,
        'modifyQuestion' => $modifyQuestion, 
        'newQuestion' => $newQuestion,
    ]).'&'.api_get_cidreq();
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

    $typesInformation = Question::getQuestionPtTypeList();
    $formTitleExtra = isset($typesInformation[$type][1]) ? get_lang($typesInformation[$type][1]) : null;

    $code = '';
    if (isset($objQuestion->code) && !empty($objQuestion->code)) {
        $code = ' ('.$objQuestion->code.')';
    }

    // form title
    $form->addHeader($text.': '.$formTitleExtra.$code);

    // question form elements
    $objQuestion->createPtForm($form);

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

        if (isset($_GET['editQuestion'])) {
            if (empty($exerciseId)) {
                Display::addFlash(Display::return_message(get_lang('ItemUpdated')));
                $url = 'ptest_admin.php?'.http_build_query([
                    'exerciseId' => $exerciseId,
                    'editQuestion' => $objQuestion->id,
                ]).'&'.api_get_cidreq();
                echo '<script type="text/javascript">window.location.href="'.$url.'"</script>';
                exit;
            }
            echo '<script type="text/javascript">'.
                    'window.location.href="ptest_admin.php?'.http_build_query([
                        'exerciseId' => $exerciseId,
                        'page' => $page,
                        'message' => 'ItemUpdated',
                    ]).'&'.api_get_cidreq().'"'.
                '</script>';
        } else {
            // New question
            $page = 1;
            $length = api_get_configuration_value('question_pagination_length');
            if (!empty($length)) {
                $page = round($objExercise->getQuestionCount() / $length);
            }
            echo '<script type="text/javascript">'.
                'window.location.href="ptest_admin.php?'.http_build_query([
                    'exerciseId' => $exerciseId,
                    'page' => $page,
                    'message' => 'ItemAdded',
                ]).api_get_cidreq().'"'.
            '</script>';
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

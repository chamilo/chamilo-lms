<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;
/**
 * This script allows to manage the statements of questions.
 * It is included from the script admin.php.
 *
 * @author Olivier Brouckaert
 */
$type = isset($_REQUEST['answerType']) ? (int) $_REQUEST['answerType'] : 0;

if (isset($_GET['editQuestion'])) {
    $objQuestion = Question::read($_GET['editQuestion']);
    $action = api_get_self().'?'.api_get_cidreq()
        .'&exerciseId='.$exerciseId
        .'&answerType='.$type
        .'&modifyQuestion='.$modifyQuestion
        .'&editQuestion='.$objQuestion->id
        .'&page='.$page;
} else {
    $objQuestion = Question::getInstance($_REQUEST['answerType']);
    $action = api_get_self().'?'.api_get_cidreq()
        .'&exerciseId='.$exerciseId
        .'&answerType='.$type
        .'&modifyQuestion='.$modifyQuestion
        .'&newQuestion='.$newQuestion;
}

if (is_object($objQuestion)) {
    // FORM CREATION
    $form = new FormValidator('question_admin_form', 'post', $action);
    $form->addHidden('exerciseId', (int)$exerciseId);

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
        $objQuestion->processCreation($form, $objExercise);
        $objQuestion->processAnswersCreation($form, $objExercise);
        if (method_exists($objQuestion, 'saveAdaptiveScenario')) {
            $objQuestion->saveAdaptiveScenario($form, $objExercise);
        }

        if (in_array($objQuestion->type, [MULTIPLE_ANSWER_DROPDOWN, MULTIPLE_ANSWER_DROPDOWN_COMBINATION])) {
            $params = [
                'exerciseId' => $exerciseId,
                'page'       => $page,
                'mad_admin'  => $objQuestion->id,
            ];
            $url = api_get_path(WEB_CODE_PATH).'exercise/admin.php?'.api_get_cidreq().'&'.http_build_query($params);
            header("Location: $url");
            exit;
        }

        if (in_array($objQuestion->type, [HOT_SPOT, HOT_SPOT_COMBINATION, HOT_SPOT_DELINEATION])) {
            $qid = (int) ($objQuestion->id ?? 0);

            $url = api_get_path(WEB_CODE_PATH).'exercise/admin.php?'
                .api_get_cidreq()
                .'&exerciseId='.(int) $exerciseId
                .'&page='.(int) $page
                .'&hotspotadmin='.$qid;

            api_location($url);
        }

        if (isset($_GET['editQuestion'])) {
            if (empty($exerciseId)) {
                Display::addFlash(Display::return_message(get_lang('Item updated')));
                $url = api_get_path(WEB_CODE_PATH).'exercise/admin.php?exerciseId='.$exerciseId.'&'.api_get_cidreq().'&editQuestion='.$objQuestion->id;
                header("Location: $url");
                exit;
            }

            Display::addFlash(Display::return_message(get_lang('Item updated')));
            $url = api_get_path(WEB_CODE_PATH).'exercise/admin.php?exerciseId='.$exerciseId.'&'.api_get_cidreq().'&page='.$page;
            // Force fresh objects on next request (avoid stale session state)
            Session::erase('objQuestion');
            Session::erase('objAnswer');
            // Keep exercise around if you need it later
            Session::write('objExercise', $objExercise);

            // Now redirect to MAD admin
            header('Location: '.$url);
            exit;
        } else {
            $page = 1;
            $length = (int) api_get_setting('exercise.question_pagination_length');
            if ($length > 0) {
                $page = round($objExercise->getQuestionCount() / $length);
            }
            Display::addFlash(Display::return_message(get_lang('Item added')));
            $url = api_get_path(WEB_CODE_PATH).'exercise/admin.php?exerciseId='.$exerciseId.'&'.api_get_cidreq().'&page='.$page;
            header("Location: $url");
            exit;
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
        $form->display();
    }
}

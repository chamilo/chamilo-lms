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

$urlMainExercise = api_get_path(WEB_CODE_PATH).'exercice/';

// INIT QUESTION
if (isset($_GET['editQuestion'])) {
    $objQuestion = Question::read($_GET['editQuestion'], null, $objExercise);
    $action      = api_get_self()."?".api_get_cidreq(
    )."&myid=1&modifyQuestion=".$modifyQuestion."&editQuestion=".$objQuestion->id."&exerciseId=$objExercise->id";
} else {
    $objQuestion = Question::getInstance($_REQUEST['answerType'], $objExercise);
    $action      = api_get_self()."?".api_get_cidreq(
    )."&modifyQuestion=".$modifyQuestion."&newQuestion=".$newQuestion."&exerciseId=$objExercise->id";
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

    /*if (!isset($_GET['fromExercise'])) {
        $objQuestion->setDefaultQuestionValues = true;
    }*/

    // This condition depends of the exercice/question_create.php page that sets the "isContent" value
    if (isset($_REQUEST['newQuestion']) && $_REQUEST['newQuestion'] == 'yes' &&
        (isset($_REQUEST['isContent']) && $_REQUEST['isContent'] == '1')
    ) {
        $objQuestion->setDefaultQuestionValues = true;
    }

    $types_information = Question::get_question_type_list();
    $form_title_extra  = get_lang($types_information[$type][1]);

    // form title
    $form->addElement('header', $objQuestion->submitText.': '.$form_title_extra);

    // question form elements
    $objQuestion->createForm($form);

    // answer form elements
    $objQuestion->createAnswersForm($form);

    // this variable  $show_quiz_edition comes from admin.php blocks the exercise/quiz modifications

    if ($objExercise->edit_exercise_in_lp == false) {
        $form->freeze();
    }

    // Form validation
    //$result = $objQuestion->allQuestionWithMediaHaveTheSameCategory($exerciseId, 100);

    if (isset($_POST['submitQuestion']) && $form->validate()) {
        // Media is selected?
        $parentId = $form->getSubmitValue('parent_id');
        $categories = $form->getSubmitValue('questionCategory');
        $process = true;
        $message = null;

        // A media question was sent
        if (isset($parentId) && !empty($parentId)) {

            // No allowing 2 categories if a media was selected
            $tryAgain = Display::url(
                get_lang('TryAgain'),
                api_get_path(WEB_CODE_PATH).'exercice/admin.php?exerciseId='.$exerciseId.'&myid=1&editQuestion='.$objQuestion->id.'&'.api_get_cidreq(),
                array('class' => 'btn')
            );

            if (isset($categories) && !empty($categories)) {

                if (count($categories) > 1) {
                    $message = Display::display_warning_message(get_lang('WhenUsingAMediaQuestionYouCantAddMoreThanOneCategory'));
                    $message .= ' '.$tryAgain;
                    $process = false;
                }

                // If media exists
                $questionCategoriesOfMediaQuestions = $objQuestion->getQuestionCategoriesOfMediaQuestions($exerciseId, $parentId);

                if (!empty($questionCategoriesOfMediaQuestions)) {
                    // Check if the media sent matches other medias sent before
                    $result = $objQuestion->allQuestionWithMediaHaveTheSameCategory($exerciseId, $parentId, $categories, $objQuestion->id);

                    if ($result == false) {
                        $message = Display::display_warning_message(get_lang('TheSelectedCategoryDoesNotMatchWithTheOtherQuestionWithTheSameMediaQuestion'));
                        $message .= ' '.$tryAgain;
                        $process = false;
                    }
                }
            } else {
                if (!empty($objQuestion->category_list)) {
                    $message = Display::display_warning_message(get_lang('YouMustProvideACategoryBecauseTheCurrentCategoryDoesNotMatchOtherMediaQuestions'));
                    $message .= ' '.$tryAgain;
                    $process = false;
                }
            }
        }

        if ($process) {

            // Question
            $objQuestion->processCreation($form, $objExercise);

            // Answers
            $objQuestion->processAnswersCreation($form);

            // TODO: maybe here is the better place to index this tool, including answers text

            // redirect
            if ($objQuestion->type != HOT_SPOT && $objQuestion->type != HOT_SPOT_DELINEATION) {
                if (isset($_GET['editQuestion'])) {
                    echo '<script type="text/javascript">window.location.href="'.$urlMainExercise.'admin.php?exerciseId='.$exerciseId.'&'.api_get_cidreq().'&message=ItemUpdated"</script>';
                } else {
                    //New question
                    echo '<script type="text/javascript">window.location.href="'.$urlMainExercise.'admin.php?exerciseId='.$exerciseId.'&'.api_get_cidreq().'&message=ItemAdded"</script>';
                }
            } else {
                echo '<script type="text/javascript">window.location.href="'.$urlMainExercise.'admin.php?exerciseId='.$exerciseId.'&hotspotadmin='.$objQuestion->id.'&'.api_get_cidreq().'"</script>';
            }
        } else {
            echo $message;
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

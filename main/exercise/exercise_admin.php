<?php

/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * Exercise administration
 * This script allows to manage an exercise. It is included from
 * the script admin.php.
 *
 * @author Olivier Brouckaert, Julio Montoya
 */
require_once __DIR__.'/../inc/global.inc.php';
$this_section = SECTION_COURSES;

api_protect_course_script(true);

if (!api_is_allowed_to_edit(null, true)) {
    api_not_allowed(true);
}

$htmlHeadXtra[] = '<script>
    function activate_start_date() {
        if(document.getElementById(\'start_date_div\').style.display == \'none\') {
            document.getElementById(\'start_date_div\').style.display = \'block\';
        } else {
            document.getElementById(\'start_date_div\').style.display = \'none\';
        }
    }

    function activate_end_date() {
        if(document.getElementById(\'end_date_div\').style.display == \'none\') {
            document.getElementById(\'end_date_div\').style.display = \'block\';
        } else {
            document.getElementById(\'end_date_div\').style.display = \'none\';
        }
    }

    function feedbackselection() {
        var index = document.exercise_admin.exerciseFeedbackType.selectedIndex;

        if (index == \'1\') {
            document.exercise_admin.exerciseType[1].checked=true;
            document.exercise_admin.exerciseType[0].disabled=true;
        } else {
            document.exercise_admin.exerciseType[0].disabled=false;
        }
    }

    function option_time_expired() {
        if(document.getElementById(\'timercontrol\').style.display == \'none\') {
          document.getElementById(\'timercontrol\').style.display = \'block\';
        } else {
          document.getElementById(\'timercontrol\').style.display = \'none\';
        }
    }

    function check_per_page_one() {
         //document.getElementById(\'exerciseType_0\').checked=true;
    }

    function check_per_page_all() {
        if (document.getElementById(\'exerciseType_1\') && document.getElementById(\'exerciseType_1\').checked) {
            document.getElementById(\'exerciseType_0\').checked = true;
        }
    }

    function check_feedback() {
        if (document.getElementById(\'result_disabled_1\').checked == true) {
            document.getElementById(\'result_disabled_0\').checked = true;
        }

        if (document.getElementById(\'exerciseType_0\').checked == true) {
            document.getElementById(\'result_disabled_0\').checked = true;
        }
    }

    function check_direct_feedback() {
        document.getElementById(\'option_page_one\').checked = true;
        document.getElementById(\'result_disabled_0\').checked = true;
    }

    function check_results_disabled() {
        document.getElementById(\'exerciseType_2\').checked = true;
    }

    function disabledHideRandom() {
        $("#hidden_random option:eq(0)").prop("selected", true);
        $("#hidden_random").hide();
    }

    function checkQuestionSelection() {
        var selection = $("#questionSelection option:selected").val();
        switch (selection) {
            case "'.EX_Q_SELECTION_ORDERED.'":
                disabledHideRandom();
                $("#hidden_matrix").hide();
                break;
            case "'.EX_Q_SELECTION_RANDOM.'":
                $("#hidden_random").show();
                $("#hidden_matrix").hide();
                break;
            case "'.EX_Q_SELECTION_CATEGORIES_ORDERED_QUESTIONS_ORDERED.'":
                disabledHideRandom();
                $("#hidden_matrix").show();
                break;
            case "per_categories":
                $("#questionSelection option:eq('.EX_Q_SELECTION_CATEGORIES_ORDERED_QUESTIONS_ORDERED.')").prop("selected", true);
                disabledHideRandom();
                $("#hidden_matrix").show();
                break;
            default:
                disabledHideRandom();
                $("#hidden_matrix").show();
                break;
        }
    }

    function setFocus(){
        $("#exercise_title").focus();
    }

    // to correct #4029 Random and number of attempt menu empty added window.onload=advanced_parameters;
    $(function() {
        setFocus();
    });
</script>';

$objExercise = new Exercise();
$course_id = api_get_course_int_id();

if (isset($_GET['exerciseId'])) {
    $form = new FormValidator(
        'exercise_admin',
        'post',
        api_get_self().'?'.api_get_cidreq().'&exerciseId='.intval($_GET['exerciseId'])
    );
    $objExercise->read($_GET['exerciseId'], false);
    $form->addElement('hidden', 'edit', 'true');
} else {
    $form = new FormValidator(
        'exercise_admin',
        'post',
        api_get_self().'?'.api_get_cidreq()
    );
    $form->addElement('hidden', 'edit', 'false');
}

$objExercise->createForm($form);

if ($form->validate()) {
    $objExercise->processCreation($form);
    if ($form->getSubmitValue('edit') === 'true') {
        Display::addFlash(
            Display::return_message(get_lang('ExerciseEdited'), 'success')
        );
    } else {
        Display::addFlash(
            Display::return_message(get_lang('ExerciseAdded'), 'success')
        );
    }
    $exercise_id = $objExercise->iid;
    Session::erase('objExercise');
    header('Location:admin.php?exerciseId='.$exercise_id.'&'.api_get_cidreq());
    exit;
} else {
    if (api_is_in_gradebook()) {
        $interbreadcrumb[] = [
            'url' => Category::getUrl(),
            'name' => get_lang('ToolGradebook'),
        ];
    }
    $nameTools = get_lang('ExerciseManagement');
    $interbreadcrumb[] = [
        'url' => 'exercise.php?'.api_get_cidreq(),
        'name' => get_lang('Exercises'),
    ];
    $interbreadcrumb[] = [
        'url' => 'admin.php?exerciseId='.$objExercise->iid.'&'.api_get_cidreq(),
        'name' => $objExercise->selectTitle(true),
    ];

    Display::display_header($nameTools, get_lang('Exercise'));

    echo '<div class="actions">';
    if ($objExercise->iid != 0) {
        echo '<a href="admin.php?'.api_get_cidreq().'&exerciseId='.$objExercise->iid.'">'.
            Display::return_icon('back.png', get_lang('GoBackToQuestionList'), '', ICON_SIZE_MEDIUM).'</a>';
    } else {
        if (!empty($_GET['lp_id']) || !empty($_POST['lp_id'])) {
            if (!empty($_POST['lp_id'])) {
                $lp_id = $_POST['lp_id'];
            //TODO:this remains to be implemented after press the first post
            } else {
                $lp_id = $_GET['lp_id'];
            }
            $lp_id = (int) $lp_id;
            echo "<a
                href=\"../lp/lp_controller.php?".api_get_cidreq()."&gradebook=&action=add_item&type=step&lp_id=".$lp_id."#resource_tab-2\">".
                Display::return_icon('back.png', get_lang("BackTo").' '.get_lang('LearningPaths'), '', ICON_SIZE_MEDIUM)."</a>";
        } else {
            echo '<a href="exercise.php?'.api_get_cidreq().'">'.
                Display::return_icon('back.png', get_lang('BackToExercisesList'), '', ICON_SIZE_MEDIUM).
                '</a>';
        }
    }
    echo '</div>';

    if (in_array($objExercise->getFeedbackType(), [EXERCISE_FEEDBACK_TYPE_DIRECT, EXERCISE_FEEDBACK_TYPE_POPUP])) {
        echo Display::return_message(get_lang('DirectFeedbackCantModifyTypeQuestion'));
    }

    if (api_get_setting('search_enabled') === 'true' &&
        !extension_loaded('xapian')
    ) {
        echo Display::return_message(get_lang('SearchXapianModuleNotInstalled'), 'error');
    }

    // to hide the exercise description
    echo '<style> .media { display:none;}</style>';
    $form->display();
}
Display::display_footer();

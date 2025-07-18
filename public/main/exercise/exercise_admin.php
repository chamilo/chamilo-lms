<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Enums\ActionIcon;
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
$exerciseId = isset($_REQUEST['exerciseId']) ? (int) $_REQUEST['exerciseId'] : 0;
//INIT FORM
if (!empty($exerciseId)) {
    $form = new FormValidator(
        'exercise_admin',
        'post',
        api_get_self().'?'.api_get_cidreq().'&exerciseId='.$exerciseId
    );
    $objExercise->read($exerciseId, false);
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
    if ('true' === $form->getSubmitValue('edit')) {
        Display::addFlash(
            Display::return_message(get_lang('Test name and settings have been saved.'), 'success')
        );
    } else {
        Display::addFlash(
            Display::return_message(get_lang('Test added'), 'success')
        );
    }
    $exercise_id = $objExercise->getId();
    Session::erase('objExercise');
    header('Location:admin.php?exerciseId='.$exercise_id.'&'.api_get_cidreq());
    exit;
} else {
    if (api_is_in_gradebook()) {
        $interbreadcrumb[] = [
            'url' => Category::getUrl(),
            'name' => get_lang('Assessments'),
        ];
    }
    $nameTools = get_lang('Tests management');
    $interbreadcrumb[] = [
        'url' => 'exercise.php?'.api_get_cidreq(),
        'name' => get_lang('Tests'),
    ];
    $interbreadcrumb[] = [
        'url' => 'admin.php?exerciseId='.$objExercise->getId().'&'.api_get_cidreq(),
        'name' => $objExercise->selectTitle(true),
    ];

    Display::display_header($nameTools, get_lang('Test'));

    $actions = '';
    if (0 != $objExercise->getId()) {
        $actions .= '<a href="admin.php?'.api_get_cidreq().'&exerciseId='.$objExercise->getId().'">'.
            Display::getMdiIcon(ActionIcon::BACK, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Go back to the questions list')).'</a>';
    } else {
        if (!empty($_GET['lp_id']) || !empty($_POST['lp_id'])) {
            if (!empty($_POST['lp_id'])) {
                $lp_id = $_POST['lp_id'];
            } else {
                //TODO:this remains to be implemented after press the first post
                $lp_id = $_GET['lp_id'];
            }
            $lp_id = (int) $lp_id;
            $actions .= '<a
                href="../lp/lp_controller.php?'.api_get_cidreq().'&gradebook=&action=add_item&type=step&lp_id='.$lp_id.'#resource_tab-2">'.
                Display::getMdiIcon('back', 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Back to').' '.get_lang('Learning paths')).'</a>';
        } else {
            $actions .= '<a href="exercise.php?'.api_get_cidreq().'">'.
                Display::getMdiIcon(ActionIcon::BACK, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Back to test list')).
                '</a>';
        }
    }

    echo Display::toolbarAction('toolbar', [$actions]);

    if (in_array($objExercise->getFeedbackType(), [EXERCISE_FEEDBACK_TYPE_DIRECT, EXERCISE_FEEDBACK_TYPE_POPUP])) {
        echo Display::return_message(
            get_lang(
                'The test type cannot be modified since it was set to self evaluation. Self evaluation gives you the possibility to give direct feedback to the user, but this is not compatible with all question types and, so this type quiz cannot be changed afterward.'
            )
        );
    }

    if ('true' === api_get_setting('search_enabled') &&
        !extension_loaded('xapian')
    ) {
        echo Display::return_message(get_lang('The Xapian search module is not installed'), 'error');
    }

    // to hide the exercise description
    echo '<style> .media { display:none;}</style>';
    $form->display();
}
Display::display_footer();

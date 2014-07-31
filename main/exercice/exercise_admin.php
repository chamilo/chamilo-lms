<?php
/* For licensing terms, see /license.txt */
/**
* Exercise administration
* This script allows to manage an exercise. It is included from
* the script admin.php
* @package chamilo.exercise
* @author Olivier Brouckaert, Julio Montoya
*/
/**
 * Code
 */

use \ChamiloSession as Session;


require_once 'exercise.class.php';
require_once 'question.class.php';
require_once 'answer.class.php';
////require_once '../inc/global.inc.php';
$this_section = SECTION_COURSES;

if (!api_is_allowed_to_edit(null,true)) {
	api_not_allowed(true);
}

$url = api_get_path(WEB_AJAX_PATH).'exercise.ajax.php?1=1';

$htmlHeadXtra[] = '<script>
    function check() {
        $("#category_id option:selected").each(function() {
            var id = $(this).val();
            var name = $(this).text();
            if (id != "" ) {
                $.ajax({
                    async: false,
                    url: "'.$url.'&a=exercise_category_exists",
                    data: "id="+id,
                    success: function(return_value) {
                        if (return_value == 0 ) {
                            alert("'.get_lang('CategoryDoesNotExists').'");

                            // Deleting select option tag
                            $("#category_id").find("option").remove();

                            $(".holder li").each(function () {
                                if ($(this).attr("rel") == id) {
                                    $(this).remove();
                                }
                            });
                        } else {
                            option_text = $("#category_id").find("option").text();
                            $("#category_inputs").append( option_text + "<input type=text>");
                        }
                    },
                });
            }
        });
    }

    $(function() {
        $("#category_id").fcbkcomplete({
            json_url: "'.$url.'&a=search_category",
            maxitems: 20,
            addontab: false,
            input_min_size: 1,
            cache: false,
            complete_text: "'.get_lang('StartToType').'",
            firstselected: false,
            onselect: check,
            // oncreate: add_item,
            filter_selected: true,
            newel: true
        });
        $("input[name=\'model_type\']").each(function(index, value) {
            $(this).click(function() {
                var value = $(this).attr("value");
                // Committee‎
                if (value == 2) {
                    $("#score_type").show();
                } else {
                    $("#score_type").hide();
                }

            });
        });
    });

    function FCKeditor_OnComplete( editorInstance ) {
           if (document.getElementById ( \'HiddenFCK\' + editorInstance.Name )) {
              HideFCKEditorByInstanceName (editorInstance.Name);
           }
    }

    function HideFCKEditorByInstanceName ( editorInstanceName ) {
        if (document.getElementById ( \'HiddenFCK\' + editorInstanceName ).className == "HideFCKEditor" ) {
              document.getElementById ( \'HiddenFCK\' + editorInstanceName ).className = "media";
        }
    }

    function show_media() {
        var my_display = document.getElementById(\'HiddenFCKexerciseDescription\').style.display;
            if(my_display== \'none\' || my_display == \'\') {
                document.getElementById(\'HiddenFCKexerciseDescription\').style.display = \'block\';
                document.getElementById(\'media_icon\').innerHTML=\' '.Display::return_icon('media-question.png').' '.addslashes(get_lang('ExerciseDescription')).'\';
            } else {
                document.getElementById(\'HiddenFCKexerciseDescription\').style.display = \'none\';
                document.getElementById(\'media_icon\').innerHTML=\'  '.Display::return_icon('media-question.png').' '.addslashes(get_lang('ExerciseDescription')).'\';
            }
    }

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
        if(document.getElementById(\'timercontrol\').style.display == \'none\')
        {
          document.getElementById(\'timercontrol\').style.display = \'block\';
        } else {
          document.getElementById(\'timercontrol\').style.display = \'none\';
        }
    }

    function check_per_page_one() {
        /*if (document.getElementById(\'divtimecontrol\').style.display==\'none\') {
            document.getElementById(\'divtimecontrol\').style.display=\'block\';
            document.getElementById(\'divtimecontrol\').display=block;
            document.getElementById(\'timecontrol\').display=none;
        }*/
        document.getElementById(\'exerciseType_0\').checked=true;
    }

    function check_per_page_all() {
        /*if (document.getElementById(\'divtimecontrol\').style.display==\'block\') {
            document.getElementById(\'divtimecontrol\').style.display=\'none\';
            document.getElementById(\'enabletimercontroltotalminutes\').value=\'\';
        }*/

        if (document.getElementById(\'exerciseType_1\') && document.getElementById(\'exerciseType_1\').checked) {
            document.getElementById(\'exerciseType_0\').checked = true;
        }
    }

    function check_feedback() {
        document.getElementById(\'result_disabled_0\').checked = true;
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
        var selection = $("#questionSelection option:selected").val()
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
</script>';

// to correct #4029 Random and number of attempt menu empty added window.onload=advanced_parameters;
$htmlHeadXtra[] = '<script>
function setFocus(){
    $("#exercise_title").focus();
}
$(document).ready(function () {
    setFocus();
});
</script>';

// INIT EXERCISE

$objExercise = new Exercise();
$objExercise->setCategoriesGrouping(false);
$course_id = api_get_course_int_id();

//INIT FORM
if (isset($_GET['exerciseId'])) {
	$form = new FormValidator('exercise_admin', 'post', api_get_self().'?'.api_get_cidreq().'&exerciseId='.intval($_GET['exerciseId']));
	$objExercise->read($_GET['exerciseId']);
	$form->addElement('hidden','edit','true');
} else {
	$form = new FormValidator('exercise_admin','post',api_get_self().'?'.api_get_cidreq());
	$form->addElement('hidden','edit','false');
}

$objExercise->createForm($form);

// VALIDATE FORM
if ($form->validate()) {
    $objExercise->processCreation($form);
    if ($form->getSubmitValue('edit') == 'true') {
        $message = 'ExerciseEdited';
    } else {
        $message = 'ExerciseAdded';
    }
    $exercise_id = $objExercise->id;
    Session::erase('objExercise');
    header('Location:admin.php?message='.$message.'&exerciseId='.$exercise_id.'&'.api_get_cidreq());
    exit;
} else {
    // DISPLAY FORM
	if (isset($_SESSION['gradebook'])) {
		$gradebook=	$_SESSION['gradebook'];
	}

	if (!empty($gradebook) && $gradebook=='view') {
		$interbreadcrumb[]= array ('url' => '../gradebook/'.$_SESSION['gradebook_dest'],'name' => get_lang('ToolGradebook'));
	}
	$nameTools = get_lang('ExerciseManagement');
	$interbreadcrumb[] = array("url"=>'exercice.php', 'name'=> get_lang('Exercices'));
    $interbreadcrumb[] = array("url"=>"admin.php?exerciseId=".$objExercise->id, "name" => $objExercise->name);

	Display::display_header($nameTools,get_lang('Exercise'));

	echo '<div class="actions">';

	if ($objExercise->id != 0) {
	    echo '<a href="admin.php?'.api_get_cidReq().'&exerciseId='.$objExercise->id.'">' . Display :: return_icon('back.png', get_lang('GoBackToQuestionList'),'',ICON_SIZE_MEDIUM).'</a>';
	} else {
		if (!empty($_GET['lp_id']) || !empty($_POST['lp_id'])){
			if (!empty($_POST['lp_id'])){
				$lp_id = Security::remove_XSS($_POST['lp_id']);//TODO:this remains to be implemented after press the first post
			} else {
				$lp_id = Security::remove_XSS($_GET['lp_id']);
			}
			echo "<a href=\"../newscorm/lp_controller.php?".api_get_cidreq()."&gradebook=&action=add_item&type=step&lp_id=".$lp_id."#resource_tab-2\">".Display::return_icon('back.png', get_lang("BackTo").' '.get_lang("LearningPaths"),'',ICON_SIZE_MEDIUM)."</a>";
        } else {
	    	echo '<a href="exercice.php">' . Display :: return_icon('back.png', get_lang('BackToExercisesList'),'',ICON_SIZE_MEDIUM).'</a>';
		}
	}
	echo '</div>';

    if ($objExercise->feedback_type == 1) {
		Display::display_normal_message(get_lang('DirectFeedbackCantModifyTypeQuestion'));
    }

	if (api_get_setting('search_enabled')=='true' && !extension_loaded('xapian')) {
		Display::display_error_message(get_lang('SearchXapianModuleNotInstalled'));
	}

    if ($objExercise->id != 0 && $objExercise->edit_exercise_in_lp == false) {
        $form->freeze();
    }
	$form->display();
}
Display::display_footer();

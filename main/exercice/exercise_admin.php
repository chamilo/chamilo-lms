<?php
/* For licensing terms, see /license.txt */
/**
* Exercise administration
* This script allows to manage an exercise. It is included from 
* the script admin.php
* @package chamilo.exercise
* @author Olivier Brouckaert
*/
/**
 * Code
 */
// name of the language file that needs to be included
$language_file='exercice';

require_once 'exercise.class.php';
require_once 'question.class.php';
require_once 'answer.class.php';
require_once '../inc/global.inc.php';
require_once 'exercise.lib.php';
$this_section = SECTION_COURSES;

if(!api_is_allowed_to_edit(null,true)) {
	api_not_allowed(true);
}

$htmlHeadXtra[] = '<script type="text/javascript">
		function advanced_parameters() {
			if(document.getElementById(\'options\').style.display == \'none\') {
				document.getElementById(\'options\').style.display = \'block\';
				document.getElementById(\'img_plus_and_minus\').innerHTML=\' <img style="vertical-align:middle;" src="../img/div_hide.gif" alt="" /> '.addslashes(api_htmlentities(get_lang('AdvancedParameters'))).'\';
			} else {
				document.getElementById(\'options\').style.display = \'none\';
				document.getElementById(\'img_plus_and_minus\').innerHTML=\' <img style="vertical-align:middle;" src="../img/div_show.gif" alt="" /> '.addslashes(api_htmlentities(get_lang('AdvancedParameters'))).'\';
			}
		}

		function FCKeditor_OnComplete( editorInstance ) {
			   if (document.getElementById ( \'HiddenFCK\' + editorInstance.Name )) {
			      HideFCKEditorByInstanceName (editorInstance.Name);
			   }
		}

		function HideFCKEditorByInstanceName ( editorInstanceName ) {
			if (document.getElementById ( \'HiddenFCK\' + editorInstanceName ).className == "HideFCKEditor" )
			{
			      document.getElementById ( \'HiddenFCK\' + editorInstanceName ).className = "media";
			}
		}
		
		function show_media() {
			var my_display = document.getElementById(\'HiddenFCKexerciseDescription\').style.display;
				if(my_display== \'none\' || my_display == \'\') {
					document.getElementById(\'HiddenFCKexerciseDescription\').style.display = \'block\';
					document.getElementById(\'media_icon\').innerHTML=\' <img src="../img/looknfeelna.png" alt="" /> '.addslashes(api_htmlentities(get_lang('ExerciseDescription'))).'\';
				} else {
					document.getElementById(\'HiddenFCKexerciseDescription\').style.display = \'none\';
					document.getElementById(\'media_icon\').innerHTML=\' <img src="../img/looknfeel.png" alt="" /> '.addslashes(api_htmlentities(get_lang('ExerciseDescription'))).'\';
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
			if (document.getElementById(\'exerciseType_1\').checked) {
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
		</script>';

    // to correct #4029 Random and number of attempt menu empty added window.onload=advanced_parameters;
$htmlHeadXtra[] = '<script type="text/javascript">
function setFocus(){
    $("#exercise_title").focus();
}
$(document).ready(function () {
    setFocus();
}); 
    window.onload=advanced_parameters;
</script>';

// INIT EXERCISE

$objExercise = new Exercise();

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
	api_session_unregister('objExercise');
	header('Location:admin.php?message='.$message.'&exerciseId='.$exercise_id);
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
	    echo '<a href="admin.php?'.api_get_cidReq().'&exerciseId='.$objExercise->id.'">' . Display :: return_icon('back.png', get_lang('GoBackToQuestionList'),'','32').'</a>';
	} else {
		if (!empty($_GET['lp_id']) || !empty($_POST['lp_id'])){		
			if (!empty($_POST['lp_id'])){			
				$lp_id=Security::remove_XSS($_POST['lp_id']);//TODO:this remains to be implemented after press the first post
			}
			else{
				$lp_id=Security::remove_XSS($_GET['lp_id']);
			}
			
			echo "<a href=\"../newscorm/lp_controller.php?".api_get_cidreq()."&gradebook=&action=add_item&type=step&lp_id=".$lp_id."#resource_tab-2\">".Display::return_icon('back.png', get_lang("BackTo").' '.get_lang("LearningPaths"),'','32')."</a>";		
		}	
		else{
			
	    	echo '<a href="exercice.php">' . Display :: return_icon('back.png', get_lang('BackToExercisesList'),'','32').'</a>';
		}
	}
	echo '</div>';	
	
	if ($objExercise->feedbacktype==1)
		Display::display_normal_message(get_lang('DirectFeedbackCantModifyTypeQuestion'));
		
	if (api_get_setting('search_enabled')=='true' && !extension_loaded('xapian')) {
		Display::display_error_message(get_lang('SearchXapianModuleNotInstalled'));
	}

	// to hide the exercise description
	echo '<style> .media { display:none;}</style>';
		
	if (isset($objExercise) && !empty($objExercise->id)) {
		$TBL_LP_ITEM	= Database::get_course_table(TABLE_LP_ITEM);
		$sql="SELECT max_score FROM $TBL_LP_ITEM
			  WHERE item_type = '".TOOL_QUIZ."' AND path ='".Database::escape_string($objExercise->id)."'";			  
		$result = Database::query($sql);
		if (Database::num_rows($result) > 0) {		
			$form->freeze();
		}	
	}
	$form->display();
}
Display::display_footer();

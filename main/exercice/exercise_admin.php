<?php
/* For licensing terms, see /dokeos_license.txt */

/**
*	Exercise administration
*	This script allows to manage an exercise. It is included from the script admin.php
*	@package dokeos.exercise
* 	@author Olivier Brouckaert
* 	@version $Id$
*/


// name of the language file that needs to be included
$language_file='exercice';

include('exercise.class.php');
include('question.class.php');
include('answer.class.php');
include('../inc/global.inc.php');
include('exercise.lib.php');
$this_section=SECTION_COURSES;

if(!api_is_allowed_to_edit(null,true)) {
	api_not_allowed(true);
}

$htmlHeadXtra[] = '<script>

		function advanced_parameters() {
			if(document.getElementById(\'options\').style.display == \'none\') {
				document.getElementById(\'options\').style.display = \'block\';
				document.getElementById(\'img_plus_and_minus\').innerHTML=\'&nbsp;<img style="vertical-align:middle;" src="../img/div_hide.gif" alt="" />&nbsp;'.get_lang('AdvancedParameters').'\';

			} else {

				document.getElementById(\'options\').style.display = \'none\';
				document.getElementById(\'img_plus_and_minus\').innerHTML=\'&nbsp;<img style="vertical-align:middle;" src="../img/div_show.gif" alt="" />&nbsp;'.get_lang('AdvancedParameters').'\';
			}
		}


		function FCKeditor_OnComplete( editorInstance )
			{
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
				document.getElementById(\'media_icon\').innerHTML=\'&nbsp;<img src="../img/looknfeelna.png" alt="" />&nbsp;'.get_lang('ExerciseDescription').'\';
			} else {
				document.getElementById(\'HiddenFCKexerciseDescription\').style.display = \'none\';
				document.getElementById(\'media_icon\').innerHTML=\'&nbsp;<img src="../img/looknfeel.png" alt="" />&nbsp;'.get_lang('ExerciseDescription').'\';

			}
		}

		function timelimit() {
			if(document.getElementById(\'options2\').style.display == \'none\') {
				document.getElementById(\'options2\').style.display = \'block\';
			} else {
				document.getElementById(\'options2\').style.display = \'none\';
			}
		}

		function feedbackselection()
		{
			var index = document.exercise_admin.exerciseFeedbackType.selectedIndex;

			if (index == \'1\') {
				document.exercise_admin.exerciseType[1].checked=true;
				document.exercise_admin.exerciseType[0].disabled=true;

			} else {
				document.exercise_admin.exerciseType[0].disabled=false;
			}
		}
		</script>';

/*********************
 * INIT EXERCISE
 *********************/

include_once(api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');
$objExercise = new Exercise();

/*********************
 * INIT FORM
 *********************/
if(isset($_GET['exerciseId'])) {
	$form = new FormValidator('exercise_admin', 'post', api_get_self().'?exerciseId='.$_GET['exerciseId']);
	$objExercise -> read (intval($_GET['exerciseId']));
	$form -> addElement ('hidden','edit','true');
} else {
	$form = new FormValidator('exercise_admin');
	$form -> addElement ('hidden','edit','false');
}

$objExercise -> createForm ($form);

/*********************
 * VALIDATE FORM
 *********************/
if ($form -> validate()) {
	$objExercise -> processCreation($form);
	if ($form -> getSubmitValue('edit') == 'true') {
		header('Location:exercice.php?message=ExerciseEdited&'.api_get_cidreq());
	} else {
		header('Location:admin.php?message=ExerciseAdded&exerciseId='.$objExercise->id);
	}
} else {
	/*********************
	 * DISPLAY FORM
	 *********************/
	if (isset($_SESSION['gradebook'])) {
		$gradebook=	$_SESSION['gradebook'];
	}

	if (!empty($gradebook) && $gradebook=='view') {
		$interbreadcrumb[]= array (
				'url' => '../gradebook/'.$_SESSION['gradebook_dest'],
				'name' => get_lang('Gradebook')
			);
	}
	$nameTools=get_lang('ExerciseManagement');
	$interbreadcrumb[] = array ("url"=>"exercice.php", "name"=> get_lang('Exercices'));
	Display::display_header($nameTools,"Exercise");
	if ($objExercise->feedbacktype==1)
		Display::display_normal_message(get_lang("DirectFeedbackCantModifyTypeQuestion"));
		if(api_get_setting('search_enabled')=='true' && !extension_loaded('xapian')) {
				Display::display_error_message(get_lang('SearchXapianModuleNotInstaled'));
		}

	// to hide the exercise description
	echo '<style> .media { display:none;}</style>';
	$form -> display ();
}
Display::display_footer();
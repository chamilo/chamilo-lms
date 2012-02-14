<?php
/* For licensing terms, see /license.txt */
/**
*	Statement (?) administration
*	This script allows to manage the statements of questions.
* 	It is included from the script admin.php
*	@package chamilo.exercise
* 	@author Olivier Brouckaert
* 	@version $Id: question_admin.inc.php 22126 2009-07-15 22:38:39Z juliomontoya $
*/
/**
 * Code
 */

// ALLOWED_TO_INCLUDE is defined in admin.php
if(!defined('ALLOWED_TO_INCLUDE')) {
	exit();
}

$course_id = api_get_course_int_id();

// INIT QUESTION
if (isset($_GET['editQuestion'])) {
	$objQuestion = Question::read ($_GET['editQuestion']);
	$action = api_get_self()."?".api_get_cidreq()."&myid=1&modifyQuestion=".$modifyQuestion."&editQuestion=".$objQuestion->id;

	if (isset($exerciseId) && !empty($exerciseId)) {
		$TBL_LP_ITEM	= Database::get_course_table(TABLE_LP_ITEM);
		$sql="SELECT max_score FROM $TBL_LP_ITEM
			  WHERE c_id = $course_id AND item_type = '".TOOL_QUIZ."' AND path ='".Database::escape_string($exerciseId)."'";
		$result = Database::query($sql);
		if (Database::num_rows($result) > 0) {
			//Display::display_warning_message(get_lang('EditingScoreCauseProblemsToExercisesInLP'));
		}
	}
} else {
	$objQuestion = Question :: getInstance($_REQUEST['answerType']);
	$action = api_get_self()."?".api_get_cidreq()."&modifyQuestion=".$modifyQuestion."&newQuestion=".$newQuestion;
}


if(is_object($objQuestion)) {
	//INIT FORM    
	$form = new FormValidator('question_admin_form','post',$action);
    //FORM CREATION
    
	if(isset($_GET['editQuestion'])) {
		$class="save";
		$text=get_lang('ModifyQuestion');
		$type = Security::remove_XSS($_GET['type']);
	} else {
		$class="add";
		$text=get_lang('AddQuestionToExercise');
		$type = $_REQUEST['answerType'];
	}

	$types_information = $objQuestion->get_question_type_list();
	$form_title_extra = get_lang($types_information[$type][1]);

	// form title
	$form->addElement('header', '', $text.': '.$form_title_extra);
    
	// question form elements
	$objQuestion->createForm ($form,array('Height'=>150));

	// answer form elements
	$objQuestion->createAnswersForm ($form);

	// this variable  $show_quiz_edition comes from admin.php blocks the exercise/quiz modifications
	if (!$show_quiz_edition) {
		$form->freeze();
	}

	// submit button is implemented in every question type

	//$form->addElement('style_submit_button','submitQuestion',$text, 'class="'.$class.'"');
	//$renderer = $form->defaultRenderer();
	//$renderer->setElementTemplate('<div class="row"><div class="label">{label}</div><div class="formw">{element}</div></div>','submitQuestion');
	
	// FORM VALIDATION
	if (isset($_POST['submitQuestion']) && $form->validate()) {	    

		// question
	    $objQuestion->processCreation($form,$objExercise);
	    // answers
        
	    $objQuestion->processAnswersCreation($form,$nb_answers);

        // TODO: maybe here is the better place to index this tool, including answers text

	    // redirect
	    if ($objQuestion -> type != HOT_SPOT && $objQuestion -> type !=  HOT_SPOT_DELINEATION) {
	    	
	    	if(isset($_GET['editQuestion'])) {
	    		echo '<script type="text/javascript">window.location.href="admin.php?exerciseId='.$exerciseId.'&message=ItemUpdated"</script>';
	    	} else {
	    		//New question
	    		echo '<script type="text/javascript">window.location.href="admin.php?exerciseId='.$exerciseId.'&message=ItemAdded"</script>';
	    	}
	    } else {
	    	echo '<script type="text/javascript">window.location.href="admin.php?exerciseId='.$exerciseId.'&hotspotadmin='.$objQuestion->id.'"</script>';
	    }
	} else {	 
		echo '<h3>'.$questionName.'</h3>';
		if(!empty($pictureName)){
			echo '<img src="../document/download.php?doc_url=%2Fimages%2F'.$pictureName.'" border="0">';
		}
		if(!empty($msgErr)) {
			Display::display_normal_message($msgErr); //main API
		}
		// display the form
		$form->display();
	}
}

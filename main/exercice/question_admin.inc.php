<? // $Id: question_admin.inc.php 10597 2007-01-05 14:14:29Z elixir_inter $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact address: Dokeos, 44 rue des palais, B-1030 Brussels, Belgium
	Mail: info@dokeos.com
==============================================================================
*/
/**
==============================================================================
*	STATEMENT ADMINISTRATION
*	This script allows to manage the statements of questions.
*	It is included from the script admin.php
*
*	@author Olivier Brouckaert
*	@package dokeos.exercise
==============================================================================
*/

/*
==============================================================================
		INIT SECTION
==============================================================================
*/

include_once(api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');

// ALLOWED_TO_INCLUDE is defined in admin.php
if(!defined('ALLOWED_TO_INCLUDE'))
{
	exit();
}


/*********************
 * INIT QUESTION
 *********************/
if(isset($_GET['editQuestion']))
{
	$objQuestion = Question::read ($_GET['editQuestion']);
	$action = $_SERVER['PHP_SELF']."?modifyQuestion=".$modifyQuestion."&editQuestion=".$objQuestion->id;
}
else
{
	$objQuestion = Question :: getInstance($_REQUEST['answerType']);
	$action = $_SERVER['PHP_SELF']."?modifyQuestion=".$modifyQuestion."&newQuestion=".$newQuestion;
}

if(is_object($objQuestion))
{

	/*********************
	 * FORM STYLES
	 *********************/
	 // if you have a better way to improve the display, please inform me e.marguin@elixir-interactive.com
	$styles = '
	<style>
	div.row div.label{
		width: 10%;
	}
	div.row div.formw{
		width: 85%;
	}
	</style>
	';
	echo $styles;
	
	
	/*********************
	 * INIT FORM
	 *********************/
	$form = new FormValidator('question_admin_form','post',$action);
	
	
	/*********************
	 * FORM CREATION
	 *********************/
	
	$objQuestion -> createForm ($form);	
	
	$objQuestion -> createAnswersForm ($form);	
	
	$form->addElement('submit','submitQuestion',get_lang('Ok'));
	
	
	/**********************
	 * FORM VALIDATION  
	 **********************/
	if(isset($_POST['submitQuestion']) && $form->validate())
	{        
		// question
	    $objQuestion -> processCreation($form,$objExercise);
	    
	    // answers
	    $objQuestion -> processAnswersCreation($form,$nb_answers);
	}
	else 
	{	
	
		/******************
		 * FORM DISPLAY
		 ******************/
		echo '<h3>'.$questionName.'</h3>';
		
		
		if(!empty($pictureName)){
			echo '<img src="../document/download.php?doc_url=%2Fimages%2F'.$pictureName.'" border="0">';
		}
		
		if(!empty($msgErr))
		{
			Display::display_normal_message($msgErr); //main API
		}
		
		
		// display the form
		$form->display();
		
	}
}

?>
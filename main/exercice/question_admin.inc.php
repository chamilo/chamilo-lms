<?php
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004-2009 Dokeos SPRL
	
	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact address: Dokeos, rue du Corbeau, 108, B-1030 Brussels, Belgium
	Mail: info@dokeos.com
==============================================================================
*/


/**
*	Statement (?) administration
*	This script allows to manage the statements of questions.
* 	It is included from the script admin.php
*	@package dokeos.exercise
* 	@author Olivier Brouckaert
* 	@version $Id: question_admin.inc.php 19500 2009-04-02 15:15:56Z cvargas1 $
*/

/*
==============================================================================
		INIT SECTION
==============================================================================
*/

include_once(api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');
include_once(api_get_path(LIBRARY_PATH).'image.lib.php');

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
	$action = api_get_self()."?modifyQuestion=".$modifyQuestion."&editQuestion=".$objQuestion->id;
}
else
{
	$objQuestion = Question :: getInstance($_REQUEST['answerType']);
	$action = api_get_self()."?modifyQuestion=".$modifyQuestion."&newQuestion=".$newQuestion;
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

	$objQuestion -> createForm ($form,array('Height'=>150));

	$objQuestion -> createAnswersForm ($form);
	
	if(isset($_GET['editQuestion'])) {
		$class="save";
		$text=get_lang('ModifyQuestion');
	} else {
		$class="add";
		$text=get_lang('AddQuestionToExercise'); 
	}		

	$form->addElement('style_submit_button','submitQuestion',$text, 'class="'.$class.'"');
	$renderer = $form->defaultRenderer();
	$renderer->setElementTemplate('<div class="row"><div class="label">{label}</div><div class="formw">{element}</div></div>','submitQuestion');


	/**********************
	 * FORM VALIDATION
	 **********************/
	if(isset($_POST['submitQuestion']) && $form->validate())
	{
		
		// question
	    $objQuestion -> processCreation($form,$objExercise);
	    // answers
	    $objQuestion -> processAnswersCreation($form,$nb_answers);

        // TODO: maybe here is the better place to index this tool, including answers text

	    // redirect
	    if($objQuestion -> type != HOT_SPOT)
	    	echo '<script type="text/javascript">window.location.href="admin.php"</script>';
	    else
	    	echo '<script type="text/javascript">window.location.href="admin.php?hotspotadmin='.$objQuestion->id.'"</script>';
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

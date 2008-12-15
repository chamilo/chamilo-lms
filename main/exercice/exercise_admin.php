<?php
/*
    DOKEOS - elearning and course management software

    For a full list of contributors, see documentation/credits.html

    This program is free software; you can redistribute it and/or
    modify it under the terms of the GNU General Public License
    as published by the Free Software Foundation; either version 2
    of the License, or (at your option) any later version.
    See "documentation/licence.html" more details.

    Contact:
		Dokeos
		Rue des Palais 44 Paleizenstraat
		B-1030 Brussels - Belgium
		Tel. +32 (2) 211 34 56
*/


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

if(!api_is_allowed_to_edit())
{
	api_not_allowed(true);
}

$htmlHeadXtra[] = '<script>
			function advanced_parameters() {
				if(document.getElementById(\'options\').style.display == \'none\') {
					document.getElementById(\'options\').style.display = \'block\';
					document.getElementById(\'img_plus_and_minus\').innerHTML=\'&nbsp;<img src="../img/nolines_minus.gif" alt="" />&nbsp;'.get_lang('AdvancedParameters').'\';
				} else {
				document.getElementById(\'options\').style.display = \'none\';
				document.getElementById(\'img_plus_and_minus\').innerHTML=\'&nbsp;<img src="../img/nolines_plus.gif" alt="" />&nbsp;'.get_lang('AdvancedParameters').'\';
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
if(isset($_GET['exerciseId']))
{
	$form = new FormValidator('exercise_admin', 'post', api_get_self().'?exerciseId='.$_GET['exerciseId']);
	$objExercise -> read (intval($_GET['exerciseId']));
	$form -> addElement ('hidden','edit','true');
}else
{
	$form = new FormValidator('exercise_admin');
	$form -> addElement ('hidden','edit','false');
}
$objExercise -> createForm ($form);


/*********************
 * VALIDATE FORM
 *********************/
if($form -> validate())
{
	$objExercise -> processCreation($form);
	if($form -> getSubmitValue('edit') == 'true')
	{
		header('Location:exercice.php?message=ExerciseEdited');
	}
	else
	{
		header('Location:admin.php?message=ExerciseStored&exerciseId='.$objExercise->id);
	}
}
else
{
	/*********************
	 * DISPLAY FORM
	 *********************/
	$nameTools=get_lang('ExerciseManagement');
	$interbreadcrumb[] = array ("url"=>"exercice.php", "name"=> get_lang('Exercices'));

	Display::display_header($nameTools,"Exercise");

	$form -> display ();
}

Display::display_footer();


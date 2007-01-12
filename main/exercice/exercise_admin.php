<?php // $Id$
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) Olivier Brouckaert

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact: Dokeos, 181 rue Royale, B-1000 Brussels, Belgium, info@dokeos.com
==============================================================================
*/
/**
==============================================================================
*	EXERCISE ADMINISTRATION
*
*	This script allows to manage an exercise.
*	It is included from the script admin.php
*
*	@author Olivier Brouckaert
*	@package dokeos.exercise
==============================================================================
*/

include('exercise.class.php');
include('question.class.php');
include('answer.class.php');


// name of the language file that needs to be included 
$language_file='exercice';

include('../inc/global.inc.php');
include('exercise.lib.php');
$this_section=SECTION_COURSES;
include_once(api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');

if(!api_is_allowed_to_edit())
{
	api_not_allowed();
}


/*********************
 * INIT EXERCISE
 *********************/

$objExercise = new Exercise();


/*********************
 * INIT FORM
 *********************/
$form = new FormValidator('exercise_admin');
if(isset($_GET['exerciseId']))
{
	$objExercise -> read (intval($_GET['exerciseId']));
	$form -> addElement ('hidden','edit','true');
}else
{
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
		header('Location:exercice.php');
	}
	else
	{
		header('Location:admin.php?exerciseId='.$objExercise->id);
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


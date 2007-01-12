<?php // $Id: hotspot.class.php 10234 2006-12-26
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
*	File containing the HotSpot class.
*
*	@author Eric Marguin
*	@package dokeos.exercise
==============================================================================
*/

if(!class_exists('HotSpot')):

/**
	CLASS HotSpot
 *
 *	This class allows to instantiate an object of type HotSpot (MULTIPLE CHOICE, UNIQUE ANSWER),
 *	extending the class question
 *
 *	@author Eric Marguin
 *	@package dokeos.exercise
 **/

class HotSpot extends Question {

	function HotSpot(){
		parent::question();
		$this -> type = HOT_SPOT;
	}

	function display(){

	}

	function createForm ($form) {
		parent::createForm ($form);
		if(!isset($_GET['editQuestion']))
		{
			$form->addElement('file','imageUpload',get_lang('UploadFile'));
			$form->addRule('imageUpload', get_lang('OnlyJPG'), 'mimetype','image/jpeg');
			$form->addRule('imageUpload', get_lang('NoImage'), 'uploadedfile');
		}
	}

	function processCreation ($form, $objExercise) {
		$file_info = $form -> getSubmitValue('imageUpload');
		parent::processCreation ($form, $objExercise);
		if(!empty($file_info['tmp_name']))
		{
			$this->uploadPicture($file_info['tmp_name'], $file_info['name']);
			$this->resizePicture('any',350);
			$this->save();
		}		
	}

	function createAnswersForm ($form) {

    	// nothing

	}

	function processAnswersCreation ($form) {

		// nothing

	}

}

endif;
?>
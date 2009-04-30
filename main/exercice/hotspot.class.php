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
*	File containing the HotSpot class.
*	@package dokeos.exercise
* 	@author Eric Marguin
* 	@version $Id: admin.php 10680 2007-01-11 21:26:23Z pcool $
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

	static $typePicture = 'hotspot.gif';
	static $explanationLangVar = 'Hotspot';


	function HotSpot(){
		parent::question();
		$this -> type = HOT_SPOT;
	}

	function display(){

	}
	
	function createForm ($form) {
		parent::createForm ($form);
		if(!isset($_GET['editQuestion'])) {
			$renderer = $form->defaultRenderer();
			$form->addElement('html', '<div class="row"><div class="label"></div><div class="formw">'.get_lang('UploadJpgPicture').'</div></div>');
			$form->addElement('file','imageUpload','<span class="form_required">*</span><img src="../img/hotspots.png" />');
			$renderer->setElementTemplate('<div class="row"><div class="label" style="margin-top:-30px;">{label}</div><div class="formw" >{element}</div></div>','imageUpload');
			$form->addRule('imageUpload', get_lang('OnlyImagesAllowed'), 'filetype', array ('jpg', 'jpeg', 'png', 'gif'));
			$form->addRule('imageUpload', get_lang('NoImage'), 'uploadedfile');

		}
	}

	function processCreation ($form, $objExercise) {
		$file_info = $form -> getSubmitValue('imageUpload');
		parent::processCreation ($form, $objExercise);
		if(!empty($file_info['tmp_name']))
		{
			$this->uploadPicture($file_info['tmp_name'], $file_info['name']);
				list($width,$height) = @getimagesize($file_info['tmp_name']);
				if($width>=$height) {
					$this->resizePicture('width',544);
				} else {
					$this->resizePicture('height',408);
				}
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

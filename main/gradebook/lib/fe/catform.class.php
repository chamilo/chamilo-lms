<?php
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2008 Dokeos Latinoamerica SAC
	Copyright (c) 2006 Dokeos SPRL
	Copyright (c) 2006 Ghent University (UGent)
	Copyright (c) various contributors

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
require_once (dirname(__FILE__).'/../../../inc/global.inc.php');
require_once (dirname(__FILE__).'/../be.inc.php');
require_once (api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');

/**
 * Extends formvalidator with add&edit forms
 * @author Stijn Konings
 * @package dokeos.gradebook
 */
 
class CatForm extends FormValidator {
    
    const TYPE_ADD = 1;
    const TYPE_EDIT = 2;
    const TYPE_MOVE = 3;
    const TYPE_SELECT_COURSE = 4;
    private $category_object;
    
	/**
	 * Builds a form containing form items based on a given parameter
	 * @param int form_type 1=add, 2=edit,3=move,4=browse
	 * @param obj cat_obj the category object
	 * @param string form name
	 * @param method method
	 */
    function CatForm($form_type, $category_object,$form_name,$method = 'post',$action=null) {
		parent :: __construct($form_name, $method, $action);
    	$this->form_type = $form_type;
    	if (isset ($category_object)) {	
    		$this->category_object = $category_object;
    	}
    	if ($this->form_type == self :: TYPE_EDIT) {
    		$this->build_editing_form();
    	} elseif ($this->form_type == self :: TYPE_ADD) {
    		$this->build_add_form();
    	} elseif ($this->form_type == self :: TYPE_MOVE) {
    		$this->build_move_form();
    	} elseif ($this->form_type == self :: TYPE_SELECT_COURSE) {
    		$this->build_select_course_form();
    	}
    	$this->setDefaults();
    }

	/**
	 * This function will build a move form that will allow the user to move a category to
	 * a another
	 */
   	protected function build_move_form() {
		$renderer =& $this->defaultRenderer();
		$renderer->setElementTemplate('<span>{element}</span> ');
		$this->addElement('static',null,null,'"'.$this->category_object->get_name().'" ');
		$this->addElement('static',null,null,get_lang('MoveTo'). ' : ');
		$select = $this->addElement('select','move_cat',null,null);
		foreach ($this->category_object->get_target_categories() as $cat) {
			for ($i=0;$i<$cat[2];$i++) {
				$line .= '--';
			}
			if ($cat[0] != $this->category_object->get_parent_id()) {
				$select->addoption($line.' '.$cat[1],$cat[0]);
			} else {
				$select->addoption($line.' '.$cat[1],$cat[0],'disabled');
			}	
			$line = '';
		}
   		$this->addElement('submit', null, get_lang('Ok'));
   	}
	/** 
	 * This function builds an 'add category form, if parent id is 0, it will only
	 * show courses
	 */
   	protected function build_add_form() {
		//check if we are a root category
		//if so, you can only choose between courses
		if ($this->category_object->get_parent_id() == '0') {
			$select = $this->addElement('select','select_course',array(get_lang('PickACourse'),'test'), null);
			$coursecat = Category :: get_not_created_course_categories(api_get_user_id());
			if (count($coursecat)==0) {
				$select->addoption(get_lang('CourseIndependent'),'COURSEINDEPENDENT','disabled');
			} else {
				$select->addoption(get_lang('CourseIndependent'),'COURSEINDEPENDENT');
			}
			//only return courses that are not yet created by the teacher

			foreach($coursecat as $row) {
				$select->addoption($row[1],$row[0]);
			}
   			$this->setDefaults(array(
   			   'hid_user_id' => $this->category_object->get_user_id(),
   			   'hid_parent_id' => $this->category_object->get_parent_id()
   			));		
		} else {			
   			$this->setDefaults(array(
   		    'hid_user_id' => $this->category_object->get_user_id(),
   		    'hid_parent_id' => $this->category_object->get_parent_id()
   		    ));
   		    $this->addElement('hidden','course_code', $this->category_object->get_course_code()); 		    		
		}
   		$this->build_basic_form();
   	}
   	
	/**
	 * Builds an form to edit a category
	 */
   	protected function build_editing_form() {
   		$this->setDefaults(array(
			'name' => $this->category_object->get_name(),
    		'description' => $this->category_object->get_description(),
    		'hid_user_id' => $this->category_object->get_user_id(),
    		'hid_parent_id' => $this->category_object->get_parent_id(),	
   	 		'weight' => $this->category_object->get_weight(),	
   	 		'visible' => $this->category_object->is_visible(),
   	 		'certif_min_score' => $this->category_object->get_certificate_min_score(),
    		));
   		$this->addElement('hidden','hid_id', $this->category_object->get_id());
   		$this->addElement('hidden','course_code', $this->category_object->get_course_code());
		$this->build_basic_form();
   	}
   	
   	private function build_basic_form() {
		$this->addElement('hidden', 'zero', 0);
		$this->add_textfield('name', get_lang('CategoryName'),true,array('size'=>'54','maxlength'=>'50'));
		$this->add_textfield('weight', get_lang('Weight'),true,array('size'=>'4','maxlength'=>'4'));			
		$this->add_textfield('certif_min_score', get_lang('CertificateMinScore'),false,array('size'=>'4','maxlength'=>'4'));			
   		$this->addElement('hidden','hid_user_id');
   		$this->addElement('hidden','hid_parent_id');
		$this->addElement('textarea', 'description', get_lang('Description'),array('rows'=>'3','cols' => '34'));
		$this->addElement('checkbox', 'visible',get_lang('Visible'));
		$this->addElement('style_submit_button', null, get_lang('EditCategory'), 'class="save"');
		$this->addRule('weight',get_lang('OnlyNumbers'),'numeric');
		$this->addRule('weight',get_lang('NoDecimals'),'nopunctuation');
		$this->addRule(array ('weight', 'zero'), get_lang('NegativeValue'), 'compare', '>=');
		$this->addRule('certif_min_score',get_lang('OnlyNumbers'),'numeric');
		$this->addRule('certif_min_score',get_lang('NoDecimals'),'nopunctuation');
		$this->addRule(array ('certif_min_score', 'zero'), get_lang('NegativeValue'), 'compare', '>=');
   	}
	/** 
	 * This function builds an 'select course' form in the add category process, 
	 * if parent id is 0, it will only show courses
	 */
   	protected function build_select_course_form() {
		$select = $this->addElement('select','select_course',array(get_lang('PickACourse'),'test'), null);
		$coursecat = Category :: get_all_courses(api_get_user_id());
		//only return courses that are not yet created by the teacher

		foreach($coursecat as $row) {
			$select->addoption($row[1],$row[0]);
		}
		$this->setDefaults(array(
		   'hid_user_id' => $this->category_object->get_user_id(),
		   'hid_parent_id' => $this->category_object->get_parent_id()
		));		
   		$this->addElement('hidden','hid_user_id');
   		$this->addElement('hidden','hid_parent_id');
		$this->addElement('submit', null, get_lang('Ok'));
   	}
     	
   	function display() {
   		parent :: display();
   	}
   	
   	function setDefaults($defaults = array ()) {
   		parent :: setDefaults($defaults);
   	}
}
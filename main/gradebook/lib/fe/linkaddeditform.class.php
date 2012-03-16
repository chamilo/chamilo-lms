<?php
/* For licensing terms, see /license.txt */
/**
 * Script
 * @package chamilo.gradebook
 */
/**
 * Init
 */
require_once dirname(__FILE__).'/../../../inc/global.inc.php';
require_once dirname(__FILE__).'/../be.inc.php';
require_once dirname(__FILE__).'/../gradebook_functions.inc.php';
require_once api_get_path(LIBRARY_PATH) . 'groupmanager.lib.php';

/**
 * Form used to add or edit links
 * @author Stijn Konings
 * @author Bert Steppé
 * @package chamilo.gradebook
 */
class LinkAddEditForm extends FormValidator
{

	const TYPE_ADD = 1;
	const TYPE_EDIT = 2;

	/**
	 * Constructor
	 * To add link, define category_object and link_type
	 * To edit link, define link_object
	 */
    function LinkAddEditForm($form_type, $category_object, $link_type, $link_object, $form_name, $action = null) {
		parent :: __construct($form_name, 'post', $action);

		// set or create link object
		if (isset ($link_object)) {
			$link = $link_object;
		} elseif (isset ($link_type) && isset ($category_object)) {
			$link = LinkFactory :: create ($link_type);
			$link->set_course_code(api_get_course_id());
			/*
			$cc = $category_object->get_course_code();
			if (empty($cc) && !empty($_GET['course_code'])) {
				$link->set_course_code(Database::escape_string($_GET['course_code']));
			} else {
				$link->set_course_code($category_object->get_course_code());
			}*/
		} else {
			die ('LinkAddEditForm error: define link_type/category_object or link_object');
		}
		$defaults = array();
		$this->addElement('hidden', 'zero', 0);

		if (!empty($_GET['editlink'])) {
			$this->addElement('header', '', get_lang('EditLink'));
		}

		// ELEMENT: name
		if ($form_type == self :: TYPE_ADD || $link->is_allowed_to_change_name()) {
			if ($link->needs_name_and_description()) {
				$this->add_textfield('name', get_lang('Name'), true, array('size'=>'40', 'maxlength'=>'40'));
			} else {
				$select = $this->addElement('select', 'select_link', get_lang('ChooseItem'));				
				foreach ($link->get_all_links() as $newlink) {
					$select->addoption($newlink[1],$newlink[0]);
				}
			}
		} else {
			$this->addElement('static','label',get_lang('Name'),  '<span class="freeze">'.$link->get_name().' ['.$link->get_type_name().']</span>');
			$this->addElement('hidden','name_link',$link->get_name(),array('id'=>'name_link'));
		}  
        $select_gradebook = $this->addElement('select', 'select_gradebook', get_lang('SelectGradebook'), array(), array('id' => 'hide_category_id'));
        
        $default_weight = 0;
        if (!empty($category_object)) {
            foreach($category_object as $my_cat) {
                if ($my_cat->get_course_code() == api_get_course_id()) {
                    if ($my_cat->get_parent_id() == 0 ) {
                        $default_weight = $my_cat->get_weight();
                        $select_gradebook->addoption(get_lang('Default'), $my_cat->get_id());
                    } else {
                        $select_gradebook->addoption($my_cat->get_name(), $my_cat->get_id());
                    }
                    
                    if ($link->get_category_id() == $my_cat->get_id()) {
                        $default_weight = $my_cat->get_weight();                        
                    }
                }           
            }
        }
        

		// ELEMENT: weight
		
        
        $this->add_textfield('weight', array(get_lang('Weight'), null, '/ <span id="max_weight">'.$default_weight.'</span>'), true, array (
            'size' => '4',
            'maxlength' => '5',
            'class' => 'span1'
        ));
        
		$this->addRule('weight',get_lang('OnlyNumbers'),'numeric');
		$this->addRule(array ('weight', 'zero'), get_lang('NegativeValue'), 'compare', '>=');
		if ($form_type == self :: TYPE_EDIT) {
			$defaults['weight'] = $link->get_weight();            
            $defaults['select_gradebook'] = $link->get_category_id();
            
		}
		// ELEMENT: max
		if ($link->needs_max()) {
			if ($form_type == self :: TYPE_EDIT && $link->has_results()) {
				$this->add_textfield('max', get_lang('QualificationNumeric'), false, array ('size' => '4','maxlength' => '5', 'disabled' => 'disabled'));
			} else {
				$this->add_textfield('max', get_lang('QualificationNumeric'), true, array ('size' => '4','maxlength' => '5'));
				$this->addRule('max', get_lang('OnlyNumbers'), 'numeric');
				$this->addRule(array ('max', 'zero'), get_lang('NegativeValue'), 'compare', '>=');
			}
			if ($form_type == self :: TYPE_EDIT) {
				$defaults['max'] = $link->get_max();
			}

		}

		// ELEMENT: date
		//$this->add_datepicker('date',get_lang('Date'));
		//$defaults['date'] = ($form_type == self :: TYPE_EDIT ? $link->get_date() : time());


		// ELEMENT: description
		if ($link->needs_name_and_description()) {
			$this->addElement('textarea', 'description', get_lang('Description'), array ('rows' => '3','cols' => '34'));
			if ($form_type == self :: TYPE_EDIT) {
				$defaults['description'] = $link->get_description();
			}
		}

		// ELEMENT: visible
		$visible = ($form_type == self :: TYPE_EDIT && $link->is_visible()) ? '1' : '0';
		$this->addElement('checkbox', 'visible', null, get_lang('Visible'), $visible);
		if ($form_type == self :: TYPE_EDIT) {
			$defaults['visible'] = $link->is_visible();
		}
		
	
		
		// ELEMENT: add results
		if ($form_type == self :: TYPE_ADD && $link->needs_results()) {
			$this->addElement('checkbox', 'addresult', get_lang('AddResult'));
		}
		// submit button
		if ($form_type == self :: TYPE_ADD) {
			$this->addElement('style_submit_button', 'submit', get_lang('CreateLink'),'class="save"');
		} else {
			$this->addElement('style_submit_button', 'submit', get_lang('LinkMod'),'class="save"');
		}

		// set default values
		$this->setDefaults($defaults);

	}
}

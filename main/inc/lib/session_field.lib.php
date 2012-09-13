<?php

/* For licensing terms, see /license.txt */

class SessionField extends ExtraField {
      
    public function __construct() {
       parent::__construct('session');
    }

    function display() {
        // action links
        echo '<div class="actions">';
       	echo  '<a href="../admin/index.php">'.Display::return_icon('back.png', get_lang('BackTo').' '.get_lang('PlatformAdmin'),'', ICON_SIZE_MEDIUM).'</a>';	   
        echo '<a href="'.api_get_self().'?action=add">'.Display::return_icon('add_user_fields.png',get_lang('Add'),'', ICON_SIZE_MEDIUM).'</a>';               

        echo '</div>';
        echo Display::grid_html('session_fields');
    }

     public function return_form($url, $action) {
		
        $form = new FormValidator('session_field', 'post', $url);
        // Settting the form elements
        $header = get_lang('Add');        
        if ($action == 'edit') {
            $header = get_lang('Modify');
        }
        
        $form->addElement('header', $header);
        $id = isset($_GET['id']) ? intval($_GET['id']) : '';
        $form->addElement('hidden', 'id', $id);        
               
        $form->addElement('text', 'field_display_text', get_lang('Name'), array('class' => 'span5'));  
        
        // Field type
        $types = self::get_field_types();
        
        $form->addElement('select', 'field_type', get_lang('FieldType'), $types, array('id' => 'field_type', 'class' => 'chzn-select', 'data-placeholder' => get_lang('Select')));
        $form->addElement('label', get_lang('Example'), '<div id="example">-</div>');
        
        //$form->addElement('advanced_settings','<a class="btn btn-show" id="advanced_parameters" href="javascript://">'.get_lang('AdvancedParameters').'</a>');
        //$form->addElement('html','<div id="options" style="display:none">');
       
        $form->addElement('text', 'field_variable', get_lang('FieldLabel'), array('class' => 'span5'));        
        $form->addElement('text', 'field_options', get_lang('FieldPossibleValues'), array('id' => 'field_options', 'class' => 'span6'));        
        if ($action == 'edit') {
            $url = Display::url(get_lang('EditOptions'), 'extra_field_options.php?type=session&field_id='.$id);
            $form->addElement('label', null, $url);
        }
        $form->addElement('text', 'field_default_value', get_lang('FieldDefaultValue'), array('id' => 'field_default_value', 'class' => 'span5'));        
                
        $group = array();
        $group[] = $form->createElement('radio', 'field_visible', null, get_lang('Yes'), 1);
        $group[] = $form->createElement('radio', 'field_visible', null, get_lang('No'), 0);
        $form->addGroup($group, '', get_lang('Visible'), '', false);
        
        $group = array();
        $group[] = $form->createElement('radio', 'field_changeable', null, get_lang('Yes'), 1);
        $group[] = $form->createElement('radio', 'field_changeable', null, get_lang('No'), 0);
        $form->addGroup($group, '', get_lang('FieldChangeability'), '', false);
        
        $group = array();
        $group[] = $form->createElement('radio', 'field_filter', null, get_lang('Yes'), 1);
        $group[] = $form->createElement('radio', 'field_filter', null, get_lang('No'), 0);
        $form->addGroup($group, '', get_lang('FieldFilter'), '', false);
 
	    /*$status_list = $this->get_status_list();         
        $form->addElement('select', 'status', get_lang('Status'), $status_list);*/
        
        $defaults = array();
        
        if ($action == 'edit') {
            // Setting the defaults
            $defaults = $this->get($id);
            $option = new SessionFieldOption('session');
            if ($defaults['field_type'] == ExtraField::FIELD_TYPE_DOUBLE_SELECT) {
                $form->freeze('field_options');
            }
            $defaults['field_options'] = $option->get_field_options_by_field_to_string($id);
            
            
        	$form->addElement('button', 'submit', get_lang('Modify'), 'class="save"');
        } else {
            $defaults['field_visible'] = 0;
            $defaults['field_changeable'] = 0;
            $defaults['field_filter'] = 0;
        	$form->addElement('button', 'submit', get_lang('Add'), 'class="save"');
        }
        
        if (!empty($defaults['created_at'])) {
        	$defaults['created_at'] = api_convert_and_format_date($defaults['created_at']);
        }
        if (!empty($defaults['updated_at'])) {
        	$defaults['updated_at'] = api_convert_and_format_date($defaults['updated_at']);
        }
        $form->setDefaults($defaults);
    
        // Setting the rules
        $form->addRule('field_display_text', get_lang('ThisFieldIsRequired'), 'required');        
        //$form->addRule('field_variable', get_lang('ThisFieldIsRequired'), 'required');
        $form->addRule('field_type', get_lang('ThisFieldIsRequired'), 'required');
        
		return $form;                                
    }  
}
<?php

/* For licensing terms, see /license.txt */

class SessionField extends Model {
    public $columns = array('id', 'field_type', 'field_variable', 'field_display_text', 'field_default_value', 'field_order', 'field_visible', 'field_changeable', 'field_filter', 'tms');
     
    public function __construct() {
       $this->table = Database::get_main_table(TABLE_MAIN_SESSION_FIELD);        
    }
    
    public function add_elements($form, $session_id = null) {
        if (empty($form)) {
            return false;
        }        
        $extra_data = array();
        if (!empty($session_id)) {
            $extra_data = self::get_session_extra_data($session_id);
            if ($form) {
                $form->setDefaults($extra_data);
            }
        }        
        $extra_fields = self::get_all();        
        UserManager::set_extra_fields_in_form($form, null, 'session_field', false, false, $extra_fields);        
    }
     
    public function get_count() {
        $row = Database::select('count(*) as count', $this->table, array(), 'first');
        return $row['count'];
    }
    
    public function get_max_field_order() {        
        $sql = "SELECT MAX(field_order) FROM {$this->table}";
        $res = Database::query($sql);

        $order = 0;
        if (Database::num_rows($res)>0) {
            $row = Database::fetch_row($res);
            $order = $row[0]+1;
        }
        return $order;
    }
    
    public function get_session_field_info_by_field_variable($field) {
        $field = Database::escape_string($field);
        $sql_field = "SELECT * FROM {$this->table} WHERE field_variable = '$field'";
		$result = Database::query($sql_field);
        if (Database::num_rows($result)) {
            $r_field = Database::fetch_array($result, 'ASSOC');
            return $r_field;
        } else {
            return false;
        }
    }
    
    public function get_all($where_conditions = array()) {
        $options = Database::select('*', $this->table, array('where'=>$where_conditions,'order' =>'field_display_text ASC'));
        $sesion_field_option = new SessionFieldOption();
        if (!empty($options)) {
            foreach ($options as &$option) {                
                $option['options'] = $sesion_field_option->get_field_options_by_field($option['id']);
            }
        }        
        return $options;
    }
    
    public function get_session_extra_data($session_id) {
        if (empty($session_id)) {
            return array();
        }
		$extra_data = array();		
        $session_fields = self::get_all();        
        $session_field_values = new SessionFieldValue();		
		
		if (!empty($session_fields) > 0) {
			foreach ($session_fields as $session_field) {
				//if ($session_field['field_type'] == self::USER_FIELD_TYPE_TAG) {
					//$tags = self::get_user_tags_to_string($user_id,$row['id'],false);                    
					//$extra_data['extra_'.$row['fvar']] = $tags;
				//} else {
                    $field_value = $session_field_values->get_values_by_session_and_field_id($session_id, $session_field['id']);
                    if ($field_value) {
                        $field_value = $field_value['field_value'];
                        switch ($session_field['field_type']) {
                            case UserManager::USER_FIELD_TYPE_SELECT_MULTIPLE:
                                $field_value = split(';', $field_value);                                
                            case UserManager::USER_FIELD_TYPE_RADIO:
                                $extra_data['extra_'.$session_field['field_variable']]['extra_'.$session_field['field_variable']] = $field_value;
                                break;
                            default:
                                $extra_data['extra_'.$session_field['field_variable']] = $field_value;
                                break;
                        }
                    }                   
				//}
			}
		}        
		return $extra_data;
    }
    
    public function get_field_types() {
        $types = UserManager::get_user_field_types();
        unset($types[UserManager::USER_FIELD_TYPE_TAG]);
        unset($types[UserManager::USER_FIELD_TYPE_SOCIAL_PROFILE]);
        return $types;
    }
    
    public function get_field_type_by_id($id) {
        $types = self::get_field_types();
        if (isset($types[$id])) {
            return $types[$id];
        }
        return null;
    }
    
    function display() {
        // action links
        echo '<div class="actions">';
       	echo  '<a href="../admin/index.php">'.Display::return_icon('back.png', get_lang('BackTo').' '.get_lang('PlatformAdmin'),'', ICON_SIZE_MEDIUM).'</a>';	   
        echo '<a href="'.api_get_self().'?action=add">'.Display::return_icon('add_user_fields.png',get_lang('Add'),'', ICON_SIZE_MEDIUM).'</a>';               

        echo '</div>';
        echo Display::grid_html('session_fields');
    }
    
    function clean_parameters($params) {
        if (!isset($params['field_variable']) || empty($params['field_variable'])) {             
            $params['field_variable'] = trim(strtolower(str_replace(" ","_", $params['field_display_text'])));	            
        }
        
        if (!isset($params['field_order'])) {
            $max_order = self::get_max_field_order();
            $params['field_order'] = $max_order;
        }
        return $params;
    }
        
    public function save($params, $show_query = false) {
        $session_field_info = self::get_session_field_info_by_field_variable($params['field_variable']);        
        $params = self::clean_parameters($params);            
        if ($session_field_info) {
            return $session_field_info['id'];
        } else {    
            $id = parent::save($params, $show_query);
            if ($id) {            
                $session_field_option = new SessionFieldOption();
                $params['field_id'] = $id;
                $session_field_option->save($params);
            }
            return $id;
        } 
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
       
        $form->addElement('text', 'field_variable', get_lang('FieldLabel'), array('class' => 'span4'));        
        $form->addElement('text', 'field_options', get_lang('FieldPossibleValues'), array('id' => 'field_options'));        
        $form->addElement('text', 'field_default_value', get_lang('FieldDefaultValue'), array('id' => 'field_default_value'));        
                
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
 
        //$form->addElement('html', '</div>');
      
   
        
	    /*$status_list = $this->get_status_list();         
        $form->addElement('select', 'status', get_lang('Status'), $status_list);*/
        
        if ($action == 'edit') {            
            //$form->freeze('created_at');
        }
        
        $defaults = array();
        
        if ($action == 'edit') {
            // Setting the defaults
            $defaults = $this->get($id);
            $option = new SessionFieldOption();
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
     
     public function update($params) {
         $params = self::clean_parameters($params);
         
        if (isset($params['id'])) {            
             $session_field_option = new SessionFieldOption();
             $params['field_id'] = $params['id'];
             $session_field_option->save($params);
         }
         parent::update($params);         
     }
     
    public function delete($id) {
	    parent::delete($id);
	    //event_system(LOG_CAREER_DELETE, LOG_CAREER_ID, $id, api_get_utc_datetime(), api_get_user_id());
    }     
}
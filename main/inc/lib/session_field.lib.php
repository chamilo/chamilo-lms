<?php

class SessionField extends Model {
     public $columns = array('id', 'field_type', 'field_variable', 'field_display_text', 'field_display_default_value', 'field_order', 'field_visible', 'field_changeable', 'field_filter', 'tms');
     
     public function __construct() {
        $this->table = Database::get_main_table(TABLE_MAIN_SESSION_FIELD);        
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
        $sql_field = "SELECT id FROM {$this->table} WHERE field_variable = '$field'";
		$result = Database::query($sql_field);
        if (Database::num_rows($result)) {
            $r_field = Database::fetch_row($result);
            return $r_field;
        } else {
            return false;
        }
    }
    
    public function get_field_types() {
        return UserManager::get_user_field_types();
    }
    
    function display() {
        // action links
        echo '<div class="actions">';
       	echo  '<a href="../admin/index.php">'.Display::return_icon('back.png', get_lang('BackTo').' '.get_lang('PlatformAdmin'),'', ICON_SIZE_MEDIUM).'</a>';	   
        echo '<a href="'.api_get_self().'?action=add">'.Display::return_icon('add_user_fields.png',get_lang('Add'),'', ICON_SIZE_MEDIUM).'</a>';               

        echo '</div>';
        echo Display::grid_html('session_fields');
    }
        
    public function save($params, $show_query = false) {
        $session_field_info = self::get_session_field_info_by_field_variable($params['field_variable']);
        if ($session_field_info) {
            return $session_field_info['id'];
        } else {
            $max_order = self::get_max_field_order();
            if (!isset($params['field_order'])) {
                $params['field_order'] = $max_order;
            }
            $id = parent::save($params, $show_query);
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
        $form->addElement('text', 'field_variable', get_lang('FieldLabel'), array('class' => 'span4'));
        
        // Field type
        $types = self::get_field_types();
        $form->addElement('select', 'field_type', get_lang('FieldType'), $types, array('class' => 'chzn-select'));        
                
        $group = array ();
        $group[] = $form->createElement('radio', 'field_visible', null, get_lang('Yes'), 1);
        $group[] = $form->createElement('radio', 'field_visible', null, get_lang('No'), 0);
        $form->addGroup($group, '', get_lang('Visible'), '', false);
        
        $group = array ();
        $group[] = $form->createElement('radio', 'field_changeable', null, get_lang('Yes'), 1);
        $group[] = $form->createElement('radio', 'field_changeable', null, get_lang('No'), 0);
        $form->addGroup($group, '', get_lang('FieldChangeability'), '', false);
        
        $group = array ();
        $group[] = $form->createElement('radio', 'field_filter', null, get_lang('Yes'), 1);
        $group[] = $form->createElement('radio', 'field_filter', null, get_lang('No'), 0);
        $form->addGroup($group, '', get_lang('FieldFilter'), '', false);
   
        
	    /*$status_list = $this->get_status_list();         
        $form->addElement('select', 'status', get_lang('Status'), $status_list);*/
        
        if ($action == 'edit') {            
            //$form->freeze('created_at');
        }
        
        $defaults = array();
        
        if ($action == 'edit') {
            // Setting the defaults
            $defaults = $this->get($id);
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
        $form->addRule('field_variable', get_lang('ThisFieldIsRequired'), 'required');
        $form->addRule('field_type', get_lang('ThisFieldIsRequired'), 'required');
        
		return $form;                                
    }
     
     public function update($params) {
         parent::update($params);
         
     }
     
    public function delete($id) {
	    parent::delete($id);
	    //event_system(LOG_CAREER_DELETE, LOG_CAREER_ID, $id, api_get_utc_datetime(), api_get_user_id());
    }    
     
}
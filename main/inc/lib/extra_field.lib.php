<?php

class ExtraField extends model {
    public $columns = array('id', 'field_type', 'field_variable', 'field_display_text', 'field_default_value', 'field_order', 'field_visible', 'field_changeable', 'field_filter', 'tms');   
    
    CONST FIELD_TYPE_TEXT =                    1;
    CONST FIELD_TYPE_TEXTAREA =                2;
    CONST FIELD_TYPE_RADIO =                   3;
    CONST FIELD_TYPE_SELECT =                  4;
    CONST FIELD_TYPE_SELECT_MULTIPLE =         5;
    CONST FIELD_TYPE_DATE =                    6;
    CONST FIELD_TYPE_DATETIME =                7;
    CONST FIELD_TYPE_DOUBLE_SELECT =           8;
    CONST FIELD_TYPE_DIVIDER =                 9;
    CONST FIELD_TYPE_TAG =                     10;
    CONST FIELD_TYPE_TIMEZONE =                11;
    CONST FIELD_TYPE_SOCIAL_PROFILE =          12;
    
    public $type = 'user'; //or session
    public $handler_id = 'user_id';
    
    function __construct($type) {
        $this->type = $type;
        switch ($this->type) {
            case 'user':            
                //$this->table_field          = Database::get_main_table(TABLE_MAIN_USER_FIELD);
                $this->table_field_options  = Database::get_main_table(TABLE_MAIN_USER_FIELD_OPTIONS);
                $this->table_field_values   = Database::get_main_table(TABLE_MAIN_USER_FIELD_VALUES);
                
                //Used for the model
                $this->table = Database::get_main_table(TABLE_MAIN_USER_FIELD);                
                $this->handler_id =  'user_id';              
                break;
            case 'session':
                //$this->table_field          = Database::get_main_table(TABLE_MAIN_SESSION_FIELD);
                $this->table_field_options  = Database::get_main_table(TABLE_MAIN_SESSION_FIELD_OPTIONS);
                $this->table_field_values   = Database::get_main_table(TABLE_MAIN_SESSION_FIELD_VALUES);
                $this->handler_id =  'session_id'; 
                
                $this->table = Database::get_main_table(TABLE_MAIN_SESSION_FIELD);
            break;                
        }        
    }    
         
    public function get_count() {
        $row = Database::select('count(*) as count', $this->table, array(), 'first');
        return $row['count'];
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
    
       
    public function get_handler_field_info_by_field_variable($field_variable) {
        $field_variable = Database::escape_string($field_variable);
        $sql_field = "SELECT * FROM {$this->table} WHERE field_variable = '$field_variable'";
		$result = Database::query($sql_field);
        if (Database::num_rows($result)) {
            $r_field = Database::fetch_array($result, 'ASSOC');
            return $r_field;
        } else {
            return false;
        }
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
    
    public static function get_extra_fields_by_handler($handler) {
        $types = array();
        $types[self::FIELD_TYPE_TEXT]            = get_lang('FieldTypeText');
        $types[self::FIELD_TYPE_TEXTAREA]        = get_lang('FieldTypeTextarea');
        $types[self::FIELD_TYPE_RADIO]           = get_lang('FieldTypeRadio');
        $types[self::FIELD_TYPE_SELECT]          = get_lang('FieldTypeSelect');
        $types[self::FIELD_TYPE_SELECT_MULTIPLE] = get_lang('FieldTypeSelectMultiple');
        $types[self::FIELD_TYPE_DATE]            = get_lang('FieldTypeDate');
        $types[self::FIELD_TYPE_DATETIME]        = get_lang('FieldTypeDatetime');
        $types[self::FIELD_TYPE_DOUBLE_SELECT]   = get_lang('FieldTypeDoubleSelect');
        $types[self::FIELD_TYPE_DIVIDER]         = get_lang('FieldTypeDivider');
        $types[self::FIELD_TYPE_TAG]             = get_lang('FieldTypeTag');
        $types[self::FIELD_TYPE_TIMEZONE]        = get_lang('FieldTypeTimezone');
        $types[self::FIELD_TYPE_SOCIAL_PROFILE]  = get_lang('FieldTypeSocialProfile');
        
        switch ($handler) {
            case 'session':                
                unset($types[self::FIELD_TYPE_TAG]);
                unset($types[self::FIELD_TYPE_SOCIAL_PROFILE]);            
                break;
            case 'user':                
                break;
        }
        return $types;
    }
    
        
    public function add_elements($form, $item_id = null) {
        if (empty($form)) {
            return false;
        }   
        $extra_data = false;
        if (!empty($item_id)) {
            $extra_data = self::get_handler_extra_data($item_id);            
            if ($form) {
                $form->setDefaults($extra_data);
            }            
        }        
        $extra_fields = self::get_all();        
        $extra = ExtraField::set_extra_fields_in_form($form, $extra_data, $this->type.'_field', false, false, $this->type, $extra_fields);        
        return $extra;
    }
      
    
    public function get_handler_extra_data($item_id) {
        if (empty($item_id)) {
            return array();
        }
		$extra_data = array();		
        $fields = self::get_all();        
        $session_field_values = new ExtraFieldValue($this->type);		
		
		if (!empty($fields) > 0) {
			foreach ($fields as $field) {
                $field_value = $session_field_values->get_values_by_handler_and_field_id($item_id, $field['id']);                    
                if ($field_value) {
                    $field_value = $field_value['field_value'];                    
                    
                    switch ($field['field_type']) {
                        case ExtraField::FIELD_TYPE_DOUBLE_SELECT:                            
                            $selected_options = explode('::', $field_value);                            
                            $extra_data['extra_'.$field['field_variable']]['extra_'.$field['field_variable']] = $selected_options[0];
                            $extra_data['extra_'.$field['field_variable']]['extra_'.$field['field_variable'].'_second'] = $selected_options[1];
                            break;
                        case ExtraField::FIELD_TYPE_SELECT_MULTIPLE:
                            $field_value = explode(';', $field_value);                                
                        case ExtraField::FIELD_TYPE_RADIO:
                            $extra_data['extra_'.$field['field_variable']]['extra_'.$field['field_variable']] = $field_value;
                            break;
                        default:
                            $extra_data['extra_'.$field['field_variable']] = $field_value;
                            break;
                    }
                }				
			}
		}        
		return $extra_data;
    }
    
    public static function get_all_extra_field_by_type($field_type) {		
		// all the information of the field
		$sql = "SELECT * FROM  {$this->table} WHERE field_type='".Database::escape_string($field_type)."'";
		$result = Database::query($sql);
        $return = array();
		while ($row = Database::fetch_array($result)) {
			$return[] = $row['id'];
		}
		return $return;
	}
    
        
    public function get_field_types() {
        return self::get_extra_fields_by_handler($this->type);        
    }
    
    public function get_field_type_by_id($id) {            
        $types = self::get_field_types();
        if (isset($types[$id])) {
            return $types[$id];
        }
        return null;    
    }
    
    
    /**
     * Converts a string like this:
     * France:Paris;Bretagne;Marseilles;Lyon|Belgique:Bruxelles;Namur;Liège;Bruges|Peru:Lima;Piura;
     * into
     * array('France' => array('Paris', 'Bregtane', 'Marseilles'), 'Belgique' => array('Namur', 'Liège', etc
     * @param string $string
     * @return array
     */
    static function extra_field_double_select_convert_string_to_array($string) {
        $options = explode('|', $string);
        $options_parsed = array();
        $id = 0;
        if (!empty($options)) {
            foreach ($options as $sub_options) {
                $options = explode(':', $sub_options);
                $sub_sub_options = explode(';', $options[1]);
                $options_parsed[$id] = array('label' => $options[0], 'options' => $sub_sub_options);
                $id++;
            }
        }
        return $options_parsed;        
    }
    
    static function extra_field_double_select_convert_array_to_ordered_array($options) {
        $options_parsed = array();
        if (!empty($options)) {
            foreach ($options as $option) {            
                if ($option['option_value'] == 0 ) {
                    $options_parsed[$option['id']][] = $option;
                } else {
                    $options_parsed[$option['option_value']][] = $option;
                }
            }
        }
        return $options_parsed;
    }
    
    /**
  
     * @param array options the result of the get_field_options_by_field() array
     */
    static function extra_field_double_select_convert_array_to_string($options) {
        $string = null;
        //var_dump($options);
        $options_parsed = self::extra_field_double_select_convert_array_to_ordered_array($options);
        
        if (!empty($options_parsed)) {
            foreach ($options_parsed as $option) {
                foreach ($option as $key => $item) {                
                    $string .= $item['option_display_text'];          
                    if ($key == 0) {
                        $string .= ':';
                    } else {
                        if (isset($option[$key+1])) {
                            $string .= ';';
                        }
                    }
                }
                $string .= '|';            
            }
        }
        
        if (!empty($string)) {
           $string = substr($string, 0, strlen($string)-1);
        }        
        return $string;
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
        $session_field_info = self::get_handler_field_info_by_field_variable($params['field_variable']);        
        $params = self::clean_parameters($params);            
        if ($session_field_info) {
            return $session_field_info['id'];
        } else {
            if (!isset($params['tms'])) {
                $params['tms'] = api_get_utc_datetime();
            }
            $id = parent::save($params, $show_query);
            if ($id) {            
                $session_field_option = new SessionFieldOption();
                $params['field_id'] = $id;
                $session_field_option->save($params);
            }
            return $id;
        } 
     }
     
          
    public function update($params) {
        $params = self::clean_parameters($params);        
        if (isset($params['id'])) {            
             $field_option = new ExtraFieldOption($this->type);
             $params['field_id'] = $params['id'];
             $field_option->save($params);
         }
         parent::update($params);         
    }
     
    public function delete($id) {
	    parent::delete($id);
        $field_option = new ExtraFieldOption($this->type);        
        $field_option->delete_all_options_by_field_id($id);
        
        $session_field_values = new ExtraFieldValue($this->type);
        $session_field_values->delete_all_values_by_field_id($id);       
    }
    
    static function set_extra_fields_in_form($form, $extra_data, $form_name, $admin_permissions = false, $user_id = null, $type = 'user', $extra = null) {          
        $user_id = intval($user_id);
        
        // User extra fields
        if ($type == 'user') {
            $extra = UserManager::get_extra_fields(0, 50, 5, 'ASC', true, null, true);            
        }       
        
        $jquery_ready_content = null;
        
        if (!empty($extra))
        foreach ($extra as $field_details) {       
            if (!$admin_permissions) {
                if ($field_details['field_visible'] == 0) {
                    continue;
                }
            }           
            switch ($field_details['field_type']) {
                case ExtraField::FIELD_TYPE_TEXT:
                    $form->addElement('text', 'extra_'.$field_details['field_variable'], $field_details['field_display_text'], array('class' => 'span4'));
                    $form->applyFilter('extra_'.$field_details['field_variable'], 'stripslashes');
                    $form->applyFilter('extra_'.$field_details['field_variable'], 'trim');                    
                    if (!$admin_permissions) {
                        if ($field_details['field_visible'] == 0)	$form->freeze('extra_'.$field_details['field_variable']);
                    }
                    break;
                case ExtraField::FIELD_TYPE_TEXTAREA:
                    $form->add_html_editor('extra_'.$field_details['field_variable'], $field_details['field_display_text'], false, false, array('ToolbarSet' => 'Profile', 'Width' => '100%', 'Height' => '130'));
                    //$form->addElement('textarea', 'extra_'.$field_details['field_variable'], $field_details['field_display_text'], array('size' => 80));
                    $form->applyFilter('extra_'.$field_details['field_variable'], 'stripslashes');
                    $form->applyFilter('extra_'.$field_details['field_variable'], 'trim');
                    if (!$admin_permissions) {
                        if ($field_details['field_visible'] == 0) $form->freeze('extra_'.$field_details['field_variable']);
                    }
                    break;
                case ExtraField::FIELD_TYPE_RADIO:
                    $group = array();
                    foreach ($field_details['options'] as $option_id => $option_details) {
                        $options[$option_details['option_value']] = $option_details['option_display_text'];
                        $group[] = $form->createElement('radio', 'extra_'.$field_details['field_variable'], $option_details['option_value'],$option_details['option_display_text'].'<br />',$option_details['option_value']);
                    }
                    $form->addGroup($group, 'extra_'.$field_details['field_variable'], $field_details['field_display_text'], '');
                    if (!$admin_permissions) {
                        if ($field_details['field_visible'] == 0)	$form->freeze('extra_'.$field_details['field_variable']);
                    }
                    break;
                case ExtraField::FIELD_TYPE_SELECT:
                    $get_lang_variables = false;
                    if (in_array($field_details['field_variable'], array('mail_notify_message','mail_notify_invitation', 'mail_notify_group_message'))) {
                        $get_lang_variables = true;
                    }                
                    $options = array();
                   
                    foreach ($field_details['options'] as $option_id => $option_details) {
                        //$options[$option_details['option_value']] = $option_details['option_display_text'];
                        if ($get_lang_variables) {
                            $options[$option_details['option_value']] = get_lang($option_details['option_display_text']);
                        } else {
                          $options[$option_details['option_value']] = $option_details['option_display_text'];  
                        }
                    }
                    if ($get_lang_variables) {
                        $field_details['field_display_text'] = get_lang($field_details['field_display_text']);
                    }                    
                    //chzn-select doesn't work for sessions??
                    $form->addElement('select','extra_'.$field_details['field_variable'], $field_details['field_display_text'], $options, array('class'=>'', 'id'=>'extra_'.$field_details['field_variable']));
                    if (!$admin_permissions) {
                        if ($field_details['field_visible'] == 0) {
                            $form->freeze('extra_'.$field_details['field_variable']);
                        }
                    }
                    break;
                case ExtraField::FIELD_TYPE_SELECT_MULTIPLE:
                    $options = array();
                    foreach ($field_details['options'] as $option_id => $option_details) {
                        $options[$option_details['option_value']] = $option_details['option_display_text'];
                    }
                    $form->addElement('select','extra_'.$field_details['field_variable'], $field_details['field_display_text'], $options, array('multiple' => 'multiple'));
                    if (!$admin_permissions) {
                        if ($field_details['field_visible'] == 0) {
                            $form->freeze('extra_'.$field_details['field_variable']);
                        }
                    }
                    break;
                case ExtraField::FIELD_TYPE_DATE:
                    $form->addElement('datepickerdate', 'extra_'.$field_details['field_variable'], $field_details['field_display_text'], array('form_name' => $form_name));
                    $form->_elements[$form->_elementIndex['extra_'.$field_details['field_variable']]]->setLocalOption('minYear', 1900);
                    $defaults['extra_'.$field_details['field_variable']] = date('Y-m-d 12:00:00');
                    $form -> setDefaults($defaults);
                    if (!$admin_permissions) {
                        if ($field_details['field_visible'] == 0) {
                            $form->freeze('extra_'.$field_details['field_variable']);                            
                        }
                    }
                    $form->applyFilter('theme', 'trim');
                    break;
                case ExtraField::FIELD_TYPE_DATETIME:
                    $form->addElement('datepicker', 'extra_'.$field_details['field_variable'], $field_details['field_display_text'], array('form_name' => $form_name));
                    $form->_elements[$form->_elementIndex['extra_'.$field_details['field_variable']]]->setLocalOption('minYear', 1900);
                    $defaults['extra_'.$field_details['field_variable']] = date('Y-m-d 12:00:00');
                    $form -> setDefaults($defaults);
                    if (!$admin_permissions) {
                        if ($field_details['field_visible'] == 0) {
                            $form->freeze('extra_'.$field_details['field_variable']);
                        }
                    }
                    $form->applyFilter('theme', 'trim');
                    break;
                case ExtraField::FIELD_TYPE_DOUBLE_SELECT:
                    $first_select_id = 'first_extra_'.$field_details['field_variable'];
                    
                    $url = api_get_path(WEB_AJAX_PATH).'extra_field.ajax.php?1=1';

                    $jquery_ready_content .= '                        
                        $("#'.$first_select_id.'").on("change", function() {                   
                            var id = $(this).val();
                            if (id) {
                                $.ajax({ 
                                    url: "'.$url.'&a=get_second_select_options", 
                                    dataType: "json",
                                    data: "type='.$type.'&field_id='.$field_details['id'].'&option_value_id="+id,
                                    success: function(data) {
                                        $("#second_extra_'.$field_details['field_variable'].'").empty();
                                        $.each(data, function(index, value) {
                                            $("#second_extra_'.$field_details['field_variable'].'").append($("<option/>", {
                                                value: index,
                                                text: value
                                            }));
                                        });                           
                                    },            
                                });  
                            } else {
                                $("#second_extra_'.$field_details['field_variable'].'").empty();
                            }
                        });';
                    
                    $first_id = null;
                    $second_id = null;
                    
                    if (!empty($extra_data)) {
                        $first_id = $extra_data['extra_'.$field_details['field_variable']]['extra_'.$field_details['field_variable']];
                        $second_id = $extra_data['extra_'.$field_details['field_variable']]['extra_'.$field_details['field_variable'].'_second'];                        
                    }

                    $options = ExtraField::extra_field_double_select_convert_array_to_ordered_array($field_details['options']);
                    $values = array('' => get_lang('Select'));
                    
                    $second_values = array();
                    if (!empty($options)) {                        
                        foreach ($options as $option) {                            
                            foreach ($option as $sub_option) {
                                if ($sub_option['option_value'] == '0') {
                                    $values[$sub_option['id']] = $sub_option['option_display_text'];
                                } else {
                                    if ($first_id === $sub_option['option_value']) {
                                        $second_values[$sub_option['id']] = $sub_option['option_display_text'];
                                    }
                                }
                            }
                        }
                    }                    
                    $group = array();
                    $group[] = $form->createElement('select', 'extra_'.$field_details['field_variable'], null, $values, array('id' => $first_select_id));
                    $group[] = $form->createElement('select', 'extra_'.$field_details['field_variable'].'_second', null, $second_values, array('id'=>'second_extra_'.$field_details['field_variable']));
                    $form->addGroup($group, 'extra_'.$field_details['field_variable'], $field_details['field_display_text'], '&nbsp;');
                    
                    if (!$admin_permissions) {
                        if ($field_details['field_visible'] == 0) {
                            $form->freeze('extra_'.$field_details['field_variable']);
                        }
                    }
                    break;
                case ExtraField::FIELD_TYPE_DIVIDER:
                    $form->addElement('static', $field_details['field_variable'], '<br /><strong>'.$field_details['field_display_text'].'</strong>');
                    break;
                case ExtraField::FIELD_TYPE_TAG:
                    //the magic should be here
                    $user_tags = UserManager::get_user_tags($user_id, $field_details['id']);                    

                    $tag_list = '';
                    if (is_array($user_tags) && count($user_tags) > 0) {
                        foreach ($user_tags as $tag) {
                            $tag_list .= '<option value="'.$tag['tag'].'" class="selected">'.$tag['tag'].'</option>';
                        }
                    }

                    $multi_select = '<select id="extra_'.$field_details['field_variable'].'" name="extra_'.$field_details['field_variable'].'">
                                    '.$tag_list.'
                                    </select>';

                    $form->addElement('label',$field_details['field_display_text'], $multi_select);
                    $url = api_get_path(WEB_AJAX_PATH).'user_manager.ajax.php';
                    $complete_text = get_lang('StartToType');
                    //if cache is set to true the jquery will be called 1 time
                    $field_variable = $field_details['field_variable'];
                    $field_id = $field_details['id'];
                    $jquery_ready_content .=  <<<EOF
                    $("#extra_$field_variable").fcbkcomplete({
                        json_url: "$url?a=search_tags&field_id=$field_id",
                        cache: false,
                        filter_case: true,
                        filter_hide: true,
                        complete_text:"$complete_text",
                        firstselected: true,
                        //onremove: "testme",
                        //onselect: "testme",
                        filter_selected: true,
                        newel: true
                    });
EOF;
                    break;
                case ExtraField::FIELD_TYPE_TIMEZONE:
                    $form->addElement('select', 'extra_'.$field_details['field_variable'], $field_details['field_display_text'], api_get_timezones(), '');
                    if ($field_details['field_visible'] == 0)	$form->freeze('extra_'.$field_details['field_variable']);
                    break;
                case ExtraField::FIELD_TYPE_SOCIAL_PROFILE:
                    // get the social network's favicon
                    $icon_path = UserManager::get_favicon_from_url($extra_data['extra_'.$field_details['field_variable']], $field_details['field_default_value']);
                    // special hack for hi5
                    $leftpad = '1.7'; 
                    $top = '0.4'; 
                    $domain = parse_url($icon_path, PHP_URL_HOST); 
                    if ($domain == 'www.hi5.com' or $domain == 'hi5.com') {
                        $leftpad = '3'; $top = '0';                        
                    }
                    // print the input field
                    $form->addElement('text', 'extra_'.$field_details['field_variable'], $field_details['field_display_text'], array('size' => 60, 'style' => 'background-image: url(\''.$icon_path.'\'); background-repeat: no-repeat; background-position: 0.4em '.$top.'em; padding-left: '.$leftpad.'em; '));
                    $form->applyFilter('extra_'.$field_details['field_variable'], 'stripslashes');
                    $form->applyFilter('extra_'.$field_details['field_variable'], 'trim');
                    if ($field_details['field_visible'] == 0) {	
                        $form->freeze('extra_'.$field_details['field_variable']);
                    }
                    break;
            }
        }
        $return = array();
        $return['jquery_ready_content'] = $jquery_ready_content;
        return $return;
    }
}
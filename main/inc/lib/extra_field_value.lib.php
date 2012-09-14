<?php

class ExtraFieldValue extends Model {
    public $type = null;
    public $columns = array('id', 'field_id', 'field_value', 'tms');
    public $handler_id = null;
     
    public function __construct($type) {
        $this->type = $type;
        $extra_field = new ExtraField($this->type);
        $this->handler_id = $extra_field->handler_id;
        switch ($this->type) {
            case 'user':
                $this->table = Database::get_main_table(TABLE_MAIN_USER_FIELD_VALUES);
                $this->table_session_field = Database::get_main_table(TABLE_MAIN_USER_FIELD);                              
                break;
            case 'session':
                $this->table = Database::get_main_table(TABLE_MAIN_SESSION_FIELD_VALUES);
                $this->table_session_field = Database::get_main_table(TABLE_MAIN_SESSION_FIELD);                
            break;
        }
        $this->columns[] = $this->handler_id;
    }
     
    public function get_count() {
        $row = Database::select('count(*) as count', $this->table, array(), 'first');
        return $row['count'];
    }
    
    public function save_field_values($params) {
        $session_field = new SessionField();
        if (empty($params['session_id'])) {
            return false;            
        }
        
        //Parse params        
        foreach ($params as $key => $value) {
            if (substr($key, 0, 6) == 'extra_') { //an extra field
                $field_variable = substr($key, 6);
                $session_field_info = $session_field->get_handler_field_info_by_field_variable($field_variable);                
                if ($session_field_info) {                
                    $new_params = array(
                        'session_id'    => $params['session_id'],
                        'field_id'      => $session_field_info['id'],
                        'field_value'   => $value
                    );                    
                    self::save($new_params);
                }
            }
        }        
    }
    
    public function save($params, $show_query = false) {        
        $session_field = new SessionField();
        //$session_field_option = new SessionFieldOption();
        
        //Setting value to insert
        $value = $params['field_value'];       
        
        $value_to_insert = null;
        
        if (is_array($value)) {
            $value_to_insert = implode(';', $value);            
		} else {
			$value_to_insert = Database::escape_string($value);
		}                
        $params['field_value'] = $value_to_insert;
        
        //If field id exists
        $session_field_info = $session_field->get($params['field_id']);
                
        if ($session_field_info) {            
            switch ($session_field_info['field_type']) {
                case ExtraField::FIELD_TYPE_TAG :
                    break;
                case ExtraField::FIELD_TYPE_RADIO:
				case ExtraField::FIELD_TYPE_SELECT:
				case ExtraField::FIELD_TYPE_SELECT_MULTIPLE:
                    //$field_options = $session_field_option->get_field_options_by_field($params['field_id']);                  
					//$params['field_value'] = split(';', $value_to_insert);                    
                    /*
					if ($field_options) {
						$check = false;
						foreach ($field_options as $option) {
							if (in_array($option['option_value'], $values)) {
								$check = true;
								break;
							}
						}
						if (!$check) {
							return false; //option value not found
						}
					} else {
						return false; //enumerated type but no option found
					}*/
                    break;
                case ExtraField::FIELD_TYPE_TEXT:
                case ExtraField::FIELD_TYPE_TEXTAREA:
                    break;
                case ExtraField::FIELD_TYPE_DOUBLE_SELECT:
                    if (is_array($value)) {                        
                        if (isset($value['extra_'.$session_field_info['field_variable']]) && 
                            isset($value['extra_'.$session_field_info['field_variable'].'_second'])
                             ) {
                            $value_to_insert = $value['extra_'.$session_field_info['field_variable']].'::'.$value['extra_'.$session_field_info['field_variable'].'_second'];                        
                        } else {
                            $value_to_insert = null;
                        }
                    }
                default:
                    break;
            }            
            $session_field_values = self::get_values_by_handler_and_field_id($params['session_id'], $params['field_id']);            
            if ($session_field_values) {
                self::delete_values_by_handler_and_field_id($params['session_id'], $params['field_id']);                
            }            
            $params['field_value'] = $value_to_insert;
            $params['tms'] = api_get_utc_datetime();            
            parent::save($params, $show_query);
        }        
    }
    
    /**
     * 
     * @param int $session_id
     * @param int $field_id
     * @param bool transform the result to a human readable strings
     * @return boolean
     */
    public function get_values_by_handler_and_field_id($item_id, $field_id, $transform = false) {
        $field_id = intval($field_id);
        $item_id = intval($item_id);
        
        $sql = "SELECT s.*, field_type FROM {$this->table} s INNER JOIN {$this->table_session_field} sf ON (s.field_id = sf.id)
                WHERE {$this->handler_id} = '$item_id'  AND field_id = '".$field_id."' ORDER BY id";
        $result = Database::query($sql);        
        if (Database::num_rows($result)) {            
            $result = Database::fetch_array($result, 'ASSOC'); 
            if ($transform) {
                if ($result['field_type'] == ExtraField::FIELD_TYPE_DOUBLE_SELECT) {
                    if (!empty($result['field_value'])) {
                        $session_field_option = new SessionFieldOption();
                        $options = explode('::', $result['field_value']);    
                        // only available for PHP 5.4  :( $result['field_value'] = $session_field_option->get($options[0])['id'].' -> ';
                        $result = $session_field_option->get($options[0]);
                        $result_second = $session_field_option->get($options[1]);
                        if (!empty($result)) {
                            $result['field_value'] = $result['option_display_text'].' -> ';
                            $result['field_value'] .= $result_second['option_display_text'];
                        }
                    }            
                }
            }
            return $result;
        } else {
            return false;
        }        
        /*$field = Database::escape_string($field);
        $sql_field = "SELECT id FROM {$this->table} WHERE field_variable = '$field'";
		$result = Database::query($sql_field);
        if (Database::num_rows($result)) {
            $r_field = Database::fetch_row($result);
            return $r_field;
        } else {
            return false;
        }*/
    }
    
    /* Get all values by field id */
    public function get_values_by_field_id($field_id) {        
        $sql = "SELECT s.*, field_type FROM {$this->table} s INNER JOIN {$this->table_session_field} sf ON (s.field_id = sf.id)
                WHERE field_id = '".$field_id."' ORDER BY id";
        $result = Database::query($sql);        
        if (Database::num_rows($result)) {            
            return Database::store_result($result, 'ASSOC'); 
        }
        return false;
    }
    
    public function delete_all_values_by_field_id($field_id) {
        $field_id = intval($field_id);
        $sql = "DELETE FROM  {$this->table} WHERE field_id = $field_id";
        Database::query($sql); 
    }
    
    public function delete_values_by_handler_and_field_id($item_id, $field_id) {
        $field_id = intval($field_id);
        $item_id = intval($item_id);
        $sql = "DELETE FROM {$this->table} WHERE {$this->handler_id} = '$item_id' AND field_id = '".$field_id."' ";
        Database::query($sql); 
    }   
}
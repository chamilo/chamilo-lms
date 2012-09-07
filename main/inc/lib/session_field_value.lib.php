<?php

class SessionFieldValue extends Model {
     public $columns = array('id', 'session_id', 'field_id', 'field_value', 'tms');
     
     public function __construct() {
        $this->table = Database::get_main_table(TABLE_MAIN_SESSION_FIELD_VALUES);        
     }
     
    public function get_count() {
        $row = Database::select('count(*) as count', $this->table, array(), 'first');
        return $row['count'];
    }
    
    public function save_session_field_values($params) {
        $session_field = new SessionField();
        if (empty($params['session_id'])) {
            return false;            
        }        
        
        //Parse params
        foreach ($params as $key => $value) {
            if (substr($key, 0, 6) == 'extra_') { //an extra field
                $field_variable = substr($key, 6);
                $session_field_info = $session_field->get_session_field_info_by_field_variable($field_variable);                
                if ($session_field_info) {                
                    $new_params = array(
                        'session_id' => $params['session_id'],
                        'field_id' => $session_field_info['id'],
                        'field_value' => $value
                    );                    
                    self::save($new_params);
                }
            }
        }
    }
    
    public function save($params, $show_query = false) {        
        $session_field = new SessionField();
        $session_field_option = new SessionFieldOption();
        
        //Setting value to insert
        $value = $params['field_value'];
        $value_to_insert = null;
        if (is_array($value)) {
			foreach ($value as $val) {
				$value_to_insert .= Database::escape_string($val).';';
			}
			if (!empty($value)) {
				$value_to_insert = substr($value, 0, -1);
			}
		} else {
			$value_to_insert = Database::escape_string($value);
		}
        
        $params['field_value'] = $value_to_insert;
        
        //If field id exists
        $session_field_info = $session_field->get($params['field_id']);
                
        if ($session_field_info) {
            
            switch ($session_field_info['field_type']) {
                case UserManager::USER_FIELD_TYPE_TAG :
                    break;
                case UserManager::USER_FIELD_TYPE_RADIO:
				case UserManager::USER_FIELD_TYPE_SELECT:
				case UserManager::USER_FIELD_TYPE_SELECT_MULTIPLE:
                    $field_options = $session_field_option->get_field_options_by_field($params['field_id']);                  
					$params['field_value'] = split(';', $value_to_insert);
                    
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
                case UserManager::USER_FIELD_TYPE_TEXT:
                case UserManager::USER_FIELD_TYPE_TEXTAREA:
                default:
                    break;
            }
            
            $session_field_values = self::get_values_by_session_and_field_id($params['session_id'], $params['field_id']);
            
            if ($session_field_values) {
                self::delete_values_by_session_and_field_id($params['session_id'], $params['field_id']);                
            }            
            $params['field_value'] = $value_to_insert;
            $params['tms'] = api_get_utc_datetime();                
            
            parent::save($params, $show_query);
        }        
    }
     
    public function get_values_by_session_and_field_id($session_id, $field_id) {
        $field_id = intval($field_id);
        $session_id = intval($session_id);
    
        $sql = "SELECT * FROM {$this->table} WHERE session_id = '$session_id' AND field_id = '".$field_id."' ORDER BY id";
        $result = Database::query($sql);        
        if (Database::num_rows($result)) {
            return Database::fetch_array($result, 'ASSOC');                        
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
    
    public function delete_values_by_session_and_field_id($session_id, $field_id) {
        $field_id = intval($field_id);
        $session_id = intval($session_id);
        $sql = "DELETE FROM {$this->table} WHERE session_id = '$session_id' AND field_id = '".$field_id."' ";
        Database::query($sql); 
    }
     
     public function update($params) {
         
     }
     
}
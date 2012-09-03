<?php
/* For licensing terms, see /license.txt */

class SessionFieldOption extends Model {
     public $columns = array('id', 'field_id', 'option_value', 'option_display_text', 'option_order', 'tms');
     
     public function __construct() {
        $this->table = Database::get_main_table(TABLE_MAIN_SESSION_FIELD_OPTIONS);        
     }
     
    public function get_count() {
        $row = Database::select('count(*) as count', $this->table, array(), 'first');
        return $row['count'];
    }    
    
    public function save($params, $show_query = false) {
        $field_id = intval($params['field_id']);
        
        if (empty($field_id)) {
            return false;
        }
    
        if (!empty($params['field_options']) && 
            in_array($params['field_type'], array(
                UserManager::USER_FIELD_TYPE_RADIO, 
                UserManager::USER_FIELD_TYPE_SELECT, 
                UserManager::USER_FIELD_TYPE_SELECT_MULTIPLE, 
                UserManager::USER_FIELD_TYPE_DOUBLE_SELECT))
            ) {
            if ($params['field_type'] == UserManager::USER_FIELD_TYPE_DOUBLE_SELECT) {
                $twolist = explode('|', $params['field_options']);
                $counter = 0;
                foreach ($twolist as $individual_list) {
                    $splitted_individual_list = split(';', $individual_list);
                    foreach	($splitted_individual_list as $individual_list_option) {
                        //echo 'counter:'.$counter;
                        if ($counter == 0) {
                            $list[] = $individual_list_option;
                        } else {
                            $list[] = str_repeat('*', $counter).$individual_list_option;
                        }
                    }
                    $counter++;
                }
            } else {
                $list = split(';', $params['field_options']);
            }
            
            if (!empty($list)) {
                foreach ($list as $option) {
                    $option_info = self::get_field_option_by_field_and_option($field_id, $option);
                    
                    if ($option_info == false) {
                        $order = self::get_max_order($field_id);
                        $time = api_get_utc_datetime();
                        $new_params = array(
                            'field_id' => $field_id,                            
                            'option_value' => $option,
                            'option_display_text' => $option,
                            'option_order' => $order,
                            'tms' => $time,
                        );
                        parent::save($new_params, $show_query);                         
                    }
                }
            }
        }
        return true;         
    }
        
    public function get_field_option_by_field_and_option($field_id, $option_value) {
        $field_id = intval($field_id);
        $option_value = Database::escape_string($option_value);
        
        $sql = "SELECT * FROM {$this->table} WHERE field_id = $field_id AND option_value = '".$option_value."'";
        $result = Database::query($sql);
        if (Database::num_rows($result) > 0) {
            return Database::store_result($result, 'ASSOC');
        }
        return false;        
    }
    
    public function get_field_options_by_field($field_id) {
        $field_id = intval($field_id);
        $option_value = Database::escape_string($option_value);
        
        $sql = "SELECT * FROM {$this->table} WHERE field_id = $field_id ";
        $result = Database::query($sql);
        if (Database::num_rows($result) > 0) {
            return Database::store_result($result, 'ASSOC');
        }
        return false;        
    }
    
    public function get_field_options_by_field_to_string($field_id) {
        $options = self::get_field_options_by_field($field_id);
        $elements = array();
        if (!empty($options)) {
            foreach ($options as $option) {
                $elements[]= $option['option_value'];
            }
            $html = implode(';', $elements);
            return $html;
        }
        return null;
        
    }
    
    public function get_max_order($field_id) {
        $field_id = intval($field_id);
        $sql = "SELECT MAX(option_order) FROM {$this->table} WHERE field_id = $field_id";
        $res = Database::query($sql);
        $max = 1;
        if (Database::num_rows($res) > 0) {
            $row = Database::fetch_array($res);
            $max = $row[0] + 1;
        }
        return $max;
    }
    
    public function update($params) {
        parent::update($params);
    }     
}
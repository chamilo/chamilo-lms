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
    
    public function save($params, $show_query = false) { 
        $session_field = new SessionField();
        $session_field_info = $session_field->get_session_field_info_by_field_variable($params['field_id']);
        if ($session_field_info) {
            $id = parent::save($params, $show_query);    
        }        
        return $id;         
    }
     
    public function get_values_by_session_and_field_id($session_id, $field_id) {
        $field_id = intval($field_id);
        $session_id = intval($session_id);
    
        $sql = "SELECT * FROM {$this->table} WHERE session_id = '$session_id' AND field_id = '".$field_id."' ORDER BY id";
        $result = Database::query($sql);        
        if (Database::num_rows($result)) {
            return Database::store_result($result, 'ASSOC');                        
        } else {
            return false;
        }
        
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
     
     public function update($params) {
         
     }
     
}
<?php
/**
 * Declaration for the ExtraFieldValue class, managing the values in extra 
 * fields for any datatype
 * @package chamilo.library
 */
/**
 * Class managing the values in extra fields for any datatype
 * @package chamilo.library.extrafields
 */
class ExtraFieldValue extends Model {
    public $type = null;
    public $columns = array('id', 'field_id', 'field_value', 'tms');
    public $handler_id = null;//session_id, course_code, user_id
    /**
     * Formats the necessary elements for the given datatype
     * @param string The type of data to which this extra field applies (user, course, session, ...)
     * @return void (or false if unmanaged datatype)
     * @assert (-1) === false
     */ 
    public function __construct($type) {
        $this->type = $type;
        $extra_field = new ExtraField($this->type);
        $this->handler_id = $extra_field->handler_id;
        switch ($this->type) {
            case 'course':
                $this->table = Database::get_main_table(TABLE_MAIN_COURSE_FIELD_VALUES);
                $this->table_handler_field = Database::get_main_table(TABLE_MAIN_COURSE_FIELD); 
                break;
            case 'user':
                $this->table = Database::get_main_table(TABLE_MAIN_USER_FIELD_VALUES);
                $this->table_handler_field = Database::get_main_table(TABLE_MAIN_USER_FIELD); 
                break;
            case 'session':
                $this->table = Database::get_main_table(TABLE_MAIN_SESSION_FIELD_VALUES);
                $this->table_handler_field = Database::get_main_table(TABLE_MAIN_SESSION_FIELD); 
                break;
            default:
                //unmanaged datatype, return false to let the caller know it
                // didn't work
                return false;
        }
        $this->columns[] = $this->handler_id;
    }
    /**
     * Gets the number of values stored in the table (all fields together) 
     * for this type of resource
     * @return integer Number of rows in the table
     * @assert () !== false
     */ 
    public function get_count() {
        $row = Database::select('count(*) as count', $this->table, array(), 'first');
        return $row['count'];
    }
    /**
     * Saves a series of records given as parameter into the coresponding table
     * @param array  Structured parameter for the insertion into the *_field_values table
     * @return mixed false on empty params, void otherwise
     * @assert (array()) === false
     */ 
    public function save_field_values($params) {
        $extra_field = new ExtraField($this->type);
        if (empty($params[$this->handler_id])) {
            return false; 
        }
 
        //Parse params 
        foreach ($params as $key => $value) {
            if (substr($key, 0, 6) == 'extra_') { //an extra field
                $field_variable = substr($key, 6);
                $extra_field_info = $extra_field->get_handler_field_info_by_field_variable($field_variable); 
                if ($extra_field_info) { 
                    $new_params = array(
                        $this->handler_id   => $params[$this->handler_id],
                        'field_id'          => $extra_field_info['id'],
                        'field_value'       => $value
                    ); 
                    self::save($new_params);
                }
            }
        } 
    }
    /**
     * Save values in the *_field_values table
     * @param array Structured array with the values to save
     * @param boolean Whether to show the insert query (passed to the parent save() method)
     * @result mixed The result sent from the parent method
     * @assert (array()) === false
     */
    public function save($params, $show_query = false) { 
        $extra_field = new ExtraField($this->type);
 
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
        $extra_field_info = $extra_field->get($params['field_id']);
 
        if ($extra_field_info) {
            switch ($extra_field_info['field_type']) {
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
                        if (isset($value['extra_'.$extra_field_info['field_variable']]) && 
                            isset($value['extra_'.$extra_field_info['field_variable'].'_second'])
                             ) {
                            $value_to_insert = $value['extra_'.$extra_field_info['field_variable']].'::'.$value['extra_'.$extra_field_info['field_variable'].'_second']; 
                        } else {
                            $value_to_insert = null;
                        }
                    }
                default:
                    break;
            } 
            $field_values = self::get_values_by_handler_and_field_id($params[$this->handler_id], $params['field_id']); 
            if ($field_values) {
                self::delete_values_by_handler_and_field_id($params[$this->handler_id], $params['field_id']); 
            } 
            $params['field_value'] = $value_to_insert;
            $params['tms'] = api_get_utc_datetime(); 
            return parent::save($params, $show_query);
        } 
    }
 
    /**
     * Returns the value of the given extra field on the given resource
     * @param int Item ID (It could be a session_id, course_id or user_id)
     * @param int Field ID (the ID from the *_field table)
     * @param bool Whether to transform the result to a human readable strings
     * @return mixed A structured array with the field_id and field_value, or fals on error
     * @assert (-1,-1) === false 
     */
    public function get_values_by_handler_and_field_id($item_id, $field_id, $transform = false) {
        $field_id = intval($field_id);
        $item_id = Database::escape_string($item_id);
 
        $sql = "SELECT s.*, field_type FROM {$this->table} s 
                INNER JOIN {$this->table_handler_field} sf ON (s.field_id = sf.id)
                WHERE {$this->handler_id} = '$item_id'  AND 
                      field_id = '".$field_id."' 
                ORDER BY id"; 
        $result = Database::query($sql); 
        if (Database::num_rows($result)) { 
            $result = Database::fetch_array($result, 'ASSOC'); 
            if ($transform) {
                if (!empty($result['field_value'])) {
                    switch ($result['field_type']) {
                        case ExtraField::FIELD_TYPE_DOUBLE_SELECT:
 
                            $field_option = new ExtraFieldOption($this->type);
                            $options = explode('::', $result['field_value']); 
                            // only available for PHP 5.4  :( $result['field_value'] = $field_option->get($options[0])['id'].' -> ';
                            $result = $field_option->get($options[0]);
                            $result_second = $field_option->get($options[1]);
                            if (!empty($result)) {
                                $result['field_value'] = $result['option_display_text'].' -> ';
                                $result['field_value'] .= $result_second['option_display_text'];
                            }
                            break;
                        case ExtraField::FIELD_TYPE_SELECT:
                            $field_option = new ExtraFieldOption($this->type);
                            $extra_field_option_result = $field_option->get_field_option_by_field_and_option($result['field_id'], $result['field_value']);
                            if (isset($extra_field_option_result[0])) { 
                                $result['field_value'] = $extra_field_option_result[0]['option_display_text']; 
                            } 
                            break;
                    }
                }
            } 
            return $result;
        } else {
            return false;
        }
    }
    /**
     * Gets a structured array of the original item and its extra values, using
     * a specific original item and a field name (like "branch", or "birthdate")
     * @param int Item ID from the original table
     * @param string The name of the field we are looking for
     * @return mixed Array of results, or false on error or not found
     * @assert (-1,'') === false
     */ 
    public function get_values_by_handler_and_field_variable($item_id, $field_variable, $transform = false) {
        $field_id = intval($field_id);
        $item_id = Database::escape_string($item_id);
        $field_variable = Database::escape_string($field_variable);
 
        $sql = "SELECT s.*, field_type FROM {$this->table} s 
                INNER JOIN {$this->table_handler_field} sf ON (s.field_id = sf.id)
                WHERE   {$this->handler_id} = '$item_id'  AND 
                        field_variable = '".$field_variable."' 
                ORDER BY id"; 
        $result = Database::query($sql); 
        if (Database::num_rows($result)) { 
            $result = Database::fetch_array($result, 'ASSOC'); 
            if ($transform) {
                if ($result['field_type'] == ExtraField::FIELD_TYPE_DOUBLE_SELECT) {
                    if (!empty($result['field_value'])) {
                        $field_option = new ExtraFieldOption($this->type);
                        $options = explode('::', $result['field_value']); 
                        // only available for PHP 5.4  :( $result['field_value'] = $field_option->get($options[0])['id'].' -> ';
                        $result = $field_option->get($options[0]);
                        $result_second = $field_option->get($options[1]);
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
    }
    /**
     * Gets the ID from the item (course, session, etc) for which
     * the given field is defined with the given value
     * @param string Field (type of data) we want to check
     * @param string Data we are looking for in the given field
     * @return mixed Give the ID if found, or false on failure or not found
     * @assert (-1,-1) === false
     */
    public function get_item_id_from_field_variable_and_field_value($field_variable, $field_value, $transform = false) { 
        $field_value = Database::escape_string($field_value);
        $field_variable = Database::escape_string($field_variable);
 
        $sql = "SELECT {$this->handler_id} FROM {$this->table} s
                INNER JOIN {$this->table_handler_field} sf ON (s.field_id = sf.id)
                WHERE  field_value  = '$field_value'  AND 
                       field_variable = '".$field_variable."' 
                "; 

        $result = Database::query($sql); 
        if ($result !== false && Database::num_rows($result)) { 
            $result = Database::fetch_array($result, 'ASSOC'); 
            return $result;
        } else {
            return false;
        }
    }
    /**
     * Get all values for a specific field id
     * @param int Field ID
     * @return mixed Array of values on success, false on failure or not found
     * @assert (-1) === false
     */
    public function get_values_by_field_id($field_id) { 
        $sql = "SELECT s.*, field_type FROM {$this->table} s INNER JOIN {$this->table_handler_field} sf ON (s.field_id = sf.id)
                WHERE field_id = '".$field_id."' ORDER BY id";
        $result = Database::query($sql); 
        if (Database::num_rows($result)) { 
            return Database::store_result($result, 'ASSOC'); 
        }
        return false;
    }
    /**
     * Deletes all the values related to a specific field ID
     * @param int Field ID
     * @return void
     * @assert ('a') == null
     */
    public function delete_all_values_by_field_id($field_id) {
        $field_id = intval($field_id);
        $sql = "DELETE FROM  {$this->table} WHERE field_id = $field_id";
        Database::query($sql); 
    }
    /**
     * Deletes values of a specific field for a specific item
     * @param int Item ID (session id, course id, etc)
     * @param int Field ID
     * @return void
     * @assert (-1,-1) == null
     */
    public function delete_values_by_handler_and_field_id($item_id, $field_id) {
        $field_id = intval($field_id);
        $item_id = Database::escape_string($item_id);
        $sql = "DELETE FROM {$this->table} WHERE {$this->handler_id} = '$item_id' AND field_id = '".$field_id."' ";
        Database::query($sql); 
    }
    /**
     * Not yet implemented - Compares the field values of two items
     * @param int Item 1
     * @param int Item 2
     * @return mixed Differential array generated from the comparison
     */
    public function compare_item_values($item_id, $item_to_compare) { 
    }
}

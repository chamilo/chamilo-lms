<?php
/* For licensing terms, see /license.txt */

/**
 * Declaration for the ExtraFieldValue class, managing the values in extra
 * fields for any datatype
 * @package chamilo.library
 */
/**
 * Class managing the values in extra fields for any datatype
 * @package chamilo.library.extrafields
 */

class ExtraFieldValue extends Model
{
    public $type = null;
    public $columns = array('id', 'field_id', 'field_value', 'tms', 'comment');
    /** @var string session_id, course_code, user_id, question id */
    public $handler_id = null;
    public $entityName;

    /**
     * Formats the necessary elements for the given datatype
     * @param string The type of data to which this extra field applies (user, course, session, ...)
     * @return void (or false if unmanaged datatype)
     * @assert (-1) === false
     */
    public function __construct($type)
    {
        $this->type = $type;
        $extra_field = new ExtraField($this->type);
        $this->handler_id = $extra_field->handler_id;

        switch ($this->type) {
            case 'course':
                $this->table = Database::get_main_table(TABLE_MAIN_COURSE_FIELD_VALUES);
                $this->table_handler_field = Database::get_main_table(TABLE_MAIN_COURSE_FIELD);
                $this->author_id = 'user_id';
                $this->entityName = 'ChamiloLMS\Entity\CourseFieldValues';
                break;
            case 'user':
                $this->table = Database::get_main_table(TABLE_MAIN_USER_FIELD_VALUES);
                $this->table_handler_field = Database::get_main_table(TABLE_MAIN_USER_FIELD);
                $this->author_id = 'author_id';
                $this->entityName = 'ChamiloLMS\Entity\UserFieldValues';
                break;
            case 'session':
                $this->table = Database::get_main_table(TABLE_MAIN_SESSION_FIELD_VALUES);
                $this->table_handler_field = Database::get_main_table(TABLE_MAIN_SESSION_FIELD);
                $this->author_id = 'user_id';
                $this->entityName = 'ChamiloLMS\Entity\SessionFieldValues';
                break;
            case 'question':
                $this->table = Database::get_main_table(TABLE_MAIN_QUESTION_FIELD_VALUES);
                $this->table_handler_field = Database::get_main_table(TABLE_MAIN_QUESTION_FIELD);
                $this->author_id = 'user_id';
                $this->entityName = 'ChamiloLMS\Entity\QuestionFieldValues';
                break;
            case 'lp':
                $this->table = Database::get_main_table(TABLE_MAIN_LP_FIELD_VALUES);
                $this->table_handler_field = Database::get_main_table(TABLE_MAIN_LP_FIELD);
                $this->author_id = 'lp_id';
                //$this->entityName = 'ChamiloLMS\Entity\QuestionFieldValues';
                break;
            default:
                //unmanaged datatype, return false to let the caller know it
                // didn't work
                return false;
        }
        $this->columns[] = $this->handler_id;
        $this->columns[] = $this->author_id;
    }

    /**
     * Gets the number of values stored in the table (all fields together)
     * for this type of resource
     * @return integer Number of rows in the table
     * @assert () !== false
     */
    public function get_count()
    {
        $row = Database::select('count(*) as count', $this->table, array(), 'first');
        return $row['count'];
    }

    /**
     * Saves a series of records given as parameter into the coresponding table
     * @param array  Structured parameter for the insertion into the *_field_values table
     * @return mixed false on empty params, void otherwise
     * @assert (array()) === false
     */
    public function save_field_values($params)
    {
        $extra_field = new ExtraField($this->type);

        if (empty($params[$this->handler_id])) {
            return false;
        }

        foreach ($params as $key => $value) {
            $found = strpos($key, '__persist__');
            if ($found) {
                $tempKey = str_replace('__persist__', '', $key);
                if (!isset($params[$tempKey])) {
                    $params[$tempKey] = array();
                }
            }
        }

        // Parse params.
        foreach ($params as $key => $value) {
            if (substr($key, 0, 6) == 'extra_') {
                // An extra field.
                $field_variable = substr($key, 6);
                $extra_field_info = $extra_field->get_handler_field_info_by_field_variable($field_variable);

                if ($extra_field_info) {
                    $commentVariable = 'extra_'.$field_variable.'_comment';
                    $comment = isset($params[$commentVariable]) ? $params[$commentVariable] : null;

                    switch ($extra_field_info['field_type']) {
                        case ExtraField::FIELD_TYPE_TAG :

                            $old = self::getAllValuesByItemAndField(
                                $params[$this->handler_id],
                                $extra_field_info['id']
                            );

                            $deleteItems = array();
                            if (!empty($old)) {
                                $oldIds = array();
                                foreach ($old as $oldItem) {
                                    $oldIds[] = $oldItem['field_value'];
                                }
                                $deleteItems = array_diff($oldIds, $value);
                            }

                            foreach ($value as $optionId) {
                                $new_params = array(
                                    $this->handler_id   => $params[$this->handler_id],
                                    'field_id'          => $extra_field_info['id'],
                                    'field_value'       => $optionId,
                                    'comment'           => $comment
                                );
                                self::save($new_params);
                            }

                            if (!empty($deleteItems)) {
                                foreach ($deleteItems as $deleteFieldValue) {
                                    self::deleteValuesByHandlerAndFieldAndValue(
                                        $params[$this->handler_id],
                                        $extra_field_info['id'],
                                        $deleteFieldValue
                                    );
                                }
                            }
                            break;
                        default;
                            $new_params = array(
                                $this->handler_id   => $params[$this->handler_id],
                                'field_id'          => $extra_field_info['id'],
                                'field_value'       => $value,
                                'comment'           => $comment
                            );
                            self::save($new_params);
                    }
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
    public function save($params, $show_query = false)
    {
        $extra_field = new ExtraField($this->type);

        // Setting value to insert.
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
                    break;
                default:
                    break;
            }

            if ($extra_field_info['field_type'] == ExtraField::FIELD_TYPE_TAG) {
                $field_values = self::getAllValuesByItemAndFieldAndValue(
                    $params[$this->handler_id],
                    $params['field_id'],
                    $value
                );
            } else {
                $field_values = self::get_values_by_handler_and_field_id(
                    $params[$this->handler_id],
                    $params['field_id']
                );
            }

            $params['field_value'] = $value_to_insert;
            $params['tms'] = api_get_utc_datetime();
            $params[$this->author_id] = api_get_user_id();

            // Insert
            if (empty($field_values)) {
                if ($extra_field_info['field_loggeable'] == 1) {
                    global $app;
                    switch($this->type) {
                        case 'question':
                            $extraFieldValue = new ChamiloLMS\Entity\QuestionFieldValues();
                            $extraFieldValue->setUserId(api_get_user_id());
                            $extraFieldValue->setQuestionId($params[$this->handler_id]);
                            break;
                        case 'course':
                            $extraFieldValue = new ChamiloLMS\Entity\CourseFieldValues();
                            $extraFieldValue->setUserId(api_get_user_id());
                            $extraFieldValue->setQuestionId($params[$this->handler_id]);
                            break;
                        case 'user':
                            $extraFieldValue = new ChamiloLMS\Entity\UserFieldValues();
                            $extraFieldValue->setUserId($params[$this->handler_id]);
                            $extraFieldValue->setAuthorId(api_get_user_id());
                            break;
                        case 'session':
                            $extraFieldValue = new ChamiloLMS\Entity\SessionFieldValues();
                            $extraFieldValue->setUserId(api_get_user_id());
                            $extraFieldValue->setSessionId($params[$this->handler_id]);
                            break;
                    }
                    if (isset($extraFieldValue)) {
                        if (!empty($params['field_value'])) {
                            $extraFieldValue->setComment($params['comment']);
                            $extraFieldValue->setFieldValue($params['field_value']);
                            $extraFieldValue->setFieldId($params['field_id']);
                            $extraFieldValue->setTms(api_get_utc_datetime(null, false, true));
                            $app['orm.ems']['db_write']->persist($extraFieldValue);
                            $app['orm.ems']['db_write']->flush();
                        }
                    }
                } else {
                    if ($extra_field_info['field_type'] == ExtraField::FIELD_TYPE_TAG) {

                        $option = new ExtraFieldOption($this->type);
                        $optionExists = $option->get($params['field_value']);
                        if (empty($optionExists)) {
                            $optionParams = array(
                                'field_id' => $params['field_id'],
                                'option_value' => $params['field_value']
                            );
                            $optionId = $option->saveOptions($optionParams);
                        } else {
                            $optionId = $optionExists['id'];
                        }

                        $params['field_value'] = $optionId;
                        if ($optionId) {
                            return parent::save($params, $show_query);
                        }
                    } else {
                        return parent::save($params, $show_query);
                    }
                }
            } else {
                // Update
                if ($extra_field_info['field_loggeable'] == 1) {
                    global $app;
                    switch($this->type) {
                        case 'question':
                            $extraFieldValue = $app['orm.ems']['db_write']->getRepository('ChamiloLMS\Entity\QuestionFieldValues')->find($field_values['id']);
                            $extraFieldValue->setUserId(api_get_user_id());
                            $extraFieldValue->setQuestionId($params[$this->handler_id]);
                            break;
                        case 'course':
                            $extraFieldValue = $app['orm.ems']['db_write']->getRepository('ChamiloLMS\Entity\CourseFieldValues')->find($field_values['id']);
                            $extraFieldValue->setUserId(api_get_user_id());
                            $extraFieldValue->setCourseCode($params[$this->handler_id]);
                            break;
                        case 'user':
                            $extraFieldValue = $app['orm.ems']['db_write']->getRepository('ChamiloLMS\Entity\UserFieldValues')->find($field_values['id']);
                            $extraFieldValue->setUserId(api_get_user_id());
                            $extraFieldValue->setAuthorId(api_get_user_id());
                            break;
                        case 'session':
                            $extraFieldValue = $app['orm.ems']['db_write']->getRepository('ChamiloLMS\Entity\SessionFieldValues')->find($field_values['id']);
                            $extraFieldValue->setUserId(api_get_user_id());
                            $extraFieldValue->setSessionId($params[$this->handler_id]);
                            break;
                    }

                    if (isset($extraFieldValue)) {
                        if (!empty($params['field_value'])) {

                            /*
                             *  If the field value is similar to the previous value then the comment will be the same
                                in order to no save in the log an empty record
                            */
                            if ($extraFieldValue->getFieldValue() == $params['field_value']) {
                                if (empty($params['comment'])) {
                                    $params['comment'] = $extraFieldValue->getComment();
                                }
                            }

                            $extraFieldValue->setComment($params['comment']);
                            $extraFieldValue->setFieldValue($params['field_value']);
                            $extraFieldValue->setFieldId($params['field_id']);
                            $extraFieldValue->setTms(api_get_utc_datetime(null, false, true));
                            $app['orm.ems']['db_write']->persist($extraFieldValue);
                            $app['orm.ems']['db_write']->flush();
                        }
                    }
                } else {
                    $params['id'] = $field_values['id'];
                    return parent::update($params, $show_query);
                }
            }
        }
    }

    /**
     * Returns the value of the given extra field on the given resource
     * @param int Item ID (It could be a session_id, course_id or user_id)
     * @param int Field ID (the ID from the *_field table)
     * @param bool Whether to transform the result to a human readable strings
     * @return mixed A structured array with the field_id and field_value, or false on error
     * @assert (-1,-1) === false
     */
    public function get_values_by_handler_and_field_id($item_id, $field_id, $transform = false)
    {
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
                            $extra_field_option_result = $field_option->get_field_option_by_field_and_option(
                                $result['field_id'],
                                $result['field_value']
                            );
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
     * @param string $tag
     * @param int $field_id
     * @param int $limit
     * @return array
     */
    public function searchValuesByField($tag, $field_id, $limit = 10)
    {
        $field_id = intval($field_id);
        $limit = intval($limit);
        $tag = Database::escape_string($tag);
        $sql = "SELECT DISTINCT s.field_value, s.field_id
                FROM {$this->table} s
                INNER JOIN {$this->table_handler_field} sf ON (s.field_id = sf.id)
                WHERE
                    field_id = '".$field_id."' AND
                    field_value LIKE '%$tag%'
                ORDER BY field_value
                LIMIT 0, $limit
                ";
        $result = Database::query($sql);
        $values = array();
        if (Database::num_rows($result)) {
            $values = Database::store_result($result, 'ASSOC');
        }
        return $values;
    }

    /**
     * Gets a structured array of the original item and its extra values, using
     * a specific original item and a field name (like "branch", or "birthdate")
     * @param int Item ID from the original table
     * @param string The name of the field we are looking for
     * @return mixed Array of results, or false on error or not found
     * @assert (-1,'') === false
     */
    public function get_values_by_handler_and_field_variable($item_id, $field_variable, $transform = false)
    {
        $item_id = Database::escape_string($item_id);
        $field_variable = Database::escape_string($field_variable);

        $sql = "SELECT s.*, field_type FROM {$this->table} s
                INNER JOIN {$this->table_handler_field} sf
                ON (s.field_id = sf.id)
                WHERE
                    {$this->handler_id} = '$item_id'  AND
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
    public function get_item_id_from_field_variable_and_field_value($field_variable, $field_value, $transform = false)
    {
        $field_value = Database::escape_string($field_value);
        $field_variable = Database::escape_string($field_variable);

        $sql = "SELECT {$this->handler_id} FROM {$this->table} s
                INNER JOIN {$this->table_handler_field} sf
                ON (s.field_id = sf.id)
                WHERE
                    field_value  = '$field_value' AND
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
    public function get_values_by_field_id($field_id)
    {
        $field_id = intval($field_id);
        $sql = "SELECT s.*, field_type FROM {$this->table} s
                INNER JOIN {$this->table_handler_field} sf
                ON (s.field_id = sf.id)
                WHERE field_id = '".$field_id."' ORDER BY id";
        $result = Database::query($sql);
        if (Database::num_rows($result)) {
            return Database::store_result($result, 'ASSOC');
        }
        return false;
    }

    /**
     * @param int $itemId
     * @param int $fieldId
     * @return array
     */
    public function getAllValuesByItemAndField($itemId, $fieldId)
    {
        $fieldId = intval($fieldId);
        $itemId = intval($itemId);
        $sql = "SELECT s.* FROM {$this->table} s
                INNER JOIN {$this->table_handler_field} sf
                ON (s.field_id = sf.id)
                WHERE
                    field_id = '".$fieldId."' AND
                    {$this->handler_id} = '$itemId'
                ORDER BY field_value";
        $result = Database::query($sql);
        if (Database::num_rows($result)) {
            return Database::store_result($result, 'ASSOC');
        }
        return false;
    }

    /**
     * @param int $itemId
     * @param int $fieldId
     * @param string $fieldValue
     * @return array|bool
     */
    public function getAllValuesByItemAndFieldAndValue($itemId, $fieldId, $fieldValue)
    {
        $fieldId = intval($fieldId);
        $itemId = intval($itemId);
        $fieldValue = Database::escape_string($fieldValue);
        $sql = "SELECT s.* FROM {$this->table} s
                INNER JOIN {$this->table_handler_field} sf
                ON (s.field_id = sf.id)
                WHERE
                    field_id = '".$fieldId."' AND
                    {$this->handler_id} = '$itemId' AND
                    field_value = $fieldValue
                ORDER BY field_value";

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
    public function delete_all_values_by_field_id($field_id)
    {
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
    public function delete_values_by_handler_and_field_id($item_id, $field_id)
    {
        $field_id = intval($field_id);
        $item_id = Database::escape_string($item_id);
        $sql = "DELETE FROM {$this->table} WHERE {$this->handler_id} = '$item_id' AND field_id = '".$field_id."' ";
        Database::query($sql);
    }

    /**
     * @param int $itemId
     * @param int $fieldId
     * @param int $fieldValue
     */
    public function deleteValuesByHandlerAndFieldAndValue($itemId, $fieldId, $fieldValue)
    {
        $itemId = intval($itemId);
        $fieldId = intval($fieldId);
        $fieldValue = Database::escape_string($fieldValue);

        $sql = "DELETE FROM {$this->table}
                WHERE
                    {$this->handler_id} = '$itemId' AND
                    field_id = '".$fieldId."' AND
                    field_value = '$fieldValue'";
        Database::query($sql);
    }

    /**
     * Not yet implemented - Compares the field values of two items
     * @param int Item 1
     * @param int Item 2
     * @return mixed Differential array generated from the comparison
     */
    public function compare_item_values($item_id, $item_to_compare)
    {
    }
}

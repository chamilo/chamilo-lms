<?php
/**
 * Manage specific tools
 * @package chamilo.library
 */
/**
 * Code
 */
// Database table definitions
$table_sf     = Database :: get_main_table(TABLE_MAIN_SPECIFIC_FIELD);
$table_sf_val   = Database :: get_main_table(TABLE_MAIN_SPECIFIC_FIELD_VALUES);

/**
 * Add a specific field
 * @param string $name specific field name
 */
function add_specific_field($name) {
  $table_sf     = Database :: get_main_table(TABLE_MAIN_SPECIFIC_FIELD);
  $name = trim($name);
  if (empty($name)) {
    return FALSE;
  }
  $sql = 'INSERT INTO %s(id, code, name) VALUES(NULL, \'%s\', \'%s\')';
  $_safe_name = Database::escape_string($name);
  $_safe_code = substr($_safe_name,0,1);
  $_safe_code = get_specific_field_code_from_name($_safe_code);
  if ($_safe_code === false) { return false; }
  $sql = sprintf($sql, $table_sf, $_safe_code, $_safe_name);
  $result = Database::query($sql);
  if ($result) {
    return Database::insert_id();
  }
  else {
    return FALSE;
  }
}

/**
 * Delete a specific field
 * @param int $id specific field id
 */
function delete_specific_field($id) {
  $table_sf     = Database :: get_main_table(TABLE_MAIN_SPECIFIC_FIELD);
  $id = (int)$id;
  if (!is_numeric($id)) {
    return FALSE;
  }
  $sql = 'DELETE FROM %s WHERE id=%s LIMIT 1';
  $sql = sprintf($sql, $table_sf, $id);
  $result = Database::query($sql);
  //TODO also delete the corresponding values
}

/**
 * Edit a specific field
 * @param int $id specific field id
 * @param string $name new field name
 */
function edit_specific_field($id, $name) {
  $table_sf     = Database :: get_main_table(TABLE_MAIN_SPECIFIC_FIELD);
  $id = (int)$id;
  if (!is_numeric($id)) {
    return FALSE;
  }
  $sql = 'UPDATE %s SET name = \'%s\' WHERE id = %s LIMIT 1';
  $sql = sprintf($sql, $table_sf, $name, $id);
  $result = Database::query($sql);
}

/**
 * @param array $conditions a list of condition (exemple : status=>STUDENT)
 * @param array $order_by a list of fields on which to sort
 * @return array An array with all specific fields, at platform level
 */
function get_specific_field_list($conditions = array(), $order_by = array()) {
  $table_sf     = Database :: get_main_table(TABLE_MAIN_SPECIFIC_FIELD);
  $return_array = array();
  $sql = "SELECT * FROM $table_sf";
  if (count($conditions) > 0) {
    $sql .= ' WHERE ';
    $conditions_string_array = array();
    foreach ($conditions as $field => $value) {
      $conditions_string_array[] = $field.' = '. $value;
    }
    $sql .= implode(' AND ', $conditions_string_array);
  }
  if (count($order_by) > 0) {
    $sql .= ' ORDER BY '.implode(',',$order_by);
  }
  $sql_result = Database::query($sql);
  while ($result = Database::fetch_array($sql_result)) {
    $return_array[] = $result;
  }

  return $return_array;
}

/**
 * @param array $conditions a list of condition (exemple : status=>STUDENT)
 * @param array $order_by a list of fields on which sort
 * @return array An array with all users of the platform.
 */
function get_specific_field_values_list($conditions = array(), $order_by = array()) {
  $table_sfv     = Database :: get_main_table(TABLE_MAIN_SPECIFIC_FIELD_VALUES);
  $return_array = array();
  $sql = "SELECT * FROM $table_sfv";
  if (count($conditions) > 0) {
    $sql .= ' WHERE ';
    $conditions_string_array = array();
    foreach ($conditions as $field => $value) {
      $conditions_string_array[] = $field.' = '. $value;
    }
    $sql .= implode(' AND ', $conditions_string_array);
  }
  if (count($order_by) > 0) {
    $sql .= ' ORDER BY '.implode(',',$order_by);
  }
  $sql_result = Database::query($sql);
  while ($result = Database::fetch_array($sql_result)) {
    $return_array[] = $result;
  }

  return $return_array;
}

/**
 * @param char $prefix xapian prefix
 * @param string $course_code
 * @param string $tool_id Constant from mainapi.lib.php
 * @param int $ref_id representative id inside one tool item
 * @return array
 */
function get_specific_field_values_list_by_prefix($prefix, $course_code, $tool_id, $ref_id) {
  $table_sf = Database :: get_main_table(TABLE_MAIN_SPECIFIC_FIELD);
  $table_sfv = Database :: get_main_table(TABLE_MAIN_SPECIFIC_FIELD_VALUES);
  $sql = 'SELECT sfv.value FROM %s sf LEFT JOIN %s sfv ON sf.id = sfv.field_id' .
         ' WHERE sf.code = \'%s\' AND sfv.c_id = \'%s\' AND tool_id = \'%s\' AND sfv.ref_id = %s';
  $sql = sprintf($sql, $table_sf, $table_sfv, $prefix, $course_code, $tool_id, $ref_id);
  $sql_result = Database::query($sql);
  while ($result = Database::fetch_array($sql_result)) {
    $return_array[] = $result;
  }
  return $return_array;
}

/**
 * Add a specific field value
 * @param int $id_specific_field specific field id
 * @param string $course_id course code
 * @param string $tool_id tool id, from main.api.lib
 * @param int $ref_id intern id inside specific tool table
 * @param string $value specific field value
 */
function add_specific_field_value($id_specific_field, $course_id, $tool_id, $ref_id, $value) {
    $table_sf_values = Database :: get_main_table(TABLE_MAIN_SPECIFIC_FIELD_VALUES);
    $value = trim($value);
    if (empty($value)) {
        return false;
    }
    $sql = 'INSERT INTO %s(id, course_code, tool_id, ref_id, field_id, value) VALUES(NULL, \'%s\', \'%s\', %s, %s, \'%s\')';
    $sql = sprintf($sql, $table_sf_values, $course_id, $tool_id, $ref_id, $id_specific_field, Database::escape_string($value));
    $result = Database::query($sql);
    if ($result) {
        return Database::insert_id();
    } else {
        return false;
    }
}

/**
 * Delete all values from a specific field id, course_id, ref_id and tool
 * @param string $course_id course code
 * @param int $id_specific_field specific field id
 * @param string $tool_id tool id, from main.api.lib
 * @param int $ref_id intern id inside specific tool table
 */
function delete_all_specific_field_value($course_id, $id_specific_field, $tool_id, $ref_id) {
  $table_sf_values = Database :: get_main_table(TABLE_MAIN_SPECIFIC_FIELD_VALUES);
  $sql = 'DELETE FROM %s WHERE course_code = \'%s\' AND tool_id = \'%s\' AND ref_id = %s AND field_id = %s';
  $sql = sprintf($sql, $table_sf_values, $course_id, $tool_id, $ref_id, $id_specific_field);
  $result = Database::query($sql);
}

/**
 * Delete all values from a specific item (course_id, tool_id and ref_id).
 * To be used when deleting such item from Dokeos
 * @param   string  Course code
 * @param   string  Tool ID
 * @param   int     Internal ID used in specific tool table
 */
function delete_all_values_for_item($course_id, $tool_id, $ref_id) {
  $table_sf_values = Database :: get_main_table(TABLE_MAIN_SPECIFIC_FIELD_VALUES);
  $sql = 'DELETE FROM %s WHERE course_code = \'%s\' AND tool_id = \'%s\' AND ref_id = %s';
  $sql = sprintf($sql, $table_sf_values, $course_id, $tool_id, $ref_id);
  $result = Database::query($sql);
}

/**
 * Generates a code (one-letter string) for a given field name
 * Defaults to the first letter of the name, otherwise iterate through available
 * letters
 * @param   string  Name
 * @return  string  One-letter code, upper-case
 */
function get_specific_field_code_from_name($name) {
    // Z is used internally by Xapian
    // O & C already used by tool_id and course_id
    $list = array('A','B','D','E','F','G','H','I','J','K','L','M','N','P','Q','R','S','T','U','V','W','X','Y');
    $table_sf     = Database :: get_main_table(TABLE_MAIN_SPECIFIC_FIELD);
    $sql = "SELECT code FROM $table_sf ORDER BY code";
    $res = Database::query($sql);
    $code = strtoupper(substr($name,0,1));
    //if no code exists in DB, return current one
    if (Database::num_rows($res)<1) { return $code;}

    $existing_list = array();
    while ($row = Database::fetch_array($res)) {
    	$existing_list[] = $row['code'];
    }
    //if the current code doesn't exist in DB, return current one
    if (!in_array($code,$existing_list)) { return $code;}

    $idx = array_search($code,$list);
    $c = count($list);
    for ($i = $idx+1, $j=0 ; $j<$c ; $i++, $j++) {
        if (!in_array($list[$i],$existing_list)) { return $idx[$i]; }
    }
    // all 26 codes are used
    return false;
}

<?php
/**
 * Migration class (ease the migration work)
 */
class Migration {
  public $odbtype = '';
  public $odbhost = '';
  public $odbport = '';
  public $odbuser = '';
  public $odbpass = '';
  public $odbname = '';
  public $errors_stack = array();
  public $origin_tables = array();
  public $destination_tables = array();
  public $relationship_tables = array();
  public $odbrows = null;
  /**
   *
   */
  public function __construct($dbhost, $dbport, $dbuser, $dbpass, $dbname) {
    if (empty($dbhost) || empty($dbport) || empty($dbuser) || empty ($dbpass) || empty($dbname)) {
      $this->errors_stack[] = 'All origin database params must be given. Received '.print_r(func_get_args(),1);
      return false;
    }
    $this->odbtype = $dbtype;
    $this->odbhost = $dbhost;
    $this->odbport = $dbport;
    $this->odbuser = $dbuser;
    $this->odbpass = $dbpass;
    $this->odbname = $dbname;
  }
  /**
   *
   */
  public function connect() {
    //extend in child class
  }
  /**
   *
   */
  public function migrate($matches) {
    $found = false;
    $table_idx = -1;
    foreach ($matches as $id => $table) {
      error_log('Found table '.$table['orig_table']);
      $build_only = false;
      if (empty($table['dest_table'])) {
        error_log(' ...which is just for data building');
        $build_only = true;
      }
      // Process the migration of fields from the given table
      $sql_select_fields = array();
      foreach ($table['fields_match'] as $id_field => $details) {
        if (empty($details['orig'])) {
          //Ignore if the field declared in $matches doesn't exist in
          // the original database
          continue;
        }
        $sql_select_fields[$details['orig']] = $details['orig'];
        // If there is something to alter in the SQL query, rewrite the entry
        if (!empty($details['sql_alter'])) {
          $func_alter = $details['sql_alter'];
          $sql_select_fields[$details['orig']] = MigrationCustom::$func_alter($details['orig']);
        }
        error_log('  Found field '.$details['orig'].' to be selected as '.$sql_select_fields[$details['orig']]);
      }
      $this->select_all($table['orig_table'], $sql_select_fields);
      if (count($table['fields_match']) == 0) {
        error_log('No fields found');
        continue;
      }
      while ($row = $this->fetch_row()) {
        $dest_row = array();
        $first_field = '';
        foreach ($table['fields_match'] as $id_field => $details) {
          if ($id_field == 0) { $first_field = $details['dest']; }
          // process the fields one by one
          if ($details['func'] == 'none') {
            $dest_data = $row[$details['orig']];
          } else {
            $dest_data = MigrationCustom::$details['func']($row[$details['orig']]);
          }
          $dest_row[$details['dest']] = $dest_data;
        }
        if (!empty($table['dest_func'])) {
          error_log('Calling MigrationCustom::'.$table['dest_func'].' on data recovered: '.print_r($dest_row,1));
          //$table['dest_func']($dest_row);
        } else {
          $this->errors_stack[] = "No destination data dest_func found. Abandoning data with first field $first_field = ".$dest_row[$first_field];
        }
      }
      error_log('Finished processing table '.$table['orig_table']);
    }
    die();
  }
}

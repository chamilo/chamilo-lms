<?php

/**
 * Scipt defining the Migration class
 */

/**
 * Migration class (ease the migration work). This class *must* be extended
 * in a database server-specific implementation as migration.[DB].class.php
 */
class Migration {

    /**
     * Origin DB type holder
     */
    public $odbtype = '';

    /**
     * Origin DB host holder
     */
    public $odbhost = '';

    /**
     * Origin DB port holder
     */
    public $odbport = '';

    /**
     * Origin DB user holder
     */
    public $odbuser = '';

    /**
     * Origin DB password holder
     */
    public $odbpass = '';

    /**
     * Origin DB name holder
     */
    public $odbname = '';

    /**
     * Array holding all errors/warnings ocurring during one execution
     */
    public $errors_stack = array();

    /**
     * Temporary handler for SQL result
     */
    public $odbrows = null;

    /**
     * Temporary holder for the list of users, courses and sessions and their 
     * data. Store values here (preferably using the same indexes as the
     * destination database field names) until ready to insert into Chamilo.
     */
    public $data_list = array('users' => array(), 'courses' => array(), 'sessions' => array());

    /**
     * The constructor assigns all database connection details to the migration
     * object
     * @param string The original database's host
     * @param string The original database's port
     * @param string The original database's user
     * @param string The original database's password
     * @param string The original database's name
     * @return boolean False on error. Void on success.
     */
    public function __construct($dbhost, $dbport, $dbuser, $dbpass, $dbname) {
        if (empty($dbhost) || empty($dbport) || empty($dbuser) || empty($dbpass) || empty($dbname)) {
            $this->errors_stack[] = 'All origin database params must be given. Received ' . print_r(func_get_args(), 1);
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
     * The connect method should be extended by the child class
     */
    public function connect() {
        //extend in child class
    }

    /**
     * The migrate method launches the migration process based on an array of
     * tables and fields matches defined in the given array.
     * @param array Structured array of matches (see migrate.php)
     */
    public function migrate($matches) {
        $found = false;
        $table_idx = -1;
        error_log("\n".'------------ Migration->migrate function called ------------'."\n");
        
        foreach ($matches as $id => $table) {
            error_log('Found table ' . $table['orig_table']);
            $build_only = false;
            
            if (empty($table['dest_table'])) {
                error_log(' ... which is just for data building');
                $build_only = true;
            }
            
            //Create extra fields
            if (isset($table['extra_fields']) && in_array($table['dest_table'], array('course', 'user', 'session'))) {
                error_log('Inserting (if does not exists) extra fields for : ' . $table['dest_table']." \n");
                foreach ($table['extra_fields'] as $extra_field) {
                    $options = $extra_field['options'];
                    unset($extra_field['options']);
                    
                    $extra_field_obj = new ExtraField($table['dest_table']);
                    $extra_field_id = $extra_field_obj->save($extra_field);
                    
                    $selected_fields = self::prepare_field_match($options);
                    //error_log('$selected_fields: ' . print_r($selected_fields, 1));
                    
                    $extra_field_option_obj = new ExtraFieldOption($table['dest_table']);

                    $this->select_all($options['orig_table'], $selected_fields);                        
                    $num_rows = $this->num_rows();            
                    //error_log('Records found: '.$num_rows);
                    if ($num_rows) {
                        $data_to_insert = array();
                        $data_to_insert['field_id'] = $extra_field_id;

                        while ($row = $this->fetch_array()) { 
                            $data = self::execute_field_match($options, $row);
                            $data_to_insert = array_merge($data_to_insert, $data);                                                              
                        }
                        //error_log('$data: ' . print_r($data_to_insert, 1));     
                        $extra_field_option_obj->save_one_item($data_to_insert, false, false); 
                    }                        
                }
                exit;
            }
            
            // Process the migration of fields from the given table
            $sql_select_fields = self::prepare_field_match($table);
            $this->select_all($table['orig_table'], $sql_select_fields);
            
            if (count($table['fields_match']) == 0) {
                error_log('No fields found');
                continue;
            }
            
            $num_rows = $this->num_rows();
            
            if ($num_rows) {            
                error_log('Records found: '.$num_rows);
                while ($row = $this->fetch_array()) {                    
                    self::execute_field_match($table, $row);
                }                
                error_log('Finished processing table ' . $table['orig_table']."\n\n");                
                if ($table['orig_table'] == 'ProgramaAcademico')  {
                    exit;
                }
            } else {
                error_log('No records found');
            }
        }
    }
    
    function prepare_field_match($table) {        
        $sql_select_fields = array();
        foreach ($table['fields_match'] as $details) {
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
            error_log('Found field ' . $details['orig'] . ' to be selected as ' . $sql_select_fields[$details['orig']]);
        }
        return $sql_select_fields;     
    }
    
    function execute_field_match($table, $row) {
        $dest_row = array();
        $first_field = '';
        foreach ($table['fields_match'] as $id_field => $details) {
            if ($id_field == 0) {
                $first_field = $details['dest'];
            }
            // process the fields one by one
            if ($details['func'] == 'none') {
                $dest_data = $row[$details['orig']];
            } else {
                $dest_data = MigrationCustom::$details['func']($row[$details['orig']], $this->data_list);
            }
            $dest_row[$details['dest']] = $dest_data;
        }
        if (!empty($table['dest_func'])) {
            error_log('Calling ' . $table['dest_func'] . ' on data recovered: ' . print_r($dest_row, 1));
            call_user_func_array($table['dest_func'], array($dest_row));                        
        } else {
            $this->errors_stack[] = "No destination data dest_func found. Abandoning data with first field $first_field = " . $dest_row[$first_field];
        }
        return $dest_row;
    }
}
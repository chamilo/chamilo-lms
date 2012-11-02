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
        //$this->odbtype = $dbtype;
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
        error_log("\n" . '------------ Migration->migrate function called ------------' . "\n");
        $extra_fields = array();
        // Browsing through 1st-level arrays in db_matches.php
        foreach ($matches as $table) {
            error_log('Found table ' . $table['orig_table'] . ' in db_matches');
            $build_only = false;

            if (empty($table['dest_table'])) {
                //If there is no destination for this table, report
                error_log(' ... which is just for data collection');
                $build_only = true;
            }

            // Creating extra fields if necessary inside Chamilo (to store 
            // original fields)
            if (isset($table['extra_fields']) && in_array($table['dest_table'], array('course', 'user', 'session'))) {
                $extra_fields = self::_create_extra_fields($table);
            }

            // Process the migration of fields from the given table
            $sql_select_fields = self::prepare_field_match($table);
            $this->select_all($table['orig_table'], $sql_select_fields, $table);

            if (count($table['fields_match']) == 0) {
                error_log('No fields found');
                continue;
            }
            $num_rows = $this->num_rows();

            if ($num_rows) {
                error_log('Records found: ' . $num_rows);
                $item = 1;
                while ($row = $this->fetch_array()) {
                    //error_log('Loading: ');error_log(print_r($row, 1));
                    self::execute_field_match($table, $row, $extra_fields);
                    $percentage = $item / $num_rows * 100;
                    if (round($percentage) % 10 == 0) {
                        $percentage = round($percentage, 3);
                        error_log("Processing item {$table['orig_table']} #$item $percentage%");
                    }
                    $item++;
                }
                error_log('Finished processing table ' . $table['orig_table'] . " \n\n");
            } else {
                error_log('No records found');
            }

            //Stop here (only for tests)
            if ($table['orig_table'] == 'gradebook_evaluation_type') {
                exit;
            }
        }
    }
    
    function soap_call($web_service_params, $function_name, $params = array()) {
        // Create the client instance
        $url = $web_service_params['url'];        
        error_log("\nCalling function '$function_name' in $url with params: ");        
        var_dump($params);

        try {
            $client = new SoapClient($url);
        } catch (SoapFault $fault) {
            $error = 1;
            die('Error connecting');
        }
        
        $client->debug_flag = true;     
        try {            
            $data = $client->$function_name($params);            
        } catch (SoapFault $fault) {
            $error = 2;
            die("Problem querying service - $function_name");
        }
        
        if (!empty($data)) {
            error_log("Calling {$web_service_params['class']}::$function_name");
            $result = $web_service_params['class']::$function_name($data);           
        } else {
            error_log('No data found');
        }
        error_log("\n--End--");
    }

    function search_transactions($web_service_params) {
        $libpath = api_get_path(LIBRARY_PATH);
        error_log('search_transactions');
        
        //Testing transactions
        
        $result = self::soap_call($web_service_params, 'transacciones', array('ultimo' => 1, 'cantidad' => 10));

        $result = self::soap_call($web_service_params, 'usuarioDetalles', array('uididpersona' => 'D236776B-D7A5-47FF-8328-55EBE9A59015'));
        $result = self::soap_call($web_service_params, 'programaDetalles', array('uididprograma' => 'C3671999-095E-4018-9826-678BAFF595DF'));
        $result = self::soap_call($web_service_params, 'cursoDetalles', array('uididcurso' => 'E2334974-9D55-4BB4-8B57-FCEFBE2510DC'));        
        
        $result = self::soap_call($web_service_params, 'faseDetalles', array('uididfase' => 'EBF63F1C-FBD7-46A5-B039-80B5AF064929'));
        $result = self::soap_call($web_service_params, 'frecuenciaDetalles', array('uididfrecuencia' => '0091CD3B-F042-11D7-B338-0050DAB14015'));
        $result = self::soap_call($web_service_params, 'intensidadDetalles', array('uididintensidad' => '0091CD3C-F042-11D7-B338-0050DAB14015'));
        $result = self::soap_call($web_service_params, 'mesesDetalles', array('uididfase' => 'EBF63F1C-FBD7-46A5-B039-80B5AF064929'));
        $result = self::soap_call($web_service_params, 'sedeDetalles', array('uididsede' => '7379A7D3-6DC5-42CA-9ED4-97367519F1D9'));        
        $result = self::soap_call($web_service_params, 'horarioDetalles', array('uididhorario' => 'E395895A-B480-456F-87F2-36B3A1EBB81C'));
        
        if (!empty($result)) {
            error_log(count($result)." transactions found: ");
            foreach ($result as $transaction_info) {
                /*
                id transaccion
                id sede
                id accion
                id
                origen
                destino
                timestamp                 
                 */
                //Add transactions here
                $params = array(
                    'action' => 'usuario_agregar',
                    'item_id' =>  '1',
                    'orig_id' => '0',
                    'branch_id' => $transaction_info['sede'],
                    'dest_id' => null,
                    'status_id' => 0
                );        
                $transaction_id = self::add_transaction($params);
                if ($transaction_id) {
                    error_log("Transaction #$transaction_id was created");
                }
            }
        }
    }
    
    function add_transaction($params) {
        $table = Database::get_main_table(TABLE_MIGRATION_TRANSACTION);
        if (isset($params['id'])) {
            unset($params['id']);
        }
        $params['time_update'] = $params['time_insert'] = api_get_utc_datetime();        
        
        $inserted_id = Database::insert($table, $params);
        if ($inserted_id) {
            error_log("Transaction added #$inserted_id");
        }
        return $inserted_id;        
    }
        
    function get_branches() {
        $table = Database::get_main_table(TABLE_MIGRATION_TRANSACTION);
        $sql = "SELECT DISTINCT branch_id FROM $table ORDER BY branch_id";
        $result = Database::query($sql);
        return Database::store_result($result, 'ASSOC');
    }
    
    /** Get unprocesses */
    function get_transactions($status_id = 0, $branch_id = null) {
        $table = Database::get_main_table(TABLE_MIGRATION_TRANSACTION);
        $branch_id = intval($branch_id);
        $status_id = intval($status_id);
        
        $extra_conditions = null;
        if (!empty($branch_id)) {
            $extra_conditions = " AND branch_id = $branch_id ";
        }
        $sql = "SELECT * FROM $table WHERE status_id = $status_id $extra_conditions ORDER BY id ";
        $result = Database::query($sql);        
        return Database::store_result($result, 'ASSOC');
    }
    
    function get_latest_completed_transaction_by_branch($branch_id) {
        $table = Database::get_main_table(TABLE_MIGRATION_TRANSACTION);
        $branch_id = intval($branch_id);
        $sql = "SELECT id FROM $table WHERE status_id = 2 AND branch_id = $branch_id ORDER BY id DESC  LIMIT 1";
        $result = Database::query($sql);
        if (Database::num_rows($result)) {
            $row = Database::fetch_array($result);
            return $row['id'];
        }
        return 0;
    }
    
    function get_latest_transaction_by_branch($branch_id) {
        $table = Database::get_main_table(TABLE_MIGRATION_TRANSACTION);
        $branch_id = intval($branch_id);
        $sql = "SELECT id FROM $table WHERE branch_id = $branch_id ORDER BY id DESC  LIMIT 1";
        $result = Database::query($sql);
        if (Database::num_rows($result)) {
            $row = Database::fetch_array($result);
            return $row['id'];
        }
        return 0;
    }
    function get_transaction_by_params($params, $type_result = 'all') {
        $table = Database::get_main_table(TABLE_MIGRATION_TRANSACTION);
        return Database::select('*', $table, $params, $type_result);
    }
    
    function update_transaction($params) {
        return false;
        $table = Database::get_main_table(TABLE_MIGRATION_TRANSACTION);
        if (empty($params['id'])) {
            error_log('No transaction id provided during update_transaction');
            return false;
        }
        $params['time_update'] = api_get_utc_datetime();
        error_log("Transaction updated #{$params['id']}");
        
        //Failed - do something else
        if ($params['status_id'] == MigrationCustom::TRANSACTION_STATUS_FAILED) {
            //event_system($event_type, $event_value_type, $event_value, $datetime = null, $user_id = null, $course_code = null) {
            event_system('transaction_error', 'transaction_id', $params['id'], $params['time_update']);
        }
        return Database::update($table, $params, array('id = ?' => $params['id']));
    }    

    /* Load transactions */

    function load_transactions($matches) {
        $actions = $matches['actions'];
        
        //Getting transactions of the migration_transaction table
        $branches = self::get_branches();
        if (!empty($branches)) {
            foreach ($branches as $branch_info) {
                //Get uncompleted transactions                
                $transactions = self::get_transactions(0, $branch_info['branch_id']);                
         
                $options = array('where' => array('branch_id = ? and status_id <> ?' => array($branch_info['branch_id'], 0)), 'order' => 'id desc', 'limit' => '1');
                $transaction_info = self::get_transaction_by_params($options, 'first');               
                $latest_id = $transaction_info['id'];
                
                $latest_id_attempt = $latest_id + 1;
                
                $item = 1;

                if (!empty($transactions)) {
                    $count = count($transactions);
                    error_log("\nTransactions found: $count");

                    //Looping transactions
                    foreach ($transactions as $transaction) {
                        
                        //Calculating percentage
                        $percentage = $item / $count * 100;
                        if (round($percentage) % 10 == 0) {
                            $percentage = round($percentage, 3);
                            error_log("\nProcessing transaction #{$transaction['id']} $percentage%");
                        }
                        $item++;
                        //--
                        
                        //Checking "huecos"
                        //Waiting transaction is fine continue:
                        if ($transaction['id'] == $latest_id_attempt) {
                            $latest_id_attempt++;
                        } else {
                            error_log("Transaction #$latest_id_attempt is missing in branch #{$branch_info['branch_id']}");
                            exit;
                        }
                        
                        //Loading function
                        $function_to_call = "transaction_" . $transaction['action'];
                        if (method_exists('MigrationCustom', $function_to_call)) {
                            $result = MigrationCustom::$function_to_call($transaction, $matches['web_service_calls']);
                            
                            error_log("Calling function $function_to_call");
                            if ($result) {
                                //Updating transaction
                                self::update_transaction(array('id' => $transaction['id'] , 'status_id' => $result['status']));                        
                            } else {
                                //failed
                                self::update_transaction(array('id' => $transaction['id'] , 'status_id' => MigrationCustom::TRANSACTION_STATUS_FAILED));
                            }
                        } else {
                            //	method does not exist
                            error_log("Function does $function_to_call not exists");
                            //Failed
                            self::update_transaction(array('id' => $transaction['id'] , 'status_id' => MigrationCustom::TRANSACTION_STATUS_FAILED));
                        }
                    }
                } else {
                    error_log('No transactions to load');
                }
            }
        } else {
            error_log('No branches found');
        }

        $actions = array(); //load actions from Mysql
        foreach ($actions as $action_data) {
            if (in_array($action_data['action'], $transactions)) {
                $function_to_call = $transactions[$action_data['action']];
                $function_to_call($action_data['params']);
            }
        }
    }

    function prepare_field_match($table) {
        $sql_select_fields = array();
        if (!empty($table['fields_match'])) {
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
                //error_log('Found field ' . $details['orig'] . ' to be selected as ' . $sql_select_fields[$details['orig']]);
            }
        }
        return $sql_select_fields;
    }

    function execute_field_match($table, $row, $extra_fields = array()) {
        //error_log('execute_field_match');
        $dest_row = array();
        $first_field = '';

        $my_extra_fields = isset($table['dest_table']) && isset($extra_fields[$table['dest_table']]) ? $extra_fields[$table['dest_table']] : null;
        $extra_field_obj = null;
        $extra_field_value_obj = null;

        if (!empty($table['dest_table'])) {
            $extra_field_obj = new Extrafield($table['dest_table']);
            $extra_field_value_obj = new ExtraFieldValue($table['dest_table']);
        }

        $extra_fields_to_insert = array();
        foreach ($table['fields_match'] as $id_field => $details) {
            if ($id_field == 0) {
                $first_field = $details['dest'];
            }
            if (isset($details['orig'])) {
                $field_exploded = explode('.', $details['orig']);
                if (isset($field_exploded[1])) {
                    $details['orig'] = $field_exploded[1];
                }
            }

            // process the fields one by one
            if ($details['func'] == 'none' || empty($details['func'])) {
                $dest_data = $row[$details['orig']];
            } else {
                $dest_data = MigrationCustom::$details['func']($row[$details['orig']], $this->data_list, $row);
            }

            if (isset($dest_row[$details['dest']])) {
                $dest_row[$details['dest']] .= ' ' . $dest_data;
            } else {
                $dest_row[$details['dest']] = $dest_data;
            }

            //Extra field values
            $extra_field = isset($my_extra_fields) && isset($my_extra_fields[$details['dest']]) ? $my_extra_fields[$details['dest']] : null;
            //error_log('-----');
            //error_log(print_r($extra_field, 1));
            if (!empty($extra_field) && $extra_field_obj) {
                if (isset($extra_field['options'])) {
                    $options = $extra_field['options'];
                    $field_type = $extra_field['field_type'];

                    if (!empty($options)) {
                        foreach ($options as $option) {
                            foreach ($option as $key => $value) {
                                //error_log("$key $value --> {$dest_row[$details['dest']]} ");
                                if ($key == 'option_value' && $value == $dest_row[$details['dest']]) {
                                    $value = $option['option_display_text'];
                                    if ($field_type == Extrafield::FIELD_TYPE_SELECT) {
                                        $value = $option['option_value'];
                                    }
                                    $params = array(
                                        'field_id' => $option['field_id'],
                                        'field_value' => $value,
                                    );
                                    break(2);
                                }
                            }
                        }
                    }
                } else {
                    $params = array(
                        'field_id' => $extra_field,
                        'field_value' => $dest_row[$details['dest']],
                    );
                }
                if (!empty($params)) {
                    $extra_fields_to_insert[] = $params;
                }
                unset($dest_row[$details['dest']]);
            }
        }

        if (!empty($table['dest_func'])) {
            //error_log('Calling '.$table['dest_func'].' on data recovered: '.print_r($dest_row, 1));            
            $dest_row['return_item_if_already_exists'] = true;

            $item_result = call_user_func_array($table['dest_func'], array($dest_row, $this->data_list));

            if (isset($table['show_in_error_log']) && $table['show_in_error_log'] == false) {
                
            } else {
                //error_log('Result of calling ' . $table['dest_func'] . ': ' . print_r($item_result, 1));
            }
            //error_log('Result of calling ' . $table['dest_func'] . ': ' . print_r($item_result, 1));
            //After the function was executed fill the $this->data_list array
            switch ($table['dest_table']) {
                case 'course':
                    //Saving courses in array
                    if ($item_result) {
                        //$this->data_list['courses'][$dest_row['uidIdCurso']] = $item_result;        
                    } else {
                        error_log('Course Not FOUND');
                        error_log(print_r($item_result, 1));
                        exit;
                    }
                    $handler_id = $item_result['code'];
                    break;
                case 'user':
                    if (!empty($item_result)) {
                        $handler_id = $item_result['user_id'];
                        //error_log($dest_row['email'].' '.$dest_row['uidIdPersona']);
                        if (isset($dest_row['uidIdAlumno'])) {
                            //$this->data_list['users_alumno'][$dest_row['uidIdAlumno']]['extra'] = $item_result;
                        }
                        if (isset($dest_row['uidIdEmpleado'])) {
                            //print_r($dest_row['uidIdEmpleado']);exit;                           
                            //$this->data_list['users_empleado'][$dest_row['uidIdEmpleado']]['extra'] = $item_result;
                        }
                    } else {
                        global $api_failureList;
                        error_log(print_r($api_failureList, 1));
                    }
                    break;
                case 'session':
                    //$this->data_list['sessions'][$dest_row['uidIdPrograma']] = $item_result;                    
                    $handler_id = $item_result; //session_id
                    break;
            }

            //Saving extra fields of the element
            if (!empty($extra_fields_to_insert)) {
                foreach ($extra_fields_to_insert as $params) {
                    $params[$extra_field_value_obj->handler_id] = $handler_id;
                    $extra_field_value_obj->save($params);
                }
            }
        } else {
            // $this->errors_stack[] = "No destination data dest_func found. Abandoning data with first field $first_field = " . $dest_row[$first_field];
        }
        unset($extra_fields_to_insert); //remove to free up memory
        return $dest_row;
    }

    /**
     * Helper function to create extra fields in the Chamilo database
     * @param Array An array containing an 'extra_fields' entry with details about the required extra fields
     * @return void
     */
    private function _create_extra_fields(&$table) {
        $extra_fields = array();

        error_log('Inserting (if exists) extra fields for : ' . $table['dest_table'] . " \n");
        foreach ($table['extra_fields'] as $extra_field) {
            error_log('Preparing for insertion of extra field ' . $extra_field['field_display_text'] . "\n");
            $options = isset($extra_field['options']) ? $extra_field['options'] : null;
            unset($extra_field['options']);

            $extra_field_obj = new ExtraField($table['dest_table']);
            $extra_field_id = $extra_field_obj->save($extra_field);

            $selected_fields = self::prepare_field_match($options);

            //Adding options
            if (!empty($options)) {
                $extra_field_option_obj = new ExtraFieldOption($table['dest_table']);
                $this->select_all($options['orig_table'], $selected_fields);
                $num_rows = $this->num_rows();

                if ($num_rows) {
                    $data_to_insert = array();
                    $data_to_insert['field_id'] = $extra_field_id;

                    while ($row = $this->fetch_array()) {
                        $data = self::execute_field_match($options, $row);
                        $data_to_insert = array_merge($data_to_insert, $data);
                        $extra_field_option_obj->save_one_item($data_to_insert, false, false);
                        //error_log(print_r($extra_fields[$table['dest_table']]['extra_field_'.$extra_field['field_variable']], 1));
                        $extra_fields[$table['dest_table']]['extra_field_' . $extra_field['field_variable']]['options'][] = $data_to_insert;
                        $extra_fields[$table['dest_table']]['extra_field_' . $extra_field['field_variable']]['field_type'] = $extra_field['field_type'];
                    }
                    //$extra_fields[$table['dest_table']]['extra_field_'.$extra_field['field_variable']]['selected_option'] = 
                    //error_log('$data: ' . print_r($data_to_insert, 1));
                }
            } else {
                $extra_fields[$table['dest_table']]['extra_field_' . $extra_field['field_variable']] = $extra_field_id;
            }
        }
        return $extra_fields;
    }

}
<?php

/**
 *
 */
class MigrationMSSQL extends Migration {

    public function __construct($dbhost, $dbport = '1433', $dbuser, $dbpass, $dbname) {
        parent::__construct($dbhost, $dbport, $dbuser, $dbpass, $dbname);
        ini_set('display_errors', 1);
        ini_set('mssql.datetimeconvert', 0);
        $this->odbtype = 'mssql';
    }

    public function connect() {
        $this->c = mssql_connect($this->odbhost, $this->odbuser, $this->odbpass, TRUE);
        if ($this->c === false) {
            $this->errors_stack[] = 'Could not connect. MSSQL error: ' . mssql_get_last_message();
            return false;
        }
        mssql_select_db($this->odbname, $this->c);
        return true;
    }

    public function select_all($table, $fields) {
        $fields_sql = '';
        foreach ($fields as $field) {
            $fields_sql .= $field . ', ';
        }
        if (!empty($fields_sql)) {
            $fields_sql = substr($fields_sql, 0, -2);
        }
        //In order to process X item of each table add TOP X
        $sql = "SELECT $fields_sql FROM $table";
        //remove
        error_log($sql);
        $this->rows_iterator = mssql_query($sql, $this->c);
        
        if ($this->rows_iterator  === false) {
            error_log("--- Error with query $sql MSSQL error: ".mssql_get_last_message()."-- \n");
        }
    }

    public function fetch_row() {        
        return mssql_fetch_row($this->rows_iterator);
    }
    
    public function fetch_array() {        
        return mssql_fetch_array($this->rows_iterator, MSSQL_ASSOC);
    }
    public function num_rows() {
        return mssql_num_rows($this->rows_iterator);
    }
}
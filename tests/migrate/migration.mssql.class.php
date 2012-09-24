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
        mssql_select_db($dbname, $this->c);
        return true;
    }

    public function select_all($table, $fields) {
        $fields_sql = '';
        foreach ($fields as $field) {
            $fields_sql .= $field . ', ';
        }
        $fields_sql = substr($fields_sql, 0, -2);
        $sql = 'SELECT ' . $fields_sql . ' FROM ' . $table;
        //remove
        error_log($sql);
        $this->rows_iterator = mssql_query($sql, $this->c);
    }

    public function fetch_row() {
        return mssql_fetch_row($this->rows_iterator);
    }

}

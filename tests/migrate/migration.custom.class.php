<?php

/**
 * This file contains the MigrationCustom class, which defines methods to
 * alter the data from the original database when importing it to the Chamilo
 * database
 */

/**
 * The custom migration class allows you to define rules to process data
 * during the migration
 */
class MigrationCustom {

    /**
     * The only required method is the 'none' method, which will not trigger
     * any process at all
     * @param mixed Data
     * @param mixed Unaltered data
     */
    public function none($data) {
        return $data;
    }

    /**
     * Transform the uid identifiers from MSSQL to a string
     * @param string Field name
     * @return string SQL select string to include in the final select
     */
    public function sql_alter_unhash_50($field) {
        return "cast( $field  as varchar(50)) as $field ";
    }

    /**
     * Log data from the original users table
     */
    public function log_original_user_unique_id(&$omigrate, $data) {
        $omigrate['users'][$data] = 0;
    }

    /**
     * Log data from the original users table
     */
    public function log_original_course_unique_id(&$omigrate, $data) {
        $omigrate['courses'][$data] = 0;
    }

    /**
     * Log data from the original users table
     */
    public function log_original_session_unique_id(&$omigrate, $data) {
        $omigrate['sessions'][$data] = 0;
    }
    
    public function store_user_data($data = array()) {
        
    }
}
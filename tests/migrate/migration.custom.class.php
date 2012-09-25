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
    public function log_original_user_unique_id($data, &$omigrate, $row_data) {        
        //omigrate['users_persona'][$row_data['uidIdPersona']] = $row_data;
        $omigrate['users_alumno'][$row_data['uidIdPersona']] = $row_data;
        //error_log(print_r($omigrate, 1));
        //$omigrate['users']['extra'] = $row_data
    }
    
    public function log_original_persona_unique_id($data, &$omigrate, $row_data) {        
        $omigrate['users_persona'][$row_data['uidIdPersona']] = $row_data;
    }
    
    public function log_original_teacher_unique_id($data, &$omigrate, $row_data) {        
        //$omigrate['users_persona'][$row_data['uidIdPersona']] = $row_data;
        
        $omigrate['users_empleado'][$row_data['uidIdPersona']] = $row_data;
        //error_log(print_r($omigrate, 1));
        //$omigrate['users']['extra'] = $row_data
    }

    /**
     * Log data from the original users table
     */
    public function log_original_course_unique_id($data, &$omigrate) {
        $omigrate['courses'][$data] = 0;
        return $data;
    }

    /**
     * Log data from the original users table
     */
    public function log_original_session_unique_id($data, &$omigrate) {
        $omigrate['sessions'][$data] = 0;
    }
    
    public function get_real_course_code($data, &$omigrate, $row_data) {
        /*error_log('get_real_course_code');
        error_log(print_r($data,1));
        error_log(print_r($omigrate['courses'][$data], 1));*/
        return $omigrate['courses'][$data]['code'];
    }
    
    public function get_real_teacher_id($data, &$omigrate, $row_data) {
        //error_log('get_real_teacher_id');
        //error_log(print_r($data, 1));
        //error_log(print_r($omigrate['users_empleado']), 1);
        
        //error_log(print_r($omigrate['users_empleado'], 1));
        //error_log($data);
        
        if (empty($omigrate['users_empleado'][$data]['uidIdPersona'])) {
            return api_get_user_id();
        } else {
            error_log('get_real_teacher_id'.$omigrate['users_empleado'][$data]['uidIdPersona']['user_id']);
            error_log(print_r($omigrate['users_empleado'][$data]['uidIdPersona'], 1));
            return $omigrate['users_empleado'][$data]['uidIdPersona']['user_id'];
        }
        return $omigrate['users_empleado'][$data]['user_id'];
    }
    
    public function store_user_data($data = array()) {
        
    }
    
    public function create_user($data, $data_list = array()) {       
        //error_log(print_r($data, 1));
        //error_log(print_r($data_list['user'], 1));        
        $user_id = UserManager::add($data);        
        return $user_id;
    }
    
     public function add_course_to_session($data, $data_list = array()) {       
        //error_log(print_r($data, 1));
        //error_log(print_r($data_list['user'], 1));        
        $user_id = UserManager::add($data);        
        return $user_id;
    }

}
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
        $omigrate['users_alumno'][$row_data['uidIdPersona']] = $row_data;        
    }
    
    public function log_original_persona_unique_id($data, &$omigrate, $row_data) {  
        if (isset($omigrate['users_persona'][$row_data['uidIdPersona']])) {
            $omigrate['users_persona'][$row_data['uidIdPersona']][] = $omigrate['users_persona'][$row_data['uidIdPersona']];
            $omigrate['users_persona'][$row_data['uidIdPersona']][] = $row_data;
            //error_log(print_r($row_data, 1));
            //error_log(print_r($omigrate['users_persona'][$row_data['uidIdPersona']], 1));            
            error_log('WHAT??');
        } else {
            $omigrate['users_persona'][$row_data['uidIdPersona']] = $row_data;
        }
    }
    
    public function log_original_teacher_unique_id($data, &$omigrate, $row_data) {        
        $omigrate['users_empleado'][$row_data['uidIdPersona']] = $row_data;                
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
        return $data;
    }
    
    public function get_real_course_code($data, &$omigrate, $row_data) {
        /*error_log('get_real_course_code');
        error_log(print_r($data,1));
        error_log(print_r($omigrate['courses'][$data], 1));*/
        if (!isset($omigrate['courses'][$data])) {
            error_log("Course not found in data_list array");
            error_log(print_r($data, 1));
            exit;
        }
        return $omigrate['courses'][$data]['code'];
    }
    
    function get_session_id($data, &$omigrate, $row_data) {
        error_log(print_r($data, 1));
        error_log(print_r($row_data, 1));
        if (!isset($omigrate['sessions'][$data])) {
            error_log(print_r($omigrate['sessions'], 1));
            error_log("sessions not found in data_list array ");
            exit;
        }
        return $omigrate['sessions'][$data];        
    }
    
    public function get_real_teacher_id($data, &$omigrate, $row_data) {
        //error_log('get_real_teacher_id');
        //error_log(print_r($data, 1));                
        //error_log(print_r($omigrate['users_empleado'], 1));        
        //error_log('get_real_teacher_id');
        //error_log($data);               
        if (empty($omigrate['users_empleado'][$data])) {
            //error_log('not set');
            return 1;
        } else {            
            $persona_id = $omigrate['users_empleado'][$data]['uidIdPersona'];  
            if (!empty($persona_id)) {
                return $omigrate['users_persona'][$persona_id]['user_id'];    
            }
        }        
        return $omigrate['users_empleado'][$data]['user_id'];
    }
    
    public function store_user_data($data = array()) {
        
    }
    
    public function create_user($data, $omigrate) {       
        //error_log(print_r($data, 1));  
        
        if (empty($data['uidIdPersona'])) {
            error_log('User does not have a uidIdPersona');
            error_log(print_r($data, 1));    
            exit;
        }
        
        if (isset($omigrate['users_persona'][$data['uidIdPersona']])) {
            ///error_log('persona!');
        }
        
        //Is a teacher
        if (isset($omigrate['users_empleado'][$data['uidIdPersona']])) {
            //error_log(print_r($omigrate['users_empleado'][$data['uidIdPersona']], 1));  
            //error_log(print_r($data, 1));
            //error_log('teacher');
            $data['username'] = UserManager::create_unique_username($data['firstname'], $data['lastname']);
            $data['status'] = COURSEMANAGER;                
        }
        
        //Is a student
        if (isset($omigrate['users_alumno'][$data['uidIdPersona']])) {
            $uidIdPersona = $data['uidIdPersona'];            
            $persona_info = $omigrate['users_alumno'][$uidIdPersona];
            //error_log(print_r($omigrate['users_alumno'][$data['uidIdPersona']], 1));  
            //error_log(print_r($data, 1));
            //error_log(print_r($uidIdPersona, 1));
            //error_log(print_r($persona_info, 1));
            $data['username'] = strtolower($persona_info['vchCodal']);
            $data['password'] = $persona_info['chrPasswordT'];
            $data['status'] = STUDENT;
            //error_log(print_r($data, 1));error_log('student');            
        }
        
        if (empty($data['username'])) {
            //Last chance to have a nice username
            $data['username'] = UserManager::create_unique_username($data['firstname'], $data['lastname']);
        }
                
        if (empty($data['username'])) {
            error_log('No Username provided');
            error_log(print_r($data, 1));
            exit;
        }
        
        unset($data['uidIdPersona']);
        
        global $api_failureList;
        $api_failureList = array();
        //error_log(print_r($data, 1));
        $user_info = UserManager::add($data);        
        if (isset($omigrate['users_empleado'][$data['uidIdPersona']])) {
            
        }
        return $user_info;
    }
    
     public function add_user_to_session($data, &$data_list) {       
        error_log(print_r($data, 1));
        //error_log(print_r($data_list['user'], 1));        
        //$user_id = UserManager::add($data);  
         
        //SessionManager::suscribe_users_to_session($data['session_id'], array($data['user_id']));
        return $user_id;
    }
    
    
}
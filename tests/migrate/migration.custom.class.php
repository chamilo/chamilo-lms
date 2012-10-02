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
    
    public $default_admin_id = 1;

    /**
     * The only required method is the 'none' method, which will not trigger
     * any process at all
     * @param mixed Data
     * @param mixed Unaltered data
     */
    public function none($data) {
        return $data;
    }
    
     public function join_horario($data, &$omigrate, $row_data) {
         return $row_data['chrHoraInicial'].' '.$row_data['chrHoraFinal'];
     }

    /**
     * Transform the uid identifiers from MSSQL to a string
     * @param string Field name
     * @return string SQL select string to include in the final select
     */
    public function sql_alter_unhash_50($field) {
        $as_field = explode('.', $field);     
        if (isset($as_field[1])) {
            $as_field = $as_field[1];
        } else {
            $as_field = $field;
        }
        return " cast( $field  as varchar(50)) as $as_field ";
    }
 
    /**
     * Log data from the original users table
     */
    public function log_original_user_unique_id($data, &$omigrate, $row_data) {        
        $omigrate['users_alumno'][$row_data['uidIdAlumno']] = $row_data;
        return $data;
    }
    
    public function clean_utf8($value) {
        return utf8_encode($value);        
    }
    
    public function clean_session_name($value, &$omigrate, $row_data) {
        return self::clean_utf8($row_data['session_name']);        
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
        return $data;
    }
    
    public function log_original_teacher_unique_id($data, &$omigrate, $row_data) {        
        $omigrate['users_empleado'][$row_data['uidIdEmpleado']] = $row_data;
        return $row_data['uidIdEmpleado'];               
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
    public function log_original_session_unique_id($data, &$omigrate, $row_data) {
        $omigrate['sessions'][$row_data['uidIdPrograma']] = $row_data;
        return $data;
    }
    
    public function get_real_course_code($data, &$omigrate, $row_data) {
        if (!isset($omigrate['courses'][$data])) {
            error_log("Course not found in data_list array");
           //error_log(print_r($data, 1));
            //exit;
        }
        return $omigrate['courses'][$data]['code'];
    }
    
    function get_session_id_by_programa_id($data, &$omigrate, $row_data) {
        /*error_log(print_r($data, 1));
        error_log(print_r($row_data, 1));
        error_log($omigrate['sessions'][$data]);*/
        if (!isset($omigrate['sessions'][$data])) {
            error_log(print_r($omigrate['sessions'], 1));
            error_log("Sessions not found in data_list array ");
            exit;
        }
        return $omigrate['sessions'][$data];        
    }
    
    public function get_user_id($data, &$omigrate, $row_data) {
        //error_log('get_real_teacher_id');
        //error_log(print_r($data, 1));                
        //error_log(print_r($omigrate['users_empleado'], 1));        
        //error_log('get_real_teacher_id');
        //error_log($data);               
        if (empty($omigrate['users_alumno'][$data])) {
            //error_log('not set');
            return 1;
        } else {            
            $persona_id = $omigrate['users_alumno'][$data]['uidIdPersona'];  
            if (!empty($persona_id)) {
                return $omigrate['users_persona'][$persona_id]['user_id'];    
            }
        }        
        return $omigrate['users_alumno'][$data]['user_id'];
    }
    
    public function get_real_teacher_id($data, &$omigrate, $row_data) {
        $default_teacher_id = $this->default_admin_id;
        //error_log('get_real_teacher_id');
        //error_log(print_r($data, 1));                
        //error_log(print_r($omigrate['users_empleado'], 1));        
        //error_log('get_real_teacher_id');
        //error_log($data);             
        if (empty($data)) {
            //error_log('No teacher provided');
            return $default_teacher_id;
        }
        
        if (!isset($omigrate['users_empleado'][$data])) {
            //error_log(' Teacher not found big problem! ');    
            //echo $data;
            //print_r($omigrate['users_empleado'][$data]);
            //echo $data;exit;
            return $default_teacher_id;            
        } else {
            //error_log('Teacher found: '.$omigrate['users_empleado'][$data]['extra']['user_id']);
            return isset($omigrate['users_empleado'][$data]['extra']) ? $omigrate['users_empleado'][$data]['extra']['user_id'] : $default_teacher_id;        
        }        
    }
    
    public function create_user($data, $omigrate) {
        //error_log(print_r($data, 1));  
        
        if (empty($data['uidIdPersona'])) {
            error_log('User does not have a uidIdPersona');
            error_log(print_r($data, 1));    
            exit;
        }
            
        //Is a teacher
        if (isset($omigrate['users_empleado'][$data['uidIdEmpleado']])) {
            //error_log(print_r($omigrate['users_empleado'][$data['uidIdPersona']], 1));  
            //error_log(print_r($data, 1));
            //error_log('teacher');
            //$data['username'] = UserManager::create_unique_username($data['firstname'], $data['lastname']);        
            $data['status'] = COURSEMANAGER;                
        }
        
        //Is a student
        if (isset($omigrate['users_alumno'][$data['uidIdAlumno']])) {
            $data['status'] = STUDENT;            
        }
        
        if (!isset($data['username']) || empty($data['username'])) {
            $data['firstname'] = (string) trim($data['firstname']); 
            $data['lastname'] = (string) trim($data['lastname']); 
            
            if (empty($data['firstname']) && empty($data['lastname'])) {
                $wanted_user_name = UserManager::purify_username($data['uidIdPersona']);
                //$wanted_user_name = UserManager::create_unique_username(null, null);                
            } else {
                $wanted_user_name = UserManager::create_username($data['firstname'], $data['lastname']);
            }
            
            $extra_data = UserManager::get_extra_user_data_by_value('uidIdPersona', $data['uidIdPersona']);
            
            if ($extra_data) {
                $user_info = api_get_user_info($extra_data[0]);
                //print_r($extra_data);
                //error_log("User_already_added - {$user_info['user_id']}  - {$user_info['username']} - {$user_info['firstname']} - {$user_info['lastname']}");
                return $user_info;
            }
            
            if (UserManager::is_username_available($wanted_user_name)) {
                $data['username'] = $wanted_user_name;
                error_log("username available  $wanted_user_name");
            } else {
                //the user already exists?
                $user_info = UserManager::get_user_info_simple($wanted_user_name);
                $user_persona = UserManager::get_extra_user_data_by_field($user_info['user_id'], 'uidIdPersona');
                
                if (isset($user_persona['uidIdPersona']) && $data['uidIdPersona'] == $user_persona['uidIdPersona']) {
                    error_log("Skip user already added: {$user_info['username']}");                    
                    return $user_info;
                } else {
                    error_log("homonym - wanted_username: $wanted_user_name - uidIdPersona: {$user_persona['uidIdPersona']} - username: {$user_info['username']}");       
                    print_r($data);
                     //The user has the same firstname and lastname but it has another uiIdPersona could by an homonym  
                    $data['username'] = UserManager::create_unique_username($data['firstname'], $data['lastname']);       
                    error_log("homonym username created ". $data['username']);
                }
            }
            
            if (empty($data['username'])) {
                //Last chance to have a nice username   
                if (empty($data['firstname']) && empty($data['lastname'])) {
                    $data['username'] = UserManager::create_unique_username(uniqid());
                    error_log("username empty 1". $data['username']);
                } else {
                    $data['username'] = UserManager::create_unique_username($data['firstname'], $data['lastname']);
                    error_log("username empty 2". $data['username']);
                }
            }
        } else {
             if (UserManager::is_username_available($data['username'])) {
                //error_log("username available {$data['username']} ");
            } else {
                //the user already exists?
                $user_info = UserManager::get_user_info_simple($data['username']);
                $user_persona = UserManager::get_extra_user_data_by_field($user_info['user_id'], 'uidIdPersona');
               
                
                if (isset($user_persona['uidIdPersona']) && (string)$data['uidIdPersona'] == (string)$user_persona['uidIdPersona']) {
                    //error_log("2 Skip user already added: {$user_info['username']}");                    
                    return $user_info;
                } else {
                    //print_r($user_persona);
                    //error_log("2 homonym - wanted_username: {$data['username']} - uidIdPersona: {$user_persona['uidIdPersona']} - username: {$user_info['username']}");       
                    //print_r($data);
                     //The user has the same firstname and lastname but it has another uiIdPersona could by an homonym  
                    $data['username'] = UserManager::create_unique_username($data['firstname'], $data['lastname']);       
                    //error_log("2 homonym username created ". $data['username']);                    
                }
            }
        }
                
        if (empty($data['username'])) {
            error_log('No Username provided');
            error_log(print_r($data, 1));
            exit;
        }
        
        unset($data['uidIdPersona']);
        unset($data['uidIdAlumno']);
        unset($data['uidIdEmpleado']);
        
        global $api_failureList;
        $api_failureList = array();
        //error_log(print_r($data, 1));
        $user_info = UserManager::add($data);
        if (!$user_info) {
            echo 'error';
        }
        return $user_info;
    }
    
    public function create_course($data) {
        //Fixes wrong wanted codes
        $data['wanted_code'] = str_replace(array('-', '_'), '000', $data['wanted_code']);  
        
        //Creates an evaluation
        $data['create_gradebook_evaluation'] = true;
        $data['gradebook_params'] = array(
            'name' => 'Evaluación general',
            'user_id' => $this->default_admin_id,
            'weight' => '20',
            'max' => '20'
        );
        return CourseManager::create_course($data);
    }
    public function create_session($data) {
        $session_id = SessionManager::add($data);
        if (!$session_id) {
            print_r($data);
            exit;
        }
        return $session_id;
    }
    
    public function add_user_to_session($data) {
        //error_log('add_user_to_session');
        ///print_r($data);
       
        
        //Search  uidIdPrograma        
        //Search  uidIdAlumno
        
        $extra_field_value = new ExtraFieldValue('session');
        $result = $extra_field_value->get_item_id_from_field_variable_and_field_value('uidIdPrograma', $data['uidIdPrograma']);
        
        $session_id = null;
        $user_id = null;
        
        if ($result && $result['session_id']) {
            $session_id = $result['session_id'];   
         
        }
        
        $extra_field_value = new ExtraFieldValue('user');
        $result = $extra_field_value->get_item_id_from_field_variable_and_field_value('uidIdPersona', $data['uidIdPersona']);
        
        if ($result && $result['user_id']) {               
            $user_id = $result['user_id'];                   
        }
        
        if (!empty($session_id) && !empty($user_id)){          
            error_log('Called: add_user_to_session - Subscribing: session_id: '.$session_id. '  user_id: '.$user_id);
            SessionManager::suscribe_users_to_session($session_id, array($user_id));       
        } else {            
            //error_log('Called: add_user_to_session - No idPrograma: '.$data['uidIdPrograma'].' - No uidIdPersona: '.$data['uidIdPersona']);            
        }     
    }
}
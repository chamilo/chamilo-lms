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
    
    const default_admin_id = 1;
    const TRANSACTION_STATUS_TO_BE_EXECUTED = 1;
    const TRANSACTION_STATUS_SUCCESSFUL = 2;
    const TRANSACTION_STATUS_DEPRECATED = 3; //??
    const TRANSACTION_STATUS_FAILED = 4;   

    /**
     * The only required method is the 'none' method, which will not trigger
     * any process at all
     * @param mixed Data
     * @param mixed Unaltered data
     */
    static function none($data) {
        return $data;
    }
    
    static function join_horario($data, &$omigrate, $row_data) {
        return '('.$row_data['chrIdHorario'].') '.$row_data['chrHoraInicial'].' '.$row_data['chrHoraFinal'];
    }
    
    /**
     * Converts 2008-02-01 12:20:20.540 to  2008-02-01 12:20:20 
     */
    static function clean_date_time($date) {
        return substr($date, 0, 19);
    }

    /**
     * Transform the uid identifiers from MSSQL to a string
     * @param string Field name
     * @return string SQL select string to include in the final select
     */
    static function sql_alter_unhash_50($field) {
        $as_field = explode('.', $field);     
        if (isset($as_field[1])) {
            $as_field = $as_field[1];
        } else {
            $as_field = $field;
        }
        return " cast( $field  as varchar(50)) as $as_field ";
    }
    
    static function clean_utf8($value) {
        return utf8_encode($value);        
    }
    
    static function clean_session_name($value, &$omigrate, $row_data) {
        return self::clean_utf8($row_data['session_name']);        
    }
    
    static function get_real_course_code($data) {        
        $extra_field = new ExtraFieldValue('course');
        $values = $extra_field->get_item_id_from_field_variable_and_field_value('uidIdCurso', $data);        
        if ($values) {
            return $values['course_code'];
        } else {
            error_log("Course ".print_r($data,1)." not found in DB");
        }
    }
    
    static function get_session_id_by_programa_id($uidIdProgram) {        
        $extra_field = new ExtraFieldValue('session');
        $values = $extra_field->get_item_id_from_field_variable_and_field_value('uidIdPrograma', $uidIdProgram);        
        if ($values) {
            return $values['session_id'];
        } else {
            //error_log("session id not found in DB");
        }      
    }
    
    /* Not used */
    static function get_user_id_by_persona_id($uidIdPersona) {
        //error_log('get_user_id_by_persona_id');
        $extra_field = new ExtraFieldValue('user');
        $values = $extra_field->get_item_id_from_field_variable_and_field_value('uidIdPersona', $uidIdPersona);        
        if ($values) {
            return $values['user_id'];
        } else {
            return 0;
        }
    }
    
    static function get_real_teacher_id($uidIdPersona) {
        $default_teacher_id = self::default_admin_id;       
        if (empty($uidIdPersona)) {
            //error_log('No teacher provided');
            return $default_teacher_id;
        }
        
        $extra_field = new ExtraFieldValue('user');
        $values = $extra_field->get_item_id_from_field_variable_and_field_value('uidIdPersona', $uidIdPersona);
        
        if ($values) {
            return $values['user_id'];
        } else {
            return $default_teacher_id; 
        }
        
        /*
        if (!isset($omigrate['users_empleado'][$data])) {
            //error_log(' Teacher not found big problem! ');    
            //echo $data;
            //print_r($omigrate['users_empleado'][$data]);
            //echo $data;exit;
            return $default_teacher_id;            
        } else {
            //error_log('Teacher found: '.$omigrate['users_empleado'][$data]['extra']['user_id']);
            return isset($omigrate['users_empleado'][$data]['extra']) ? $omigrate['users_empleado'][$data]['extra']['user_id'] : $default_teacher_id;        
        } */       
    }
    
    /**
     * Manage the user creation, including checking if the user hasn't been 
     * created previously
     * @param array User data
     * @param object List of migrated things
     * @return array User info (from Chamilo DB)
     */
    static function create_user($data, $omigrate) {
        //error_log('In create_user, receiving '.print_r($data,1));
        if (empty($data['uidIdPersona'])) {
            error_log('User does not have a uidIdPersona');
            error_log(print_r($data, 1));    
            exit;
        }
            
        //Is a teacher
        /*if (isset($omigrate['users_empleado'][$data['uidIdEmpleado']])) {            
            $data['status'] = COURSEMANAGER;                
        } else {     
            $data['status'] = STUDENT;    
        }*/
        
        $data['status'] = STUDENT;
        if (isset($data['uidIdEmpleado'])) {
            $data['status'] = COURSEMANAGER;
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
                    error_log("Homonym - wanted_username: $wanted_user_name - uidIdPersona: {$user_persona['uidIdPersona']} - username: {$user_info['username']}");       
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
        $id_persona = $data['uidIdPersona']; 
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
        UserManager::update_extra_field_value($user_info['user_id'], 'uidIdPersona', $id_persona);
        return $user_info;
    }
    
    /**
     * Manages the course creation based on the rules in db_matches.php
     */
    static function create_course($data) {
        //error_log('In create_course, received '.print_r($data,1));
        //Fixes wrong wanted codes
        $data['wanted_code'] = str_replace(array('-', '_'), '000', $data['wanted_code']);
        
        //Specific to ICPNA, set the default language to English
        $data['language'] = 'english';
        $data['visibility'] = COURSE_VISIBILITY_REGISTERED;
        
        //Creates an evaluation
        $data['create_gradebook_evaluation'] = false;
        /*
        $data['gradebook_params'] = array(
            'name'      => 'General evaluation',
            'user_id'   => self::default_admin_id,
            'weight'    => '20',
            'max'       => '20'
        );*/
        $course_data = CourseManager::create_course($data);        
        return $course_data;
    }
    
    /**
     * Manages the session creation, based on data provided by the rules
     * in db_matches.php
     */
    static function create_session($data) {
        //Hack to add the default gradebook course to the session course
        $data['create_gradebook_evaluation'] = true;        
        /*$data['gradebook_params'] = array(
            'name'      => 'General evaluation',
            'user_id'   => self::default_admin_id,
            'weight'    => '20',
            'max'       => '20'
        );*/
        
        //Here the $data variable has $data['course_code'] that will be added when creating the session
        $session_id = SessionManager::add($data);
        //error_log('create_session');        
        if (!$session_id) {
            //error_log($session_id);
            error_log('failed create_session');
            //print_r($data);
            //exit;
        } else{
            //error_log('session_id created');            
        }
        return $session_id;
    }
    
    /**
     * Assigns a user to a session based on rules in db_matches.php
     */
    static function add_user_to_session($data) {
        $extra_field_value = new ExtraFieldValue('session');
        $result = $extra_field_value->get_item_id_from_field_variable_and_field_value('uidIdPrograma', $data['uidIdPrograma']);
        //error_log('data[uidIdPrograma] '.$data['uidIdPrograma'].' returned $result[session_id]: '.$result['session_id']);
        $session_id = null;
        $user_id = null;
        
        if ($result && $result['session_id']) {
            $session_id = $result['session_id'];
        }
        
        $extra_field_value = new ExtraFieldValue('user');
        $result = $extra_field_value->get_item_id_from_field_variable_and_field_value('uidIdPersona', $data['uidIdPersona']);
        //error_log('data[uidIdPersona] '.$data['uidIdPersona'].' returned $result[user_id]: '.$result['user_id']);
        if ($result && $result['user_id']) {               
            $user_id = $result['user_id'];                   
        }
        
        if (!empty($session_id) && !empty($user_id)) {          
            //error_log('Called: add_user_to_session - Subscribing: session_id: '.$session_id. '  user_id: '.$user_id);
            SessionManager::suscribe_users_to_session($session_id, array($user_id), SESSION_VISIBLE_READ_ONLY, false, false);       
            //exit;
        } else {            
            //error_log('Called: add_user_to_session - No idPrograma: '.$data['uidIdPrograma'].' - No uidIdPersona: '.$data['uidIdPersona']);            
        }     
    }
    
    static function create_attendance($data) {        
        error_log('create_attendance');
        $session_id = $data['session_id'];
        $user_id    = $data['user_id'];
        
        if (!empty($session_id) && !empty($user_id)) {
            $attendance = new Attendance();            
            $course_list = SessionManager::get_course_list_by_session_id($session_id);
            $attendance_id = null;
          
            if (!empty($course_list)) {
                $course = current($course_list);
             
                //Creating attendance
                if (isset($course['code'])) {
                    $course_info = api_get_course_info($course['code']);
                    
                    $attendance->set_course_id($course_info['code']);
                    $attendance->set_course_int_id($course_info['real_id']);
                    $attendance->set_session_id($session_id);

                    $attendance_list = $attendance->get_attendances_list($course_info['real_id'], $session_id);                            
                    if (empty($attendance_list)) {
                        $attendance->set_name('Asistencia');
                        $attendance->set_description('');
                        //$attendance->set_attendance_qualify_title($_POST['attendance_qualify_title']);
                        //$attendance->set_attendance_weight($_POST['attendance_weight']);
                        $link_to_gradebook = false;              			    			
                        //$attendance->category_id = $_POST['category_id'];
                        $attendance_id = $attendance->attendance_add($link_to_gradebook, self::default_admin_id);                        
                        error_log("Attendance added course code: {$course['code']} - session_id: $session_id");
                        //only 1 course per session                
                    } else {
                        $attendance_data = current($attendance_list);                        
                        $attendance_id = $attendance_data['id'];
                        error_log("Attendance found in attendance_id = $attendance_id - course code: {$course['code']} - session_id: $session_id");
                    }
               
                    if ($attendance_id) {
                        //Attendance date exists?
                        $cal_info = $attendance->get_attendance_calendar_data_by_date($attendance_id, $data['fecha']);                             
                        if ($cal_info && isset($cal_info['id'])) {
                            $cal_id = $cal_info['id'];
                        } else {
                            //Creating the attendance date
                            $attendance->set_date_time($data['fecha']);
                            $cal_id = $attendance->attendance_calendar_add($attendance_id, true);
                            error_log("Creating attendance calendar $cal_id");
                        }
                        //Adding presence for the user (by default everybody is present)
                        $users_present = array($user_id);
                        $attendance->attendance_sheet_add($cal_id, $users_present, $attendance_id, false, false);
                        error_log("Adding calendar to user: $user_id to calendar: $cal_id");             
                    } else {
                        error_log('No attendance_id created');
                    }
                } else {
                    error_log("Course not found for session: $session_id");
                }
            }
        } else {
            error_log("Missing data: session: $session_id - user_id: $user_id");
        }
    }
    
    static function create_thematic($data) {
        error_log('create_thematic');
        $session_id = $data['session_id'];
                
        if (!empty($session_id)) {
            $course_list = SessionManager::get_course_list_by_session_id($session_id);
            
            if (!empty($course_list)) {
                $course_data = current($course_list);
                $course_info = api_get_course_info($course_data['code']);
                
                if (!empty($course_data)) {
                    $thematic = new Thematic();
                    $thematic->set_course_int_id($course_info['real_id']);
                    $thematic->set_session_id($session_id);                   
                    $thematic_info = $thematic->get_thematic_by_title($data['thematic']);
                    
                    if (empty($thematic_info)) {
                        $thematic->set_thematic_attributes(null, $data['thematic'], null, $session_id);
                        $thematic_id = $thematic->thematic_save();
                        error_log("Thematic added to course code: {$course_info['code']} - session_id: $session_id");
                    } else {
                        $thematic_id = isset($thematic_info['id']) ? $thematic_info['id'] : null;                        
                        error_log("Thematic id #$thematic_id found in course: {$course_info['code']} - session_id: $session_id");
                    }
                    
                    if ($thematic_id) {                    
                        $thematic->set_thematic_plan_attributes($thematic_id, $data['thematic_plan'], null, 6);
                        $thematic->thematic_plan_save();
                        error_log("Saving plan attributes: {$data['thematic_plan']}");
                    }
                    error_log("Adding thematic id : $thematic_id to session: $session_id to course: {$course_info['code']} real_id: {$course_info['real_id']}");
                        
                    if ($thematic_id) {
                        error_log("Thematic saved: $thematic_id");
                    } else {
                        error_log("Thematic NOT saved");
                    }
                }
                
                if ($course_info['code'] != 'B05') {
                    //exit;
                }
            } else {
                error_log("No courses in session $session_id ");                   
            }
        }        
    }
    
    static function add_evaluation_type($params) {
        $table = Database::get_main_table(TABLE_MAIN_GRADEBOOK_EVALUATION_TYPE);
        if (!empty($params['name']) && !empty($params['external_id'])) {
            if (isset($params['return_item_if_already_exists'])) {
                unset($params['return_item_if_already_exists']);
            }
            Database::insert($table, $params);
        }        
    }
    
    static function get_evaluation_type($external_id) {
        $table = Database::get_main_table(TABLE_MAIN_GRADEBOOK_EVALUATION_TYPE);
        $external_id = intval($external_id);
        $sql = "SELECT * FROM $table WHERE external_id = $external_id";
        $result = Database::query($sql);        
        if (Database::num_rows($result)) {
            $result = Database::fetch_array($result, 'ASSOC');
            return $result['id'];
        }
        return false;        
    }
    
    static function create_gradebook_evaluation($data){
        error_log('create_gradebook_evaluation');
        $session_id = isset($data['session_id']) ? $data['session_id'] : null;
        
        if (!empty($session_id)) {
            $course_list = SessionManager::get_course_list_by_session_id($session_id);
            if (!empty($course_list)) {
                $course_data = current($course_list);
                if (isset($course_data['code'])) {
                    //Get gradebook
                    $gradebook = new Gradebook();
                    $gradebook = $gradebook->get_first(array('where' => array('course_code = ? AND session_id = ?' => array($course_data['code'], $session_id))));
                    error_log("Looking gradebook in course code:  {$course_data['code']} - session_id: $session_id");                    
                    if (!empty($gradebook)) {               
                        //error_log("Gradebook exists {$gradebook['id']}");
                        //Check if gradebook exists
                        $eval = new Evaluation();                        
                        $evals_found = $eval->load(null, null, $course_data['code'], null, null, null, $data['gradebook_description']);
                                                
                        if (empty($evals_found)) {                  
                            $eval->set_name($data['gradebook_description']);
                            $eval->set_description($data['gradebook_description']);
                            $eval->set_evaluation_type_id($data['gradebook_evaluation_type_id']);
                            $eval->set_user_id(self::default_admin_id);
                            $eval->set_course_code($course_data['code']);                        
                            $eval->set_category_id($gradebook['id']);

                            //harcoded values
                            $eval->set_weight(20);	
                            $eval->set_max(20);
                            $eval->set_visible(1);
                            $eval->add();
                            error_log("Gradebook evaluation was created!!");
                        } else {
                            error_log("Gradebook evaluation was already created :( ");
                        }                 
                    } else {
                        error_log("Gradebook does not exists");
                    }                    
                } else {
                    error_log("Something is wrong with the course ");
                }                
            } else {
                error_log("NO course found for session id: $session_id");    
            }
            
        } else {
            error_log("NO session id found: $session_id");
        }     
    }
    
    static function add_gradebook_result($data) {
        error_log('add_gradebook_result');
        $session_id = isset($data['session_id']) ? $data['session_id'] : null;
        $user_id = isset($data['user_id']) ? $data['user_id'] : null;        
        
        if (!empty($session_id) && !empty($user_id)) {
            $course_list = SessionManager::get_course_list_by_session_id($session_id);
            if (!empty($course_list)) {
                $course_data = current($course_list);
                if (isset($course_data['code'])) {
                    //Get gradebook
                    $gradebook = new Gradebook();
                    $gradebook = $gradebook->get_first(array('where' => array('course_code = ? AND session_id = ?' => array($course_data['code'], $session_id))));
                    error_log("Looking gradebook in course code:  {$course_data['code']} - session_id: $session_id, user_id: $user_id");                    
                    if (!empty($gradebook)) {                  
                        error_log("Gradebook exists: {$gradebook['id']}");
                        
                        //Check if gradebook exists
                        $eval = new Evaluation();                        
                        $evals_found = $eval->load(null, null, null, $gradebook['id'], null, null);
                        
                        if (!empty($evals_found)) {            
                            $evaluation = current($evals_found);
                            $eval_id = $evaluation->get_id();
                            
                            //Eval found                            
                            $res = new Result();                            
                            $check_result = Result :: load (null, $user_id, $eval_id);
                            if (empty($check_result)) {
                                $res->set_evaluation_id($eval_id);
                                $res->set_user_id($user_id);
                                //if no scores are given, don't set the score
                                $res->set_score($data['nota']);
                                $res->add();
                                error_log("Result saved :)");
                            } else {
                                error_log("Result already added ");
                            }
                        } else {
                            error_log("Evaluation not found ");
                        }
                    } else {
                        error_log("Gradebook does not exists");
                    }                    
                } else {
                    error_log("Something is wrong with the course ");
                }                
            } else {
                error_log("NO course found for session id: $session_id");    
            }
            
        } else {
            error_log("NO session id found: $session_id");
        }
    }
    
    static function add_gradebook_result_with_evaluation($data) {
        error_log('add_gradebook_result_with_evaluation');
        $session_id = isset($data['session_id']) ? $data['session_id'] : null;
        $user_id = isset($data['user_id']) ? $data['user_id'] : null;
        
        //Default evaluation title
        $title = 'Evaluación General';
        
        if (!empty($session_id) && !empty($user_id)) {
            $course_list = SessionManager::get_course_list_by_session_id($session_id);
            if (!empty($course_list)) {
                $course_data = current($course_list);
                if (isset($course_data['code'])) {
                    //Get gradebook
                    $gradebook = new Gradebook();
                    $gradebook = $gradebook->get_first(array('where' => array('course_code = ? AND session_id = ?' => array($course_data['code'], $session_id))));
                    error_log("Looking gradebook in course code:  {$course_data['code']} - session_id: $session_id, user_id: $user_id");                    
                    if (!empty($gradebook)) {                  
                        error_log("Gradebook exists: {$gradebook['id']}");
                        
                        //Creates                        
                        $eval = new Evaluation();
                        $evals_found = $eval->load(null, null, $course_data['code'], $gradebook['id'], null, null, $title);
                                                
                        if (empty($evals_found)) {
                            $eval->set_name($title);                            
                            //$eval->set_evaluation_type_id($data['gradebook_evaluation_type_id']);
                            $eval->set_user_id(self::default_admin_id);
                            $eval->set_course_code($course_data['code']);                        
                            $eval->set_category_id($gradebook['id']);

                            //harcoded values
                            $eval->set_weight(20);	
                            $eval->set_max(20);
                            $eval->set_visible(1);
                            $eval->add();
                            error_log("Gradebook evaluation was created!!");
                            $eval_id = $eval->get_id();
                            error_log("eval id created: $eval_id");     
                        } else {
                            $eval = current($evals_found);
                            error_log("Gradebook evaluation already exists ");
                            $eval_id = $eval->get_id();
                            error_log("eval id loaded : $eval_id");     
                        }
                                                
                        if ($eval_id) {
                            $res = new Result();
                            //Check if already exists                            
                            $check_result = Result :: load (null, $user_id, $eval_id);
                            if (empty($check_result)) {
                                $res->set_evaluation_id($eval_id);
                                $res->set_user_id($user_id);
                                //if no scores are given, don't set the score
                                $res->set_score($data['nota']);
                                $res->add();
                                error_log("Result Added :)");
                            } else {
                                error_log("Result already added ");
                            }                            
                        } else {
                            error_log("Evaluation not detected");
                        }                                  
                    } else {
                        error_log("Gradebook does not exists");
                    }                    
                } else {
                    error_log("Something is wrong with the course ");
                }                
            } else {
                error_log("NO course found for session id: $session_id");    
            }            
        } else {
            error_log("NO session id found: $session_id");
        }
    }
    
    
    
    
    /* Transaction methods */
    
    
    
    
    //añadir usuario: usuario_agregar UID
    static function transaction_usuario_agregar($data, $web_service_details) {
         $uidIdPersonaId = $data['item_id'];            
         //Add user call the webservice         
         $user_info = $web_service_details['class']::usuarioDetalles($uidIdPersonaId);
         if ($user_info['error'] == false) {
            $user_id = UserManager::add($user_info);
            if ($user_id) {
                return array(
                    'message' => "User was created : $user_id",
                    'status_id' => self::TRANSACTION_STATUS_SUCCESSFUL
                );
            } else  {
                return array(
                    'message' => "User was not created : $uidIdPersonaId",
                    'status_id' => self::TRANSACTION_STATUS_FAILED
                );
            }
         } else {
            return $user_info;
         } 
    }
    
    //eliminar usuario usuario_eliminar UID
    static function transaction_usuario_eliminar($data) {
        $uidIdPersonaId = $data['item_id'];        
        $user_id = self::get_user_id_by_persona_id($uidIdPersonaId);
        if ($user_id) {
            UserManager::delete_user($user_id);
            return array(
                'message' => "User was deleted : $user_id",
                'status_id' => self::TRANSACTION_STATUS_SUCCESSFUL
            );
        } else {
            return array(
                'message' => "User was not found : $uidIdPersonaId",
                'status_id' => self::TRANSACTION_STATUS_FAILED
            );
        }
    }
    
    //editar detalles de usuario (nombre/correo/contraseña) usuario_editar UID
    static function transaction_usuario_editar($data, $web_service_details) {
        $uidIdPersonaId = $data['item_id'];
        $user_id = self::get_user_id_by_persona_id($uidIdPersonaId);
        if ($user_id) {            
            $user_info = $web_service_details['class']::usuarioDetalles($uidIdPersonaId);
            if ($user_info['error'] == false) {     
                //Edit user
                $user_info['user_id'] = $user_id;
                UserManager::update($user_info);
                return array(
                    'message' => "User was updated : $user_id",
                    'status_id' => self::TRANSACTION_STATUS_SUCCESSFUL
                );
            } else {
                return $user_info;
            }
        } else {
            return array(
                'message' => "User was not found : $uidIdPersonaId",
                'status_id' => self::TRANSACTION_STATUS_FAILED
            );
        }
    }
    
    //cambiar usuario de progr. académ. (de A a B, de A a nada, de nada a A) (como estudiante o profesor) usuario_matricula UID ORIG DEST
    static function transaction_usuario_matricula($data) {
        $uidIdPersona = $data['item_id'];
        $uidIdPrograma = $data['orig_id'];
        $uidIdProgramaDestination = $data['dest_id'];
        $user_id = self::get_user_id_by_persona_id($uidIdPersona);
        if (empty($user_id)) {
            return array(
                'message' => "User does not exists: $uidIdPersona",
                'status_id' => self::TRANSACTION_STATUS_FAILED
            );
        }
        
        //Move A to B
        if (!empty($uidIdPrograma) && !empty($uidIdProgramaDestination)) {
            $session_id = self::get_session_id_by_programa_id($uidIdPrograma);
            $destination_session_id = self::get_session_id_by_programa_id($uidIdProgramaDestination);
            
            if (!empty($session_id) && !empty($destination_session_id)) {
                SessionManager::unsubscribe_user_from_session($session_id, $user_id);
                SessionManager::suscribe_users_to_session($destination_session_id, array($user_id), SESSION_VISIBLE_READ_ONLY, false, false);
                
                return array(
                    'message' => "User $user_id was added to Session $destination_session_id & was removed from  $session_id - Move Session A to Session B",
                    'status_id' => self::TRANSACTION_STATUS_SUCCESSFUL
                );                
            } else {
                return array(
                    'message' => "Session does not exists $uidIdProgramaDestination - Move Session A to Session B",
                    'status_id' => self::TRANSACTION_STATUS_FAILED
                );
            }
        }
        
        //Move A to empty
        if (!empty($uidIdPrograma) && empty($uidIdProgramaDestination)) {            
            $session_id = self::get_session_id_by_programa_id($uidIdPrograma);
            if (!empty($session_id)) {
                SessionManager::suscribe_users_to_session($session_id, array($user_id), SESSION_VISIBLE_READ_ONLY, false, false);
                return array(
                    'message' => "User $user_id added to Session $session_id  - Move Session to empty",
                    'status_id' => self::TRANSACTION_STATUS_SUCCESSFUL
                );
            } else {
                return array(
                    'message' => "Session does not exists $uidIdPrograma  - Move Session to empty",
                    'status_id' => self::TRANSACTION_STATUS_FAILED
                );
            }
        }
        
         //Move empty to A
        if (empty($uidIdPrograma) && !empty($uidIdProgramaDestination)) {
            $session_id = self::get_session_id_by_programa_id($uidIdProgramaDestination);
            if (!empty($session_id)) {                
                SessionManager::suscribe_users_to_session($session_id, array($user_id), SESSION_VISIBLE_READ_ONLY, false, false);
            } else {
                return array(
                    'message' => "Session does not exists $uidIdProgramaDestination - Move empty to Session",
                    'status_id' => self::TRANSACTION_STATUS_FAILED
                );
            }
        }
    }
    
    //Cursos
    //añadir curso curso_agregar CID
    static function transaction_curso_agregar($data, $web_service_details) {
        $uidCursoId = $data['item_id'];        
        $course_info = $web_service_details['class']::cursoDetalles($uidCursoId);
        if ($course_info['error'] == false) { 
            $course_code = CourseManager::create_course($course_info);
            if (!empty($course_code)) {
                return array(
                        'message' => "Course was created $course_code ",
                        'status_id' => self::TRANSACTION_STATUS_SUCCESSFUL
                );
            } else {
                return array(
                        'message' => "Course does not exists $course_code ",
                        'status_id' => self::TRANSACTION_STATUS_FAILED
                );
            }
        } else {
            return $course_info;
        }
    }
    
    //eliminar curso curso_eliminar CID
    static function transaction_curso_eliminar($data) {
        $course_code = self::get_real_course_code($data['item_id']);
        if (!empty($course_code)) {
            CourseManager::delete_course($course_code);
            return array(
                    'message' => "Course was deleted $course_code ",
                    'status_id' => self::TRANSACTION_STATUS_SUCCESSFUL
            );
        } else {
            return array(
                    'message' => "Coursecode does not exists $course_code ",
                    'status_id' => self::TRANSACTION_STATUS_FAILED
            );
        }
        
    }
    
    //editar detalles de curso curso_editar CID
    static function transaction_curso_editar($data, $web_service_details) {
        $course_code = self::get_real_course_code($data['item_id']);        
        if (!empty($course_code)) {        
            $course_info = $web_service_details['class']::cursoDetalles($data['item_id']);
            if ($course_info['error'] == false) {
                //do some cleaning
                CourseManager::update_attributes($course_info['real_id'], $course_info);
                return array(
                        'message' => "Course was updated $course_code ",
                        'status_id' => self::TRANSACTION_STATUS_SUCCESSFUL
                );
            } else {
                return $course_info;
            }            
         } else {
            return array(
                    'message' => "couCoursese_code does not exists $course_code ",
                    'status_id' => self::TRANSACTION_STATUS_FAILED
            );
        }
    }
    
    //cambiar curso de progr. académ. (de nada a A) curso_matricula CID ORIG DEST
    static function transaction_curso_matricula($data) {
        $course_code = self::get_real_course_code($data['item_id']);
        $uidIdPrograma = $data['orig_id'];
        $uidIdProgramaDestination = $data['dest_id'];
        
        $session_id = self::get_session_id_by_programa_id($uidIdPrograma);
        $destination_session_id = self::get_session_id_by_programa_id($uidIdProgramaDestination);
        
        //@todo ???
        if (!empty($course_code)) {
            SessionManager::add_courses_to_session($destination_session_id, array($course_code));
            return array(
                   'message' => "Session updated $uidIdPrograma",
                   'status_id' => self::TRANSACTION_STATUS_SUCCESSFUL
            );
        } else {
            return array(
                   'message' => "Course does not exists $course_code",
                   'status_id' => self::TRANSACTION_STATUS_FAILED
            );
        }
    }
    
    //Programas académicos
    //añadir p.a. pa_agregar PID
    static function transaction_pa_agregar($data, $web_service_details) {        
        $session_info = $web_service_details['class']::programaDetalles($data['item_id']);      
        if ($session_info['error'] == false) {
            SessionManager::add($session_info);
        } else {
            return $session_info;
        }
    }
    
    //editar detalles de p.a. pa_editar PID
    static function transaction_pa_editar($data, $web_service_details) {        
        $uidIdPrograma = $data['item_id'];        
        $session_id = self::get_session_id_by_programa_id($uidIdPrograma);
        if (!empty($session_id)) {
            $session_info = $web_service_details['class']::programaDetalles($data['item_id']);
            if ($session_info['error'] == false) {
                SessionManager::update($session_info);
                return array(
                   'message' => "Session updated $uidIdPrograma",
                   'status_id' => self::TRANSACTION_STATUS_SUCCESSFUL
                );
            } else {
                return $session_info;
            }            
        } else {
            return array(
                   'message' => "Session does not exists $uidIdPrograma",
                   'status_id' => self::TRANSACTION_STATUS_FAILED
            );
        }            
    }   
    
    //eliminar p.a. pa_eliminar PID
    static function transaction_pa_eliminar($data) {
        $uidIdPrograma = $data['item_id'];        
        $session_id = self::get_session_id_by_programa_id($uidIdPrograma);    
        if (!empty($session_id)) {
            SessionManager::delete($session_id);
            return array(
                   'message' => "Session does not exists $uidIdPrograma",
                   'status_id' => self::TRANSACTION_STATUS_SUCCESSFUL
            );
        } else {
            return array(
                   'message' => "Session does not exists $uidIdPrograma",
                   'status_id' => self::TRANSACTION_STATUS_FAILED
            );
        }
    }
    
    static function transaction_cambiar_generic($extra_field_variable, $data) {
        $uidIdPrograma = $data['item_id'];
        
        //$orig_id = $data['orig_id'];
        $destination_id = $data['dest_id'];
        
        $session_id = self::get_session_id_by_programa_id($uidIdPrograma);    
        if (!empty($session_id)) {
            //??
            $extra_field = new ExtraField('session');
            $extra_field_info = $extra_field->get_handler_field_info_by_field_variable($extra_field_variable); //horario, aula, etc
            
            //check if option exists
            $extra_field_option = new ExtraFieldOption('session');
            $extra_field_option_info = $extra_field_option->get_field_option_by_field_and_option($extra_field_info['field_id'], $destination_id); //horario, aula, etc
            if ($extra_field_option_info) {           
        
                $extra_field_value = new ExtraFieldValue('session');                
                $params = array(
                    'session_id' => $session_id,
                    'field_id' => $extra_field_info['id'],
                    'field_value' => $destination_id,
                );            
                $extra_field_value->save($params);            
                return array(
                       'message' => "Session does not exists $uidIdPrograma",
                       'status_id' => self::TRANSACTION_STATUS_SUCCESSFUL
                );
            } else {
                return array(
                       'message' => "Option does not exists $destination_id",
                       'status_id' => self::TRANSACTION_STATUS_SUCCESSFUL
                );
            }
        } else {
            return array(
                   'message' => "Change variable $extra_field_variable - Session does not exists $uidIdPrograma",
                   'status_id' => self::TRANSACTION_STATUS_FAILED
            );
        }
        
    }
 
    //cambiar aula pa_cambiar_aula PID ORIG DEST
    static function transaction_pa_cambiar_aula($data) {
        self::transaction_cambiar_generic('aula', $data);
    }
    
    //cambiar horario pa_cambiar_horario PID ORIG DEST
    static function transaction_pa_cambiar_horario($data) {
        self::transaction_cambiar_generic('horario', $data);
    }    
    
    //cambiar sede pa_cambiar_sede PID ORIG DEST    
    static function transaction_pa_cambiar_sede($data) {
        self::transaction_cambiar_generic('sede', $data);
    }
    
    //cambiar intensidad pa_cambiar_fase_intensidad CID ORIG DEST (id de "intensidadFase")
    static function transaction_cambiar_pa_fase_intensidad($data) {
        self::transaction_cambiar_generic('fase', $data);
    }
    
    //-------
 
    static function transaction_extra_field_agregar_generic($extra_field_variable, $data, $web_service_details) {
        $function_name = $extra_field_variable."Detalles";
        $data = $web_service_details['class']::$function_name($data['item_id']);
        if ($data['error'] == false) {           
            $extra_field = new ExtraField('session');
            $extra_field_info = $extra_field->get_handler_field_info_by_field_variable($extra_field_variable);
            $extra_field_option = new ExtraFieldOption('session');

            $params = array(
                'field_id'  => $extra_field_info['id'],
                'option_value' => $data['item_id'],
                'option_display_text' => $data['name'],
                'option_order' => null
            );
            $extra_field_option->save_one_item($params);        
        } else {
            return $data;
        }
    }    
    
    static function transaction_extra_field_editar_generic($extra_field_variable, $data, $web_service_details) {
        $extra_field = new ExtraField('session');
        $extra_field_info = $extra_field->get_handler_field_info_by_field_variable($extra_field_variable);
        
        $extra_field_option = new ExtraFieldOption('session');        
        $extra_field_option_info = $extra_field_option->get_field_option_by_field_and_option($extra_field_info['field_id'], $data['item_id']);
        
        $function_name = $extra_field_variable."Detalles";
        $data = $web_service_details['class']::$function_name($data['item_id']);
        if ($data['error'] == false) {
            //update array
            $extra_field_option_info = array(
                'id' => $extra_field_option_info['id'],
                'field_id' => $extra_field_info['field_id'],
                'option_value' => $data['item_id'],
                'option_display_text' => $data['name'],
                'option_order' => null
            );        
            $extra_field_option->update($extra_field_option_info);
        } else {
            return $data;
        }        
    }
    
    static function transaction_extra_field_eliminar_generic($extra_field_variable, $data, $web_service_details) { //horario
        $extra_field = new ExtraField('session');
        $extra_field_info = $extra_field->get_handler_field_info_by_field_variable($extra_field_variable);
        
        $extra_field_option = new ExtraFieldOption('session');        
        $extra_field_option_info = $extra_field_option->get_field_option_by_field_and_option($extra_field_info['field_id'], $data['item_id']);
        //@todo Delete all horario in sessions?
        $extra_field_option->delete($extra_field_option_info['id']);        
    }

    
    //        Horario
    //            añadir horario_agregar HID    
    static function transaction_horario_agregar($data, $web_service_details) {
        self::transaction_extra_field_agregar_generic('horario', $data, $web_service_details);       
    }
    
    //            eliminar horario_eliminar HID
    static function transaction_horario_eliminar($data, $web_service_details) {
        self::transaction_extra_field_eliminar_generic('horario', $data, $web_service_details);
    }
    
    //            editar horario_editar HID
    static function transaction_horario_editar($data, $web_service_details) {
        self::transaction_extra_field_editar_generic('horario', $data, $web_service_details);
    }
    
    // Aula
    //            añadir aula_agregar AID
    static function transaction_aula_agregar($data, $web_service_details) {
        self::transaction_extra_field_agregar_generic('aula', $data, $web_service_details);
    }
    
    //            eliminar aula_eliminar AID
    static function transaction_aula_eliminar($data, $web_service_details) {
        self::transaction_extra_field_eliminar_generic('aula', $data, $web_service_details);
    }
    //            editar aula_editor AID
    static function transaction_aula_editar($data, $web_service_details) {
        self::transaction_extra_field_editar_generic('aula', $data, $web_service_details);
    }
    //        Sede
    //            añadir aula_agregar SID
    static function transaction_sede_agregar($data, $web_service_details) {
        self::transaction_extra_field_agregar_generic('sede', $data, $web_service_details);
    }
    //            eliminar aula_eliminar SID
    static function transaction_sede_eliminar($data, $web_service_details) {
        self::transaction_extra_field_eliminar_generic('sede', $data, $web_service_details);
    }
    //            editar aula_editar SID
    static function transaction_sede_editar($data, $web_service_details) {
        self::transaction_extra_field_editar_generic('sede', $data, $web_service_details);
    }
    
    //
    //        Frecuencia
    //            añadir frec FID
    static function transaction_frecuencia_agregar($data, $web_service_details) {
        self::transaction_extra_field_agregar_generic('frecuencia', $data, $web_service_details);
    }
    
    //            eliminar Freca_eliminar FID
    static function transaction_frecuencia_eliminar($data, $web_service_details) {
        self::transaction_extra_field_eliminar_generic('frecuencia', $data, $web_service_details);
    }
    
    //             editar aula_editar FID
    static function transaction_frecuencia_editar($data, $web_service_details) {
        self::transaction_extra_field_editar_generic('frecuencia', $data, $web_service_details);
    }
    
    //
    //        Intensidad/Fase
    //            añadir intfase_agregar IID
    static function transaction_intfase_agregar($data, $web_service_details) {
        self::transaction_extra_field_agregar_generic('intensidad', $data, $web_service_details);
    }
    
    //            eliminar intfase_eliminar IID
    static function transaction_intfase_eliminar($data, $web_service_details) {
        self::transaction_extra_field_eliminar_generic('intensidad', $data, $web_service_details);
    }
    //            editar intfase_editar IID
    static function transaction_intfase_editar($data, $web_service_details) {
        self::transaction_extra_field_editar_generic('intensidad', $data, $web_service_details);
    }
    
        //
    //        Intensidad/Fase
    //            añadir intfase_agregar IID
    static function transaction_meses_agregar($data, $web_service_details) {
        self::transaction_extra_field_agregar_generic('meses', $data, $web_service_details);
    }
    
    //            eliminar intfase_eliminar IID
    static function transaction_meses_eliminar($data, $web_service_details) {
        self::transaction_extra_field_eliminar_generic('meses', $data, $web_service_details);
    }
    //            editar intfase_editar IID
    static function transaction_meses_editar($data, $web_service_details) {
        self::transaction_extra_field_editar_generic('meses', $data, $web_service_details);
    }
}
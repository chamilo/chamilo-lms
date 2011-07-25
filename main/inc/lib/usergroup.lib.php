<?php
/* For licensing terms, see /license.txt */
/**
 * This class provides methods for the UserGroup management.
 * Include/require it in your code to use its features.
 * @package chamilo.library
 */
/**
 * Code
 */
require_once 'model.lib.php';
require_once api_get_path(LIBRARY_PATH).'sessionmanager.lib.php';
/**
 * Class
 * @package chamilo.library
 */
class UserGroup extends Model {

    var $columns = array('id', 'name','description');
    
	public function __construct() {
        $this->table                        =  Database::get_main_table(TABLE_USERGROUP);
        $this->usergroup_rel_user_table     =  Database::get_main_table(TABLE_USERGROUP_REL_USER);
        $this->usergroup_rel_course_table   =  Database::get_main_table(TABLE_USERGROUP_REL_COURSE);
        $this->usergroup_rel_session_table  =  Database::get_main_table(TABLE_USERGROUP_REL_SESSION);
	}
    
    /**
     * Displays the title + grid
     */
    function display() {
        // action links
        echo '<div class="actions">';
       	echo  '<a href="../admin/index.php">'.Display::return_icon('back.png', get_lang('BackTo').' '.get_lang('PlatformAdmin'),'','32').'</a>';
	   //echo '<a href="career_dashboard.php">'.Display::return_icon('back.png',get_lang('Back')).get_lang('Back').'</a>';       
        echo '<a href="'.api_get_self().'?action=add">'.Display::return_icon('new_class.png',get_lang('langAddClasses'),'','32').'</a>';   
        echo '</div>';   
        echo Display::grid_html('usergroups');  
    } 
    
     /**
     * Gets a list of course ids by user group
     * @param   int     user group id
     * @return  array   
     */     
    public function get_courses_by_usergroup($id) {        
        $results = Database::select('course_id',$this->usergroup_rel_course_table, array('where'=>array('usergroup_id = ?'=>$id)));
        $array = array();
        if (!empty($results)) {    
            foreach($results as $row) {
                $array[]= $row['course_id'];            
            }
        }                       
        return $array;
    }    
    
    /**
     * Gets a list of session ids by user group
     * @param   int     user group id
     * @return  array   
     */
    public function get_sessions_by_usergroup($id) {
        $results = Database::select('session_id',$this->usergroup_rel_session_table, array('where'=>array('usergroup_id = ?'=>$id)));
        $array = array();
        if (!empty($results)) {    
            foreach($results as $row) {
                $array[]= $row['session_id'];            
            }
        }                
        return $array;
    }      
    
    /**
     * Gets a list of user ids by user group
     * @param   int     user group id
     * @return  array   with a list of user ids
     */
    public function get_users_by_usergroup($id) {
        $results = Database::select('user_id',$this->usergroup_rel_user_table, array('where'=>array('usergroup_id = ?'=>$id)));
        $array = array();
        if (!empty($results)) {    
            foreach($results as $row) {
                $array[]= $row['user_id'];            
            }
        }                       
        return $array; 	
    }
    
    /**
     * Gets the usergroup id list by user id
     * @param   int user id
     */
    public function get_usergroup_by_user($id) {
        $results = Database::select('usergroup_id',$this->usergroup_rel_user_table, array('where'=>array('user_id = ?'=>$id)));
        $array = array();
        if (!empty($results)) {    
            foreach($results as $row) {
                $array[]= $row['usergroup_id'];            
            }
        }                       
        return $array;  
    }    
    
    
    /**
     * Subscribes sessions to a group  (also adding the members of the group in the session and course)
     * @param   int     usergroup id
     * @param   array   list of session ids
    */
    function subscribe_sessions_to_usergroup($usergroup_id, $list) {
        require_once api_get_path(LIBRARY_PATH).'sessionmanager.lib.php';
        $current_list = self::get_sessions_by_usergroup($usergroup_id);
        $user_list    = self::get_users_by_usergroup($usergroup_id);
     
        $delete_items = $new_items = array();
        if (!empty($list)) {                
            foreach ($list as $session_id) {
                if (!in_array($session_id, $current_list)) {
                	$new_items[] = $session_id;
                }           	
            }
        }            
        if (!empty($current_list)) {  
            foreach($current_list as $session_id) {
        	   if (!in_array($session_id, $list)) {
                    $delete_items[] = $session_id;
                }  
            }
        }

        //Deleting items
        if (!empty($delete_items)) {
            foreach($delete_items as $session_id) {
                if (!empty($user_list)) {
                    foreach($user_list as $user_id) {
                        
                        SessionManager::unsubscribe_user_from_session($session_id, $user_id);
                        /*foreach ($course_list as $course_data) {
                            foreach($user_list as $user_id) {
                                CourseManager::subscribe_user($user_id, $course_data['code'], $session_id);
                            }
                        }*/
                    }
                }
                Database::delete($this->usergroup_rel_session_table, array('usergroup_id = ? AND session_id = ?'=>array($usergroup_id, $session_id)));
            }
        }     
        
        //Addding new relationships
        if (!empty($new_items)) {
            foreach($new_items as $session_id) {                
                $params = array('session_id'=>$session_id, 'usergroup_id'=>$usergroup_id);
                Database::insert($this->usergroup_rel_session_table, $params); 
            
                if (!empty($user_list)) {
                    SessionManager::suscribe_users_to_session($session_id, $user_list, null, false);
                }
                /*
                $course_list = SessionManager::get_course_list_by_session_id($id);
                foreach ($course_list as $course_data) {
                    foreach($user_list as $user_id) {
                        CourseManager::subscribe_user($user_id, $course_data['code'], $id);
                    }
                }*/
            }
        }
    }
    
    /**
     * Subscribes courses to a group (also adding the members of the group in the course)
     * @param   int     usergroup id
     * @param   array   list of course ids
     */
    function subscribe_courses_to_usergroup($usergroup_id, $list) {
        require_once api_get_path(LIBRARY_PATH).'course.lib.php';
  
        $current_list = self::get_courses_by_usergroup($usergroup_id);
        $user_list    = self::get_users_by_usergroup($usergroup_id);
     
        $delete_items = $new_items = array();
        if (!empty($list)) {                
            foreach ($list as $id) {
                if (!in_array($id, $current_list)) {
                    $new_items[] = $id;
                }               
            }
        }
        if (!empty($current_list)) {         
            foreach($current_list as $id) {
                if (!in_array($id, $list)) {
                    $delete_items[] = $id;
                }  
            }
        }
        
        //Deleting items
        if (!empty($delete_items)) {
            foreach($delete_items as $course_id) {
                $course_info = api_get_course_info_by_id($course_id);     
                if (!empty($user_list)) {
                    foreach($user_list as $user_id) {                                   
                        CourseManager::unsubscribe_user($user_id, $course_info['code']);                    
                    }
                }
                Database::delete($this->usergroup_rel_course_table, array('usergroup_id = ? AND course_id = ?'=>array($usergroup_id, $course_id)));
            }
        }
        
        //Addding new relationships
        if (!empty($new_items)) {
            foreach($new_items as $course_id) {                
                $course_info = api_get_course_info_by_id($course_id);    
                if (!empty($user_list)) {
                    foreach($user_list as $user_id) {         
                        CourseManager::subscribe_user($user_id, $course_info['code']);
                    }
                }
                 
                $params = array('course_id'=>$course_id, 'usergroup_id'=>$usergroup_id);
                Database::insert($this->usergroup_rel_course_table, $params);
            }
        }
    }   
    
     /**
     * Subscribes users to a group
     * @param   int     usergroup id
     * @param   array   list of user ids
     */
    function subscribe_users_to_usergroup($usergroup_id, $list) {
        $current_list    = self::get_users_by_usergroup($usergroup_id);  
        $course_list  = self::get_courses_by_usergroup($usergroup_id);
        $session_list = self::get_sessions_by_usergroup($usergroup_id);
        
        $delete_items = $new_items = array();
        if (!empty($list)) {
            foreach ($list as $user_id) {
                if (!in_array($user_id, $current_list)) {
                	$new_items[] = $user_id;
                }           	
            }
        }
        if (!empty($current_list)) {
            foreach($current_list as $user_id) {
        	   if (!in_array($user_id, $list)) {
                    $delete_items[] = $user_id;
                }  
            }
        }

        //Deleting items
        if (!empty($delete_items)) {
            foreach($delete_items as $user_id) {
                //Removing courses
                if (!empty($course_list)) {
                    foreach($course_list as $course_id) {
                        $course_info = api_get_course_info_by_id($course_id);
                        CourseManager::unsubscribe_user($user_id, $course_info['code']);                    
                    }
                }
                //Removing sessions
                if (!empty($session_list)) {
                    foreach($session_list as $session_id) {
                        SessionManager::unsubscribe_user_from_session($session_id, $user_id);
                    }
                }
                Database::delete($this->usergroup_rel_user_table, array('usergroup_id = ? AND user_id = ?'=>array($usergroup_id, $user_id)));
            }
        }

        //Addding new relationships
        if (!empty($new_items)) {
             //Adding sessions
            if (!empty($session_list)) {
                foreach($session_list as $session_id) {
                    SessionManager::suscribe_users_to_session($session_id, $new_items, null, false);
                }
            }
            foreach($new_items as $user_id) {
                //Adding courses
                if (!empty($course_list)) {
                    foreach($course_list as $course_id) {
                        $course_info = api_get_course_info_by_id($course_id);
                        CourseManager::subscribe_user($user_id, $course_info['code']);
                    }
                }
                $params = array('user_id'=>$user_id, 'usergroup_id'=>$usergroup_id);
                Database::insert($this->usergroup_rel_user_table, $params);
            }
        }
    }
}

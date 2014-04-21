<?php

namespace Notebook;

use \ChamiloSession as Session;

/**
 * Html request for course description.
 * 
 * @author Laurent Opprecht <laurent@opprecht.info> for the Univesity of Genevas
 * @license /license.txt
 */
class Request extends \Request
{

    const PARAM_ID = 'id';
    const PARAM_IDS = 'ids';
    const PARAM_C_ID = 'c_id';
    const PARAM_SESSION_ID = 'id_session';
    const PARAM_ACTION = 'action';
    const PARAM_SEC_TOKEN = 'sec_token';
    const PARAM_IS_STUDENT_VIEW = 'isStudentView';
    const PARAM_SORT_COLUMN = 'sort_column';
    const PARAM_SORT_DIRECTION = 'sort_direction';

    /**
     * Action to perform.      * 
     * @return string
     */
    public static function get_action()
    {
        $result = Request::get(self::PARAM_ACTION, '');
        return $result;
    }

    /**
     * Returns the object id. 
     * 
     * @return int
     */
    public static function get_id()
    {
        $result = \Request::get(self::PARAM_ID, 0);
        $result = intval($result);
        return $result;
    }

    /**
     * List of objet ids
     * 
     * @return array 
     */
    public static function get_ids()
    {
        $result = Request::get(self::PARAM_IDS, array());
        if (is_array($result)) {
            return $result;
        }

        $result = trim($result);
        if (empty($result)) {
            return array();
        }

        $result = explode(',', $result);
        return $result;
    }

    /**
     * Returns the course id. 
     * 
     * @return int
     */
    public static function get_c_id()
    {
        $result = Request::get(self::PARAM_C_ID, 0);
        $result = intval($result);
        $result = $result ? $result : api_get_real_course_id();
        $result = $result ? $result : 0;
        return $result;
    }

    /**
     * Returns the session id. 
     * 
     * @return int
     */
    public static function get_session_id()
    {
        $result = Request::get(self::PARAM_SESSION_ID, 0);
        $result = intval($result);
        return $result;
    }

    /**
     * Returns the security token. 
     * 
     * @return string
     */
    public static function get_security_token()
    {
        $result = Request::get(self::PARAM_SEC_TOKEN, 0);
        return $result;
    }

    /**
     * Returns true if the user is in "student view". False otherwise. 
     * 
     * @return bool
     */
    public static function is_student_view()
    {
        return Request::get(self::PARAM_IS_STUDENT_VIEW, false) == 'true';
    }

    /**
     * Returns a course key parameters. I.e. not a real course but an 
     * object with the course c_id and session set up.
     * 
     * @return object
     */
    public static function get_course_key()
    {
        $result = (object) array();
        $result->c_id = Request::get_c_id();
        $result->session_id = Request::get_session_id();
        return $result;
    }

    /**
     * Returns an item key. I.e. not a real entity object but an 
     * object with the object keys set up.
     * 
     * @return object
     */
    public static function get_item_key()
    {
        $result = (object) array();
        $result->c_id = Request::get_c_id();
        $result->id = Request::get_id();
        return $result;
    }

    public static function get_sort_column()
    {
        $result = Request::get(self::PARAM_SORT_COLUMN, 'name');
        if($result == 'title'){
            return $result;
        }
        if($result == 'creation_date'){
            return $result;
        }
        if($result == 'update_date'){
            return $result;
        }
        return 'creation_date';
    }
    
    public static function get_sort_direction(){
        $result = Request::get(self::PARAM_SORT_DIRECTION, 'name');
        $result = strtoupper($result);
        $result = ($result == 'DESC') ? 'DESC' : 'ASC';
        return $result;
    }   
    

}
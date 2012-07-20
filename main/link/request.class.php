<?php

namespace Link;

/**
 * Html request for Link.
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
    const PARAM_SECURITY_TOKEN = 'sec_token';
    const PARAM_IS_STUDENT_VIEW = 'isStudentView';
    const PARAM_GROUP_ID = 'toolgroup';

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
     * Returns the session id. 
     * 
     * @return int
     */
    public static function get_group_id()
    {
        $result = Request::get(self::PARAM_GROUP_ID, 0);
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

}
<?php

/**
 * Utility functions to manage uris/urls.
 * 
 * @license see /license.txt
 * @author Laurent Opprecht <laurent@opprecht.info> for the Univesity of Geneva
 */
class Uri
{
    
    public static function chamilo()
    {
        return 'http://chamilo.org/';
    }

    /**
     * Application web root
     */
    public static function www()
    {
        static $result = false;
        if (empty($result))
        {
            $result = api_get_path(WEB_PATH);
        }
        return $result;
    }

    /**
     * Returns a full url from local/absolute path and parameters.
     * Append the root as required for relative urls.
     * 
     * @param string $path
     * @param array $params
     * @return string 
     */
    public static function url($path = '', $params = array(), $html = true)
    {
        $result = $path;
        if (strpos($result, 'http') !== 0)
        {
            $result = ltrim($result, '/');
            $result = self::www() . $result;
        }
        if ($params)
        {

            $result = rtrim($result, '?');
            $result = $result . '?' . self::params($params, $html);
        }
        return $result;
    }

    /**
     * Format url parameters
     * 
     * @param array $params
     * @return string
     */
    public static function params($params = array(), $html = true)
    {
        $result = array();
        foreach ($params as $key => $value)
        {
            $result[] = $key . '=' . urlencode($value);
        }
        $result = implode($html ? '&amp;' : '&', $result);
        return $result;
    }

    /**
     * Returns the course parameters. If null default to the current user parameters.
     * 
     * @param string $course_code
     * @param string|int $session_id
     * @param string|int $group_id
     * @return type 
     */
    public static function course_params($course_code = null, $session_id = null, $group_id = null)
    {
        $course_code = is_null($course_code) ? api_get_course_id() : $course_code;
        $session_id = is_null($session_id) ? api_get_session_id() : $session_id;
        $session_id = $session_id ? $session_id : '0';
        $group_id = is_null($group_id) ? '' : $group_id;
        $group_id = $group_id ? $group_id : '0';
        return array('cidReq' => $course_code, 'id_session' => $session_id, 'gidReq' => $group_id);
    }

}
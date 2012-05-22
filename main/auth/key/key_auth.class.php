<?php

use \ChamiloSession as Session;


/**
 * Used to authenticate user with an access token. By default this method is disabled.
 * Method used primarily to make API calls:Rss, etc.
 * 
 * Access is granted only for the services that are enabled. 
 * 
 * You need to call 
 * 
 *      KeyAuth::enable_services('my_service');
 * 
 * to enable this access method for a specific service before a call to global.inc.php.
 * 
 * To be secured this method must 
 * 
 *      1) be called through httpS to avoid sniffing (note that this is the case anyway with other methods such as cookies)
 *      2) the url/access token must be secured
 * 
 * This authentication method is session less. This is to ensure that the navigator 
 * do not receive an access cookie that will grant it access to other parts of the
 * application.
 *
 * @license see /license.txt
 * @author Laurent Opprecht <laurent@opprecht.info> for the Univesity of Geneva
 */
class KeyAuth
{

    protected static $services = array();

    /**
     * Returns enabled services
     * 
     * @return array
     */
    public static function get_services()
    {
        return self::$services;
    }

    /**
     * Name of the service for which we are goint to check the API Key. 
     * If empty it disables authentication.
     *  
     * !! 10 chars max !!
     */
    public static function enable_services($_)
    {
        $args = func_get_args();
        $names = array();
        foreach ($args as $arg)
        {
            if (is_object($arg))
            {
                $f = array($arg, 'get_service_name');
                $name = call_user_func($f);
            }
            else
            {
                $name = $arg;
            }
            $name = substr($name, 0, 10);
            self::$services[$name] = $name;
        }
    }

    public static function disable_services($_)
    {
        $args = func_get_args();
        $names = array();
        foreach ($args as $name)
        {
            $name = substr($name, 0, 10);
            unset(self::$services[$name]);
        }
    }

    public static function clear_services()
    {
        self::$services[$name] = array();
    }
    
    /**
     * @return Returns true if authentication method is enabled. False otherwise.
     */
    public static function is_enabled()
    {
        return !empty(self::$services);
    }

    /**
     * @return KeyAuth 
     */
    public static function instance()
    {
        static $result = null;
        if(empty($result))
        {
            $result = new self();
        }
        return $result;
    }

    protected function __construct()
    {
        
    }

    /**
     * Returns true if security accepts to run otherwise returns false.
     * 
     * @return boolean 
     */
    public function accept()
    {
        $user_id = $this->get_user_id();
        if (empty($user_id))
        {
            return false;
        }

        $services = $this->get_services();
        if (empty($services))
        {
            return false;
        }

        $token = $this->get_token();
        if (empty($token))
        {
            return false;
        }

//        $control = $this->get_control();
//        if (empty($control))
//        {
//            return false;
//        }

        $user = UserManager::get_user_info_by_id($user_id);
        if (empty($user))
        {
            return false;
        }

        if ($user['active'] != 1)
        {
            return false;
        }

        foreach ($services as $service)
        {
            $keys = UserManager::get_api_keys($user_id, $service);
            $keys = $keys ? $keys : array();
            foreach ($keys as $key)
            {
                if ($key == $token)
                {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * If accepted tear down session, log in user and returns true. 
     * If not accepted do nothing and returns false.
     * 
     * @return boolean 
     */
    public function login()
    {
        if (!$this->accept())
        {
            return false;
        }
        /**
         * ! important this is to ensure we don't grant access for other parts 
         */
        Session::destroy();

        global $_user, $_uid;
        $_uid = $this->get_user_id();
        $_user = UserManager::get_user_info_by_id($_uid);

        Session::write('_user',$_user);
        Session::write('_uid',$_uid);
        
        /**
         * We don't allow redirection since access is granted only for this call 
         */
        global $no_redirection, $noredirection;
        $no_redirection = true;
        $noredirection = true;
        Session::write('noredirection',$noredirection);
        
        return true;
    }

    /**
     * Returns the request user id parameter
     * 
     * @return int 
     */
    public function get_user_id()
    {
        return (int) Request::get('user_id');
    }

    /**
     * Returns the request security token parameter
     * 
     * @return string 
     */
    public function get_token()
    {
        return Request::get('token');
    }

    /**
     * Returns the control token parameter
     * 
     * @return string 
     */
    public function get_control()
    {
        return Request::get('control');
    }

}
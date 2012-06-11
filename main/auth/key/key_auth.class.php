<?php

use \ChamiloSession as Session;

/**
 * Used to authenticate user with an access token. By default this method is disabled.
 * Method used primarily to make API calls: Rss, file upload.
 * 
 * Access is granted only for the services that are enabled.  
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
 * 
 * Usage:
 * 
 * Enable KeyAuth for a specific service. Add the following lines so that 
 * the key authentication method is enabled for a specific service before 
 * calling global.inc.php.
 * 
 *      include_once '.../main/inc/autoload.inc.php';
 *      KeyAuth::enable_services('my_service');
 *      include_once '.../main/inc/global.inc.php';
 * 
 * 
 * Enable url access for a short period of time:
 * 
 *      token = KeyAuth::create_temp_token();
 *      url = '...?access_token=' . $token ;
 * 
 * @see AccessToken
 * @license see /license.txt
 * @author Laurent Opprecht <laurent@opprecht.info> for the Univesity of Geneva
 */
class KeyAuth
{

    const PARAM_ACCESS_TOKEN = 'access_token';

    protected static $services = array();

    public static function create_temp_token($service = null, $duration = 60, $user_id = null)
    {
        return UserApiKeyManager::create_temp_token($service, $duration, $user_id);
    }

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
        foreach ($args as $arg) {
            if (is_object($arg)) {
                $f = array($arg, 'get_service_name');
                $name = call_user_func($f);
            } else {
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
        foreach ($args as $name) {
            $name = substr($name, 0, 10);
            unset(self::$services[$name]);
        }
    }

    public static function is_service_enabled($service)
    {
        $services = self::get_services();
        foreach ($services as $s) {
            if ($s == $service) {
                return true;
            }
        }
        return false;
    }

    public static function clear_services()
    {
        self::$services[$name] = array();
    }

    /**
     * Enable key authentication for the default service - i.e. chamilo 
     */
    public static function enable()
    {
        self::enable_services(UserApiKeyManager::default_service());
    }

    public static function disable()
    {
        self::$services[$name] = array();
    }

    /**
     * Returns true if the key authentication method is enabled. False otherwise.
     * Default to false.
     * 
     * @return bool
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
        if (empty($result)) {
            $result = new self();
        }
        return $result;
    }

    protected function __construct()
    {
        
    }

    /**
     * Returns true if authentication accepts to run otherwise returns false.
     * 
     * @return boolean 
     */
    public function accept()
    {
        /**
         * Authentication method must be enabled 
         */
        if (!self::is_enabled()) {
            return false;
        }

        $token = $this->get_access_token();
        if ($token->is_empty()) {
            return false;
        }

        $key = UserApiKeyManager::get_by_id($token->get_id());
        if (empty($key)) {
            return false;
        }

        /**
         * The service corresponding to the key must be enabled. 
         */
        $service = $key['api_service'];
        if (!self::is_service_enabled($service)) {
            return false;
        }

        /**
         * User associated with the key must be active 
         */
        $user = UserManager::get_user_info_by_id($token->get_user_id());
        if (empty($user)) {
            return false;
        }
        if (!$user['active']) {
            return false;
        }

        /**
         * Token must be valid. 
         */
        return $token->is_valid();
    }

    /**
     * If accepted tear down session, log in user and returns true. 
     * If not accepted do nothing and returns false.
     * 
     * @return boolean 
     */
    public function login()
    {
        if (!$this->accept()) {
            return false;
        }
        /**
         * ! important this is to ensure we don't grant access for other parts 
         */
        Session::destroy();

        /**
         * We don't allow redirection since access is granted only for this call 
         */
        global $no_redirection, $noredirection;
        $no_redirection = true;
        $noredirection = true;
        Session::write('noredirection', $noredirection);
        
        $user_id = $this->get_user_id();
        $course_code = $this->get_course_code();
        $group_id = $this->get_group_id();
        
        Login::init_user($user_id, true);
        Login::init_course($course_code, true);
        Login::init_group($group_id, true);

        return true;
    }

    /**
     * Returns the request access token
     * 
     * @return AccessToken
     */
    public function get_access_token()
    {
        $string = Request::get(self::PARAM_ACCESS_TOKEN);
        return AccessToken::parse($string);
    }
    
    public function get_user_id()
    {
        return $this->get_access_token()->get_user_id();
    }
    
    public function get_course_code()
    {
        return Request::get('cidReq', 0);
    }
    
    public function get_group_id()
    {
        return Request::get('gidReq', 0);
    }

}
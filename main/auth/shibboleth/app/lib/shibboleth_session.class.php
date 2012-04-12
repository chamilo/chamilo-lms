<?php

/**
 * A Chamilo user session. Used as there is no session object so far provided by the core API.
 * Should be moved to the core library.Prefixed by Shibboleth to avoid name clashes.
 *
 * @license see /license.txt
 * @author Laurent Opprecht <laurent@opprecht.info>, Nicolas Rod for the University of Geneva
 */
class ShibbolethSession
{

    /**
     * @return ShibbolethSession
     */
    public static function instance()
    {
        static $result = false;
        if (empty($result))
        {
            $result = new self();
        }
        return $result;
    }

    function is_logged_in()
    {
        return isset($_SESSION['_user']['user_id']);
    }

    function user()
    {
        return $_SESSION['_user'];
    }

    function logout()
    {
        $_SESSION['_user'] = array();
        
        $logout_no_redirect = true;
        online_logout();
    }

    /**
     * Create a Shibboleth session for the user ID
     *
     * @param  string $_uid - The user ID
     * @return $_user (array) - The user infos array created when the user logs in
     */
    function login($_uid)
    {
        $user = User::store()->get_by_user_id($_uid);
        if (empty($user))
        {
            return;
        }
        
        $this->logout();
        
        api_session_start();
        api_session_register('_uid');
        
        global $_user;
        $_user = (array)$user;

        $_SESSION['_user'] = $_user;
        $_SESSION['_user']['user_id'] = $_uid;
        $_SESSION['noredirection'] = true;
        
        //must be called before 'init_local.inc.php'
        event_login();

        //used in 'init_local.inc.php' this is BAD but and should be changed
        $loginFailed = false;
        $uidReset = true;

        $gidReset = true;
        $cidReset = false; //FALSE !!      

        $mainDbName = Database :: get_main_database();
        $includePath = api_get_path(INCLUDE_PATH);
        
        global $is_platformAdmin; 
        /* This must be set for local.inc.php to set up correctly the platform admin
         * This is BAD.
         */

        require("$includePath/local.inc.php");


        return $_user;
    }

}
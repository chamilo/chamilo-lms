<?php

namespace Shibboleth;

use ChamiloSession as Session;
use Database;
use Event;

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
        if (empty($result)) {
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
        online_logout(null, false);
        global $logoutInfo;
        Event::courseLogout($logoutInfo);
    }

    /**
     * Create a Shibboleth session for the user ID
     *
     * @param  string $uid The user ID
     * @return array $_user The user infos array created when the user logs in
     */
    function login($uid)
    {
        /* This must be set for local.inc.php to register correctly the global variables in session
         * This is BAD. Logic should be migrated into a function and stop relying on global variables.
         */
        global $_uid, $is_allowedCreateCourse, $is_platformAdmin, $_real_cid, $is_courseAdmin;
        global $is_courseMember, $is_courseTutor, $is_session_general_coach, $is_allowed_in_course, $is_sessionAdmin, $_gid;
        $_uid = $uid;

        //is_allowedCreateCourse
        $user = User::store()->get_by_user_id($uid);
        if (empty($user)) {
            return;
        }

        $this->logout();

        Session::instance();
        Session::write('_uid', $_uid);

        global $_user;
        $_user = (array) $user;

        $_SESSION['_user'] = $_user;
        $_SESSION['_user']['user_id'] = $_uid;
        $_SESSION['noredirection'] = true;

        //must be called before 'init_local.inc.php'
        Event::eventLogin($_uid);

        //used in 'init_local.inc.php' this is BAD but and should be changed
        $loginFailed = false;
        $uidReset = true;

        $gidReset = true;
        $cidReset = false; //FALSE !!

        $mainDbName = Database :: get_main_database();
        $includePath = api_get_path(SYS_INC_PATH);

        $no_redirection = true;
        require("$includePath/local.inc.php");

        return $_user;
    }

}

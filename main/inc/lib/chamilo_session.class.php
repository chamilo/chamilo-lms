<?php
/**
 * Chamilo session (i.e. the session that maintains the connection open after usr login)
 * 
 * Usage:
 * 
 * 
 *      use ChamiloSession as Session;
 * 
 *      Session::read('name');
 * 
 * Or
 * 
 *      Chamilo::session()->...
 *      session()->...
 *
 * @license see /license.txt
 * @author Laurent Opprecht <laurent@opprecht.info> for the Univesity of Geneva
 */
/**
 * ChamiloSession class definition
 */
class ChamiloSession extends System\Session
{

    const NAME = 'ch_sid';

    /**
     * Generate new session instance
     * @return ChamiloSession 
     */
    static function instance()
    {
        static $result = null;
        if (empty($result)) {
            $result = new ChamiloSession();
        }
        return $result;
    }

    /**
     * Returns the session lifetime
     * @return int The session lifetime as defined in the config file, in seconds
     */
    static function session_lifetime()
    {
        global $_configuration;
        return $_configuration['session_lifetime'];
    }

    /**
     * Returns whether the sessions are stored in the database (or not)
     * @return bool True if session data are stored in the database, false if they're stored on disk
     * @assert (null) === false
     */
    static function session_stored_in_db()
    {
        return self::read('session_stored_in_db', false);
    }

    /**
     * Starts the Chamilo session.
     *
     * The default lifetime for session is set here. It is not possible to have it
     * as a database setting as it is used before the database connection has been made.
     * It is taken from the configuration file, and if it doesn't exist there, it is set
     * to 360000 seconds
     *
     * @author Olivier Brouckaert
     * @param  string variable - the variable name to save into the session
     * @return void
     */
    static function start($already_installed = true)
    {
        global $_configuration;

        /* Causes too many problems and is not configurable dynamically.
          if ($already_installed) {
          $session_lifetime = 360000;
          if (isset($_configuration['session_lifetime'])) {
          $session_lifetime = $_configuration['session_lifetime'];
          }
          //session_set_cookie_params($session_lifetime,api_get_path(REL_PATH));
          }
         */

        if (self::session_stored_in_db() && function_exists('session_set_save_handler')) {
            $handler = new SessionHandler();
            @session_set_save_handler(
                array(& $handler, 'open'),
                array(& $handler, 'close'),
                array(& $handler, 'read'),
                array(& $handler, 'write'),
                array(& $handler, 'destroy'),
                array(& $handler, 'garbage')
            );
        }

        /*
         * Prevent Session fixation bug fixes  
         * See http://support.chamilo.org/issues/3600
         * http://php.net/manual/en/session.configuration.php
         * @todo use session_set_cookie_params with some custom admin parameters
         */

        //session.cookie_lifetime
        //the session ID is only accepted from a cookie
        ini_set('session.use_only_cookies', 1);

        //HTTPS only if possible 
        //ini_set('session.cookie_secure', 1);
        //session ID in the cookie is only readable by the server 
        ini_set('session.cookie_httponly', 1);

        //Use entropy file    
        //session.entropy_file
        //ini_set('session.entropy_length', 128);
        //Do not include the identifier in the URL, and not to read the URL for
        // identifiers.
        ini_set('session.use_trans_sid', 0);

        session_name(self::NAME);
        session_start();

        $session = self::instance();

        if ($already_installed) {
            if (!isset($session['checkChamiloURL'])) {
                $session['checkChamiloURL'] = api_get_path(WEB_PATH);
            } elseif ($session['checkChamiloURL'] != api_get_path(WEB_PATH)) {
                self::clear();
            }
        }

        /*if (!$session->has('starttime') && !$session->is_expired()) {
            $session->write('starttime', time());
        }*/
        // If the session time has expired, refresh the starttime value,
        //  so we're starting to count down from a later time
        if ( $session->has('starttime') && $session->is_expired()) {
            error_log(microtime().' -- '.__LINE__);
            $session->destroy();
        } else {
            //error_log('Time not expired, extend session for a bit more');
            $session->write('starttime', time());
        }
    }

    /**
     * Session start time: that is the last time the user loaded a page (before this time)
     * @return int timestamp
     */
    function start_time()
    {
        return self::read('starttime');
    }

    /**
     * Session end time: when the session expires. This is made of the last page
     * load time + a number of seconds
     * @return int UNIX timestamp (server's timezone)
     */
    function end_time()
    {
        $start_time = $this->start_time();
        $lifetime = self::session_lifetime();
        return $start_time + $lifetime;
    }

    /**
     * Returns whether the session is expired
     * @return bool True if the session is expired, false if it is still valid
     */
    public function is_expired()
    {
        return $this->end_time() < time();
    }

    /**
     * The current (logged in) user.
     * @return CurrentUser The current user instance
     */
    public function user()
    {
        static $result = null;
        if (empty($result)) {
            $result = CurrentUser::instance();
        }
        return $result;
    }

    /**
     * Returns the current (active) course
     * @return CurrentCourse The current course instance
     */
    public function course()
    {
        static $result = null;
        if (empty($result)) {
            $result = CurrentCourse::instance();
        }
        return $result;
    }

    /**
     * The current group for the current (logged in) user.
     * @return int the current group id
     */
    public function group_id()
    {
        return Session::read('_gid');
    }
}
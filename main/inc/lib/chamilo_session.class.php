<?php

use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Chamilo session (i.e. the session that maintains the connection open after usr login).
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
 * @todo use session symfony component
 * @todo replace all $_SESSION calls with this class.
 * @todo remove System\Session class
 * ChamiloSession class definition
 */
class ChamiloSession extends System\Session
{
    public const NAME = 'ch_sid';

    /**
     * Generate new session instance.
     *
     * @return ChamiloSession
     */
    public static function instance()
    {
        static $result = null;
        if (empty($result)) {
            $result = new ChamiloSession();
        }

        return $result;
    }

    /**
     * Returns the session lifetime.
     *
     * @return int The session lifetime as defined in the config file, in seconds
     */
    public static function session_lifetime()
    {
        global $_configuration;

        return $_configuration['session_lifetime'];
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
     *
     * @param  string variable - the variable name to save into the session
     */
    public static function start($already_installed = true)
    {
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

        if (api_get_configuration_value('security_session_cookie_samesite_none')) {
            if (PHP_VERSION_ID < 70300) {
                $sessionCookieParams = session_get_cookie_params();
                session_set_cookie_params($sessionCookieParams['lifetime'], '/; samesite=None',
                $sessionCookieParams['domain'], true, $sessionCookieParams['httponly']);
            } else {
                ini_set('session.cookie_secure', 1);
                ini_set('session.cookie_samesite', 'None');
            }
        }

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

        // If the session time has expired, refresh the starttime value,
        //  so we're starting to count down from a later time
        if (self::has('starttime') && $session->is_expired()) {
            self::destroy();
        } else {
            //error_log('Time not expired, extend session for a bit more');
            self::write('starttime', time());
        }
    }

    /**
     * Session start time: that is the last time the user loaded a page (before this time).
     *
     * @return int timestamp
     */
    public function start_time()
    {
        return self::read('starttime');
    }

    /**
     * Session end time: when the session expires. This is made of the last page
     * load time + a number of seconds.
     *
     * @return int UNIX timestamp (server's timezone)
     */
    public function end_time()
    {
        $start_time = $this->start_time();
        $lifetime = self::session_lifetime();

        return $start_time + $lifetime;
    }

    /**
     * Returns whether the session is expired.
     *
     * @return bool True if the session is expired, false if it is still valid
     */
    public function is_expired()
    {
        return $this->end_time() < time();
    }
}

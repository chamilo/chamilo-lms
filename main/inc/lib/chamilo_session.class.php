<?php
/* For licensing terms, see /license.txt */

/**
 * ChamiloSession class definition
 * @todo just the Session from the Symfony2 component
 */
class ChamiloSession
{
    /** @var Symfony\Component\HttpFoundation\Session\Session */
    public static $session;

    /**
     * @param $session
     */
    public static function setSession($session)
    {
        self::$session = $session;
    }

    /**
     * @param $variable
     * @param null $default
     * @return null
     */
    public static function read($variable, $default = null)
    {
        $result = self::$session->get($variable);

        // Check if the value exists in the $_SESSION array, to keep BC.
        if (!isset($result)) {
            return isset($_SESSION[$variable]) ? $_SESSION[$variable] : $default;
        } else {
            return $result;
        }
    }

    /**
     * @param $variable
     * @param $value
     */
    public static function write($variable, $value)
    {
        // Writing the session in 2 instances because
        $_SESSION[$variable] = $value;
        self::$session->set($variable, $value);
    }

    /**
     * @param $variable
     */
    public static function erase($variable)
    {
        self::$session->remove($variable);

        if (isset($GLOBALS[$variable])) {
            unset($GLOBALS[$variable]);
        }
        if (isset($_SESSION[$variable])) {
            unset($_SESSION[$variable]);
        }
    }

    /**
    *
    */
    public static function clear()
    {
        self::$session->clear();
    }

    /**
     * Invalidate the session.
     */
    public static function destroy()
    {
        self::$session->invalidate();
    }
}

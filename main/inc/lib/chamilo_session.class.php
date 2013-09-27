<?php

/**
 * ChamiloSession class definition
  */
class ChamiloSession
{
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
        // check if the value exists in the $_SESSION array
        if (empty($result)) {
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
        $variable = (string) $variable;
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
     *
     */
    public static function destroy()
    {
        self::$session->invalidate();
    }
}

<?php

/**
 * ChamiloSession class definition
 * @todo don't use global
 */
class ChamiloSession
{

    public static function read($variable, $default = null)
    {
        global $app;
        $result = $app['session']->get($variable);
        // check if the value exists in the $_SESSION array
        if (empty($result)) {
            return isset($_SESSION[$variable]) ? $_SESSION[$variable] : $default;
        } else {
            return $result;
        }
    }

    public static function write($variable, $value)
    {
        global $app;
        // Writing the session in 2 instances because
        $_SESSION[$variable] = $value;
        $app['session']->set($variable, $value);
    }

    public static function erase($variable)
    {
        global $app;
        $variable = (string) $variable;
        $app['session']->remove($variable);

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
        global $app;
        $app['session']->clear();
        //$_SESSION = array();
    }

    /**
     *
     */
    public static function destroy()
    {
        global $app;
        $app['session']->invalidate();
        /*
        session_unset();
        $_SESSION = array();
        session_destroy();*/
    }
}

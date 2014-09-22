<?php

use Chamilo\CoreBundle\Framework\Container;

/**
 * ChamiloSession class
  */
class ChamiloSession
{
    /**
     * @param $variable
     * @param null $default
     * @return null
     */
    public static function read($variable, $default = null)
    {
        $session = Container::getSession();
        $result = null;
        if (isset($session)) {
            $result = $session->get($variable);
        }
        // Check if the value exists in the $_SESSION array
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
        $session = Container::getSession();
        // Writing the session in 2 instances because
        $_SESSION[$variable] = $value;
        $session->set($variable, $value);
    }

    /**
     * @param $variable
     */
    public static function erase($variable)
    {
        $variable = (string) $variable;
        $session = Container::getSession();
        $session->remove($variable);

        if (isset($GLOBALS[$variable])) {
            unset($GLOBALS[$variable]);
        }
        if (isset($_SESSION[$variable])) {
            unset($_SESSION[$variable]);
        }
    }

    /**
     * Clear session
     */
    public static function clear()
    {
        $session = Container::getSession();
        $session->clear();
    }

    /**
     * Invalidates a session
     */
    public static function destroy()
    {
        $session = Container::getSession();
        $session->invalidate();
    }
}

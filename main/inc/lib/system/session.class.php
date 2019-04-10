<?php
/* For licensing terms, see /license.txt */

namespace System;

/**
 * Session Management.
 *
 * @see ChamiloSession
 *
 * @license see /license.txt
 * @author Laurent Opprecht <laurent@opprecht.info> for the Univesity of Geneva
 */
class Session implements \ArrayAccess
{
    /**
     * @param string $name
     */
    public function __unset($name)
    {
        unset($_SESSION[$name]);
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function __isset($name)
    {
        return self::has($name);
    }

    /**
     * It it exists returns the value stored at the specified offset.
     * If offset does not exists returns null. Do not trigger a warning.
     *
     * @param string $name
     *
     * @return mixed
     */
    public function __get($name)
    {
        return self::read($name);
    }

    /**
     * @param string $name
     * @param mixed  $value
     */
    public function __set($name, $value)
    {
        self::write($name, $value);
    }

    /**
     * @param string $variable
     * @param null   $default
     *
     * @return mixed
     */
    public static function read($variable, $default = null)
    {
        return isset($_SESSION[$variable]) ? $_SESSION[$variable] : $default;
    }

    /**
     * @param string $variable
     * @param mixed  $value
     */
    public static function write($variable, $value)
    {
        $_SESSION[$variable] = $value;
    }

    /**
     * @param string $variable
     */
    public static function erase($variable)
    {
        $variable = (string) $variable;
        if (isset($GLOBALS[$variable])) {
            unset($GLOBALS[$variable]);
        }
        if (isset($_SESSION[$variable])) {
            unset($_SESSION[$variable]);
        }
    }

    /**
     * Returns true if session has variable set up, false otherwise.
     *
     * @param string $variable
     *
     * @return bool
     */
    public static function has($variable)
    {
        return isset($_SESSION[$variable]);
    }

    /**
     * Clear session.
     */
    public static function clear()
    {
        session_regenerate_id();
        session_unset();
        $_SESSION = [];
    }

    /**
     * Destroy session.
     */
    public static function destroy()
    {
        session_unset();
        $_SESSION = [];
        session_destroy();
    }

    /*
     * ArrayAccess
     */
    public function offsetExists($offset)
    {
        return isset($_SESSION[$offset]);
    }

    /**
     * It it exists returns the value stored at the specified offset.
     * If offset does not exists returns null. Do not trigger a warning.
     *
     * @param string $offset
     *
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return self::read($offset);
    }

    public function offsetSet($offset, $value)
    {
        self::write($offset, $value);
    }

    public function offsetUnset($offset)
    {
        unset($_SESSION[$offset]);
    }
}

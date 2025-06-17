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
     * ArrayAccess : bool
     */
    public function offsetExists($offset): bool
    {
        return isset($_SESSION[$offset]);
    }

    /**
     * If it exists, returns the value stored at the specified offset.
     * If offset does not exist, returns null. Do not trigger a warning.
     *
     * @param string $offset
     *
     * @return mixed (write offsetGet($offset): mixed on PHP 8 and & > )
     */
    //if PHP_VERSION_ID >= 80000
    #[\ReturnTypeWillChange]
    //endif
    public function offsetGet($offset)
    {
        return self::read($offset);
    }

    public function offsetSet($offset, $value): void
    {
        self::write($offset, $value);
    }

    public function offsetUnset($offset): void
    {
        unset($_SESSION[$offset]);
    }
}

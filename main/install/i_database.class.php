<?php

/**
 * Install database. Provides access to the Database class and allows to add
 * hooks for logging, testing, etc during installation.
 *
 * @license see /license.txt
 * @author Laurent Opprecht <laurent@opprecht.info> for the Univesity of Geneva
 */
class iDatabase extends Database
{

    private static $is_logging = true;

    static function is_logging()
    {
        return self::$is_logging;
    }

    static function set_is_logging($value)
    {
        self::$is_logging = $value;
    }

    static function select_db($database_name, $connection = null)
    {
        global $app;
        if (self::is_logging()) {
            $app['monolog']->addInfo(__FUNCTION__ . ' ' . $database_name);
        }
        return parent::select_db($database_name, $connection);
    }

    static function query($query, $connection = null, $file = null, $line = null)
    {
        global $app;

        if (self::is_logging()) {
            //$query = str_replace("\n", '', $query);
            //$app['monolog']->addInfo(__FUNCTION__ . ' ' . $query);
        }
        $result = parent::query($query, $connection, $file, $line);

        if (empty($result)) {
            $backtrace = debug_backtrace(); // Retrieving information about the caller statement.
            $caller = isset($backtrace[0]) ? $backtrace[0] : array();
            $file = $caller['file'];
            $line = $caller['line'];
            $message = " sql: $query \n file: $file \n line:$line";
            $app['monolog']->addError($message);
        }
        return $result;
    }
}


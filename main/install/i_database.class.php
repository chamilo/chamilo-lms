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
        if (self::is_logging()) {
            Log::notice(__FUNCTION__ . ' ' . $database_name, Log::frame(1));
        }
        return parent::select_db($database_name, $connection);
    }

    static function query($query, $connection = null, $file = null, $line = null)
    {
        if (self::is_logging()) {
            $query = str_replace("\n", '', $query);
            Log::notice(__FUNCTION__ . ' ' . $query, Log::frame(1));
        }

        return parent::query($query, $connection, $file, $line);
    }

}

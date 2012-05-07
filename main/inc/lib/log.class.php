<?php

use Monolog\Logger;

/**
 * Description of log
 *
 * @license see /license.txt
 * @author Laurent Opprecht <laurent@opprecht.info> for the Univesity of Geneva
 */
class Log
{

    private static function register_autoload()
    {
        static $has_run = false;
        if ($has_run) {
            return true;
        }

        $directory = api_get_path(LIBRARY_PATH) . 'symfony';
        if (!class_exists('Doctrine\Common\ClassLoader', false)) {
            require_once $directory . '/Doctrine/Common/ClassLoader.php';
        }

        $loader = new Doctrine\Common\ClassLoader('Monolog', $directory);
        $loader->register();

        $has_run = true;
    }

    /**
     *
     * @return \Monolog\Logger 
     */
    public static function logger()
    {
        static $result = null;
        if (empty($result)) {
            self::register_autoload();
            $name = 'name';
            $result = new Logger($name);
            $handler = new Monolog\Handler\StreamHandler('php://stderr');
            $handler->setFormatter(new Monolog\Formatter\LineFormatter('[%datetime%] [%level_name%] [%channel%]: %message% %context% %extra%' . PHP_EOL, 'Y-m-d H:i:s'));
            $result->pushHandler($handler);
            //$result->pushProcessor(new \Monolog\Processor\WebProcessor());
        }
        return $result;
    }

    /**
     * Adds a log record at the DEBUG level.
     *
     * This method allows to have an easy ZF compatibility.
     *
     * @param string $message The log message
     * @param array $context The log context
     * @return Boolean Whether the record has been processed
     */
    public static function debug($message, array $context = array())
    {
        return self::logger()->debug($message, $context);
    }

    /**
     * Adds a log record at the INFO level.
     *
     * This method allows to have an easy ZF compatibility.
     *
     * @param string $message The log message
     * @param array $context The log context
     * @return Boolean Whether the record has been processed
     */
    public static function info($message, array $context = array())
    {
        return self::logger()->info($message, $context);
    }

    /**
     * Adds a log record at the INFO level.
     *
     * This method allows to have an easy ZF compatibility.
     *
     * @param string $message The log message
     * @param array $context The log context
     * @return Boolean Whether the record has been processed
     */
    public static function notice($message, array $context = array())
    {
        return self::logger()->notice($message, $context);
    }

    /**
     * Adds a log record at the WARNING level.
     *
     * This method allows to have an easy ZF compatibility.
     *
     * @param string $message The log message
     * @param array $context The log context
     * @return Boolean Whether the record has been processed
     */
    public static function warning($message, array $context = array())
    {
        return self::logger()->warn($message, $context);
    }

    /**
     * Adds a log record at the ERROR level.
     *
     * This method allows to have an easy ZF compatibility.
     *
     * @param string $message The log message
     * @param array $context The log context
     * @return Boolean Whether the record has been processed
     */
    public static function error($message, array $context = array())
    {
        return self::logger()->err($message, $context);
    }

    /**
     * Adds a log record at the CRITICAL level.
     *
     * This method allows to have an easy ZF compatibility.
     *
     * @param string $message The log message
     * @param array $context The log context
     * @return Boolean Whether the record has been processed
     */
    public static function crit($message, array $context = array())
    {
        return self::logger()->crit($message, $context);
    }

    /**
     * Adds a log record at the ALERT level.
     *
     * This method allows to have an easy ZF compatibility.
     *
     * @param string $message The log message
     * @param array $context The log context
     * @return Boolean Whether the record has been processed
     */
    public static function alert($message, array $context = array())
    {
        return self::logger()->alert($message, $context);
    }

}
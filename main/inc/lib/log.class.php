<?php

use Monolog\Logger;

Log::register_autoload();

/**
 * Description of log
 *
 * @license see /license.txt
 * @author Laurent Opprecht <laurent@opprecht.info> for the Univesity of Geneva
 */
class Log
{

    public static function register_autoload()
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
    public static function default_logger()
    {
        $name = 'chamilo';
        $result = new Logger($name);
        $handler = new Monolog\Handler\StreamHandler('php://stderr');
        $handler->setFormatter(new Monolog\Formatter\LineFormatter('[%datetime%] [%level_name%] [%channel%] %message%' . PHP_EOL, 'Y-m-d H:i:s')); //%context% %extra%
        $result->pushHandler($handler);
        return $result;
    }

    private static $logger = null;

    /**
     *
     * @return \Monolog\Logger 
     */
    public static function logger()
    {
        if (empty(self::$logger)) {
            self::$logger = self::default_logger();
        }
        return self::$logger;
    }

    public static function set_logger($value)
    {
        self::$logger = $value;
    }

    /**
     * Returns the 
     * @param type $index
     * @return type 
     */
    public static function frame($index)
    {
        $result = debug_backtrace();
        array_shift($result);
        for ($i = 0; $i++; $i < $index) {
            array_shift($result);
        }
        return $result;
    }

    public static function write($level, $message, $context = array())
    {
        /*
         * Note that the same could be done with a monolog processor.
         */
        if (!isset($context['file'])) {
            $trace = debug_backtrace();
            array_shift($trace);
            $trace = reset($trace);
            $context['file'] = $trace['file'];
            $context['file'] = $trace['file'];
        }
        $file = $context['file'];
        $root = realpath(api_get_path(SYS_PATH));
        $file = str_replace($root, '', $file);
        $file = trim($file, DIRECTORY_SEPARATOR);
        $line = $context['line'];
        $message = "[$file:$line] " . $message;

        self::logger()->addRecord($level, $message, $context);
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
        return self::write(Logger::DEBUG, $message, $context);
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
        return self::write(Logger::INFO, self::message($message), $context);
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
        return self::write(Logger::INFO, $message, $context);
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
        return self::write(Logger::WARNING, $message, $context);
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
        return self::write(Logger::ERROR, $message, $context);
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
        return self::write(Logger::CRITICAL, $message, $context);
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
        return self::write(Logger::ALERT, $message, $context);
    }

}
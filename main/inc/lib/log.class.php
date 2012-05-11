<?php

use Monolog\Logger;

/**
 * Since static constructors do not exist in php the line below serves as a 
 * substitute.
 * 
 * Log is used by install which do not use autoload at the moment. If it becomes
 * the case then the line below may/should be moved to the main autoloading 
 * function.
 */
Log::register_autoload();

/**
 * Provides access to the main log - i.e. stderr - and allows to register events.
 * It is a facade to the monolog library:
 * 
 * Log::error('message');
 * Log::warning('message');
 * ...
 * 
 * 
 * Note:
 * This class uses a static approach which has the benefit of being simpler but do
 * no allow as much freedom as using an object approche. Another approach could be
 * 
 * Chamilo::log()->error('message');
 * Chamilo::log()->warning('message');
 * 
 * To somewhat alleviate this issue the user can register a different logger if hew
 * wants.
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
        return isset($result[$index]) ? $result[$index] : array();
    }

    public static function write($level, $message, $context = array())
    {
        /*
         * Note that the same could be done with a monolog processor.
         */
        if (!isset($context['file'])) {
            $trace = debug_backtrace();
            $trace = $trace[1];
            $context['file'] = $trace['file'];
            $context['line'] = $trace['line'];
        }
        $file = $context['file'];
        $root = realpath(api_get_path(SYS_PATH));
        $file = str_replace($root, '', $file);
        $file = trim($file, DIRECTORY_SEPARATOR);
        $line = $context['line'];
        $line = str_pad($line, 4, ' ', STR_PAD_LEFT);
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
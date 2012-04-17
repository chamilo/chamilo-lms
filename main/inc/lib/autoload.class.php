<?php

/**
 * Autoload Chamilo classes
 * 
 * @license see /license.txt
 * @author Laurent Opprecht <laurent@opprecht.info> for the Univesity of Geneva
 */
class Autoload
{

    /**
     * Register the Chamilo autoloader on the stack. 
     */
    static public function register()
    {
        $f = array(new self, 'load');
        spl_autoload_register($f);
    }

    static public function map()
    {
        static $result = false;

        if ($result)
        {
            return $result;
        }

        $dir = dirname(__FILE__);

        $result = array();
        $result['Redirect'] = $dir . '/redirect.class.php';
        $result['Request'] = $dir . '/request.class.php';
        return $result;
    }

    /**
     * Handles autoloading of classes.
     *
     * @param  string  $class_name  A class name.
     *
     * @return boolean returns true if the class has been loaded
     */
    public function load($class_name)
    {
        $map = self::map();
        if (isset($map[$class_name]))
        {
            $path = $map[$class_name];
            require_once $path;
            return true;
        }
        else
        {
            return false;
        }
    }

}

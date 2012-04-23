<?php

/**
 * Autoload Chamilo classes
 * 
 * @license see /license.txt
 * @author Laurent Opprecht <laurent@opprecht.info> for the Univesity of Geneva
 */
class Autoload
{

    static private $is_registered = false;

    /**
     * Register the Chamilo autoloader on the stack. 
     * Will only do it once so this method is repeatable.
     */
    static public function register()
    {
        if(self::is_registered())
        {
            return false;
        }
        
        $f = array(new self, 'load');
        spl_autoload_register($f);
        self::$is_registered = true;
        return true;
    }

    static public function is_registered()
    {
        return self::$is_registered;
    }

    static public function map()
    {
        static $result = false;

        if ($result)
        {
            return $result;
        }

        $dir = dirname(__FILE__) . '/';
        $sys = $dir . '../../';

        $result = array();
        $result['Chamilo'] = $dir . 'chamilo.class.php';
        $result['Redirect'] = $dir . 'redirect.class.php';
        $result['Request'] = $dir . 'request.class.php';
        $result['RequestServer'] = $dir . 'request_server.class.php';
        $result['AnnouncementEmail'] = $sys . 'announcements/announcement_email.class.php';
        $result['Javascript'] = $dir . 'javascript.class.php';
        $result['ClosureCompiler'] = $dir . 'closure_compiler.class.php';
        $result['Uri'] = $dir . 'uri.class.php';
        $result['GroupManager'] = $dir . 'groupmanager.lib.php';
        $result['Header'] = $dir . 'header.class.php';
        $result['Cache'] = $dir . 'cache.class.php';
        $result['KeyAuth'] = $sys . 'auth/key/key_auth.class.php';
        $result['CourseNoticeQuery'] = $sys . 'course_notice/course_notice_query.class.php';
        $result['CourseNoticeController'] = $sys . 'course_notice/course_notice_controller.class.php';
        $result['CourseNoticeRss'] = $sys . 'course_notice/course_notice_rss.class.php';
        
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

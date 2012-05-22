<?php

use \ChamiloSession as Session;


/**
 * Wrapper for the current course. Provide access to its data.
 *
 * @license see /license.txt
 * @author Laurent Opprecht <laurent@opprecht.info> for the Univesity of Geneva
 */
class CurrentCourse
{

    /**
     *
     * @return CurrentCourse 
     */
    public static function instance()
    {
        static $result = null;
        if (empty($result)) {
            $result = new self();
        }
        return $result;
    }

    protected function __construct()
    {
        ;
    }
    
    public function is_empty()
    {
        $id = $this->real_id();
        return empty($id);
    }

    public function data()
    {
        global $_course;
        if ($_course == '-1') {
            $_course = array();
        }
        return $_course;
    }

    public function real_id()
    {
        return (int)$this->get('real_id');
    }

    public function code()
    {
        return $this->get('code');
    }

    public function name()
    {
        return $this->get('name');
    }

    public function title()
    {
        return $this->get('title');
    }

    public function official_code()
    {
        return $this->get('official_code');
    }

    public function sys_code()
    {
        return $this->get('sysCode');
    }

    public function path()
    {
        return $this->get('path');
    }

    /**
     * not needed in Chamilo 1.9
     * 
     * @return type 
     */
    public function db_name()
    {
        return $this->get('dbName');
    }

    public function db_name_glu()
    {
        return $this->get('dbNameGlu');
    }

    public function titular()
    {
        return $this->get('titular');
    }

    public function language()
    {
        return $this->get('language');
    }

    public function category_code()
    {
        return $this->get('categoryCode');
    }

    public function category_ame()
    {
        return $this->get('category_name');
    }

    public function visibility()
    {
        return $this->get('visibility');
    }

    public function subscribe_allowed()
    {
        return $this->get('subscribe_allowed');
    }

    public function unubscribe_allowed()
    {
        return $this->get('unubscribe_allowed');
    }

    public function activate_legal()
    {
        return $this->get('activate_legal');
    }

    public function show_score()
    {
        return $this->get('show_score');
    }

    public function extrnal_link()
    {
        return $this->get('extLink');
    }
     
    /**
     * Returns the current user (logged in user) relationship with the course.
     * I.e his role
     * 
     * @return array
     */
    public function user()
    {
        $result = Session::read('_courseUser');
        $result = $result ? $result : array();
        return $result;
    }

    public function get($name, $default = false)
    {
        $data = $this->data();
        return isset($data[$name]) ? $data[$name] : $default;
    }

}
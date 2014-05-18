<?php

/**
 * Description of Entity
 *
 * @license see /license.txt
 * @author Laurent Opprecht <laurent@opprecht.info> for the Univesity of Geneva
 */
class Entity
{

    /**
     *
     * @return \Entity\Course 
     */
    public static function current_course()
    {
        static $result = false;
        if ($result === false) {
            $repo = \Entity\Course::repository();
            $course_id = api_get_course_int_id();
            if ($course_id) {
                $result = $repo->find($course_id);
            }
        }
        return $result;
    }

    /**
     *
     * @return \Entity\Session  
     */
    public static function current_session()
    {
        static $result = false;
        if ($result === false) {
            $repo = \Entity\Session::repository();
            $session_id = api_get_session_id();
            $result = $repo->find($session_id);
        }
        return $result;
    }

    function __construct($data = null)
    {
        if ($data) {
            foreach ($this as $key => $value) {
                if (isset($data->{$key})) {
                    $this->{$key} = $data->{$key};
                }
            }
        }
        $this->defaults('session_id', api_get_session_id());
    }

    function __get($name)
    {
        $f = array($this, "get_$name");
        return call_user_func($f);
    }

    function __isset($name)
    {
        $f = array($this, "get_$name");
        return is_callable($f);
    }

    function __set($name, $value)
    {
        $f = array($this, "set_$name");
        if (!is_callable($f)) {
            return;
        }
        call_user_func($f, $value);
    }

    function before_save()
    {
        $repo = $this->repository();
        $field = $repo->get_id_field();
        if (empty($field)) {
            return;
        }

        $value = isset($this->{$field}) ? $this->{$field} : null;
        if ($value) {
            return;
        }
        $next_id = $repo->next_id($this);
        $this->{$field} = $next_id;
    }

    function defaults($name, $value)
    {
        if (property_exists($this, $name) && empty($this->{$name})) {
            $this->{$name} = $value;
        }
    }

}
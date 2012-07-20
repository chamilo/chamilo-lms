<?php

namespace CourseDescription;

/**
 * Course description types. A course description is used to distinguish between
 * different types of course description. It is essentialy made of:
 * 
 *      - title 
 *      - icon 
 *      - questions that are displayed on the screen to the end user when creating a new course description
 * 
 * Usage:
 * 
 *      $type = CourseDescriptionType::instance()->find_one_by_id($id);
 *      $description = $type->create();
 * 
 * @author Laurent Opprecht <laurent@opprecht.info> for the University of Geneva
 * @licence /license.txt
 */
class CourseDescriptionType
{
    
    /**
     * Return the instance of the controller.
     * 
     * @return \CourseDescription\CourseDescriptionTypeRepository 
     */
    public static function repository()
    {
        return CourseDescriptionTypeRepository::instance();
    }
    
    protected $id = 0;
    protected $name = '';
    protected $title = '';
    protected $info = '';
    protected $question = '';
    protected $icon = '';
    protected $is_editable = true;
    protected $content = '';

    function __construct($data = null)
    {
        if ($data) {
            foreach ($this as $key => $value) {
                if (isset($data->{$key})) {
                    $this->{$key} = $data->{$key};
                }
            }
        }
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

    /**
     * Object's id
     * 
     * @return int
     */
    public function get_id()
    {
        return $this->id;
    }

    /**
     * @return void
     */
    public function set_id($value)
    {
        $this->id = $value;
    }
    
    /**
     * Object's name. That is a human readable identifier.
     * 
     * @return string
     */
    public function get_name()
    {
        return $this->name;
    }

    /**
     * @return void
     */
    public function set_name($value)
    {
        $this->name = $value;
    }

    /**
     * Title, human readable text that identify the type.
     * 
     * @return string
     */
    public function get_title()
    {
        return $this->title;
    }

    /**
     * @return void
     */
    public function set_title($value)
    {
        $this->title = $value;
    }

    /**
     * Not used.
     * 
     * @return string
     */
    public function get_info()
    {
        return $this->info;
    }

    /**
     * @return void
     */
    public function set_info($value)
    {
        $this->info = $value;
    }

    /**
     * Text displayed to the end user to help him create the course description
     * 
     * @return string
     */
    public function get_question()
    {
        return $this->question;
    }

    /**
     * @return void
     */
    public function set_question($value)
    {
        $this->question = $value;
    }

    /**
     * Name of the icon file located in /main/img/icon/{size}/.
     * 
     * @see /main/img/icon/{size}/
     * @return string
     */
    public function get_icon()
    {
        return $this->icon;
    }

    /**
     * @return void
     */
    public function set_icon($value)
    {
        $this->icon = $value;
    }

    /**
     * Not used.
     * 
     * @return string
     */
    public function is_editable()
    {
        return $this->is_editable;
    }

    /**
     * @return void
     */
    public function set_editable($value)
    {
        $this->is_editable = $value;
    }

    /**
     * Not used. Intenteded to be the default content for new course descriptions.
     * @return string
     */
    public function get_content()
    {
        return $this->content;
    }

    /**
     * @return void
     */
    public function set_content($content)
    {
        $this->content = $content;
    }

    /**
     * Create a new course description object.
     * 
     * @return \CourseDescription 
     */
    public function create()
    {
        $result = new CourseDescription();
        $result->set_description_type($this->get_id());
        return $result;
    }

}

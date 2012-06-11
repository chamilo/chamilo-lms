<?php

namespace Model;

use Database;
use ResultSet;

/**
 * Represent a database "student_publication" object. 
 * 
 * Note: 
 * 
 * Each database column is mapped to a property.
 * 
 * The item_property table is available through its own property but is loaded
 * alongside document data.
 * 
 * Some db query functions exists in this class and would need to be adapted
 * to Symphony once it is moved to production. Yet the object structure should
 * stay. 
 * 
 * @see \Model\ItemProperty
 * @see table c_student_publication
 * @license see /license.txt
 * @author Laurent Opprecht <laurent@opprecht.info> for the Univesity of Geneva
 */
class StudentPublication
{
    
    public static function void()
    {
        static $result = null;
        if($result)
        {
            return $result;
        }
        
        $result = new self();
        return $result;
    }
    
    

    /**
     *
     * @param string $where
     * @return \ResultSet 
     */
    public static function query($where)
    {
        $table_item_property = Database::get_course_table(TABLE_ITEM_PROPERTY);
        $table = Database::get_course_table(TABLE_STUDENT_PUBLICATION);
        $tool = 'work';

        $sql = "SELECT pub.*,         
                       prop.id AS property_id, 
                       prop.tool, 
                       prop.insert_user_id,
                       prop.insert_date,
                       prop.lastedit_date,
                       prop.ref,
                       prop.lastedit_type,
                       prop.lastedit_user_id,
                       prop.to_group_id, 
                       prop.to_user_id, 
                       prop.visibility, 
                       prop.start_visible, 
                       prop.end_visible, 
                       prop.id_session
                FROM 
                    $table AS pub, 
                    $table_item_property AS prop
                WHERE 
                    (pub.id = prop.ref AND
                     pub.c_id = prop.c_id AND
                     prop.tool = '$tool')";

        $sql .= $where ? "AND ($where)" : '';
        $result = new ResultSet($sql);
        return $result->return_type(__CLASS__);
    }

    /**
     *
     * @param int|Course $c_id
     * @param int $id
     * @return \Model\StudentPublication 
     */
    public static function get_by_id($c_id, $id)
    {
        $c_id = is_object($c_id) ? $c_id->get_id() : (int) $c_id;
        return self::query("pub.c_id = $c_id AND pub.id = $id")->first();
    }

    protected $c_id = 0;
    protected $id = 0;
    protected $url = '';
    protected $title = '';
    protected $description = '';
    protected $author = '';
    protected $active = null;
    protected $accepted = false;
    protected $post_group_id = 0;
    protected $sent_date = 0;
    protected $filetype = '';
    protected $has_properties = 0;
    protected $view_properties = null;
    protected $qualification = 0;
    protected $date_of_qualification = 0;
    protected $parent_id = 0;
    protected $qualificator_id = 0;
    protected $weight = 0;
    protected $session_id = 0;
    protected $user_id = null;
    protected $allow_text_assignment = 0;
    protected $contains_file = 0;
    protected $course = null;
    protected $item_property = null;

    public function __construct($data)
    {
        $data = (object) $data;
        $this->c_id = (int) $data->c_id;
        $this->id = (int) $data->id;
        $this->url = $data->url;
        $this->title = $data->title;
        $this->description = $data->description;
        $this->author = $data->author;
        $this->active = $data->active;
        $this->accepted = $data->accepted;
        $this->post_group_id = $data->post_group_id;
        $this->sent_date = $data->sent_date;
        $this->filetype = $data->filetype;
        $this->has_properties = $data->has_properties;
        $this->view_properties = $data->view_properties;
        $this->qualification = $data->qualification;
        $this->date_of_qualification = $data->date_of_qualification;
        $this->parent_id = $data->parent_id;
        $this->qualificator_id = $data->qualificator_id;
        $this->weight = $data->weight;
        $this->session_id = $data->session_id;
        $this->user_id = $data->user_id;
        $this->allow_text_assignment = $data->allow_text_assignment;
        $this->contains_file = $data->contains_file;
        $this->course = $data->course;
        $this->item_property = $data->item_property;

        $this->course = null;

        if (isset($data->property_id)) {
            $property = (array) $data;
            $property = (object) $property;
            $property->id = $property->property_id;
            $this->item_property = ItemProperty::create($property);
        } else {
            $this->item_property = null;
        }
    }
    


    public function get_c_id()
    {
        return $this->c_id;
    }

    public function set_c_id($value)
    {
        $this->c_id = $value;
    }

    public function get_id()
    {
        return $this->id;
    }

    public function set_id($value)
    {
        $this->id = $value;
    }

    public function get_url()
    {
        return $this->url;
    }

    public function set_url($value)
    {
        $this->url = $value;
    }

    public function get_title()
    {
        return $this->title;
    }

    public function set_title($value)
    {
        $this->title = $value;
    }

    public function get_description()
    {
        return $this->description;
    }

    public function set_description($value)
    {
        $this->description = $value;
    }

    public function get_author()
    {
        return $this->author;
    }

    public function set_author($value)
    {
        $this->author = $value;
    }

    public function get_active()
    {
        return $this->active;
    }

    public function set_active($value)
    {
        $this->active = $value;
    }

    public function get_accepted()
    {
        return $this->accepted;
    }

    public function set_accepted($value)
    {
        $this->accepted = $value;
    }

    public function get_post_group_id()
    {
        return $this->post_group_id;
    }

    public function set_post_group_id($value)
    {
        $this->post_group_id = $value;
    }

    public function get_sent_date()
    {
        return $this->sent_date;
    }

    public function set_sent_date($value)
    {
        $this->sent_date = $value;
    }

    public function get_filetype()
    {
        return $this->filetype;
    }

    public function set_filetype($value)
    {
        $this->filetype = $value;
    }

    public function get_has_properties()
    {
        return $this->has_properties;
    }

    public function set_has_properties($value)
    {
        $this->has_properties = $value;
    }

    public function get_view_properties()
    {
        return $this->view_properties;
    }

    public function set_view_properties($value)
    {
        $this->view_properties = $value;
    }

    public function get_qualification()
    {
        return $this->qualification;
    }

    public function set_qualification($value)
    {
        $this->qualification = $value;
    }

    public function get_date_of_qualification()
    {
        return $this->date_of_qualification;
    }

    public function set_date_of_qualification($value)
    {
        $this->date_of_qualification = $value;
    }

    public function get_parent_id()
    {
        return $this->parent_id;
    }

    public function set_parent_id($value)
    {
        $this->parent_id = $value;
    }

    public function get_qualificator_id()
    {
        return $this->qualificator_id;
    }

    public function set_qualificator_id($value)
    {
        $this->qualificator_id = $value;
    }

    public function get_weight()
    {
        return $this->weight;
    }

    public function set_weight($value)
    {
        $this->weight = $value;
    }

    public function get_session_id()
    {
        return $this->session_id;
    }

    public function set_session_id($value)
    {
        $this->session_id = $value;
    }

    public function get_user_id()
    {
        return $this->user_id;
    }

    public function set_user_id($value)
    {
        $this->user_id = $value;
    }

    public function get_allow_text_assignment()
    {
        return $this->allow_text_assignment;
    }

    public function set_allow_text_assignment($value)
    {
        $this->allow_text_assignment = $value;
    }

    public function get_contains_file()
    {
        return $this->contains_file;
    }

    public function set_contains_file($value)
    {
        $this->contains_file = $value;
    }

    public function is_folder()
    {
        return $this->filetype == 'folder';
    }

    public function is_file()
    {
        return $this->filetype == 'file';
    }

    public function is_visible()
    {
        $this->get_item_property()->get_visibility() == 1;
    }

    public function is_accessible($user = null)
    {
        $user_id = $user ? $user : api_get_user_id();
        $result = $this->is_visible() || $this->get_user_id() == $user_id || api_is_allowed_to_edit();
        return $result;
    }

    public function get_absolute_path()
    {
        return api_get_path(SYS_COURSE_PATH) . api_get_course_path() . '/' . $this->get_url();
    }

    /**
     *
     * @return \Model\ItemProperty
     */
    public function get_item_property()
    {
        if ($this->item_property && $this->item_property->get_c_id() == $this->c_id && $this->item_property->get_ref() == $this->id) {
            return $this->item_property;
        }

        $this->item_property = ItemProperty::get_by_ref($this->id, TOOL_DOCUMENT);
        return $this->item_property;
    }

    /**
     *
     * @param bool $all
     * @return ResultSet|array
     */
    public function get_children()
    {
        if (!$this->is_folder()) {
            return array();
        }
        $id = $this->id;
        $c_id = $this->c_id;
        $where = "pub.c_id = $c_id AND pub.parent_id = $id";
        return self::query($where);
    }
    
}
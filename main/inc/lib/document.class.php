<?php

namespace Model;

use Database;
use ResultSet;

/**
 * Represent a database "document" object - i.e. a file or a folder. 
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
 * @see table c_document
 * @license see /license.txt
 * @author Laurent Opprecht <laurent@opprecht.info> for the Univesity of Geneva
 */
class Document
{

    /**
     *
     * @param string $where
     * @return \ResultSet 
     */
    public static function query($where)
    {
        $table_item_property = Database::get_course_table(TABLE_ITEM_PROPERTY);
        $table_document = Database::get_course_table(TABLE_DOCUMENT);
        $tool = TOOL_DOCUMENT;

        $sql = "SELECT doc.*,         
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
                    $table_document AS doc, 
                    $table_item_property AS prop
                WHERE 
                    (doc.id = prop.ref AND
                     doc.c_id = prop.c_id AND
                     prop.tool = '$tool')";

        $sql .= $where ? "AND ($where)" : '';
        $result = new ResultSet($sql);
        return $result->return_type(__CLASS__);
    }

    /**
     *
     * @param int|Course $c_id
     * @param int $id
     * @return \Model\Document 
     */
    public static function get_by_id($c_id, $id)
    {
        $c_id = is_object($c_id) ? $c_id->get_id() : (int) $c_id;
        return self::query("doc.c_id = $c_id AND doc.id = $id")->first();
    }

    /**
     *
     * @param int|Course $c_id
     * @param int $id
     * @return \Model\Document 
     */
    public static function get_by_path($c_id, $path)
    {
        $c_id = is_object($c_id) ? $c_id->get_id() : (int) $c_id;
        return self::query("doc.c_id = $c_id AND doc.path = '$path'")->first();
    }

    protected $c_id = 0;
    protected $id = 0;
    protected $path = '';
    protected $comment = '';
    protected $title = '';
    protected $filetype = '';
    protected $size = 0;
    protected $readonly = false;
    protected $session_id = 0;
    protected $course = null;
    protected $item_property = null;

    public function __construct($data)
    {
        $data = (object) $data;
        $this->c_id = (int) $data->c_id;
        $this->id = (int) $data->id;
        $this->path = $data->path;
        $this->comment = $data->comment;
        $this->title = $data->title;
        $this->filetype = $data->filetype;
        $this->size = (int) $data->size;
        $this->readonly = (bool) $data->readonly;
        $this->session_id = (int) $data->session_id;

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
        $this->c_id = (int) $value;
        $this->course = null;
        $this->item_property = null;
    }

    public function get_id()
    {
        return $this->id;
    }

    public function set_id($value)
    {
        $this->id = (int) $value;
        $this->item_property = null;
    }

    public function get_path()
    {
        return $this->path;
    }

    public function set_path($value)
    {
        $this->path = $value;
    }

    public function get_comment()
    {
        return $this->comment;
    }

    public function set_comment($value)
    {
        $this->comment = $value;
    }

    public function get_title()
    {
        return $this->title;
    }

    public function set_title($value)
    {
        $this->title = $value;
    }

    public function get_filetype()
    {
        return $this->filetype;
    }

    public function set_filetype($value)
    {
        $this->filetype = $value;
    }

    public function get_size()
    {
        return $this->size;
    }

    public function set_size($value)
    {
        $this->size = (int) $value;
    }

    public function get_readonly()
    {
        return $this->readonly;
    }

    public function set_readonly($value)
    {
        $this->readonly = (bool) $value;
    }

    public function get_session_id()
    {
        return $this->session_id;
    }

    public function set_session_id($value)
    {
        $this->session_id = (int) $value;
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

    public function is_accessible()
    {
        return api_is_allowed_to_edit() || $this->is_visible();
    }

    /**
     *
     * @return \Model\Course
     */
    public function get_course()
    {
        if ($this->course && $this->course->get_id() == $this->c_id) {
            return $this->course;
        }

        $this->course = Course::get_by_id($this->c_id);
        return $this->course;
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

    public function get_absolute_path()
    {
        $course = $this->get_course();
        return $course->get_path() . 'document' . $this->path;
    }

    public function __toString()
    {
        return $this->get_absolute_path();
    }

    /**
     *
     * @param bool $all
     * @return ResultSet|array
     */
    public function get_children($all = false)
    {
        if (!$this->is_folder()) {
            return array();
        }
        $path = $this->path;
        $c_id = $this->c_id;
        if ($this->all) {
            $where = "doc.c_id = $c_id AND doc.path LIKE '$path/%'";
        } else {
            $where = "doc.c_id = $c_id AND doc.path LIKE '$path/%' AND doc.path NOT LIKE '$path/%/%'";
        }
        return self::query($where);
    }

}
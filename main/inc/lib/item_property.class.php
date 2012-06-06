<?php

namespace Model;
use ResultSet;

use Database;

/**
 * Represent a database "item_property" object - i.e. common properties for tool
 * objects: created date, modified date, etc. 
 * 
 * Note: 
 * 
 * Each database column is mapped to a property.
 * 
 * 
 * Some db query functions exists in this class and would need to be adapted
 * to Sympony once it is moved to production. Yet the object structure should
 * stay. 
 * 
 * @see \Model\ItemProperty
 * @see table c_item_property
 * @license see /license.txt
 * @author Laurent Opprecht <laurent@opprecht.info> for the Univesity of Geneva
 */
class ItemProperty
{

    /**
     *
     * @param string $where
     * @return \ResultSet 
     */
    public static function query($where)
    {
        $table = Database::get_course_table(TABLE_ITEM_PROPERTY);
        $sql = "SELECT * FROM $table ";
        $sql .= $where ? "WHERE $where" : '';
        $result = new ResultSet($sql);
        return $result->return_type(__CLASS__);
    }

    /**
     *
     * @param id $ref
     * @param string $tool
     * @return \Model\ItemProperty 
     */
    public static function get_by_ref($ref, $tool)
    {
        return self::query("ref=$ref AND tool = '$tool'")->first();
    }

    /**
     *
     * @param array|object $data
     * @return \Model\ItemProperty
     */
    static function create($data)
    {
        return new self($data);
    }

    protected $c_id = 0;
    protected $id = 0;
    protected $tool = '';
    protected $insert_user_id = 0;
    protected $insert_date = 0;
    protected $lastedit_date = 0;
    protected $ref = '';
    protected $lastedit_type = '';
    protected $lastedit_user_id = 0;
    protected $to_group_id = null;
    protected $to_user_id = null;
    protected $visibility = 1;
    protected $start_visible = 0;
    protected $end_visible = 0;
    protected $id_session = 0;

    public function __construct($data)
    {
        $data = (object) $data;
        $this->c_id = $data->c_id;
        $this->id = $data->id;
        $this->tool = $data->tool;
        $this->insert_user_id = $data->insert_user_id;
        $this->insert_date = $data->insert_date;
        $this->lastedit_date = $data->lastedit_date;
        $this->lastedit_date = $data->lastedit_date;
        $this->ref = $data->ref;
        $this->lastedit_type = $data->lastedit_type;
        $this->lastedit_user_id = $data->lastedit_user_id;
        $this->to_group_id = $data->to_group_id;
        $this->to_user_id = $data->to_user_id;
        $this->visibility = $data->visibility;
        $this->start_visible = $data->start_visible;
        $this->end_visible = $data->end_visible;
        $this->id_session = $data->id_session;
    }

    public function get_c_id()
    {
        return $this->c_id;
    }

    public function set_c_id($value)
    {
        $this->c_id = (int) $value;
    }

    public function get_id()
    {
        return $this->id;
    }

    public function set_id($value)
    {
        $this->id = (int) $value;
    }

    public function get_tool()
    {
        return $this->tool;
    }

    public function set_tool($value)
    {
        $this->tool = $value;
    }

    public function get_insert_user_id()
    {
        return $this->insert_user_id;
    }

    public function set_insert_user_id($value)
    {
        $this->insert_user_id = $value;
    }

    public function get_insert_date()
    {
        return $this->insert_date;
    }

    public function set_insert_date($value)
    {
        $this->insert_date = $value;
    }

    public function get_lastedit_date()
    {
        return $this->lastedit_date;
    }

    public function set_lastedit_date($value)
    {
        $this->lastedit_date = $value;
    }

    public function get_ref()
    {
        return $this->ref;
    }

    public function set_ref($value)
    {
        $this->ref = $value;
    }

    public function get_lastedit_type()
    {
        return $this->lastedit_type;
    }

    public function set_lastedit_type($value)
    {
        $this->lastedit_type = $value;
    }

    public function get_lastedit_user_id()
    {
        return $this->lastedit_user_id;
    }

    public function set_lastedit_user_id($value)
    {
        $this->lastedit_user_id = $value;
    }

    public function get_to_group_id()
    {
        return $this->to_group_id;
    }

    public function set_to_group_id($value)
    {
        $this->to_group_id = $value;
    }

    public function get_to_user_id()
    {
        return $this->to_user_id;
    }

    public function set_to_user_id($value)
    {
        $this->to_user_id = $value;
    }

    public function get_visibility()
    {
        return $this->visibility;
    }

    public function set_visibility($value)
    {
        $this->visibility = $value;
    }

    public function get_start_visible()
    {
        return $this->start_visible;
    }

    public function set_start_visible($value)
    {
        $this->start_visible = $value;
    }

    public function get_end_visible()
    {
        return $this->end_visible;
    }

    public function set_end_visible($value)
    {
        $this->end_visible = $value;
    }

    public function get_id_session()
    {
        return $this->id_session;
    }

    public function set_id_session($value)
    {
        $this->id_session = $value;
    }

}
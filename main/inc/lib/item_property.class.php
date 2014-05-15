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

    const VISIBILITY_INVISIBLE = 0;
    const VISIBILITY_VISIBLE = 1;
    const VISIBILITY_DELETED = 2;

    /**
     *
     * @return ItemPropertyRepository 
     */
    public static function repository()
    {
        return ItemPropertyRepository::instance();
    }

    /**
     *
     * @param string $where
     * @return \ResultSet 
     */
    public static function query($where)
    {
        return self::repository()->query($where);
    }

    /**
     *
     * @param id $ref
     * @param string $tool
     * @return \Model\ItemProperty 
     */
    public static function get_by_ref($ref, $tool)
    {
        return self::repository()->get_by_ref($ref, $tool);
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
    protected $visibility = self::VISIBILITY_VISIBLE;
    protected $start_visible = 0;
    protected $end_visible = 0;
    protected $id_session = 0;

    public function __construct($data)
    {
        $data = (object) $data;
        $this->c_id = (int) $data->c_id;
        $this->id = (int) $data->id;
        $this->tool = $data->tool;
        $this->insert_user_id = (int) $data->insert_user_id;
        $this->insert_date = is_numeric($data->insert_date) ? $data->insert_date : strtotime($data->insert_date);
        $this->lastedit_date = is_numeric($data->lastedit_date) ? $data->lastedit_date : strtotime($data->lastedit_date);
        $this->ref = (int) $data->ref;
        $this->lastedit_type = $data->lastedit_type;
        $this->lastedit_user_id = (int) $data->lastedit_user_id;
        $this->to_group_id = (int) $data->to_group_id;
        $this->to_user_id = (int) $data->to_user_id;
        $this->visibility = (int) $data->visibility;
        $this->start_visible = is_numeric($data->start_visible) ? $data->start_visible : strtotime($data->start_visible);
        $this->end_visible = is_numeric($data->end_visible) ? $data->end_visible : strtotime($data->end_visible);
        $this->id_session = $data->id_session;
    }

    /**
     *
     * @return int
     */
    public function get_c_id()
    {
        return $this->c_id;
    }

    public function set_c_id($value)
    {
        $this->c_id = (int) $value;
    }

    /**
     *
     * @return int
     */
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

    /**
     *
     * @return int
     */
    public function get_insert_user_id()
    {
        return $this->insert_user_id;
    }

    public function set_insert_user_id($value)
    {
        $this->insert_user_id = $value;
    }

    /**
     *
     * @return int
     */
    public function get_insert_date()
    {
        return $this->insert_date;
    }

    public function set_insert_date($value)
    {
        $value = is_numeric($value) ? $value : strtotime($value);
        $this->insert_date = $value;
    }

    /**
     *
     * @return int
     */
    public function get_lastedit_date()
    {
        return $this->lastedit_date;
    }

    public function set_lastedit_date($value)
    {
        $value = is_numeric($value) ? $value : strtotime($value);
        $this->lastedit_date = $value;
    }

    /**
     *
     * @return int
     */
    public function get_ref()
    {
        return $this->ref;
    }

    public function set_ref($value)
    {
        $this->ref = $value;
    }

    /**
     *
     * @return string
     */
    public function get_lastedit_type()
    {
        return $this->lastedit_type;
    }

    public function set_lastedit_type($value)
    {
        $this->lastedit_type = $value;
    }

    /**
     *
     * @return int 
     */
    public function get_lastedit_user_id()
    {
        return $this->lastedit_user_id;
    }

    public function set_lastedit_user_id($value)
    {
        $this->lastedit_user_id = $value;
    }

    /**
     *
     * @return int
     */
    public function get_to_group_id()
    {
        return $this->to_group_id;
    }

    public function set_to_group_id($value)
    {
        $this->to_group_id = $value;
    }

    /**
     *
     * @return int
     */
    public function get_to_user_id()
    {
        return $this->to_user_id;
    }

    public function set_to_user_id($value)
    {
        $this->to_user_id = $value;
    }

    /**
     *
     * @return int
     */
    public function get_visibility()
    {
        return $this->visibility;
    }

    public function set_visibility($value)
    {
        $this->visibility = $value;
    }

    /**
     *
     * @return int
     */
    public function get_start_visible()
    {
        return $this->start_visible;
    }

    public function set_start_visible($value)
    {
        $value = is_numeric($value) ? $value : strtotime($value);
        $this->start_visible = $value;
    }

    /**
     *
     * @return int
     */
    public function get_end_visible()
    {
        return $this->end_visible;
    }

    public function set_end_visible($value)
    {
        $value = is_numeric($value) ? $value : strtotime($value);
        $this->end_visible = $value;
    }

    /**
     *
     * @return int
     */
    public function get_id_session()
    {
        return $this->id_session;
    }

    public function set_id_session($value)
    {
        $this->id_session = $value;
    }

    public function mark_deleted()
    {
        $this->set_visibility(self::VISIBILITY_DELETED);

        $tool = $this->get_tool();
        $lastedit_type = str_replace('_', '', ucwords($tool)) . 'Deleted';
        $this->set_lastedit_type($lastedit_type);

        $user_id = api_get_user_id();
        $this->set_insert_user_id($user_id);
    }

    public function mark_visible()
    {
        $this->set_visibility(self::VISIBILITY_VISIBLE);
        $tool = $this->get_tool();

        $lastedit_type = str_replace('_', '', ucwords($tool)) . 'Visible';
        $this->set_lastedit_type($lastedit_type);

        $user_id = api_get_user_id();
        $this->set_insert_user_id($user_id);
    }

    public function mark_invisible()
    {
        $this->set_visibility(self::VISIBILITY_INVISIBLE);
        $tool = $this->get_tool();

        $lastedit_type = str_replace('_', '', ucwords($tool)) . 'Invisible';
        $this->set_lastedit_type($lastedit_type);

        $user_id = api_get_user_id();
        $this->set_insert_user_id($user_id);
    }

}

/**
 * 
 */
class ItemPropertyRepository
{

    /**
     *
     * @return \Model\ItemPropertyRepository
     */
    public static function instance()
    {
        static $result = null;
        if (empty($result)) {
            $result = new self();
        }
        return $result;
    }

    /**
     *
     * @param string $where
     * @return \ResultSet 
     */
    public function query($where)
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
    public function get_by_ref($ref, $tool)
    {
        return $this->query("ref=$ref AND tool = '$tool'")->first();
    }

    /**
     *
     * @param ItemProperty $item 
     */
    function save($item)
    {
        if ($this->exists($item)) {
            $this->update($item);
        } else {
            $this->insert($item);
        }
    }

    /**
     * Returns true if item is not new, false otherwise.
     * 
     * @param ItemProperty $item 
     * @return bool 
     */
    public function exists($item)
    {
        $id = $item->get_id();
        $c_id = $item->get_c_id();
        return !empty($id) && !empty($c_id);
    }

    /**
     *
     * @param ItemProperty $item
     * @return bool 
     */
    public function insert($item)
    {
        $this->defaults($item);
        $user_id = api_get_user_id();
        $item->set_insert_user_id($user_id);

        $c_id = $item->get_c_id();
        $id = $item->get_id();
        $tool = Database::escape_string($item->get_tool());
        $insert_user_id = $item->get_insert_user_id();
        $insert_date = api_get_utc_datetime($item->get_insert_date());
        $lastedit_date = api_get_utc_datetime($item->get_lastedit_date());
        $ref = $item->get_ref();
        $lastedit_type = Database::escape_string($item->get_lastedit_type());
        $last_edit_user_id = $item->get_lastedit_user_id();

        $to_group_id = $item->get_to_group_id();
        $to_group_id = empty($to_group_id) ? '0' : $to_group_id;

        $to_user_id = $item->get_to_user_id();
        $to_user_id = empty($to_user_id) ? '0' : $to_user_id;

        $visibility = $item->get_visibility();
        $visibility = $visibility ? $visibility : '0';

        $start_visible = $item->get_start_visible();
        $start_visible = empty($start_visible) ? '0000-00-00 00:00:00' : api_get_utc_datetime($start_visible);

        $end_visible = $item->get_end_visible();
        $end_visible = empty($end_visible) ? '0000-00-00 00:00:00' : api_get_utc_datetime($end_visible);

        $session_id = $item->get_id_session();

        $TABLE = Database::get_course_table(TABLE_ITEM_PROPERTY);


        $sql = "INSERT INTO $TABLE_ITEMPROPERTY (
                    c_id,
                    tool,
                    insert_user_id,
                    insert_date,
                    lastedit_date,
                    ref,
                    lastedit_type,   
                    lastedit_user_id, 
                    to_group_id,  
                    to_user_id,
                    visibility,   
                    start_visible,   
                    end_visible, 
                    id_session
                ) VALUES (
                    $c_id, 
                    '$tool', 
                    $insert_user_id,
                    '$insert_date',
                    '$lastedit_date',
                    $ref, 
                    '$lastedit_type', 
                    $last_edit_user_id, 
                    $to_group_id, 
                    $to_user_id, 
                    $visibility, 
                    '$start_visible', 
                    '$end_visible', 
                    '$session_id'
                )";

        $result = Database::query($sql);
        $id = Database::insert_id();
        if ($id) {
            $item->set_id($id);
        }
        return (bool) $result;
    }

    /**
     *
     * @param ItemProperty $item 
     */
    public function update($item)
    {
        $this->defaults($item);
        $user_id = api_get_user_id();
        $item->set_insert_user_id($user_id);

        $c_id = $item->get_c_id();
        $id = $item->get_id();
        //$tool = Database::escape_string($item->get_tool());
        //$insert_user_id = $item->get_insert_user_id();
        //$insert_date = api_get_utc_datetime($item->get_insert_date());
        $lastedit_date = api_get_utc_datetime($item->get_lastedit_date());
        //$ref = $item->get_ref();
        $lastedit_type = Database::escape_string($item->get_lastedit_type());
        $last_edit_user_id = $item->get_lastedit_user_id();

        $to_group_id = $item->get_to_group_id();
        $to_group_id = empty($to_group_id) ? '0' : $to_group_id;

        $to_user_id = $item->get_to_user_id();
        $to_user_id = empty($to_user_id) ? '0' : $to_user_id;

        $visibility = $item->get_visibility();
        $visibility = $visibility ? $visibility : '0';

        $start_visible = $item->get_start_visible();
        $start_visible = empty($start_visible) ? '0000-00-00 00:00:00' : api_get_utc_datetime($start_visible);

        $end_visible = $item->get_end_visible();
        $end_visible = empty($end_visible) ? '0000-00-00 00:00:00' : api_get_utc_datetime($end_visible);

        $session_id = $item->get_id_session();


        $TABLE = Database::get_course_table(TABLE_ITEM_PROPERTY);
        $sql = "UPDATE 
                    $TABLE
                SET 
                    lastedit_date		= '$lastedit_date',
                    lastedit_type		= '$lastedit_type',
                    lastedit_user_id	= $last_edit_user_id,
                    to_group_id         = $to_group_id,
                    to_user_id          = $to_user_id,
                    visibility			= $visibility,
                    start_visible       = '$start_visible',
                    end_visible         = '$end_visible',
                    id_session 			= $session_id
                WHERE 
                    c_id =  $c_id AND
                    id = $id";

        $result = Database::query($sql);
        return (bool) $result;
    }

    /**
     *
     * @param ItemProperty $item 
     */
    function defaults($item)
    {
        $now = time();
        $user = api_get_user_id();

        $value = $item->get_insert_user_id();
        if (empty($value)) {
            $item->set_insert_user_id($user);
        }

        $value = get_insert_date();
        if (empty($value)) {
            $item->set_insert_date($now);
        }

        $value = get_lastedit_date();
        if (empty($value)) {
            $item->set_lastedit_date($now);
        }

        $value = $item->get_lastedit_user_id();
        if (empty($value)) {
            $item->set_insert_user_id($user);
        }

        $value = $item->get_id_session();
        if (empty($value)) {
            $value = api_get_session_id();
            $item->set_session_id($value);
        }
    }

}
<?php

namespace Model;

use Database;
use ResultSet;

/**
 * Description of course
 *
 * @license see /license.txt
 * @author Laurent Opprecht <laurent@opprecht.info> for the Univesity of Geneva
 */
class Course
{

    /**
     *
     * @param string $where
     * @return \ResultSet 
     */
    public static function query($where)
    {
        $table = Database::get_main_table(TABLE_MAIN_COURSE);
        $sql = "SELECT * FROM $table ";
        $sql .= $where ? "WHERE $where" : '';
        $result = new ResultSet($sql);
        return $result->return_type(__CLASS__);
    }

    /**
     *
     * @param string $code
     * @return \Model\Course|null
     */
    public static function get_by_code($code)
    {
        $current = self::current();
        if ($current && $current->get_code() == $code) {
            return $current;
        }
        return self::query("code = '$code'")->first();
    }

    /**
     *
     * @param int $id
     * @return \Model\Course|null
     */
    public static function get_by_id($id)
    {
        $id = (int) $id;
        $current = self::current();
        if ($current && $current->get_id() == $id) {
            return $current;
        }
        return self::query("id = $id")->first();
    }

    /**
     * 
     * @return \Model\Course|null
     */
    public static function current()
    {
        global $_course;
        /**
         * Note that $_course = -1 when not set.
         */
        if (empty($_course) || !is_array($_course)) {
            return null;
        }

        static $result = null;
        if (empty($result)) {
            $id = $_course['real_id'];
            $result = self::query("id = $id")->first();
        }
        return $result;
    }

    protected $id = 0;
    protected $code = 0;
    protected $directory = '';
    protected $show_score = '';

    public function __construct($data)
    {
        $data = (object) $data;
        $this->id = $data->id;
        $this->code = $data->code;
        $this->directory = $data->directory;
        $this->show_score = $data->show_score;
    }

    public function get_id()
    {
        return $this->id;
    }

    public function set_id($value)
    {
        $this->id = (int) $value;
    }

    public function get_code()
    {
        return $this->code;
    }

    public function set_code($value)
    {
        $this->code = (int) $value;
    }

    public function get_directory()
    {
        return $this->directory;
    }

    public function set_directory($value)
    {
        $this->directory = (int) $value;
    }

    public function get_show_score()
    {
        return $this->show_score;
    }

    public function set_show_score($value)
    {
        $this->show_score = (int) $value;
    }

    public function get_path()
    {
        $dir = $this->directory;
        if (empty($dir)) {
            return '';
        }
        return api_get_path(SYS_COURSE_PATH) . $dir . '/';
    }

}
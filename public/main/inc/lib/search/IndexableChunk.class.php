<?php

/* For licensing terms, see /license.txt */

/**
 * Class _IndexableChunk.
 */
abstract class _IndexableChunk
{
    /* struct (array)
     * {
     *     string title; <- nombre de archivo/elemento
     *     string content; <- texto a indexar
     *     string ids;  <- los flags a guardar "cidReq:lp_id:path"
     * }
     */
    public $data;

    /**
     * array (
     * 'SE_COURSE_ID' => string <- course id from course table on main db
     * 'SE_TOOL_ID' => string <- tool id from mainapi lib constants
     * 'SE_DATA' => mixed <- extra information, depends on SE_TOOL_ID
     * 'SE_USER' => id <- user id from user table in main db
     * ).
     */
    public $xapian_data;

    /**
     * array(
     *   name => string
     *   flag => char
     * ).
     */
    public $terms;

    /**
     * Class constructor. Just generates an empty 'data' array attribute.
     */
    public function __construct()
    {
        $this->data = [];
    }

    /**
     * Class desctructor. Unsets attributes.
     */
    public function __destruct()
    {
        unset($this->data);
        unset($this->terms);
    }

    /**
     * Add a value to the indexed item.
     *
     * @param  string  Key
     * @param  string  Value
     */
    public function addValue($key, $value)
    {
        $this->data[$key] = $value;
    }

    /**
     * Add a term (like xapian definition).
     *
     * @param string Term
     * @param string Flag (one character)
     */
    public function addTerm($term, $flag)
    {
        global $charset;
        if (1 == strlen($flag)) {
            $this->terms[] = ['name' => api_convert_encoding(stripslashes($term), 'UTF-8', $charset), 'flag' => $flag];
        }
    }
}

/**
 * Extension of the _IndexableChunk class to make IndexableChunk extensible.
 */
class IndexableChunk extends _IndexableChunk
{
    /**
     * Let add course id term.
     */
    public function addCourseId($course_id)
    {
        $this->addTerm($course_id, XAPIAN_PREFIX_COURSEID);
    }

    /**
     * Let add tool id term.
     */
    public function addToolId($tool_id)
    {
        $this->addTerm($tool_id, XAPIAN_PREFIX_TOOLID);
    }
}

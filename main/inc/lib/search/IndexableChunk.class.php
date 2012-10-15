<?php

/* For licensing terms, see /license.txt */
/**
 * @package chamilo.include.search
 */
/**
 * Code
 */
// some constants to avoid serialize string keys on serialized data array
define('SE_COURSE_ID', 0);
define('SE_TOOL_ID', 1);
define('SE_DATA', 2);
define('SE_USER', 3);

// in some cases we need top differenciate xapian documents of the same tool
define('SE_DOCTYPE_EXERCISE_EXERCISE', 0);
define('SE_DOCTYPE_EXERCISE_QUESTION', 1);

// xapian prefixes
define('XAPIAN_PREFIX_COURSEID', 'C');
define('XAPIAN_PREFIX_TOOLID', 'O');

/**
 * Class
 * @package chamilo.include.search
 */
abstract class _IndexableChunk {
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
     * )
     */
    public $xapian_data;

    /**
     * array(
     *   name => string
     *   flag => char
     * )
     */
    public $terms;

    /**
     * Add a value to the indexed item
     * @param  string  Key
     * @param  string  Value
     * @return  void
     */
    function addValue($key, $value) {
        $this->data[$key] = $value;
    }

    /**
     * Add a term (like xapian definition)
     * @param string Term
     * @param string Flag (one character)
     */
    public function addTerm($term, $flag) {
        global $charset;
        if (strlen($flag) == 1) {
            $this->terms[] = array('name' => api_convert_encoding(stripslashes($term), 'UTF-8', $charset), 'flag' => $flag);
        }
    }

    /**
     * Class constructor. Just generates an empty 'data' array attribute
     */
    function __construct() {
        $this->data = array();
    }

    /**
     * Class desctructor. Unsets attributes.
     */
    function __destruct() {
        unset($this->data);
        unset($this->terms);
    }

}

/**
 * Extension of the _IndexableChunk class to make IndexableChunk extensible.
 * @package chamilo.include.search
 */
class IndexableChunk extends _IndexableChunk {

    /**
     * Let add course id term
     */
    public function addCourseId($course_id) {
        $this->addTerm($course_id, XAPIAN_PREFIX_COURSEID);
    }

    /**
     * Let add tool id term
     */
    public function addToolId($tool_id) {
        $this->addTerm($tool_id, XAPIAN_PREFIX_TOOLID);
    }

}

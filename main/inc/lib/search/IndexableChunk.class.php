<?php
abstract class _IndexableChunk
{

    /* int */
    protected $id;

    /* boolean  */
    public $parent;

    /* int  */
    public $parentId;

    /* struct (array)
     * {
     *     string title; <- nombre de archivo/elemento
     *     string content; <- texto a indexar
     *     string ids;  <- los flags a guardar "cidReq:lp_id:path"
     * }
     */
    public $data; 

    /**
     * array(
     *   name => string
     *   flag => char
     * )
     */
    public $terms;

	/**
	 * Add a value to the indexed item
	 * @param	string	Key
	 * @param	string	Value
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
    function addTerm($term, $flag) {
      if (strlen($flag) == 1) {
        $this->terms[] = array('name' => $term, 'flag' => $flag);
      }
    }

	/**
	 * Get the ID from an indexed item. In case data are in an array, get the second item of the 'ids' element of the array
	 * @return integer	ID
	 */
    function getId() {
        $id = -1;

        if (is_array($this->data)) {
            $ids = explode(':', $this->data['ids']);

            /* we need at least course_id and document_id, else it's broken */
            if (count($ids)) {
              $id = $ids[1];
            }
        }

        return $id;
    }

	/**
	 * Sets the parent of the current indexed item
	 * @param	mixed	A parent object
	 * @return void
	 */
    function setParent($parent) {
        if (is_a($parent, 'IndexableChunk')) {
            $this->parentId = $parent->getId();
            $this->parent = False;
        } else {
            $this->parentId = -1;
            $this->parent = True;
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
        unset($this->parent);
    }
}

/**
 * Extension of the _IndexableChunk class to make IndexableChunk extensible.
 */
class IndexableChunk extends _IndexableChunk 
{
}

?>
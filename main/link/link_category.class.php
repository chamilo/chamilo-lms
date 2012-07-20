<?php

namespace Link;

/**
 * Model for link_category. 
 * 
 * Links can be added to a category.
 * A link belong to at most one category.
 * A link may not belong to a category.
 * Categories cannot be nested, i.e. it is not possible to have categories inside a category.
 * 
 * 
 * @author Laurent Opprecht <laurent@opprecht.info> for the Univesity of Genevas
 * @license /license.txt
 */
class LinkCategory
{

    /**
     * @return \Link\LinkCategoryRepository
     */
    public static function repository()
    {
        return LinkCategoryRepository::instance();
    }

    /**
     * @return \Entity\LinkCategory
     */
    public static function create($data = null)
    {
        return new self($data);
    }

    /**
     * @var integer $c_id
     */
    protected $c_id;

    /**
     * @var integer $id
     */
    protected $id;

    /**
     * @var string $category_title
     */
    protected $category_title;

    /**
     * @var text $description
     */
    protected $description;

    /**
     * @var integer $display_order
     */
    protected $display_order;

    /**
     * @var integer $session_id
     */
    protected $session_id;
    protected $links = null;

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
     * Set c_id
     *
     * @param integer $value
     * @return LinkCategory
     */
    public function set_c_id($value)
    {
        $this->c_id = $value;
        return $this;
    }

    /**
     * Get c_id
     *
     * @return integer 
     */
    public function get_c_id()
    {
        return $this->c_id;
    }

    /**
     * Set id
     *
     * @param integer $value
     * @return LinkCategory
     */
    public function set_id($value)
    {
        $this->id = $value;
        return $this;
    }

    /**
     * Get id
     *
     * @return integer 
     */
    public function get_id()
    {
        return $this->id;
    }

    /**
     * Set category_title
     *
     * @param string $value
     * @return LinkCategory
     */
    public function set_category_title($value)
    {
        $value = trim($value);
        $this->category_title = $value;
        return $this;
    }

    /**
     * Get category_title
     *
     * @return string 
     */
    public function get_category_title()
    {
        return $this->category_title;
    }

    /**
     * Set description
     *
     * @param text $value
     * @return LinkCategory
     */
    public function set_description($value)
    {
        $this->description = $value;
        return $this;
    }

    /**
     * Get description
     *
     * @return text 
     */
    public function get_description()
    {
        return $this->description;
    }

    /**
     * Set display_order
     *
     * @param integer $value
     * @return LinkCategory
     */
    public function set_display_order($value)
    {
        $this->display_order = $value;
        return $this;
    }

    /**
     * Get display_order
     *
     * @return integer 
     */
    public function get_display_order()
    {
        return $this->display_order;
    }

    /**
     * Set session_id
     *
     * @param integer $value
     * @return LinkCategory
     */
    public function set_session_id($value)
    {
        $this->session_id = $value;
        return $this;
    }

    /**
     * Get session_id
     *
     * @return integer 
     */
    public function get_session_id()
    {
        return $this->session_id;
    }

    public function get_links()
    {
        if (is_null($this->links)) {
            $this->links = LinkRepository::instance()->find_by_category($this);
        }
        return $this->links;
    }

}
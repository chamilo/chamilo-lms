<?php

namespace Link;

/**
 * Model for Link/Urls
 * 
 * 
 * @author Laurent Opprecht <laurent@opprecht.info> for the Univesity of Genevas
 * @license /license.txt
 */
class Link
{

    /**
     * @return \Entity\Repository\LinkRepository
     */
    public static function repository()
    {
        return \Entity\Repository\LinkRepository::instance();
    }

    /**
     * @return \Entity\Link
     */
    public static function create($data = null)
    {
        return new self($data);
    }

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
     * @var integer $c_id
     */
    protected $c_id;

    /**
     * @var integer $id
     */
    protected $id;

    /**
     * @var text $url
     */
    protected $url;

    /**
     * @var string $title
     */
    protected $title;

    /**
     * @var text $description
     */
    protected $description;

    /**
     * @var integer $category_id
     */
    protected $category_id;

    /**
     * @var integer $display_order
     */
    protected $display_order;

    /**
     * @var string $on_homepage
     */
    protected $on_homepage;

    /**
     * @var string $target
     */
    protected $target;

    /**
     * @var integer $session_id
     */
    protected $session_id;
    protected $visibility = 0;

    /**
     * Set c_id
     *
     * @param integer $value
     * @return Link
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
     * @return Link
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
     * Set url
     *
     * @param text $value
     * @return Link
     */
    public function set_url($value)
    {
        $this->url = $value;
        return $this;
    }

    /**
     * Get url
     *
     * @return text 
     */
    public function get_url()
    {
        return $this->url;
    }

    /**
     * Set title
     *
     * @param string $value
     * @return Link
     */
    public function set_title($value)
    {
        $this->title = $value;
        return $this;
    }

    /**
     * Get title
     *
     * @return string 
     */
    public function get_title()
    {
        return $this->title;
    }

    /**
     * Set description
     *
     * @param text $value
     * @return Link
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
     * Set category_id
     *
     * @param integer $value
     * @return Link
     */
    public function set_category_id($value)
    {
        $this->category_id = $value;
        return $this;
    }

    /**
     * Get category_id
     *
     * @return integer 
     */
    public function get_category_id()
    {
        return $this->category_id;
    }

    /**
     * Set display_order
     *
     * @param integer $value
     * @return Link
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
     * Set on_homepage
     *
     * @param string $value
     * @return Link
     */
    public function set_on_homepage($value)
    {
        $this->on_homepage = $value;
        return $this;
    }

    /**
     * Get on_homepage
     *
     * @return string 
     */
    public function get_on_homepage()
    {
        return $this->on_homepage;
    }

    /**
     * Set target
     *
     * @param string $value
     * @return Link
     */
    public function set_target($value)
    {
        $this->target = $value;
        return $this;
    }

    /**
     * Get target
     *
     * @return string 
     */
    public function get_target()
    {
        return $this->target;
    }

    /**
     * Set session_id
     *
     * @param integer $value
     * @return Link
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

    public function get_visibility()
    {
        return (int) $this->visibility;
    }

    public function set_visibility($value)
    {
        $this->visibility = (int) $value;
        return $this;
    }

    public function is_visible()
    {
        return $this->get_visibility() == 1;
    }

    public function is_hidden()
    {
        return $this->get_visibility() == 0;
    }

    public function validate()
    {
        $url = $this->get_url();
        if (empty($url)) {
            return false;
        }
        if (!in_array('curl', get_loaded_extensions())) {
            true;
        }
        
        $defaults = array(
            CURLOPT_URL => $url,
            CURLOPT_FOLLOWLOCATION => true,                 
            CURLOPT_HEADER => 0,
            CURLOPT_NOBODY => true,
            CURLOPT_TIMEOUT => 4,
            CURLOPT_RETURNTRANSFER => false
        );

        $ch = curl_init();
        curl_setopt_array($ch, $defaults);

        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

}
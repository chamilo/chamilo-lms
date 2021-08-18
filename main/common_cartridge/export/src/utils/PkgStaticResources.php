<?php
/* For licensing terms, see /license.txt */

class PkgStaticResources
{

    private $values = array();
    public $finished = false;
    private static $instance = null;

    /**
     * @return PkgStaticResources
     */
    public static function instance()
    {
        if (empty(self::$instance)) {
            $c = __CLASS__;
            self::$instance = new $c();
        }
        return self::$instance;
    }

    /**
     *
     * add new element
     * @param string $identifier
     * @param string $file
     * @param boolean $main
     */
    public function add($key, $identifier, $file, $main, $node = null)
    {
        $this->values[$key] = array($identifier, $file, $main, $node);
    }

    /**
     * @return array
     */
    public function getValues()
    {
        return $this->values;
    }

    public function getIdentifier($location)
    {
        return isset($this->values[$location]) ? $this->values[$location] : false;
    }

    public function reset()
    {
        $this->values   = array();
        $this->finished = false;
    }
}


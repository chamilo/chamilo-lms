<?php
/* For licensing terms, see /license.txt */

class PkgResourceDependencies
{

    private $values = array();
    private static $instance = null;

    /**
     * @return PkgResourceDependencies
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
     * @param array $deps
     */
    public function add(array $deps)
    {
        $this->values = array_merge($this->values, $deps);
    }

    public function reset()
    {
        $this->values = array();
    }

    /**
     * @return array
     */
    public function get_deps()
    {
        return $this->values;
    }

}


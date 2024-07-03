<?php
/* For licensing terms, see /license.txt */

class PkgResourceDependencies
{
    private $values = [];
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

    public function add(array $deps)
    {
        $this->values = array_merge($this->values, $deps);
    }

    public function reset()
    {
        $this->values = [];
    }

    /**
     * @return array
     */
    public function getDeps()
    {
        return $this->values;
    }
}

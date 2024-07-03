<?php
/* For licensing terms, see /license.txt */

class PkgStaticResources
{
    public $finished = false;

    private $values = [];
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
     * add new element.
     *
     * @param string $identifier
     * @param string $file
     * @param bool   $main
     */
    public function add($key, $identifier, $file, $main, $node = null)
    {
        $this->values[$key] = [$identifier, $file, $main, $node];
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
        $this->values = [];
        $this->finished = false;
    }
}

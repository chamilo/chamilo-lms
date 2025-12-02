<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Utils;

class PkgStaticResources
{
    public $finished = false;

    private $values = [];
    private static $instance;

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
     * @param string     $identifier
     * @param string     $file
     * @param bool       $main
     * @param mixed      $key
     * @param null|mixed $node
     */
    public function add($key, $identifier, $file, $main, $node = null): void
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
        return $this->values[$location] ?? false;
    }

    public function reset(): void
    {
        $this->values = [];
        $this->finished = false;
    }
}

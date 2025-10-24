<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CourseBundle\Component\CourseCopy\CommonCartridge\Export\Utils;

class PkgResourceDependencies
{
    private $values = [];
    private static $instance;

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

    public function add(array $deps): void
    {
        $this->values = array_merge($this->values, $deps);
    }

    public function reset(): void
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

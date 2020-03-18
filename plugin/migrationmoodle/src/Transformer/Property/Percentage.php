<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Transformer\Property;

use Chamilo\PluginBundle\MigrationMoodle\Interfaces\TransformPropertyInterface;

/**
 * Class Percentage.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Transformer\Property
 */
class Percentage implements TransformPropertyInterface
{
    /**
     * @return float|int
     */
    public function transform(array $data)
    {
        list($a, $b) = array_values($data);

        if (empty($b)) {
            return 0;
        }

        return $a / $b * 100;
    }
}

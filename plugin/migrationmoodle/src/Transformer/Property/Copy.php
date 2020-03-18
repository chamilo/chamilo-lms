<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Transformer\Property;

use Chamilo\PluginBundle\MigrationMoodle\Interfaces\TransformPropertyInterface;

/**
 * Class Copy.
 */
class Copy implements TransformPropertyInterface
{
    /**
     * @return array
     */
    public function transform(array $data)
    {
        $values = array_values($data);

        return current($values);
    }
}

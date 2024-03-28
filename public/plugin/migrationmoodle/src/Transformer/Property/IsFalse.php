<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Transformer\Property;

use Chamilo\PluginBundle\MigrationMoodle\Interfaces\TransformPropertyInterface;

/**
 * Class IsFalse.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Transformer\Property
 */
class IsFalse implements TransformPropertyInterface
{
    /**
     * @return bool
     */
    public function transform(array $data)
    {
        return false;
    }
}

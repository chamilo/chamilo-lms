<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Transformer\Property;

use Chamilo\PluginBundle\MigrationMoodle\Interfaces\TransformPropertyInterface;

/**
 * Class UserActive.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Transformer\Property
 */
class UserActive implements TransformPropertyInterface
{
    /**
     * @return bool
     */
    public function transform(array $data)
    {
        $isDeleted = (bool) $data['deleted'];
        $isSuspended = (bool) $data['suspended'];

        if ($isDeleted || $isSuspended) {
            return false;
        }

        return true;
    }
}

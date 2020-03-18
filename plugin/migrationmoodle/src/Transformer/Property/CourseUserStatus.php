<?php

namespace Chamilo\PluginBundle\MigrationMoodle\Transformer\Property;

use Chamilo\PluginBundle\MigrationMoodle\Interfaces\TransformPropertyInterface;

/**
 * Class CourseUserStatus.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Transformer\Property
 */
class CourseUserStatus implements TransformPropertyInterface
{
    /**
     * @return int
     */
    public function transform(array $data)
    {
        $archetype = current($data);

        if (in_array($archetype, ['teacher', 'editingteacher'])) {
            return COURSEMANAGER;
        }

        return STUDENT;
    }
}

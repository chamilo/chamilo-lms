<?php

namespace Chamilo\PluginBundle\MigrationMoodle\Transformer\Property;

use Chamilo\PluginBundle\MigrationMoodle\Interfaces\TransformPropertyInterface;

/**
 * Class CourseUserStatusLookup.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Transformer\Property
 */
class CourseUserStatusLookup implements TransformPropertyInterface
{
    /**
     * @param array $data
     *
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

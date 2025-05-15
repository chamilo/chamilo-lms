<?php

namespace Chamilo\PluginBundle\MigrationMoodle\Transformer\Property;

use Chamilo\PluginBundle\MigrationMoodle\Task\CoursesTask;

/**
 * Class LoadedCourseCodeLookup.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Transformer\Property
 */
class LoadedCourseCodeLookup extends LoadedKeyLookup
{
    /**
     * LoadedCourseCodeLookup constructor.
     */
    public function __construct()
    {
        $this->calledClass = CoursesTask::class;
    }

    /**
     * @throws \Exception
     *
     * @return string
     */
    public function transform(array $data)
    {
        $cId = parent::transform($data);

        return api_get_course_entity($cId)->getCode();
    }
}

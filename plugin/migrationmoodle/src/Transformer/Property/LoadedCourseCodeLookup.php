<?php

namespace Chamilo\PluginBundle\MigrationMoodle\Transformer\Property;

use Chamilo\PluginBundle\MigrationMoodle\Task\EfcCoursesTask;

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
        $this->calledClass = EfcCoursesTask::class;
    }

    /**
     * @param array $data
     *
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

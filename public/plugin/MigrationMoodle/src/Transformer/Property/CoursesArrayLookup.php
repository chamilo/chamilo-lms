<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Transformer\Property;

use Exception;

/**
 * Class CoursesArrayLookup.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Transformer\Property
 */
class CoursesArrayLookup extends LoadedCourseLookup
{
    /**
     * @throws Exception
     *
     * @return mixed
     */
    public function transform(array $data)
    {
        $mCourseIds = current($data);
        $mCourseIds = explode(',', $mCourseIds);

        $courseIds = [];

        foreach ($mCourseIds as $mCourseId) {
            $courseIds[] = parent::transform([$mCourseId]);
        }

        return $courseIds;
    }
}

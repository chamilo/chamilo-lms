<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Transformer\Property;

use Chamilo\PluginBundle\MigrationMoodle\Interfaces\TransformPropertyInterface;

/**
 * Class ReplaceFilePaths.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Transformer\Property
 */
class ReplaceFilePaths extends LoadedCourseCodeLookup
{
    /**
     * @param array $data
     *
     * @throws \Exception
     *
     * @return mixed
     */
    public function transform(array $data)
    {
        list($content, $mCourseId) = array_values($data);

        $courseCode = parent::transform([$mCourseId]);
        $courseInfo = api_get_course_info($courseCode);

        $newPath = "/courses/{$courseInfo['path']}/document";

        $content = str_replace('@@PLUGINFILE@@', $newPath, $content);

        return $content;
    }
}

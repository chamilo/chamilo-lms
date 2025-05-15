<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Loader;

use Chamilo\PluginBundle\MigrationMoodle\Interfaces\LoaderInterface;

/**
 * Class CourseIntroductionLoader.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Loader
 */
class CourseIntroductionLoader implements LoaderInterface
{
    /**
     * {@inheritdoc}
     */
    public function load(array $incomingData)
    {
        return \Database::insert(
            \Database::get_course_table(TABLE_TOOL_INTRO),
            [
                'session_id' => 0,
                'c_id' => $incomingData['c_id'],
                'id' => TOOL_COURSE_HOMEPAGE,
                'intro_text' => $incomingData['description'],
            ]
        );
    }
}

<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Loader;

use Chamilo\PluginBundle\MigrationMoodle\Interfaces\LoaderInterface;

/**
 * Class CourseCategoriesLoader.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Loader
 */
class CourseCategoriesLoader implements LoaderInterface
{
    /**
     * Load the data and return the ID inserted.
     *
     * @param array $incomingData
     *
     * @return int
     */
    public function load(array $incomingData)
    {
        $id = \CourseCategory::addNode(
            $incomingData['code'],
            $incomingData['name'],
            'TRUE',
            $incomingData['parent_id']
        );

        $tblUrlCategory = \Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE_CATEGORY);
        $accessUrlId = \MigrationMoodlePlugin::create()->getAccessUrlId();

        \Database::query("UPDATE $tblUrlCategory SET access_url_id = $accessUrlId WHERE course_category_id = $id");

        return $id;
    }
}

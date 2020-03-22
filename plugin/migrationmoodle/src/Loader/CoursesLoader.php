<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Loader;

use Chamilo\PluginBundle\MigrationMoodle\Interfaces\LoaderInterface;

/**
 * Class CoursesLoader.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Loader
 */
class CoursesLoader implements LoaderInterface
{
    /**
     * @return int
     */
    public function load(array $incomingData)
    {
        $incomingData['subscribe'] = false;
        $incomingData['unsubscribe'] = false;
        $incomingData['disk_quota'] = 500 * 1024 * 1024;

        $result = \Database::select(
            'COUNT(1) AS c',
            \Database::get_main_table(TABLE_MAIN_COURSE),
            [
                'where' => [
                    'code = ?' => $incomingData['wanted_code'],
                ],
            ],
            'first'
        );

        if (!empty($result['c'])) {
            $incomingData['wanted_code'] = $incomingData['wanted_code'].substr(md5(uniqid(rand())), 0, 10);
        }

        $accessUrlId = \MigrationMoodlePlugin::create()->getAccessUrlId();

        $courseInfo = \CourseManager::create_course($incomingData, 1, $accessUrlId);

        return $courseInfo['real_id'];
    }
}

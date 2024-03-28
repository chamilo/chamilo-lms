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
    public const LOAD_MODE_REUSE = 'reuse';
    public const LOAD_MODE_DUPLICATE = 'duplicate';

    /**
     * @var string Load mode: "reuse" or "duplicate". Default is "duplicate".
     */
    private $loadMode = self::LOAD_MODE_DUPLICATE;

    /**
     * @return int
     */
    public function load(array $incomingData)
    {
        $courseInfo = api_get_course_info($incomingData['wanted_code']);

        if (!empty($courseInfo)) {
            if ($this->loadMode === self::LOAD_MODE_REUSE) {
                return $courseInfo['real_id'];
            }

            if ($this->loadMode === self::LOAD_MODE_DUPLICATE) {
                $incomingData['wanted_code'] = $incomingData['wanted_code'].substr(md5(uniqid(rand())), 0, 10);
            }
        }

        $incomingData['subscribe'] = false;
        $incomingData['unsubscribe'] = false;
        $incomingData['disk_quota'] = 500 * 1024 * 1024;

        $accessUrlId = \MigrationMoodlePlugin::create()->getAccessUrlId();

        $courseInfo = \CourseManager::create_course($incomingData, 1, $accessUrlId);

        return $courseInfo['real_id'];
    }
}

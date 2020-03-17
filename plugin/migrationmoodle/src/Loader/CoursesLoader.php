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
     * @param array $incomingData
     *
     * @return int
     */
    public function load(array $incomingData)
    {
        $incomingData['subscribe'] = false;
        $incomingData['unsubscribe'] = false;
        $incomingData['disk_quota'] = 500 * 1024 * 1024;

        $course = \Database::getManager()
            ->getRepository('ChamiloCoreBundle:Course')
            ->findOneBy(['code' => $incomingData['wanted_code']]);

        if ($course) {
            $incomingData['wanted_code'] = $unique_prefix = substr(md5(uniqid(rand())), 0, 10);
        }

        $courseInfo = \CourseManager::create_course($incomingData, 1);

        return $courseInfo['real_id'];
    }
}

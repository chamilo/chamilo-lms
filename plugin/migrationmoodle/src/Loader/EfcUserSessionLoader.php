<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Loader;

use Chamilo\PluginBundle\MigrationMoodle\Interfaces\LoaderInterface;
use Chamilo\PluginBundle\MigrationMoodle\Task\EfcUserSessionsTask;

/**
 * Class EfcUserSessionLoader.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Loader
 */
class EfcUserSessionLoader implements LoaderInterface
{
    /**
     * @inheritDoc
     */
    public function load(array $incomingData)
    {
        $datetime = api_get_utc_datetime();
        $coachId = 1;

        $courseCodes = explode(EfcUserSessionsTask::SEPARATOR_NAME, $incomingData['courses_list']);
        $courseIds = [];

        foreach ($courseCodes as $courseCode) {
            $courseId = api_get_course_int_id($courseCode);

            if (empty($courseId)) {
                throw new \Exception(
                    "Course ($courseCode) not found when creating course session for user ({$incomingData['user_id']})"
                );
            }

            $courseIds[] = $courseId;
        }

        $sessionId = \SessionManager::create_session(
            $incomingData['name'],
            $datetime,
            '',
            $datetime,
            '',
            $datetime,
            '',
            $coachId,
            0
        );
        \SessionManager::add_courses_to_session($sessionId, $courseIds);
        \SessionManager::subscribeUsersToSession($sessionId, [$incomingData['user_id']]);

        return $sessionId;
    }
}

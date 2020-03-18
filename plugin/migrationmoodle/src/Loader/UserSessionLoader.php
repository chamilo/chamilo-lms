<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Loader;

use Chamilo\PluginBundle\MigrationMoodle\Interfaces\LoaderInterface;
use Chamilo\PluginBundle\MigrationMoodle\Task\UserSessionsTask;

/**
 * Class UserSessionLoader.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Loader
 */
class UserSessionLoader implements LoaderInterface
{
    /**
     * {@inheritdoc}
     */
    public function load(array $incomingData)
    {
        $datetime = api_get_utc_datetime();
        $coachId = 1;

        $courseCodes = explode(UserSessionsTask::SEPARATOR_NAME, $incomingData['courses_list']);
        $courseIds = [];

        foreach ($courseCodes as $courseCode) {
            $courseId = api_get_course_int_id($courseCode);

            if (empty($courseId)) {
                throw new \Exception("Course ($courseCode) not found when creating course session for user ({$incomingData['user_id']})");
            }

            $courseIds[] = $courseId;
        }

        $urlId = \MigrationMoodlePlugin::create()->getAccessUrlId();

        $sessionId = \SessionManager::create_session(
            $incomingData['name'],
            $datetime,
            '',
            $datetime,
            '',
            $datetime,
            '',
            $coachId,
            0,
            1,
            false,
            null,
            null,
            0,
            [],
            0,
            false,
            $urlId
        );
        \SessionManager::add_courses_to_session($sessionId, $courseIds);
        \SessionManager::subscribeUsersToSession($sessionId, [$incomingData['user_id']]);

        return $sessionId;
    }
}

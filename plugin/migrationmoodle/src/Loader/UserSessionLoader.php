<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Loader;

use Chamilo\PluginBundle\MigrationMoodle\Interfaces\LoaderInterface;

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

        foreach ($incomingData['course_ids'] as $courseId) {
            if (empty($courseId)) {
                throw new \Exception(
                    "Course ($courseId) not found when creating course session for user ({$incomingData['user_id']}). "
                        .'Session will not be created.'
                );
            }
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
        \SessionManager::add_courses_to_session($sessionId, $incomingData['course_ids']);
        \SessionManager::subscribeUsersToSession($sessionId, [$incomingData['user_id']]);

        return $sessionId;
    }
}

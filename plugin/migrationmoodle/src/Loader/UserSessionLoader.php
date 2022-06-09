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
    public const LOAD_MODE_REUSE = 'reuse';
    public const LOAD_MODE_DUPLICATE = 'duplicate';

    /**
     * @var string Load mode: "reuse" or "duplicate". Default is "duplicate".
     */
    private $loadMode = self::LOAD_MODE_DUPLICATE;

    /**
     * {@inheritdoc}
     */
    public function load(array $incomingData)
    {
        foreach ($incomingData['course_ids'] as $courseId) {
            if (empty($courseId)) {
                throw new \Exception("Course ($courseId) not found when creating course session for user ({$incomingData['user_id']}). ".'Session will not be created.');
            }
        }

        $tblSession = \Database::get_main_table(TABLE_MAIN_SESSION);

        $sessionInfo = \Database::fetch_assoc(
            \Database::query("SELECT id FROM $tblSession WHERE name = '{$incomingData['name']}'")
        );

        if (!empty($sessionInfo)) {
            if ($this->loadMode == self::LOAD_MODE_REUSE) {
                return $sessionInfo['id'];
            }

            if ($this->loadMode === self::LOAD_MODE_DUPLICATE) {
                $incomingData['name'] = '['.substr(md5(uniqid(rand())), 0, 5).'] '.$incomingData['name'];
            }
        }

        $urlId = \MigrationMoodlePlugin::create()->getAccessUrlId();
        $datetime = api_get_utc_datetime();
        $coachId = 1;

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

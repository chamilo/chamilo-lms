<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Loader;

use Chamilo\PluginBundle\MigrationMoodle\Interfaces\LoaderInterface;

/**
 * Class TrackCourseAccessLoader.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Loader
 */
class TrackCourseAccessLoader implements LoaderInterface
{
    /**
     * {@inheritdoc}
     */
    public function load(array $incomingData)
    {
        list($userId, $cId, $loginCourseDate, $ip, $sessionId) = array_values($incomingData);

        $sessionLifetime = api_get_configuration_value('session_lifetime');

        /** @var \DateTime $time */
        $time = clone $loginCourseDate;
        $time->modify("-$sessionLifetime seconds");

        $time = $time->format('Y-m-d H:i:s');
        $loginCourseDate = $loginCourseDate->format('Y-m-d H:i:s');

        $tableCourseAccess = \Database::get_main_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);

        $result = \Database::query(
            "SELECT course_access_id
                FROM $tableCourseAccess
                WHERE user_id = $userId AND c_id = $cId AND session_id = $sessionId AND login_course_date > '$time'
                ORDER BY login_course_date DESC
                LIMIT 1"
        );

        if (\Database::num_rows($result) > 0) {
            $row = \Database::fetch_assoc($result);

            \Database::query(
                "UPDATE $tableCourseAccess
                    SET logout_course_date = '$loginCourseDate', counter = counter + 1
                    WHERE course_access_id = {$row['course_access_id']}"
            );

            return $row['course_access_id'];
        }

        return \Database::insert(
            $tableCourseAccess,
            [
                'c_id' => $cId,
                'user_ip' => $ip,
                'user_id' => $userId,
                'login_course_date' => $loginCourseDate,
                'logout_course_date' => $loginCourseDate,
                'counter' => 1,
                'session_id' => $sessionId,
            ]
        );
    }
}

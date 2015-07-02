<?php
/* For licensing terms, see /license.txt */
/**
 * GamificationUtils class
 * Functions to manage the gamification mode
 * @package chamilo.library
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 */
class GamificationUtils
{

    /**
     * Get the calculated points on session with gamification mode
     * @param int $userId The user ID
     * @param int $userStatus The user Status
     * @return int
     */
    public static function getTotalUserPoints($userId, $userStatus)
    {
        $points = 0;

        $sessions = SessionManager::getSessionsFollowedByUser(
            $userId,
            $userStatus
        );

        if (empty($sessions)) {
            return 0;
        }

        foreach ($sessions as $session) {
            $points += self::getSessionPoints($session['id'], $userId);
        }

        return $points;
    }

    /**
     * Get the achieved points for an user in a session
     * @param int $sessionId The session ID
     * @param int $userId The user ID
     * @return int The count of points
     */
    public static function getSessionPoints($sessionId, $userId)
    {
        $totalPoints = 0;
        $courses = SessionManager::get_course_list_by_session_id($sessionId);

        if (empty($courses)) {
            return 0;
        }

        foreach ($courses as $course) {
            $learnPathListObject = new LearnpathList(
                $userId,
                $course['code'],
                $sessionId
            );
            $learnPaths = $learnPathListObject->get_flat_list();

            $score = 0;

            foreach ($learnPaths as $learnPathId => $learnPathInfo) {
                if (empty($learnPathInfo['seriousgame_mode'])) {
                    continue;
                }

                $learnPath = new learnpath(
                    $course['code'],
                    $learnPathId,
                    $userId
                );

                $score += $learnPath->getCalculateScore($sessionId);
            }

            $totalPoints += $score;
        }

        return $totalPoints / count($courses);
    }

    /**
     * Get the calculated progress for an user in a session
     * @param int $sessionId The session ID
     * @param int $userId The user ID
     * @return float The progress
     */
    public static function getSessionProgress($sessionId, $userId)
    {
        $courses = SessionManager::get_course_list_by_session_id($sessionId);
        $progress = 0;

        if (empty($courses)) {
            return 0;
        }

        foreach ($courses as $course) {
            $courseProgress = Tracking::get_avg_student_progress(
                $userId,
                $course['code'],
                [],
                $sessionId,
                false,
                true
            );

            if ($courseProgress === false) {
                continue;
            }

            $progress += $courseProgress;
        }

        return $progress / count($courses);
    }

    /**
     * Get the number of stars achieved for an user in a session
     * @param int $sessionId The session ID
     * @param int $userId The user ID
     * @return int The number of stars
     */
    public static function getSessionStars($sessionId, $userId)
    {
        $totalStars = 0;
        $courses = SessionManager::get_course_list_by_session_id($sessionId);

        if (empty($courses)) {
            return 0;
        }

        foreach ($courses as $course) {
            $learnPathListObject = new LearnpathList(
                $userId,
                $course['code'],
                $sessionId
            );
            $learnPaths = $learnPathListObject->get_flat_list();

            $stars = 0;

            foreach ($learnPaths as $learnPathId => $learnPathInfo) {
                if (empty($learnPathInfo['seriousgame_mode'])) {
                    continue;
                }

                $learnPath = new learnpath(
                    $course['code'],
                    $learnPathId,
                    $userId
                );

                $stars += $learnPath->getCalculateStars($sessionId);
            }

            $totalStars += $stars;
        }

        return $totalStars / count($courses);
    }

}

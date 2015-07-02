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
            $points += SessionManager::getPointsFromGamification(
                $session['id']
            );
        }

        return $points;
    }

}

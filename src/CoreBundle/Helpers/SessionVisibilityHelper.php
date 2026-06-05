<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Helpers;

use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\TrackECourseAccessRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;

/**
 * Computes the effective visibility of a Session for a given User.
 *
 * Pure service shared by SessionVoter and IsAllowedToEditHelper to avoid
 * duplicating the duration-based visibility calculation. For duration-based
 * sessions (no fixed access dates) it queries the first course access directly
 * through the repository to avoid the stale Doctrine lazy collection on the
 * User entity; for date-based sessions it falls back to
 * Session::setAccessVisibilityByUser().
 */
readonly class SessionVisibilityHelper
{
    public function __construct(
        private TrackECourseAccessRepository $trackECourseAccessRepository,
        private SettingsManager $settingsManager,
    ) {}

    public function getSessionVisibility(Session $session, User $user): int
    {
        // Coaches keep access after the duration ends when this setting is on.
        $coachAccessAfterDurationEnd = 'true' === $this->settingsManager->getSetting('session.session_coach_access_after_duration_end', true);

        // Only use the repository path for duration-based sessions without fixed dates.
        if ($session->getDuration() > 0 && !$session->getAccessStartDate() && !$session->getAccessEndDate()) {
            // If setting is enabled, coaches always have access regardless of duration end.
            if ($coachAccessAfterDurationEnd && $session->hasCoach($user)) {
                return Session::AVAILABLE;
            }

            $duration = $session->getDuration() * 24 * 60 * 60;

            // Use repository directly to avoid stale Doctrine lazy collection.
            $firstAccess = $this->trackECourseAccessRepository->findFirstAccessByUserAndSession(
                $user,
                $session->getId()
            );

            // If no previous access exists, session is still available.
            if (!$firstAccess) {
                return Session::AVAILABLE;
            }

            $userSessionSubscription = $user->getSubscriptionToSession($session);
            $userDuration = $userSessionSubscription
                ? $userSessionSubscription->getDuration() * 24 * 60 * 60
                : 0;

            $firstAccessTimestamp = $firstAccess->getLoginCourseDate()->getTimestamp();
            $totalDuration = $firstAccessTimestamp + $duration + $userDuration;

            return $totalDuration > time() ? Session::AVAILABLE : $session->getVisibility();
        }

        // Fall back to standard method for date-based sessions.
        return $session->setAccessVisibilityByUser($user, true, $coachAccessAfterDurationEnd);
    }
}

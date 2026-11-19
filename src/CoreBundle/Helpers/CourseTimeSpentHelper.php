<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Helpers;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\TrackECourseAccessRepository;
use Chamilo\CoreBundle\Service\Reporting\SessionTimeLimit;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Tracking;

/**
 * The time a user spent in a course, as the course reports count it (track_e_course_access),
 * including the part of the current access the tracking has not written yet. The time spent clock
 * and the minimum time required before a test both read it, so they always agree.
 */
final readonly class CourseTimeSpentHelper
{
    public function __construct(
        private CourseAccessActivityHelper $courseAccessActivityHelper,
        private TrackECourseAccessRepository $trackECourseAccessRepository,
        private SessionTimeLimit $sessionTimeLimit,
    ) {}

    public function getTimeSpentSeconds(User $user, int $courseId, int $sessionId, ?SessionInterface $httpSession): int
    {
        $seconds = (int) Tracking::get_time_spent_on_the_course((int) $user->getId(), $courseId, $sessionId);
        $seconds += $this->getSecondsNotYetRecorded($user, $courseId, $sessionId, $httpSession);

        return max(0, $seconds);
    }

    /**
     * The tracking only writes the end of the current access every few minutes, so the stored
     * total lags behind. Add the part of the current access that is not written yet, capped like
     * the reporting caps each access.
     */
    private function getSecondsNotYetRecorded(User $user, int $courseId, int $sessionId, ?SessionInterface $httpSession): int
    {
        $access = $this->trackECourseAccessRepository->findExistingAccess(
            $user,
            $courseId,
            $sessionId,
            $this->courseAccessActivityHelper->getMaximumInactiveSeconds(),
        );
        if (null === $access) {
            return 0;
        }

        $maxSeconds = $this->sessionTimeLimit->getMaximumSeconds();
        $login = $access->getLoginCourseDate()->getTimestamp();
        $countableUntil = $this->courseAccessActivityHelper->getCountableUntil($httpSession, $courseId, $sessionId)->getTimestamp();

        $recorded = 0;
        $logout = $access->getLogoutCourseDate()?->getTimestamp();
        if (null !== $logout && $logout > $login) {
            $recorded = SessionTimeLimit::clampSeconds($logout - $login, $maxSeconds);
        }
        $current = SessionTimeLimit::clampSeconds($countableUntil - $login, $maxSeconds);

        return max(0, $current - $recorded);
    }
}

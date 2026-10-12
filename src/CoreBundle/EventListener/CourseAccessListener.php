<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\EventListener;

use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\TrackECourseAccess;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CoreBundle\Helpers\CourseAccessActivityHelper;
use Chamilo\CoreBundle\Helpers\CourseToolAccessTracker;
use Chamilo\CoreBundle\Helpers\UserHelper;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * In and outs of a course.
 * This listener is always called when user enters the course home.
 * It also logs tool access for C2 rewritten tools under /resources/* routes.
 */
class CourseAccessListener
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly CidReqHelper $cidReqHelper,
        private readonly UserHelper $userHelper,
        private readonly CourseToolAccessTracker $courseToolAccessTracker,
        private readonly CourseAccessActivityHelper $courseAccessActivityHelper,
    ) {}

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest() || $event->getRequest()->attributes->get('access_checked')) {
            // If it's not the main request or we've already handled access in this request, do nothing.
            return;
        }

        $request = $event->getRequest();

        $courseId = (int) $this->cidReqHelper->getCourseId();
        $session = $this->cidReqHelper->getDoctrineSessionEntity();
        $sessionId = 0;
        if (!empty($session)) {
            $sessionId = $session->getId();
        }

        if ($courseId <= 0) {
            return;
        }

        $user = $this->userHelper->getCurrent();
        if (!$user) {
            return;
        }

        // only log access for the Doctrine-backed Chamilo User entity with a valid ID.
        if (!$user instanceof User || (int) $user->getId() <= 0) {
            return;
        }

        $ip = (string) ($request->getClientIp() ?? '');

        // --- Existing behavior: track_e_course_access ---
        $httpSession = $request->hasSession() ? $request->getSession() : null;
        $isBackgroundRequest = $this->courseAccessActivityHelper->isBackgroundRequest($request);
        if (!$isBackgroundRequest) {
            $this->courseAccessActivityHelper->markActivity($httpSession, $courseId, $sessionId);
        }

        // A course page left open keeps sending background requests: past the allowed
        // inactivity they no longer count as time in the course.
        if (!$isBackgroundRequest || !$this->courseAccessActivityHelper->isInactive($httpSession, $courseId, $sessionId)) {
            $this->trackCourseAccess($user, $courseId, $sessionId, $session, $ip, $httpSession);
        }

        // track_e_access + track_e_lastaccess (C2 tools) ---
        $this->logToolAccessIfNeeded($request);

        // Set a flag on the request to indicate that access has been checked.
        $request->attributes->set('access_checked', true);
    }

    private function trackCourseAccess(
        User $user,
        int $courseId,
        int $sessionId,
        ?Session $session,
        string $ip,
        ?SessionInterface $httpSession,
    ): void {
        $accessRepository = $this->em->getRepository(TrackECourseAccess::class);
        $access = $accessRepository->findExistingAccess(
            $user,
            $courseId,
            $sessionId,
            $this->courseAccessActivityHelper->getMaximumInactiveSeconds(),
        );

        if ($access) {
            $accessRepository->updateAccess(
                $access,
                $this->courseAccessActivityHelper->getCountableUntil($httpSession, $courseId, $sessionId),
            );
        } else {
            // A duration-based session starts at the learner's very first access only, not at
            // every new access to the course.
            $isFirstAccess = null === $accessRepository->findOneBy(['user' => $user, 'cId' => $courseId, 'sessionId' => $sessionId]);
            if ($isFirstAccess && null !== $session && $session->getDuration() > 0) {
                $subscription = $user->getSubscriptionToSession($session);
                if ($subscription) {
                    $duration = $session->getDuration() + $subscription->getDuration();

                    $startDate = new DateTime();
                    $endDate = (clone $startDate)->modify("+$duration days");

                    $subscription
                        ->setAccessStartDate($startDate)
                        ->setAccessEndDate($endDate)
                    ;

                    $this->em->flush();
                }
            }

            $accessRepository->recordAccess($user, $courseId, $sessionId, $ip);
        }
    }

    private function logToolAccessIfNeeded(Request $request): void
    {
        $this->courseToolAccessTracker->trackFromVueResourceRequest($request);
    }
}

<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\EventListener;

use Chamilo\CoreBundle\Entity\TrackECourseAccess;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CoreBundle\Helpers\CourseToolAccessTracker;
use Chamilo\CoreBundle\Helpers\UserHelper;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
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
        private readonly CourseToolAccessTracker $courseToolAccessTracker
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
        $accessRepository = $this->em->getRepository(TrackECourseAccess::class);
        $access = $accessRepository->findExistingAccess($user, $courseId, $sessionId);

        if ($access) {
            $accessRepository->updateAccess($access);
        } else {
            if (!empty($session) && $session->getDuration() > 0) {
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

        // track_e_access + track_e_lastaccess (C2 tools) ---
        $this->logToolAccessIfNeeded($request);

        // Set a flag on the request to indicate that access has been checked.
        $request->attributes->set('access_checked', true);
    }

    private function logToolAccessIfNeeded(Request $request): void
    {
        $this->courseToolAccessTracker->trackFromVueResourceRequest($request);
    }
}

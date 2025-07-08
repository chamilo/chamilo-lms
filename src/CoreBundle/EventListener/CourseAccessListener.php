<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\EventListener;

use Chamilo\CoreBundle\Entity\TrackECourseAccess;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * In and outs of a course
 * This listener is always called when user enters the course home.
 */
class CourseAccessListener
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly CidReqHelper $cidReqHelper,
        private readonly UserHelper $userHelper
    ) {}

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest() || $event->getRequest()->attributes->get('access_checked')) {
            // If it's not the main request or we've already handled access in this request, do nothing
            return;
        }

        $courseId = (int) $this->cidReqHelper->getCourseId();
        $session = $this->cidReqHelper->getDoctrineSessionEntity();

        if ($courseId <= 0) {
            return;
        }

        $user = $this->userHelper->getCurrent();

        if (!$user) {
            return;
        }

        $ip = $event->getRequest()->getClientIp();
        $accessRepository = $this->em->getRepository(TrackECourseAccess::class);
        $access = $accessRepository->findExistingAccess($user, $courseId, $session->getId());

        if ($access) {
            $accessRepository->updateAccess($access);
        } else {
            if ($session->getDuration() > 0) {
                $subscription = $user->getSubscriptionToSession($session);
                $duration = $session->getDuration() + $subscription->getDuration();

                $startDate = new \DateTime();
                $endDate = (clone $startDate)->modify("+$duration days");

                $subscription
                    ->setAccessStartDate($startDate)
                    ->setAccessEndDate($endDate)
                ;

                $this->em->flush();
            }

            $accessRepository->recordAccess($user, $courseId, $session->getId(), $ip);
        }

        // Set a flag on the request to indicate that access has been checked
        $event->getRequest()->attributes->set('access_checked', true);
    }
}

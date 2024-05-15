<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\EventListener;

use Chamilo\CoreBundle\Entity\TrackECourseAccess;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\ServiceHelper\CidReqHelper;
use DateTime;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * In and outs of a course
 * This listener is always called when user enters the course home.
 */
class CourseAccessListener
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
        private readonly CidReqHelper $cidReqHelper,
        private readonly TokenStorageInterface $tokenStorage,
    ) {}

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest() || $event->getRequest()->attributes->get('access_checked')) {
            // If it's not the main request or we've already handled access in this request, do nothing
            return;
        }

        $courseId = (int) $this->cidReqHelper->getCourseId();
        $sessionId = (int) $this->cidReqHelper->getSessionId();

        if ($courseId > 0) {
            $user = $this->tokenStorage->getToken()?->getUser();
            if ($user instanceof User) {
                $ip = $this->requestStack->getCurrentRequest()->getClientIp();
                $access = $this->findExistingAccess($user, $courseId, $sessionId);

                if ($access) {
                    $this->updateAccess($access);
                } else {
                    $this->recordAccess($user, $courseId, $sessionId, $ip);
                }

                // Set a flag on the request to indicate that access has been checked
                $event->getRequest()->attributes->set('access_checked', true);
            }
        }
    }

    private function findExistingAccess(User $user, int $courseId, int $sessionId)
    {
        return $this->em->getRepository(TrackECourseAccess::class)
            ->findOneBy(['user' => $user, 'cId' => $courseId, 'sessionId' => $sessionId]);
    }

    private function updateAccess(TrackECourseAccess $access): void
    {
        $now = new DateTime();
        if (!$access->getLogoutCourseDate() || $now->getTimestamp() - $access->getLogoutCourseDate()->getTimestamp() > 300) {
            $access->setLogoutCourseDate($now);
            $access->setCounter($access->getCounter() + 1);
            $this->em->flush();
        }
    }

    private function recordAccess(User $user, int $courseId, int $sessionId, string $ip): void
    {
        $access = new TrackECourseAccess();
        $access->setUser($user);
        $access->setCId($courseId);
        $access->setSessionId($sessionId);
        $access->setUserIp($ip);
        $access->setLoginCourseDate(new \DateTime());
        $access->setCounter(1);
        $this->em->persist($access);
        $this->em->flush();
    }
}

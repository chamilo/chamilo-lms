<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\EventListener;

use Chamilo\CoreBundle\Entity\TrackECourseAccess;
use Chamilo\CourseBundle\Event\SessionAccess;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class SessionAccessListener
{
    protected ?Request $request = null;

    public function __construct(
        private readonly EntityManager $em,
        RequestStack $requestStack
    ) {
        $this->request = $requestStack->getCurrentRequest();
    }

    public function __invoke(SessionAccess $event): void
    {
        $user = $event->getUser();
        $course = $event->getCourse();
        $session = $event->getSession();
        $ip = $this->request->getClientIp();

        $access = new TrackECourseAccess();
        $access
            ->setCId($course->getId())
            ->setUser($user)
            ->setSessionId($session->getId())
            ->setUserIp($ip)
        ;

        $this->em->persist($access);
        $this->em->flush();
    }
}

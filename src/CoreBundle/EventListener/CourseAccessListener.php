<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\EventListener;

use Chamilo\CoreBundle\Entity\TrackECourseAccess;
use Chamilo\CourseBundle\Event\CourseAccess;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * In and outs of a course
 * This listener is always called when user enters the course home.
 */
class CourseAccessListener
{
    protected ?Request $request = null;

    public function __construct(
        private readonly EntityManager $em,
        RequestStack $requestStack
    ) {
        $this->request = $requestStack->getCurrentRequest();
    }

    public function __invoke(CourseAccess $event): void
    {
        // CourseAccess
        $user = $event->getUser();
        $course = $event->getCourse();
        $ip = $this->request->getClientIp();

        $access = new TrackECourseAccess();
        $access
            ->setCId($course->getId())
            ->setUser($user)
            ->setSessionId(0)
            ->setUserIp($ip)
        ;

        $this->em->persist($access);
        $this->em->flush();
    }
}

<?php

namespace Chamilo\CourseBundle\EventListener;

use Chamilo\CourseBundle\Event\CourseAccess;
use Doctrine\ORM\EntityManager;
use Chamilo\CoreBundle\Entity\TrackEAccess;

class CourseListener
{
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function onCourseAccessEvent(CourseAccess $event)
    {
        $user = $event->getUser();
        $course = $event->getCourse();

        $trackAccess = new TrackEAccess();
        $trackAccess->setCId($course->getId());
        $trackAccess->setAccessUserId($user->getId());
        $trackAccess->setAccessSessionId(0);

        $this->em->persist($trackAccess);
        $this->em->flush();
    }
}

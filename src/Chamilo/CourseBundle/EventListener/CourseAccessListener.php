<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\EventListener;

use Chamilo\CourseBundle\Event\CourseAccess;
use Doctrine\ORM\EntityManager;
use Chamilo\CoreBundle\Entity\TrackEAccess;

/**
 * Class CourseAccessListener
 * @package Chamilo\CourseBundle\EventListener
 */
class CourseAccessListener
{
    protected $em;

    /**
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * @param CourseAccess $event
     */
    public function onCourseAccessEvent(CourseAccess $event)
    {
        $user = $event->getUser();
        $course = $event->getCourse();
        if ($user && $course) {
            $trackAccess = new TrackEAccess();
            $trackAccess->setCId($course->getId());
            $trackAccess->setAccessUserId($user->getId());
            $trackAccess->setAccessSessionId(0);

            $this->em->persist($trackAccess);
            $this->em->flush();
        }
    }
}

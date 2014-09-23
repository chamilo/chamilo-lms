<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\EventListener;

use Chamilo\CourseBundle\Event\SessionAccess;
use Doctrine\ORM\EntityManager;
use Chamilo\CoreBundle\Entity\TrackEAccess;

class SessionAccessListener
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
     * @param SessionAccess $event
     */
    public function onSessionAccessEvent(SessionAccess $event)
    {
        $user = $event->getUser();
        $course = $event->getCourse();
        $session = $event->getSession();

        $trackAccess = new TrackEAccess();
        $trackAccess->setCId($course->getId());
        $trackAccess->setAccessUserId($user->getId());
        $trackAccess->setAccessSessionId($session->getId());

        $this->em->persist($trackAccess);
        $this->em->flush();
    }
}

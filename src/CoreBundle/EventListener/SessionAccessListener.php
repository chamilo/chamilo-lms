<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\EventListener;

use Chamilo\CoreBundle\Entity\TrackECourseAccess;
use Chamilo\CourseBundle\Event\SessionAccess;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class SessionAccessListener.
 */
class SessionAccessListener
{
    protected $em;

    /**
     * @var \Symfony\Component\HttpFoundation\Request
     */
    protected $request;

    /**
     * SessionAccessListener constructor.
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function setRequest(RequestStack $requestStack)
    {
        $this->request = $requestStack->getCurrentRequest();
    }

    public function onSessionAccessEvent(SessionAccess $event)
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
            ->setUserIp($ip);

        $this->em->persist($access);
        $this->em->flush();
    }
}

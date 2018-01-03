<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\EventListener;

use Chamilo\CoreBundle\Entity\TrackECourseAccess;
use Chamilo\CourseBundle\Event\SessionAccess;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class SessionAccessListener
 * @package Chamilo\CourseBundle\EventListener
 */
class SessionAccessListener
{
    protected $em;

    /**
     * @var \Symfony\Component\HttpFoundation\Request
     */
    protected $request;

    /**
     * @param RequestStack $requestStack
     */
    public function setRequest(RequestStack $requestStack)
    {
        $this->request = $requestStack->getCurrentRequest();
    }

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
        $ip = $this->request->getClientIp();

        $access = new TrackECourseAccess();
        $access
            ->setCId($course->getId())
            ->setUserId($user->getId())
            ->setSessionId($session->getId())
            ->setUserIp($ip);

        $this->em->persist($access);
        $this->em->flush();
    }
}

<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\EventListener;

use Chamilo\CoreBundle\Entity\TrackECourseAccess;
use Chamilo\CourseBundle\Event\CourseAccess;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class CourseAccessListener
 * In and outs of a course
 * This listeners is always called when user enters the course home
 * @package Chamilo\CourseBundle\EventListener
 */
class CourseAccessListener
{
    protected $em;

    /**
     * @var \Symfony\Component\HttpFoundation\Request
     */
    protected $request;

    /**
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * @param RequestStack $requestStack
     */
    public function setRequest(RequestStack $requestStack)
    {
        $this->request = $requestStack->getCurrentRequest();
    }

    /**
     * @param CourseAccess $event
     */
    public function onCourseAccessEvent($event)
    {
        // CourseAccess
        $user = $event->getUser();
        $course = $event->getCourse();
        $ip = $this->request->getClientIp();

        if ($user && $course) {
            $access = new TrackECourseAccess();
            $access
                ->setCId($course->getId())
                ->setUserId($user->getId())
                ->setSessionId(0)
                ->setUserIp($ip)
            ;

            $this->em->persist($access);
            $this->em->flush();
        }
    }
}

<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Traits;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CourseBundle\Entity\CGroup;
use Psr\Container\ContainerInterface;

/**
 * Trait CourseControllerTrait.
 * Implements the functions defined by the CourseControllerInterface.
 */
trait CourseControllerTrait
{
    protected ?Course $course = null;
    protected ?Session $session = null;
    protected $container;

    /**
     * Gets the current Chamilo course based in the "_real_cid" session variable.
     *
     * @return Course
     */
    /*public function getCourse()
    {
        $request = $this->getRequest();
        if ($request) {
            $courseId = $request->getSession()->get('cid', 0);
        }

        if (empty($courseId)) {
            return null;
        }

        return $this->getDoctrine()->getManager()->find('ChamiloCoreBundle:Course', $courseId);
    }
    */

    /*public function hasCourse()
    {
        $request = $this->getRequest();
        if ($request) {
            $courseId = $request->getSession()->get('cid', 0);
            if (!empty($courseId)) {
                return true;
            }
        }

        return false;
    }*/

    /**
     * Gets the current Chamilo session based in the "sid" $_SESSION variable.
     *
     * @return Session|null
     */
    public function getCourseSession()
    {
        $request = $this->getRequest();

        if ($request) {
            $sessionId = $request->getSession()->get('sid', 0);
        }

        if (empty($sessionId)) {
            return null;
        }

        return $this->container->get('doctrine')->getManager()->find(Session::class, $sessionId);
    }

    public function getGroup(): ?CGroup
    {
        $request = $this->getRequest();

        if ($request) {
            $groupId = $request->getSession()->get('gid', 0);
        }

        if (empty($groupId)) {
            return null;
        }

        return $this->container->get('doctrine')->getManager()->find(CGroup::class, $groupId);
    }

    public function getCourseUrlQuery(): string
    {
        $url = '';
        $course = $this->getCourse();
        if ($course) {
            $url = 'cid='.$course->getId();
        }

        $session = $this->getCourseSession();
        if ($session) {
            $url .= '&sid='.$session->getId();
        } else {
            $url .= '&sid=0';
        }

        $group = $this->getGroup();
        if ($group) {
            $url .= '&gid='.$group->getIid();
        } else {
            $url .= '&gid=0';
        }

        return $url;
    }

    public function getCourseUrlQueryToArray(): array
    {
        $url = [];
        $course = $this->getCourse();
        $url['cid'] = 0;
        if ($course) {
            $url['cid'] = $course->getId();
        }
        $session = $this->getCourseSession();

        $url['sid'] = 0;
        if ($session) {
            $url['sid'] = $session->getId();
        }

        return $url;
    }

    public function setCourse(Course $course): void
    {
        $this->course = $course;
    }

    public function getCourse(): ?Course
    {
        return $this->course;
    }

    public function hasCourse(): bool
    {
        return null !== $this->course;
    }

    public function getSession(): ?Session
    {
        return $this->session;
    }

    public function setSession(Session $session = null): void
    {
        $this->session = $session;
    }

    public function getSessionId(): int
    {
        return $this->session !== null ? $this->getSession()->getId() : 0;
    }
}

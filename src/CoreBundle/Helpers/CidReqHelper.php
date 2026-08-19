<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Helpers;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\EventListener\CidReqListener;
use Chamilo\CourseBundle\Entity\CGroup;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @see CidReqListener::onKernelRequest()
 */
class CidReqHelper
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly EntityManagerInterface $em,
    ) {}

    private function getRequest(): ?Request
    {
        return $this->requestStack->getCurrentRequest();
    }

    private function getSessionHandler(): ?SessionInterface
    {
        $request = $this->getRequest();

        return $request?->getSession();
    }

    public function getSessionId(): ?int
    {
        $session = $this->getSessionHandler();

        return $session?->get('sid');
    }

    public function getSessionEntity(): ?Session
    {
        $session = $this->getSessionHandler();

        return $session?->get('session');
    }

    public function getCourseId(): mixed
    {
        $session = $this->getSessionHandler();

        return $session?->get('cid');
    }

    public function getCourseEntity(): ?Course
    {
        $session = $this->getSessionHandler();

        return $session?->get('course');
    }

    public function getGroupId(): ?int
    {
        $session = $this->getSessionHandler();

        return $session?->get('gid');
    }

    public function getGroupEntity(): ?CGroup
    {
        return $this->getSessionHandler()?->get('group');
    }

    public function getDoctrineCourseEntity(): ?Course
    {
        $courseId = $this->getCourseId();
        if (empty($courseId)) {
            return null;
        }

        return $this->em->getRepository(Course::class)->find((int) $courseId);
    }

    /**
     * Same as getDoctrineCourseEntity() for the callers that cannot work without a course.
     *
     * The listener resolved the course from the incoming cid, answered 404 when it did not
     * exist and denied the request when the user may not view it, so the only case left
     * here is a request that carried no course context at all.
     */
    public function requireDoctrineCourseEntity(): Course
    {
        return $this->getDoctrineCourseEntity()
            ?? throw new BadRequestHttpException('A valid course id is required.');
    }

    public function getDoctrineGroupEntity(): ?CGroup
    {
        $groupId = $this->getGroupId();
        if (empty($groupId)) {
            return null;
        }

        return $this->em->getRepository(CGroup::class)->find((int) $groupId);
    }

    public function getDoctrineSessionEntity(): ?Session
    {
        $sessionId = $this->getSessionId();
        if (empty($sessionId)) {
            return null;
        }

        return $this->em->getRepository(Session::class)->find((int) $sessionId);
    }
}
